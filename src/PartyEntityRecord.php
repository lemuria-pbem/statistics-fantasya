<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Model\Fantasya\Unit;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Record;

class PartyEntityRecord extends Record
{
	public static function from(Metrics $metrics): Record {
		return new self($metrics->Subject(), $metrics->Entity());
	}

	public function Key(): string {
		$unit = $this->Entity();
		if ($unit instanceof Unit) {
			$party  = $unit->Party();
			$region = $unit->Region();
			return $party->Catalog()->value . '.' . $party->Id()->Id() . '.' .
				   $region->Catalog()->value . '.' . $region->Id()->Id() . '.' . $this->Subject();
		}
		return parent::Key();
	}
}
