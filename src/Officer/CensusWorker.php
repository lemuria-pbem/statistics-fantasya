<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Statistics\Fantasya\Compilation\Number;
use Lemuria\Statistics\Fantasya\Subject\Base;
use Lemuria\Statistics\Fantasya\Subject\Category;
use Lemuria\Statistics\Metrics;

class CensusWorker extends AbstractOfficer
{
	public function __construct() {
		parent::__construct();
		$this->subjects[] = new Base(Category::Population);
	}

	public function process(Metrics $message): void {
		$entity  = $this->entity($message);
		$region  = $this->region($entity);
		$archive = $this->request($entity);

		$peasants            = $region->Resources()[Peasant::class]->Count();
		$compilation         = new Number($peasants);
		$compilation->change = $peasants - $archive->value;
		$this->store($message, $compilation);
	}
}
