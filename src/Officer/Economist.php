<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Lemuria;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Luxuries;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Quantity;
use Lemuria\Model\Fantasya\Statistics\Data\Market;
use Lemuria\Model\Fantasya\Unit;
use Lemuria\Statistics\Data\Number;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Record;

class Economist extends AbstractOfficer
{
	use BuilderTrait;

	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::Income->name;
		$this->subjects[] = Subject::Market->name;
		$this->subjects[] = Subject::MaterialPool->name;
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
				$luxuries = $this->region($message)->Luxuries();
				if ($luxuries) {
					$statistics = Lemuria::Statistics();
					$record     = $statistics->request(Record::from($message));
					$market     = $this->createMarketData($record, $luxuries);
					$statistics->store($record->setData($market));
				}
				break;
			case Subject::MaterialPool->name :
				$party = $this->party($message);
				$pool  = $this->getMaterialPool($party);
				$this->storeCommodities($message, $pool);
				break;
			default :
				throw new UnsupportedSubjectException($this, $message);
		}
	}

	protected function createMarketData(Record $record, Luxuries $luxuries): Market {
		$offer     = $luxuries->Offer();
		$luxury    = $offer->Commodity();
		$price     = $offer->Price();

		$newMarket = new Market();
		$market    = $record->Data();
		if ($market instanceof Market) {
			$newMarket[$luxury] = new Number($price, $price - $market[$luxury]->value);
			foreach ($luxuries as $offer) {
				$luxury             = $offer->Commodity();
				$price              = $offer->Price();
				$newMarket[$luxury] = new Number($price, $price - $market[$luxury]->value);
			}
		} else {
			$newMarket[$luxury] = new Number($price, $price);
			foreach ($luxuries as $offer) {
				$luxury             = $offer->Commodity();
				$price              = $offer->Price();
				$newMarket[$luxury] = new Number($price, $price);
			}
		}
		return $newMarket;
	}

	#[Pure] protected function getMaterialPool(Party $party): array {
		$pool = [];
		foreach ($party->People() as $unit /* @var Unit $unit */) {
			foreach ($unit->Inventory() as $item /* @var Quantity $item */) {
				$class = getClass($item->Commodity());
				if (isset($pool[$class])) {
					$pool[$class] += $item->Count();
				} else {
					$pool[$class] = $item->Count();
				}
			}
		}
		return $pool;
	}
}
