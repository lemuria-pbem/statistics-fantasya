<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Identifiable;
use Lemuria\Lemuria;
use Lemuria\Statistics\Record;
use Lemuria\Statistics\Subject;

class Last implements Record
{
	private static ?int $round = null;

	public function __construct(private Identifiable $identifiable, private Subject $subject) {
	}

	public function Subject(): Subject {
		return $this->subject;
	}

	public function Identifiable(): Identifiable {
		return $this->identifiable;
	}

	public function Round(): int {
		if (self::$round === null) {
			self::$round = Lemuria::Calendar()->Round();
		}
		return self::$round;
	}

	protected static function initRound(int $round): void {
		self::$round = $round;
	}
}
