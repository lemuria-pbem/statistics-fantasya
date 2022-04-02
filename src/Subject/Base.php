<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Subject;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Statistics\Subject;

class Base implements Subject
{
	private string $class;

	#[Pure] public function __construct(?Category $category = null) {
		$this->class = getClass($this);
		if ($category) {
			$this->class .= '.' . $category->name;
		}
	}

	public function __toString(): string {
		return $this->class;
	}
}
