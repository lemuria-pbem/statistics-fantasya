<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya;

use Lemuria\Storage\Provider;

interface Archivist
{
	public function createProvider(int $round): Provider;
}
