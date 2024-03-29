<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Statistics\Data\Prognoses;
use Lemuria\Model\Fantasya\Statistics\Data\Qualification;
use Lemuria\Model\Fantasya\Statistics\Data\Singletons;
use Lemuria\Model\Fantasya\Statistics\Data\Market;
use Lemuria\Statistics;
use Lemuria\Statistics\Data;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\AlreadyRegisteredException;
use Lemuria\Statistics\Fantasya\Officer\CensusWorker;
use Lemuria\Statistics\Fantasya\Officer\Chancellor;
use Lemuria\Statistics\Fantasya\Officer\Colonialist;
use Lemuria\Statistics\Fantasya\Officer\Economist;
use Lemuria\Statistics\Fantasya\Officer\Ethnologist;
use Lemuria\Statistics\Fantasya\Officer\Ranger;
use Lemuria\Statistics\Fantasya\Officer\SchoolInspector;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;
use Lemuria\Version\VersionFinder;
use Lemuria\Version\VersionTag;

class LemuriaStatistics implements Statistics
{
	protected final const OFFICERS = [
		CensusWorker::class, Chancellor::class, Colonialist::class, Economist::class, Ethnologist::class, Ranger::class,
		SchoolInspector::class
	];

	/**
	 * @var array<string, array<Officer>>
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
			foreach ($officers as $officer) {
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

		$this->archive    = $this->collection;
		$this->collection = [];
	}

	public function request(Record $record): Record {
		$key = $record->Key();
		if (isset($this->archive[$key])) {
			$data = $this->createData($record);
			return $record->setData($data->unserialize($this->archive[$key]));
		}
		return $this->requestLegacy($record);
	}

	public function store(Record $record): static {
		$this->collection[$record->Key()] = $record->Data()->serialize();
		return $this;
	}

	public function register(Officer $officer): static {
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

	public function resign(Officer $officer): static {
		$id = $officer->Id();
		foreach ($officer->Subjects() as $subject) {
			unset ($this->officers[$subject][$id]);
		}
		return $this;
	}

	public function enqueue(Metrics $message): static {
		$subject = $message->Subject();
		if (isset($this->officers[$subject])) {
			foreach ($this->officers[$subject] as $officer) {
				$officer->process($message);
			}
		}
		return $this;
	}

	public function getVersion(): VersionTag {
		$versionFinder = new VersionFinder(__DIR__ . '/..');
		return $versionFinder->get();
	}

	protected function createData(Record $record): Data {
		return match ($record->Subject()) {
			Subject::Animals->name, Subject::MaterialPool->name,
			Subject::RegionPool->name, Subject::Talents->name,
			Subject::RaceUnits->name, Subject::RacePeople->name  => new Singletons(),
			Subject::Experts->name                               => new Prognoses(),
			Subject::Market->name                                => new Market(),
			Subject::Qualification->name                         => new Qualification(),
			default                                              => new Number()
		};
	}

	protected function requestLegacy(Record $record): Record {
		if ($record instanceof PartyEntityRecord) {
			$key = $record->getLegacyKey();
		} else {
			$entity = $record->Entity();
			if ($entity) {
				$key = $entity->Catalog()->getLegacyValue() . '.' . $entity->Id()->Id() . '.' . $record->Subject();
			} else {
				$key = $record->Key();
			}
		}

		if (isset($this->archive[$key])) {
			$data = $this->createData($record);
			return $record->setData($data->unserialize($this->archive[$key]));
		}

		return $record->setData(null);
	}
}
