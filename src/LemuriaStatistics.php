<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Lemuria;
use Lemuria\Statistics;
use Lemuria\Statistics\Compilation;
use Lemuria\Statistics\Fantasya\Compilation\NotAvailable;
use Lemuria\Statistics\Fantasya\Exception\AlreadyRegisteredException;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;

class LemuriaStatistics implements Statistics
{
	protected final const FILE = 'statistics.json';

	protected int $round;

	protected int $next;

	/**
	 * @var array(string=>array)
	 */
	protected array $officers = [];

	protected array $archive;

	protected array $collection = [];

	public function __construct(private Archivist $archivist) {
		$this->round   = Lemuria::Calendar()->Round();
		$this->next    = $this->round + 1;
		$provider      = $archivist->createProvider($this->round);
		$this->archive = $provider->exists(self::FILE) ? $provider->read(self::FILE) : [];
	}

	public function __destruct() {
		foreach ($this->officers as $officers) {
			foreach ($officers as $officer /* @var Officer $officer */) {
				$officer->close();
			}
		}
		$provider = $this->archivist->createProvider($this->round + 1);
		$provider->write(self::FILE, $this->collection);
	}

	public function request(Record $record): Compilation {
		$round = $record->Round();
		$id    = $record->Identifiable();
		$key   = $id->Catalog()->value . '.' . $id->Id()->Id() . '.' . $record->Subject();
		if ($round === $this->round) {
			return $this->archive[$key] ?? NotAvailable::getInstance();
		}
		if ($round === $this->next) {
			return $this->collection[$key] ?? NotAvailable::getInstance();
		}
		return NotAvailable::getInstance();
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
