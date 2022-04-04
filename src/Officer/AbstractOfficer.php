<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Region;
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
		$change     = $archive instanceof Number ? $value - $archive->value : null;
		$data       = new Number($value, $change);
		$statistics->store($archive->setData($data));
	}
}
