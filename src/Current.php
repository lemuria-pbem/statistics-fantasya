<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

class Current extends Last
{
	protected static function initRound(int $round): void {
		parent::initRound($round + 1);
	}
}
