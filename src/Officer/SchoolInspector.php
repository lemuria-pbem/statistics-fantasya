<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Knowledge;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class SchoolInspector extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Talents->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Talents->name :
				$unit    = $this->unit($message);
				$talents = $this->getTalents($unit->Knowledge());
				$this->storeSingletons($message, $talents);
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}

	#[Pure] protected function getTalents(Knowledge $knowledge): array {
		$talents = [];
		foreach ($knowledge as $ability) {
			$class           = getClass($ability->getObject());
			$talents[$class] = $ability->Count();
		}
		return $talents;
	}
}
