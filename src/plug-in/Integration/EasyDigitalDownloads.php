<?php

namespace Dreitier\WordPress\ContinuousDelivery\Integration;

use Dreitier\WordPress\ContinuousDelivery\Event\PublishRequest;
use Dreitier\WordPress\ContinuousDelivery\Event\Published;
use Dreitier\WordPress\ContinuousDelivery\Event\UpdateReleaseProperty;
use Dreitier\WordPress\ContinuousDelivery\PlugIn;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\ConfigurationManager;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3FileReference;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\UnsupportedS3Uri;

class EasyDigitalDownloads
{
	public function __construct(public readonly ConfigurationManager $s3ConfigurationManager)
	{
	}

	public function register()
	{
		add_filter(PlugIn::hook('handle_create_release_request'), [$this, 'publish'], 10, 3);
		add_action('edd_process_download_headers', [$this, 's3_download_redirect'], 10, 4);
	}

	public static function hasRuntimeDependenciesAvailable()
	{
		return class_exists('\Easy_Digital_Downloads');
	}

	/**
	 * If our plug-in is responsible for the given file URI scheme, we let our delegator do the work.
	 * @param $requested_file_url
	 * @param $downloads
	 * @param $email
	 * @param $payment
	 */
	public function s3_download_redirect($requested_file_url, $downloads, $email, $payment): void
	{
		try {
			$this->s3ConfigurationManager->sendRedirect($requested_file_url);
		} catch (UnsupportedS3Uri $e) {
			// the given file's URI is not handled by our plug-in, e.g. if it is available local or not in one of our buckets.
			// therefore, proceed with other filters.
			return;
			// @phpstan-ignore-next-line
		} catch (\Exception $e) {
			// we cannot recover from here
			wp_die($e->getMessage());
		}

		return;
	}

	public function publish(?Published $releaseAlreadyPublished, \WP_Post $productPost, PublishRequest $publish): ?Published
	{
		// we are not responsible for that one:
		// - target product has not post_type 'download', which is the type for EDD
		// - Incoming artifact URL is not an S3 file reference
		if ($productPost->post_type != 'download' || !($publish->artifactUrl instanceof S3FileReference)) {
			return $releaseAlreadyPublished;
		}

		$eddDownloadAsPost = $productPost;

		$download = \edd_get_download($publish->product);
		$files = $download->get_files();

		$versionFoundAtIndex = null;
		$useVersion = null;

		for ($i = 0, $m = sizeof($files); $i < $m; $i++) {
			$file = $files[$i];

			if ($file['name'] == $publish->release->version) {
				$versionFoundAtIndex = $i;
				$useVersion = $file;
				break;
			}
		}

		if (!$useVersion) {
			$useVersion = [
				'index' => 0,
				'attachment_id' => 0,
				'thumbnail_size' => false,
				'name' => $publish->release->version,
				'condition' => 'all'
			];
		}

		$artifactUrl = $publish->artifactUrl->__toString();

		$useVersion['name'] = $publish->release->version;
		$useVersion['file'] = $artifactUrl;

		if ($versionFoundAtIndex !== null) {
			$files[$versionFoundAtIndex] = $useVersion;
		} else {
			array_unshift($files, $useVersion);
			$versionFoundAtIndex = 0;
		}

		// reorder
		for ($i = 0; $i < sizeof($files); $i++) {
			$files[$i]['index'] = $i;
		}

		update_post_meta($eddDownloadAsPost->ID, 'edd_download_files', $files);

		$releaseCreated = new Published(
			$useVersion['file'],
			$eddDownloadAsPost->ID,
			$versionFoundAtIndex,
			$publish->release,
		);

		return $releaseCreated;
	}

	public function updateReleaseProperty(UpdateReleaseProperty $updateReleaseProperty)
	{
	}
}