<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Lemuria;
use Lemuria\Statistics;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\AlreadyRegisteredException;
use Lemuria\Statistics\Fantasya\Officer\CensusWorker;
use Lemuria\Statistics\Fantasya\Officer\Economist;
use Lemuria\Statistics\Fantasya\Officer\Ranger;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;
use Lemuria\Version\VersionFinder;
use Lemuria\Version\VersionTag;

class LemuriaStatistics implements Statistics
{
	protected final const OFFICERS = [CensusWorker::class, Economist::class, Ranger::class];

	/**
	 * @var array(string=>array)
	 */
	protected array $officers = [];

	protected array $archive;

	protected array $collection = [];

	public function __construct() {
		foreach (self::OFFICERS as $officer) {
			$this->register(new $officer());
		}
	}

	public function __destruct() {
		foreach ($this->officers as $officers) {
			foreach ($officers as $officer /* @var Officer $officer */) {
				$officer->close();
			}
		}
	}

	public function load(): void {
		$this->archive = Lemuria::Game()->getStatistics();
	}

	public function save(): void {
		ksort($this->collection);
		Lemuria::Game()->setStatistics($this->collection);
	}

	public function request(Record $record): Record {
		$key = $record->Key();
		if (isset($this->archive[$key])) {
			$data = new Number();
			return $record->setData($data->unserialize($this->archive[$key]));
		}
		return $record->setData(null);
	}

	public function store(Record $record): Statistics {
		$this->collection[$record->Key()] = $record->Data()->serialize();
		return $this;
	}

	public function register(Officer $officer): Statistics {
		$id = $officer->Id();
		foreach ($officer->Subjects() as $subject) {
			if (!isset($this->officers[$subject])) {
				$this->officers[$subject] = [];
			}
			if (isset($this->officers[$subject][$id])) {
				throw new AlreadyRegisteredException($officer);
			}
			$this->officers[$subject][$id] = $officer;
		}
		return $this;
	}

	public function resign(Officer $officer): Statistics {
		$id = $officer->Id();
		foreach ($officer->Subjects() as $subject) {
			unset ($this->officers[$subject][$id]);
		}
		return $this;
	}

	public function enqueue(Metrics $message): Statistics {
		$subject = $message->Subject();
		if (isset($this->officers[$subject])) {
			foreach ($this->officers[$subject] as $officer /* @var Officer $officer */) {
				$officer->process($message);
			}
		}
		return $this;
	}

	public function getVersion(): VersionTag {
		$versionFinder = new VersionFinder(__DIR__ . '/..');
		return $versionFinder->get();
	}
}
