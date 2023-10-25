<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Officer;

use function Lemuria\getClass;
use Lemuria\Engine\Fantasya\Event\Game\Spawn;
use Lemuria\Engine\Fantasya\Statistics\Subject;
use Lemuria\Model\Fantasya\Commodity\Griffin;
use Lemuria\Model\Fantasya\Factory\BuilderTrait;
use Lemuria\Model\Fantasya\Party;
use Lemuria\Model\Fantasya\Party\Type;
use Lemuria\Model\Fantasya\Region;
use Lemuria\Statistics\Fantasya\Exception\UnsupportedSubjectException;
use Lemuria\Statistics\Metrics;

class Ethnologist extends AbstractOfficer
{
	use BuilderTrait;

	protected final const UNITS = 0;

	protected final const PEOPLE = 1;

	/**
	 * @var array<int, array<string, array<int>>
	 */
	protected array $races = [];

	/**
	 * @noinspection DuplicatedCode
	 */
	public function __construct() {
		parent::__construct();
		$this->subjects[] = Subject::RaceUnits->name;
		$this->subjects[] = Subject::RacePeople->name;
	}

	public function process(Metrics $message): void {
		$number = match ($message->Subject()) {
			Subject::RaceUnits->name  => self::UNITS,
			Subject::RacePeople->name => self::PEOPLE,
			default                   => throw new UnsupportedSubjectException($this, $message),
		};
		$party = $this->party($message);
		$races = $this->getRaceNumbers($party, $number);
		$this->storeSingletons($message, $races);
	}

	protected function countRaces(Party $party): void {
		$id = $party->Id()->Id();
		if (!isset($this->races[$id])) {
			$races = [];
			foreach ($party->People() as $unit) {
				$class = getClass($unit->Race());
				if (!isset($races[$class])) {
					$races[$class] = [self::UNITS => 0, self::PEOPLE => 0];
				}
				$races[$class][self::UNITS]++;
				$races[$class][self::PEOPLE] += $unit->Size();
			}
			$this->races[$id] = $races;
			$this->countGriffins($party);
		}
	}

	protected function countGriffins(Party $party): void {
		$type = $party->Type();
		if ($type === Type::Monster && $party->Id()->Id() === Spawn::getPartyId($type)->Id()) {
			$units   = 0;
			$people  = 0;
			$griffin = self::createRace(Griffin::class);
			foreach (Region::all() as $region) {
				$resources = $region->Resources();
				if (isset($resources[$griffin])) {
					$units++;
					$people += $resources[$griffin]->Count();
				}
			}

			if ($units > 0) {
				$id                       = $party->Id()->Id();
				$class                    = getClass($griffin);
				$this->races[$id][$class] = [self::UNITS => $units, self::PEOPLE => $people];
			}
		}
	}

	protected function getRaceNumbers(Party $party, int $number): array {
		$races = [];
		$this->countRaces($party);
		foreach ($this->races[$party->Id()->Id()] as $race => $numbers) {
			$races[$race] = $numbers[$number];
		}
		return $races;
	}
}
