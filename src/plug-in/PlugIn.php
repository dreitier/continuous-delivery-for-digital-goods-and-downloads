<?php

namespace Dreitier\WordPress\ContinuousDelivery;

use Dreitier\WordPress\ContinuousDelivery\Integration\DownloadMonitor;
use Dreitier\WordPress\ContinuousDelivery\Integration\EasyDigitalDownloads;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\Configuration;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\ConfigurationManager;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\ConfigurationResolver;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3ConfigurationManager;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3ConfigurationResolver;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3FileReference;
use Dreitier\WordPress\ContinuousDelivery\Storage\S3\S3FileReferenceFactory;
use EDD\Models\Download;

class PlugIn
{
	const HOOK_PREFIX = 'continuous_delivery_';
	const OPTIONS_PREFIX = 'continuous_delivery';
	const SLUG = 'continuous-delivery';
	const REST_API_NAMESPACE = 'continuous-delivery';
	const NAME = 'Continuous Delivery for Digital Goods and Downloads';

	public function __construct()
	{
	}

	public function init()
	{
		$manager = new Manager();
		$s3FileReferenceFactory = new S3FileReferenceFactory();

		// resolve S3 bucket configuration from wp_options table
		$s3ConfigurationManager = new ConfigurationManager($s3FileReferenceFactory, new class implements ConfigurationResolver {
			public function __invoke(S3FileReference $s3FileReference): ?Configuration
			{
				$options = \get_option(PlugIn::OPTIONS_PREFIX);

				if (!is_array($options) || !isset($options['buckets'])) {
					return null;
				}

				foreach ($options['buckets'] as $idx => $bucket) {
					if ($bucket['bucket_name'] == $s3FileReference->bucketName) {
						return new Configuration(
							accessKey: $bucket['access_key'],
							secretAccessKey: $bucket['secret_access_key'],
							region: $bucket['region']
						);
					}
				}

				return null;
			}
		});

		// convert incoming URLs into a unique format to check if those should be handled by our plug-in
		add_filter(self::HOOK_PREFIX . 'sanitize_artifact_release_url', function ($url) use ($s3FileReferenceFactory) {
			if (is_string($url)) {
				try {
					$r = $s3FileReferenceFactory->create($url);
					return $r;
				} catch (\Exception $e) {
					// swallow
				}
			}

			return $url;
		}, 10);

		if (DownloadMonitor::hasRuntimeDependenciesAvailable()) {
			$dlm = new DownloadMonitor($s3ConfigurationManager);
			$dlm->register();
		}

		if (EasyDigitalDownloads::hasRuntimeDependenciesAvailable()) {
			$edd = new EasyDigitalDownloads($s3ConfigurationManager);
			$edd->register();
		}

		$manager->register();
	}

	public static function hook($tag)
	{
		return self::HOOK_PREFIX . $tag;
	}
}