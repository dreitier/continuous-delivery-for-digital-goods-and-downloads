<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class OperatingSystem
{
	public function __construct(public readonly string $name)
	{
	}
}