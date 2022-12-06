<?php

namespace Dreitier\WordPress\ContinuousDelivery\Storage\S3;

use Dreitier\WordPress\ContinuousDelivery\Vendor\Akeeba\Engine\Postproc\Connector\S3v4\Configuration as ConnectorConfiguration;
use Dreitier\WordPress\ContinuousDelivery\Vendor\Akeeba\Engine\Postproc\Connector\S3v4\Connector;

/**
 * S3 configuration
 */
class Configuration
{
	public function __construct(public readonly string $accessKey, public readonly string $secretAccessKey, public readonly string $region = 'eu-central-1')
	{
	}

	public function toConnector(): Connector
	{
		$connectorConfiguration = new ConnectorConfiguration(
			$this->accessKey,
			$this->secretAccessKey,
			'v4',
			$this->region,
		);

		$connector = new Connector($connectorConfiguration);
		return $connector;
	}
}