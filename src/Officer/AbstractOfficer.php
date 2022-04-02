<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Statistics\Fantasya\Compilation\Data;
use Lemuria\Statistics\Fantasya\Current;
use Lemuria\Statistics\Fantasya\Last;
use Lemuria\Statistics\Fantasya\LemuriaStatistics;
use Lemuria\Statistics\Fantasya\Metrics\Entity;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Officer;

abstract class AbstractOfficer implements Officer
{
	protected static LemuriaStatistics $statistics;

	protected array $subjects = [];

	private static int $lastId = 0;

	private int $id;

	public static function setStatistics(LemuriaStatistics $statistics): void {
		self::$statistics = $statistics;
	}

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

	protected function request(Entity $metrics): Data {
		$record = new Last($metrics->Identifiable(), $metrics->Subject());
		/** @var Data $data */
		$data = self::$statistics->request($record);
		return $data;
	}

	protected function store(Entity $metrics, Data $compilation): void {
		$record = new Current($metrics->Identifiable(), $metrics->Subject());
		self::$statistics->store($record, $compilation);
	}

	protected function entity(Metrics $metrics): Entity {
		if ($metrics instanceof Entity) {
			return $metrics;
		}
		throw new LemuriaException('Metrics is not an entity.');
	}

	protected function region(Entity $metrics): Region {
		$region = $metrics->Identifiable();
		if ($region instanceof Region) {
			return $region;
		}
		throw new LemuriaException('Metrics has no region entity.');
	}
}
