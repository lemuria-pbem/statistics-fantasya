<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Exception\LemuriaException;
use Lemuria\Statistics;
use Lemuria\Statistics\Compilation;
use Lemuria\Statistics\Fantasya\Exception\AlreadyRegisteredException;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;

class LemuriaStatistics implements Statistics
{
	/**
	 * @var array(string=>array)
	 */
	protected array $officers = [];

	public function __construct() {
		//TODO load
	}

	public function __destruct() {
		foreach ($this->officers as $officers) {
			foreach ($officers as $officer /* @var Officer $officer */) {
				$officer->close();
			}
		}
		//TODO save
	}

	public function request(Record $record): Compilation {
		//TODO
		throw new LemuriaException('Not implemented yet.');
	}

	public function register(Officer $officer): Statistics {
		$id = $officer->Id();
		foreach ($officer->Subjects() as $subject) {
			$class = (string)$subject;
			if (!isset($this->officers[$class])) {
				$this->officers[$class] = [];
			}
			if (isset($this->officers[$class][$id])) {
				throw new AlreadyRegisteredException($officer);
			}
			$this->officers[$class][$id] = $officer;
		}
		return $this;
	}

	public function resign(Officer $officer): Statistics {
		$id = $officer->Id();
		foreach ($officer->Subjects() as $subject) {
			$class = (string)$subject;
			unset ($this->officers[$class][$id]);
		}
		return $this;
	}

	public function enqueue(Metrics $message): Statistics {
		$subject = (string)$message->Subject();
		if (isset($this->officers[$subject])) {
			foreach ($this->officers[$subject] as $officer /* @var Officer $officer */) {
				$officer->process($message);
			}
		}
		return $this;
	}
}
