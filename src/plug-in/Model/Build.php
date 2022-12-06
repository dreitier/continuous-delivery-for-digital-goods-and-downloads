<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class Build
{
	public function __construct(public readonly ?string $date, public readonly ?string $number)
	{
	}

	public function toArray(): array
	{
		return [
			'date' => $this->date,
			'number' => $this->number
		];
	}

	public static function fromMap($map): ?Build
	{
		if (!$map) {
			return null;
		}

		$date = $map['date'] ?? null;
		$number = $map['number'] ?? null;

		if ($date || $number) {
			return new Build($date, $number);
		}

		return null;
	}
}