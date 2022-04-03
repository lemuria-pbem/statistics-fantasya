<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Subject;

use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use Lemuria\Statistics\Subject;

class Base implements Subject
{
	private string $key;

	#[Pure] public function __construct(?Category $category = null) {
		$this->key = $category ? $category->name : getClass($this);
	}

	public function __toString(): string {
		return $this->key;
	}
}
