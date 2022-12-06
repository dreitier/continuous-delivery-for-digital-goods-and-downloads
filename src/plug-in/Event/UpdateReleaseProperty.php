<?php
namespace Dreitier\WordPress\ContinuousDelivery\Event;

class UpdateReleaseProperty {
    public function __construct(
        public readonly int $productId,
        public readonly string $versionNameOrId,
        public readonly ?array $properties = [],
        public readonly ?\WP_REST_Request $request,
    ) {}

    public static function fromRequest(\WP_REST_Request $request): UpdateReleaseProperty {

    }
}