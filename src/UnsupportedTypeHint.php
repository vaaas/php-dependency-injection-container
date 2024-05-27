<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

use Exception;

class UnsupportedTypeHint extends Exception {
	public function __construct() {
		parent::__construct('Missing or unsupported type hint. Union, intersection, and built-in types are not supported.');
	}
}
