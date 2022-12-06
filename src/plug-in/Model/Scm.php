<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class Scm
{
	public function __construct(public readonly ?string $revision, public readonly ?string $tag)
	{
	}

	public function toArray(): array
	{
		return [
			'revision' => $this->revision,
			'tag' => $this->tag
		];
	}

	public static function fromMap($map): ?Scm
	{
		if (!$map) {
			return null;
		}

		$revision = $map['revision'] ?? null;
		$tag = $map['tag'] ?? null;

		if ($revision || $tag) {
			return new Scm($revision, $tag);
		}

		return null;
	}
}
