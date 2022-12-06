<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class Signature
{
	public function __construct(public readonly string $type, public readonly string $hash)
	{
	}
}