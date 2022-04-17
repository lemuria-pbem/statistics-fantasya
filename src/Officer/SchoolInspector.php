<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class SchoolInspector extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Education->name;
		$this->subjects[] = Subject::Talents->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Education->name :
				$party           = $this->party($message);
				$totalExperience = $this->getTotalExperience($party);
				$this->storeNumber($message, $totalExperience);
				break;
			case Subject::Talents->name :
				$unit    = $this->unit($message);
				$talents = $this->getTalents($unit->Knowledge());
				$this->storeSingletons($message, $talents);
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}

	#[Pure] protected function getTotalExperience(Party $party): int {
		$totalExperience = 0;
		foreach ($party->People() as $unit /* @var Unit $unit */) {
			foreach ($unit->Knowledge() as $ability) {
				$totalExperience += $ability->Count();
			}
		}
		return $totalExperience;
	}

	protected function getTalents(Knowledge $knowledge): array {
		$talents = [];
		foreach ($knowledge as $ability /* @var Ability $ability */) {
			$class           = getClass($ability->getObject());
			$talents[$class] = $ability->Level();
		}
		return $talents;
	}
}
