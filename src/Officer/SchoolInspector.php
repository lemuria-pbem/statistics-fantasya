<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Command\Learn;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Ability;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Model\Fantasya\Modification;
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
		$this->subjects[] = Subject::Experts->name;
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
				$talent       = $knowledge->Talent();
				$ability      = new Ability($talent, $knowledge->Experience());
				$class        = getClass($talent);
				$modification = $modifications[$talent];
				if ($modification instanceof Modification) {
					$ability = $modification->getModified($ability);
				}
				$level      = $ability->Level();
				$difference = Ability::getExperience($knowledge->Level() + 1) - $knowledge->Experience();
				$rounds     = (int)ceil($difference / Learn::PROGRESS);
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
}
