<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Compilation;

use Lemuria\Statistics\Compilation;

final class NotAvailable implements Compilation
{
	private static ?NotAvailable $instance = null;

	public static function getInstance(): NotAvailable {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
