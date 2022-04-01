<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Exception\LemuriaException;
use Lemuria\Statistics;
use Lemuria\Statistics\Compilation;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;

class LemuriaStatistics implements Statistics
{

	public function request(Record $record): Compilation {
		throw new LemuriaException();
	}

	public function register(Officer $officer): Statistics {
		throw new LemuriaException();
	}

	public function resign(Officer $officer): Statistics {
		throw new LemuriaException();
	}

	public function enqueue(Metrics $message): Statistics {
		throw new LemuriaException();
	}
}
