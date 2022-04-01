<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Lemuria;
use Lemuria\Statistics\Officer;

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

	public function close(): Officer {
		Lemuria::Statistics()->resign($this);
		return $this;
	}
}
