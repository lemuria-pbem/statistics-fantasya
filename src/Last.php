<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

class Last extends Current
{
	public function Round(): int {
		return parent::Round() - 1;
	}
}
