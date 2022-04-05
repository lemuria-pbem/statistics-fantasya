<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Model\Fantasya\Statistics\Data\Commodities;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;
use Lemuria\Statistics\Record;

abstract class AbstractOfficer implements Officer
{
	protected array $subjects = [];

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
				unset($newData[$class]);
			} else {
				$newData[$class] = new Number($amount, $amount);
			}
		}
		foreach ($data as $class => $number) {
			$newData[$class] = new Number(0, -$number->value);
		}
		$statistics->store($archive->setData($data));
	}
}
