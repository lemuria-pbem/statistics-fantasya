<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Availability;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class CensusWorker extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Population->name;
		$this->subjects[] = Subject::Unemployment->name;
		$this->subjects[] = Subject::Wealth->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Population->name :
				$resources = $this->region($message)->Resources();
				$amount    = $resources[Peasant::class]->Count();
				break;
			case Subject::Unemployment->name :
				$availability = new Availability($this->region($message));
				$amount       = $availability->getResource(Peasant::class)->Count();
				break;
			case Subject::Wealth->name :
				$resources = $this->region($message)->Resources();
				$amount    = $resources[Silver::class]->Count();
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
		$this->storeNumber($message, $amount);
	}
}
