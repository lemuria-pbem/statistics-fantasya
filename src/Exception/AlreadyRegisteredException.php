<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Exception;

use JetBrains\PhpStorm\Pure;

use Lemuria\Exception\LemuriaException;
use Lemuria\Statistics\Officer;

class AlreadyRegisteredException extends LemuriaException
{
	#[Pure] public function __construct(Officer $officer) {
		parent::__construct('Officer ' . $officer->Id() . ' is already registered.');
	}
}
