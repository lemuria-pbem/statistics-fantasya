<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Metrics;

use Lemuria\Identifiable;
use Lemuria\Statistics\Metrics;
use Lemuria\Statistics\Subject;

class Entity implements Metrics
{
	public function __construct(private Identifiable $identifiable, private Subject $subject)
	{
	}

	public function Subject(): Subject {
		return $this->subject;
	}

	public function Identifiable(): Identifiable {
		return $this->identifiable;
	}
}
