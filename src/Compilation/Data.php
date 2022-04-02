<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Compilation;

use Lemuria\Statistics\Compilation;

abstract class Data implements Compilation
{
	public int|float $value = 0;

	public function serialize(): mixed {
		return $this->value;
	}
}
