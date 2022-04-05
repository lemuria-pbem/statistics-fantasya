<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Commodity\Camel;
use Lemuria\Model\Fantasya\Commodity\Elephant;
use Lemuria\Model\Fantasya\Commodity\Griffin;
use Lemuria\Model\Fantasya\Commodity\Horse;
use Lemuria\Model\Fantasya\Commodity\Pegasus;
use Lemuria\Model\Fantasya\Commodity\Wood;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class Ranger extends AbstractOfficer
{
	use BuilderTrait;

	protected final const ANIMALS = [Camel::class, Elephant::class, Griffin::class, Horse::class, Pegasus::class];

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Animals->name;
		$this->subjects[] = Subject::Trees->name;
	}

	public function process(Metrics $message): void {
		$resources = $this->region($message)->Resources();
		switch ($message->Subject()) {
			case Subject::Animals->name :
				$amounts = [];
				foreach (self::ANIMALS as $class) {
					$count = $resources[$class]->Count();
					if ($count > 0) {
						$amounts[$class] = $count;
					}
				}
				$this->storeCommodities($message, $amounts);
				break;
			case Subject::Trees->name :
				$this->storeNumber($message, $resources[Wood::class]->Count());
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}
}
