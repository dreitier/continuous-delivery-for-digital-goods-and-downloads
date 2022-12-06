<?php

namespace Dreitier\WordPress\ContinuousDelivery\Model;

class Signatures
{
	private array $signatures = [];

	public function add(string $type, string $hash)
	{
		$this->signatures[] = new Signature($type, $hash);
		return $this;
	}

	public function getSignature(string $type): ?Signature
	{
		foreach ($this->signatures as $signature) {
			if ($signature->type == $type) {
				return $signature;
			}
		}

		return null;
	}

	public function hasSignatures(): bool
	{
		return sizeof($this->signatures) > 0;
	}

	public function toArray(): array
	{
		$r = [];

		foreach ($this->signatures as $signature) {
			$r[$signature->type] = $signature->hash;
		}

		return $r;
	}

	public static function fromMap($map): ?Signatures
	{
		if (empty($map) || !is_array($map)) {
			return null;
		}

		$r = new Signatures();

		foreach ($map as $type => $hash) {
			$r->add($type, $hash);
		}

		if ($r->hasSignatures()) {
			return $r;
		}

		return null;
	}
}