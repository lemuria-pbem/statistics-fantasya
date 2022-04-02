<?php
declare(strict_types = 1);
namespace Lemuria\Statistics\Fantasya\Compilation;

final class NotAvailable extends Data
{
	private static ?NotAvailable $instance = null;

	public static function getInstance(): NotAvailable {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
