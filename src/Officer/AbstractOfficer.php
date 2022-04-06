<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Statistics\Data\Commodities;
use Lemuria\Statistics\Data\Number;
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

	protected function storeNumber(Metrics $message, int|float $value): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(Record::from($message));
		$data       = $archive->Data();
		$change     = $data instanceof Number ? $value - $data->value : $value;
		$data       = new Number($value, $change);
		$statistics->store($archive->setData($data));
	}

	protected function storeCachedNumber(Metrics $metrics, int|float $value): void {
		$entity = $metrics->Entity();
		if ($entity) {
			$key = $entity->Catalog()->value . '.' . $entity->Id()->Id() . '.' . $metrics->Subject();
		} else {
			$key = $metrics->Subject();
		}
		$cachedValue       = $this->cache[$key] ?? 0;
		$cachedValue      += $value;
		$this->cache[$key] = $cachedValue;
		$this->storeNumber($metrics, $cachedValue);
	}

	protected function storeCommodities(Metrics $message, array $commodities): void {
		$statistics = Lemuria::Statistics();
		$archive    = $statistics->request(Record::from($message));
		$data       = $archive->Data();
		if (!($data instanceof Commodities)) {
			$data = new Commodities();
		}
		$newData = new Commodities();
		foreach ($commodities as $class => $amount) {
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
