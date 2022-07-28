<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Command\Learn;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Intelligence;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Modification;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\People;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class SchoolInspector extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Education->name;
		$this->subjects[] = Subject::Experts->name;
		$this->subjects[] = Subject::Qualification->name;
		$this->subjects[] = Subject::Talents->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Education->name :
				$party           = $this->party($message);
				$totalExperience = $this->getTotalExperience($party);
				$this->storeNumber($message, $totalExperience);
				break;
			case Subject::Experts->name :
				$party   = $this->party($message);
				$experts = $this->calculateExperts($party);
				$this->storePrognoses($message, $experts);
				break;
			case Subject::Qualification->name :
				$unit          = $this->unit($message);
				$intelligence  = new Intelligence($unit->Region());
				$qualification = $this->calculateQualification($intelligence->getUnits($unit->Party()));
				$this->storeQualification($message, $qualification);
				break;
			case Subject::Talents->name :
				$unit    = $this->unit($message);
				$talents = $this->getTalents($unit->Knowledge(), $unit->Race()->Modifications());
				$this->storeSingletons($message, $talents);
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}

	protected function getTotalExperience(Party $party): int {
		$totalExperience = 0;
		foreach ($party->People() as $unit /* @var Unit $unit */) {
			foreach ($unit->Knowledge() as $ability) {
				$totalExperience += $ability->Count();
			}
		}
		return $totalExperience;
	}

	protected function calculateExperts(Party $party): array {
		$experts = [];
		foreach ($party->People() as $unit /* @var Unit $unit */) {
			$modifications = $unit->Race()->Modifications();
			foreach ($unit->Knowledge() as $knowledge /* @var Ability $knowledge */) {
				$level  = $this->calculateLevel($knowledge, $modifications);
				$rounds = $this->calculateRounds($knowledge);
				$class  = getClass($knowledge->Talent());
				if (isset($experts[$class])) {
					$lastLevel = $experts[$class][0];
					if ($level > $lastLevel) {
						$experts[$class][0] = $level;
						$experts[$class][1] = $rounds;
					} elseif ($level === $lastLevel && $experts[$class][1] > $rounds) {
						$experts[$class][1] = $rounds;
					}
				} else {
					$experts[$class] = [$level, $rounds];
				}
			}
		}
		return $experts;
	}

	protected function calculateQualification(People $people): array {
		$qualification = [];
		foreach ($people as $unit /* @var Unit $unit */) {
			$modifications = $unit->Race()->Modifications();
			foreach ($unit->Knowledge() as $knowledge /* @var Ability $knowledge */) {
				$level  = $this->calculateLevel($knowledge, $modifications);
				$rounds = $this->calculateRounds($knowledge);
				$class  = getClass($knowledge->Talent());
				if (!isset($qualification[$class])) {
					$qualification[$class] = [];
				}
				if (!isset($qualification[$class][$level])) {
					$qualification[$class][$level] = [$unit->Size(), $rounds];
				} else {
					$qualification[$class][$level][0] += $unit->Size();
					if ($rounds < $qualification[$class][$level][1]) {
						$qualification[$class][$level][1] = $rounds;
					}
				}
			}
		}

		ksort($qualification);
		foreach (array_keys($qualification) as $class) {
			krsort($qualification[$class]);
			$n = count($qualification[$class]);
			if ($n > 3) {
				$levels = array_keys($qualification[$class]);
				$rest   = $levels[2];
				for ($i = 3; $i < $n; $i++) {
					$level                            = $levels[$i];
					$qualification[$class][$rest][0] += $qualification[$class][$level][0];
					$qualification[$class][$rest][1]  = min($qualification[$class][$rest][1], $qualification[$class][$level][1]);
					unset($qualification[$class][$level]);
				}
			}
		}
		return $qualification;
	}

	protected function getTalents(Knowledge $knowledge, Knowledge $modifications): array {
		$talents = [];
		foreach ($knowledge as $ability /* @var Ability $ability */) {
			$talent       = $ability->Talent();
			$class        = getClass($ability->getObject());
			$modification = $modifications[$talent];
			if ($modification instanceof Modification) {
				$ability = $modification->getModified($ability);
			}
			$talents[$class] = $ability->Level();
		}
		return $talents;
	}

	private function calculateLevel(Ability $knowledge, Knowledge $modifications): int {
		$talent       = $knowledge->Talent();
		$ability      = new Ability($talent, $knowledge->Experience());
		$modification = $modifications[$talent];
		if ($modification instanceof Modification) {
			$ability = $modification->getModified($ability);
		}
		return $ability->Level();
	}

	private function calculateRounds(Ability $knowledge): int {
		$difference = Ability::getExperience($knowledge->Level() + 1) - $knowledge->Experience();
		return (int)ceil($difference / Learn::PROGRESS);
	}
}
