<?php
namespace Dreitier\WordPress\ContinuousDelivery\Event;

use Dreitier\WordPress\ContinuousDelivery\Model\Release;

class Published {
    public function __construct(
		public readonly string $artifactUrl,
        public readonly string|int $productId,
		public readonly string|int $versionId,
		public readonly Release $release,
    ) {}
}