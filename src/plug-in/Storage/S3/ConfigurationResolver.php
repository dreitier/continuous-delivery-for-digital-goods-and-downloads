<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;

/**
 * Find a bucket configuration for a given file reference.
 */
interface ConfigurationResolver
{
	public function __invoke(S3FileReference $s3FileReference): ?Configuration;
}
