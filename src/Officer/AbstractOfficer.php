<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Resources;
use Lemuria\Model\Fantasya\Statistics\Data\Prognoses;
use Lemuria\Model\Fantasya\Statistics\Data\Singletons;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Data\Prognosis;
use Lemuria\Statistics\Fantasya\PartyEntityRecord;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;

abstract class AbstractOfficer implements Officer
{
	protected array $subjects = [];

	protected array $cache = [];

	private static int $lastId = 0;

	private int $id;

	public function __construct() {
		$this->id = ++self::$lastId;
	}

	public function Id(): int {
		return $this->id;
	}

	public function Subjects(): array {
		return $this->subjects;
	}

	public function close(): void {
		Lemuria::Statistics()->resign($this);
	}

	protected function party(Metrics $metrics): Party {
		$party = $metrics->Entity();
		if ($party instanceof Party) {
			return $party;
		}
		throw new LemuriaException('Expected a Party identifiable in metrics ' . $metrics->Subject() . '.');
	}

	protected function region(Metrics $metrics): Region {
		$region = $metrics->Entity();
		if ($region instanceof Region) {
			return $region;
		}
		throw new LemuriaException('Expected a Region identifiable in metrics ' . $metrics->Subject() . '.');
	}

	protected function unit(Metrics $metrics): Unit {
		$unit = $metrics->Entity();
		if ($unit instanceof Unit) {
			return $unit;
		}
		throw new LemuriaException('Expected a Unit identifiable in metrics ' . $metrics->Subject() . '.');
	}

	protected function storeNumber(Metrics $message, int|float $value): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(Record::from($message));
		$data       = $archive->Data();
		$change     = $data instanceof Number ? $value - $data->value : $value;
		$data       = new Number($value, $change);
		$statistics->store($archive->setData($data));
	}

	protected function storeNumberPartyEntity(Metrics $message, int|float $value): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(PartyEntityRecord::from($message));
		$data       = $archive->Data();
		$change     = $data instanceof Number ? $value - $data->value : $value;
		$data       = new Number($value, $change);
		$statistics->store($archive->setData($data));
	}

	protected function storeCachedNumber(Metrics $metrics, int|float $value): void {
		$key               = Record::from($metrics)->Key();
		$cachedValue       = $this->cache[$key] ?? 0;
		$cachedValue      += $value;
		$this->cache[$key] = $cachedValue;
		$this->storeNumber($metrics, $cachedValue);
	}

	protected function storeCachedNumberPartyEntity(Metrics $metrics, int|float $value): void {
		$key               = PartyEntityRecord::from($metrics)->Key();
		$cachedValue       = $this->cache[$key] ?? 0;
		$cachedValue      += $value;
		$this->cache[$key] = $cachedValue;
		$this->storeNumberPartyEntity($metrics, $cachedValue);
	}

	protected function storeSingletons(Metrics $message, array $singletons): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(Record::from($message));
		$data       = $archive->Data();
		if (!($data instanceof Singletons)) {
			$data = new Singletons();
		}
		$newData = new Singletons();
		foreach ($singletons as $class => $amount) {
			if (isset($data[$class])) {
				$newData[$class] = new Number($amount, $amount - $data[$class]->value);
				unset($data[$class]);
			} else {
				$newData[$class] = new Number($amount, $amount);
			}
		}
		foreach ($data as $class => $number) {
			$newData[$class] = new Number(0, -$number->value);
		}
		if (count($newData) > 0) {
			$statistics->store($archive->setData($newData));
		}
	}

	protected function storePrognoses(Metrics $message, array $singletons): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(Record::from($message));
		$data       = $archive->Data();
		if (!($data instanceof Prognoses)) {
			$data = new Prognoses();
		}
		$newData = new Prognoses();
		foreach ($singletons as $class => $values) {
			$amount = $values[0];
			$eta    = $values[1];
			if (isset($data[$class])) {
				$newData[$class] = new Prognosis($amount, $amount - $data[$class]->value, $eta);
				unset($data[$class]);
			} else {
				$newData[$class] = new Prognosis($amount, $amount, $eta);
			}
		}
		foreach ($data as $class => $prognosis) {
			$newData[$class] = new Prognosis(0, -$prognosis->value);
		}
		if (count($newData) > 0) {
			$statistics->store($archive->setData($newData));
		}
	}

	protected function storeRegionPool(Metrics $message, Resources $pool): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(PartyEntityRecord::from($message));
		$data       = $archive->Data();
		if (!($data instanceof Singletons)) {
			$data = new Singletons();
		}
		$newData = new Singletons();
		foreach ($pool as $class => $quantity) {
			$amount = $quantity->Count();
			if (isset($data[$class])) {
				$newData[$class] = new Number($amount, $amount - $data[$class]->value);
				unset($data[$class]);
			} else {
				$newData[$class] = new Number($amount, $amount);
			}
		}
		foreach ($data as $class => $number) {
			$newData[$class] = new Number(0, -$number->value);
		}
		if (count($newData) > 0) {
			$statistics->store($archive->setData($newData));
		}
	}
}
