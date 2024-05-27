<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

interface IProvider {
	public static function provide(): object;
}
