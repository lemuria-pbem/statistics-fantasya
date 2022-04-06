<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class Economist extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Income->name;
		$this->subjects[] = Subject::Market->name;
		$this->subjects[] = Subject::Workers->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::Income->name :
			case Subject::Workers->name :
				$data   = $message->Data();
				$amount = $data instanceof Number ? $data->value : 0;
				$this->storeNumber($message, $amount);
				break;
			case Subject::Market->name :
				//TODO
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}
}
