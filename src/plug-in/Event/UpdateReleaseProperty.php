<?php

namespace Dreitier\WordPress\ContinuousDelivery\Event;

class UpdateReleaseProperty
{
	public function __construct(
		public readonly int               $productId,
		public readonly string            $versionNameOrId,
		public readonly ?\WP_REST_Request $request,
		public readonly ?array            $properties = [],
	)
	{
	}

	public static function fromRequest(\WP_REST_Request $request): UpdateReleaseProperty
	{
		return new UpdateReleaseProperty(0, '', $request, null);
	}
}