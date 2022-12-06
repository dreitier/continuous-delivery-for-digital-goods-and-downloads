<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class ContentTranslation
{
	public function __construct(
		public readonly string $language,
		public readonly string $content,
	)
	{
	}
}