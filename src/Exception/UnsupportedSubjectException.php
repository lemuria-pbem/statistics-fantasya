<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Exception;

use Lemuria\Exception\LemuriaException;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;

class UnsupportedSubjectException extends LemuriaException
{
	public function __construct(Officer $officer, Metrics $message) {
		parent::__construct('Officer ' . $officer->Id() . ' cannot process ' . $message->Subject() . ' message.');
	}
}
