<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Compilation;

class Number extends Data
{
	public int|float|null $change = null;

	public function __construct(int|float $value) {
		$this->value = $value;
	}

	public function serialize(): mixed {
		return [$this->value, $this->change];
	}
}
