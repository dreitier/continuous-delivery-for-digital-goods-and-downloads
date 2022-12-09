<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;

/**
 * Based upon a file reference and a given configuration, we create an accessible S3 file.
 */
class AccessibleS3File
{
	public function __construct(public readonly S3FileReference $fileReference, public readonly Configuration $configuration)
	{
	}

	public function createUrl(int $lifetime = 60, bool $useHttps = true): string
	{
		$connector = $this->configuration->toConnector();
		return $connector->getAuthenticatedURL($this->fileReference->bucketName, $this->fileReference->path, $lifetime, $useHttps);
	}
}