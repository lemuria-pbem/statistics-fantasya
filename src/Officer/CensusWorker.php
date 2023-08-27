<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Availability;
use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Factory\RealmTrait;
use Lemuria\Engine\Fantasya\State;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Model\Fantasya\Commodity\Silver;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class CensusWorker extends AbstractOfficer
{
	use BuilderTrait;
	use RealmTrait;

	private ?Context $context = null;

	/**
	 * @noinspection DuplicatedCode
	 */
	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Births->name;
		$this->subjects[] = Subject::Infrastructure->name;
		$this->subjects[] = Subject::Migration->name;
		$this->subjects[] = Subject::People->name;
		$this->subjects[] = Subject::Population->name;
		$this->subjects[] = Subject::Unemployment->name;
		$this->subjects[] = Subject::Units->name;
		$this->subjects[] = Subject::Wealth->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Births->name :
				$data   = $message->Data();
				$amount = $data instanceof Number ? $data->value : 0;
				break;
			case Subject::Infrastructure->name :
				$this->initContext();
				$amount = $this->calculateInfrastructure($this->region($message));
				break;
			case Subject::Migration->name :
				$data   = $message->Data();
				$amount = $data instanceof Number ? $data->value : 0;
				$this->storeCachedNumber($message, $amount);
				return;
			case Subject::People->name :
				$amount = $this->party($message)->People()->Size();
				break;
			case Subject::Population->name :
				$resources = $this->region($message)->Resources();
				$amount    = $resources[Peasant::class]->Count();
				break;
			case Subject::Unemployment->name :
				$availability = new Availability($this->region($message));
				$amount       = $availability->getResource(Peasant::class)->Count();
				break;
			case Subject::Units->name :
				$amount = $this->party($message)->People()->count();
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

	private function initContext(): void {
		if (!$this->context) {
			$this->context = new Context(State::getInstance());
		}
	}
}
