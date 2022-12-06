<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class Content
{
	private array $translations = [];

	public function __construct(
		public readonly string $type = 'text',
	)
	{
	}

	public function toArray(): array
	{
		$r = ['type' => $this->type, 'trans' => []];

		foreach ($this->translations as $language => $content) {
			$r['trans'][$language] = $content;
		}

		return $r;
	}

	public function hasContent(): bool
	{
		return sizeof($this->translations) > 0;
	}

	public function add(string $language, string $content)
	{
		$this->translations[] = new ContentTranslation($language, $content);
		return $this;
	}

	public static function fromMap($map): ?Content
	{
		if (!$map || !is_array($map)) {
			return null;
		}

		$type = $map['type'] ?? 'text';
		$r = new Content($type);

		if (isset($map['trans']) && is_array($map['trans'])) {
			foreach ($map['trans'] as $language => $content) {
				$r->add($language, $content);
			}
		}

		if ($r->hasContent()) {
			return $r;
		}

		return null;
	}
}