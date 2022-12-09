<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

use Dreitier\WordPress\ContinuousDelivery\ReleaseException;

class Release
{
	public function __construct(
		public readonly string          $version,
		public readonly ?string         $date,
		public readonly null|int|string $train,
		public readonly ?bool           $isSecurityRelated,
		public readonly ?bool           $hasBreakingChanges,
		public readonly ?Content        $title,
		public readonly ?Content        $description,
		public readonly ?Content        $changelog,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'version' => $this->version,
			'date' => $this->date,
			'train' => $this->train,
			'is_security_related' => $this->isSecurityRelated,
			'has_breaking_changes' => $this->hasBreakingChanges,
			'title' => $this->title?->toArray(),
			'description' => $this->description?->toArray(),
			'changelog' => $this->changelog?->toArray(),
		];
	}

	public static function fromMap($map): Release
	{
		if (!isset($map['version']) || empty($map['version'])) {
			throw new ReleaseException('missing_release_version_property', 'Property .release.version is missing', 400);
		}

		$args = [];
		$args['version'] = $map['version'];
		$args['date'] = $map['date'] ?? null;
		$args['train'] = $map['train'] ?? null;
		$args['isSecurityRelated'] = $map['is_security_related'] ?? null;
		$args['hasBreakingChanges'] = $map['has_breaking_changes'] ?? null;
		$args['title'] = Content::fromMap($map['title'] ?? null);
		$args['description'] = Content::fromMap($map['description'] ?? null);
		$args['changelog'] = Content::fromMap($map['changelog'] ?? null);

		return new Release(... $args);
	}
}
