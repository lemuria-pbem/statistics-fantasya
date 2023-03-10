<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Intelligence;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class Colonialist extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::UnitForce->name;
		$this->subjects[] = Subject::PeopleForce->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::UnitForce->name :
				$unit         = $this->unit($message);
				$intelligence = new Intelligence($unit->Region());
				$units        = $intelligence->getUnits($unit->Party())->count();
				$this->storeNumber($message, $units);
				break;
			case Subject::PeopleForce->name :
				$unit         = $this->unit($message);
				$intelligence = new Intelligence($unit->Region());
				$persons      = $intelligence->getUnits($unit->Party())->Size();
				$this->storeNumber($message, $persons);
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}
}
