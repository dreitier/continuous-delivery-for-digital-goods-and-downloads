<?php

namespace Dreitier\WordPress\ContinuousDelivery\Event;

use Dreitier\WordPress\ContinuousDelivery\Model\Architecture;
use Dreitier\WordPress\ContinuousDelivery\Model\Build;
use Dreitier\WordPress\ContinuousDelivery\Model\MimeType;
use Dreitier\WordPress\ContinuousDelivery\Model\OperatingSystem;
use Dreitier\WordPress\ContinuousDelivery\Model\Release;
use Dreitier\WordPress\ContinuousDelivery\Model\Scm;
use Dreitier\WordPress\ContinuousDelivery\Model\Signatures;
use Dreitier\WordPress\ContinuousDelivery\PlugIn;
use Dreitier\WordPress\ContinuousDelivery\ReleaseException;

class PublishRequest
{
	public function __construct(
		public readonly string|object     $artifactUrl,
		public readonly int|string        $product,
		public readonly ?int              $size,
		public readonly ?string           $filename,
		public readonly ?string           $directory,
		public readonly ?MimeType         $mimeType,
		public readonly ?Architecture     $architecture,
		public readonly ?OperatingSystem  $operatingSystem,
		public readonly ?Signatures       $signatures,
		public readonly ?Build            $build,
		public readonly ?Scm              $scm,
		public readonly ?Release          $release,
		public readonly ?array            $meta = [],
		public readonly ?\WP_REST_Request $request = null,
	)
	{
	}

	public static function fromRequest(\WP_REST_Request $request): PublishRequest
	{
		$productId = $request->get_param('id');
		$content = $request->get_json_params();

		$args = [];

		if (empty($content)) {
			throw new ReleaseException('missing_json_body', 'No JSON body given', 406);
		}

		if (empty($content['artifact_url'])) {
			throw new ReleaseException('missing_url_property', 'Property .url is missing', 400);
		}

		$args['artifactUrl'] = apply_filters(PlugIn::HOOK_PREFIX . 'sanitize_artifact_release_url', $content['artifact_url']);
		$args['product'] = $productId;
		$args['size'] = $content['size'] ?? null;
		$args['filename'] = isset($content['filename']) ? $content['filename'] : basename($content['artifact_url']);
		$args['directory'] = $content['directory'] ?? null;
		$args['mimeType'] = isset($content['mime_type']) ? new MimeType($content['mime_type']) : null;
		$args['architecture'] = isset($content['architecture']) ? new Architecture($content['architecture']) : null;
		$args['operatingSystem'] = isset($content['os']) ? new OperatingSystem($content['os']) : null;

		if (!isset($content['release']) || !is_array($content['release'])) {
			throw new ReleaseException('missing_release_property', 'Property .release is missing', 400);
		}

		$args['release'] = Release::fromMap($content['release']);
		$args['build'] = Build::fromMap($content['build'] ?? null);
		$args['scm'] = Scm::fromMap($content['scm'] ?? null);
		$args['signatures'] = Signatures::fromMap($content['signatures'] ?? null);

		$meta = !empty($content['meta']) && is_array($content['meta']) ? $content['meta'] : null;
		$args['meta'] = $meta;
		$args['request'] = $request;

		return new PublishRequest(
			... $args
		);
	}
}
