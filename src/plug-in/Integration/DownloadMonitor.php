<?php

namespace Dreitier\WordPress\ContinuousDelivery\Integration;

use Dreitier\WordPress\ContinuousDelivery\Event\PublishRequest;
use Dreitier\WordPress\ContinuousDelivery\Event\Published;
use Dreitier\WordPress\ContinuousDelivery\Event\UpdateReleaseProperty;
use Dreitier\WordPress\ContinuousDelivery\Model\Architecture;
use Dreitier\WordPress\ContinuousDelivery\Model\Build;
use Dreitier\WordPress\ContinuousDelivery\Model\MimeType;
use Dreitier\WordPress\ContinuousDelivery\Model\OperatingSystem;
use Dreitier\WordPress\ContinuousDelivery\Model\Signatures;
use Dreitier\WordPress\ContinuousDelivery\PlugIn;
use Dreitier\WordPress\ContinuousDelivery\ReleaseException;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\ConfigurationManager;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3FileReference;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\UnsupportedS3Uri;

class DownloadMonitor
{
	public function __construct(public readonly ConfigurationManager $s3ConfigurationManager)
	{
	}

	public function register()
	{
		// handle our own API endpoint
		add_filter(PlugIn::hook('handle_create_release_request'), [$this, 'publish'], 10, 3);
		// redirect to S3 when a DLM file is downloaded
		add_action('dlm_downloading', [$this, 's3_download_redirect'], 10, 3);
		// hook to push the recent published release to the product's first position
		add_action('dlm_release_version_after', [$this, 'dlm_set_active_version'], 10, 2);
	}

	/**
	 * Return if relevant DLM dependencies are available.
	 * @return bool
	 */
	public static function hasRuntimeDependenciesAvailable()
	{
		return class_exists('\DLM_Download');
	}

	/**
	 * Check if we are currently in an XHR request.
	 * @return bool
	 */
	public static function isDoingXhr() {
		return defined( 'DLM_DOING_XHR' ) && DLM_DOING_XHR;
	}

	/**
	 * Redirect to AWS S3 with a signed request.
	 *
	 * @param $download
	 * @param $version
	 * @param $file_path
	 * @return void
	 */
	public function s3_download_redirect($download, $version, $file_path)
	{
		try {
			// we are in an XHR request and have to use the DLM-Redirect header;
			$redirectTo = $this->s3ConfigurationManager->delegate($file_path)->createUrl();

			if (self::isDoingXhr()) {
				header("DLM-Redirect: " . $redirectTo);
			}
			// if not doing any XHR, send a redirect
			else {
				header("Location: " . $redirectTo);
			}

			// also track the download
			\DLM_Logging::get_instance()->log($download, $version, 'redirect', false, '');
			exit;
		} catch (UnsupportedS3Uri $e) {
			// the given file's URI is not handled by our plug-in, e.g. if it is available local or not in one of our buckets.
			// therefore, proceed with other filters.
		} catch (\Exception $e) {
			// we cannot recover from here
			wp_die($e->getMessage());
		}
	}

	/**
	 * Based upon the given DLM download, the latest download URL is resolved.
	 * @param \WP_Post $dlmDownloadAsPost
	 * @param string|null $url
	 * @return string
	 * @throws ReleaseException
	 */
	protected function resolveDownloadUrl(\WP_Post $dlmDownloadAsPost, ?string $url = null): string
	{
		if (!empty($url)) {
			return $url;
		}

		$url = null;

		// find last download URL and use this
		$availableVersions = get_posts(
			array(
				'post_parent__in' => [$dlmDownloadAsPost->ID],
				'post_type' => 'dlm_download_version',
				'orderby' => 'post_date',
				'order' => 'DESC'
			));

		// fall back to previous uploaded files URLs
		if (sizeof($availableVersions) > 0) {
			$last_version_id = $availableVersions[0]->ID;
			$url_json = get_post_meta($last_version_id, '_files', true);

			if (!empty($url_json)) {
				$urls = json_decode($url_json, true, 512, JSON_UNESCAPED_UNICODE);

				if (is_array($urls) && (sizeof($urls) > 0)) {
					$url = $urls[0];
				}
			}

			if (empty($url)) {
				throw new ReleaseException('last_url_not_available', 'URL has not been provided and last version of download has no valid _file');
			}
		}

		if (empty($url)) {
			throw new ReleaseException('missing_url', 'URL (local or remote) not provided');
		}

		return $url;
	}

	/**
	 * Get a relases' hash by either looking through the provided signatures or let DLM do the work.
	 * @param string $method
	 * @param Signatures|null $signatures
	 * @param array $hashesFromDlm
	 * @return mixed|string|null
	 */
	public function getHash(string $method, ?Signatures $signatures, array $hashesFromDlm = [])
	{
		if ($signatures) {
			$value = $signatures->getSignature($method);
			return $value->hash;
		}

		if (isset($hashesFromDlm[$method])) {
			return $hashesFromDlm[$method];
		}

		return null;
	}

	/**
	 * Publishes a new download for given DLM download
	 * @param Published|null $releaseAlreadyPublished
	 * @param \WP_Post $productPost
	 * @param PublishRequest $publish
	 * @return Published|null
	 * @throws ReleaseException
	 */
	public function publish(?Published $releaseAlreadyPublished, \WP_Post $productPost, PublishRequest $publish): ?Published
	{
		// we are not responsible for that one
		if ($productPost->post_type != 'dlm_download') {
			return $releaseAlreadyPublished;
		}

		$dlmDownloadAsPost = $productPost;

		$url = $this->resolveDownloadUrl($dlmDownloadAsPost, $publish->artifactUrl ?? null);

		$download_versions = get_posts([
			'post_parent__in' => [$dlmDownloadAsPost->ID],
			'meta_key' => '_version',
			'meta_value' => $publish->release->version,
			'post_type' => 'dlm_download_version'
		]);

		// if there is already an existing file with the provided version, the file and settings are replaced
		if (sizeof($download_versions) > 0) {
			$file = $download_versions[0];
			$file_id = $file->ID;
		} else {
			// file is new, so we have to add a new version
			$file = [
				'post_title' => 'Download #' . $dlmDownloadAsPost->ID . ' File Version',
				'post_content' => '',
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
				'post_parent' => $dlmDownloadAsPost->ID,
				'post_type' => 'dlm_download_version',
			];

			$file_id = wp_insert_post($file);
		}

		// make sure that all current versions are flushed when using "download" shortcode
		delete_transient('dlm_file_version_ids_' . $dlmDownloadAsPost->ID);

		// file Manager
		$file_manager = new \DLM_File_Manager();

		if (!$file_id) {
			throw new ReleaseException('file_not_created', 'File was not created', 501);
		}

		// Provide meta data
		update_post_meta($file_id, '_version', $publish->release->version);
		update_post_meta($file_id, '_filesize', $publish->size ?? $file_manager->get_file_size($publish->artifactUrl));

		$artifactUrl = $publish->artifactUrl;

		if ($artifactUrl instanceof S3FileReference) {
			$artifactUrl = $artifactUrl->s3Url;
		}

		update_post_meta($file_id, '_files', $file_manager->json_encode_files([$artifactUrl]));

		// set file hashes
		$hashes = $file_manager->get_file_hashes($url);

		update_post_meta($file_id, '_md5', $this->getHash('md5', $publish->signatures, $hashes));
		update_post_meta($file_id, '_sha1', $this->getHash('sha1', $publish->signatures, $hashes));
		update_post_meta($file_id, '_crc32', $this->getHash('crc32', $publish->signatures, $hashes));
		update_post_meta($file_id, '_sha256', $this->getHash('sha256', $publish->signatures, $hashes));

		// prevent DLM meta values from being overwritten by user's meta data
		$prevent_meta = $this->getDLMsPrivateMetaFields();

		$meta = $publish->meta;

		foreach ($prevent_meta as $key) {
			unset($meta['key']);
		}

		// merge our custom properties/meta into the download's meta
		$meta['continuous_delivery'] = [
			'release' => $publish->release->toArray(),
			'directory' => $publish->directory,
			'filename' => $publish->filename,
			'mime_type' => $publish->mimeType?->name ?? null,
			'architecture' => $publish->architecture?->name ?? null,
			'os' => $publish->operatingSystem?->name ?? null,
			'signatures' => $publish->signatures?->toArray(),
			'scm' => $publish->scm?->toArray() ?? null,
			'build' => $publish->build?->toArray() ?? null,
		];

		foreach ($meta as $key => $value) {
			update_post_meta($file_id, $key, $value);
		}

		$published = new Published(
			$url,
			$dlmDownloadAsPost->ID,
			$file_id,
			$publish->release,
		);

		do_action('dlm_release_version_after', $published, $publish);

		return $published;
	}

	/**
	 * Move the last updated version to the first position (menu_order = 0). Other versions are ordered by their version key number.
	 *
	 * @param $published
	 * @param $meta
	 */
	public function dlm_set_active_version(Published $published, PublishRequest $publishRequest)
	{
		$versions = get_posts([
			'post_parent__in' => [$published->productId],
			'post_type' => 'dlm_download_version',
			'posts_per_page' => -1 /* get all posts and not only 5 (default) */,
			// ordering by _version makes 1.0.9 come before 1.0.10
			'order' => 'DSC',
			'orderby' => 'meta_value',
			'meta_key' => '_version',
		]);

		$file_id = $published->versionId;

		// let zero (=first) be the last updated version
		$orderStart = 1;

		foreach ($versions as $version) {
			if ($version->ID != $file_id) {
				$version->menu_order = $orderStart;
				$orderStart++;
			} else {
				// always put last updated version as first entry
				$version->menu_order = 0;
			}

			// @phpstan-ignore-next-line
			wp_update_post($version);
		}
	}

	private function getDLMsPrivateMetaFields()
	{
		return ['_version', '_filesize', '_files', '_md5', '_sha1', '_crc32', '_sha256'];
	}
}