<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use Lemuria\Engine\Fantasya\Context;
use Lemuria\Engine\Fantasya\Factory\RealmTrait;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class Chancellor extends AbstractOfficer
{
	use RealmTrait;

	private ?Context $context = null;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::TransportUsed->name;
	}

	public function process(Metrics $message): void {
		switch ($message->Subject()) {
			case Subject::TransportUsed->name :
				$data   = $message->Data();
				$amount = $data instanceof Number ? $data->value : 0;
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
		$this->storeNumber($message, $amount);
	}
}
