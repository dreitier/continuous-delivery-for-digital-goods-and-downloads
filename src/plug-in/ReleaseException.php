<?php

namespace Dreitier\WordPress\ContinuousDelivery;

use Dreitier\WordPress\ContinuousDelivery\Model\CreateRelease;
use Dreitier\WordPress\ContinuousDelivery\Model\ReleaseCreated;
use Dreitier\WordPress\ContinuousDelivery\Model\UpdateReleaseProperty;

class ReleaseException extends \Exception
{
    private ?string $key;
    private ?int $httpErrorCode;

    public function __construct(string $key, string $message, ?int $httpErrorCode = null)
    {
        parent::__construct($message);
        $this->key = $key;
        $this->httpErrorCode = $httpErrorCode;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getHttpErrorCode()
    {
        return $this->httpErrorCode;
    }

    public function toWpError()
    {
        return new \WP_Error($this->key, $this->getMessage(), ['status' => $this->httpErrorCode ?? 400]);
    }
}