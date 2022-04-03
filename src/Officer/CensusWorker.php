<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Availability;
use Lemuria\Model\Fantasya\Commodity;
use Lemuria\Model\Fantasya\Commodity\Peasant;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Statistics\Fantasya\Metrics\Entity;
use Lemuria\Statistics\Fantasya\Subject\Base;
use Lemuria\Statistics\Fantasya\Subject\Category;
use Lemuria\Statistics\Metrics;

class CensusWorker extends AbstractOfficer
{
	use BuilderTrait;

	private Commodity $peasant;

	public function __construct() {
		parent::__construct();
		$this->peasant    = self::createCommodity(Peasant::class);
		$this->subjects[] = new Base(Category::Population);
	}

	public function process(Metrics $message): void {
		$region    = $this->region($message);
		$resources = $region->Resources();

		$peasants = $resources[$this->peasant]->Count();
		$this->storeNumber($message, $peasants);

		$message      = new Entity($region, new Base(Category::Unemployment));
		$availability = new Availability($region);
		$peasants     = $availability->getResource($this->peasant)->Count();
		$this->storeNumber($message, $peasants);
	}
}
