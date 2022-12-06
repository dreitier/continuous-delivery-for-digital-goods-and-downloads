<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;


class S3FileReference
{
    public function __construct(public readonly string $s3Url, public readonly string $bucketName, public readonly string $path)
    {
    }

    public function __toString()
    {
        return $this->s3Url;
    }
}