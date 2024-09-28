<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase {
	public function testAdd(): void {
		$container = new Container();
		$obj = new stdClass();
		$container->add(DummyClass::class, $obj);
		$this->assertSame(
			$obj,
			$container->construct(DummyClass::class)
		);
	}

	public function testProvideClassString(): void {
		$container = new Container();
		$container->provide(DummyInterface::class, DummyClass::class);
		$obj = $container->construct(DummyInterface::class);
		$this->assertInstanceOf(DummyClass::class, $obj);
	}

	public function testProvideClosure(): void {
		$container = new Container();
		$obj = new stdClass();
		$container->provide(DummyInterface::class, fn() => $obj);
		$resolved = $container->construct(DummyInterface::class);
		$this->assertSame($obj, $resolved);
		$this->assertNotInstanceOf(DummyClass::class, $resolved);
	}

	public function testProvideProvider(): void {
		$container = new Container();
		$container->provide(DummyInterface::class, DummyProvider::class);
		$obj = $container->construct(DummyInterface::class);
		$this->assertInstanceOf(stdClass::class, $obj);
	}

	public function testCall(): void {
		$container = new Container();
		$result = $container->call(function (DummyNested $x) {
			return $x->number();
		});
		$this->assertEquals(1, $result);
	}

	public function testConstructExisting(): void {
		$container = new Container([
			DummyInterface::class => new stdClass(),
		]);
		$result = $container->construct(DummyInterface::class);
		$this->assertInstanceOf(stdClass::class, $result);
	}

	public function testConstructProvider(): void {
		$container = new Container([], [
			DummyInterface::class => (fn() => new stdClass()),
		]);
		$result = $container->construct(DummyInterface::class);
		$this->assertInstanceOf(stdClass::class, $result);
	}

	public function testConstructNested(): void {
		$container = new Container();
		$result = $container->construct(DummyNested::class);
		$this->assertInstanceOf(DummyNested::class, $result);
	}

	public function testScopedVsNonScoped(): void {
		$container = new Container();
		$a = $container->scoped()->construct(DummyNested::class);
		$b = $container->construct(DummyNested::class);
		$c = $container->construct(DummyNested::class);
		$this->assertNotSame($a, $b);
		$this->assertSame($b, $c);
	}
}

interface DummyInterface {}

class DummyClass implements DummyInterface {}

class DummyProvider implements IProvider {
	public static function provide(): object {
		return new stdClass();
	}
}

class DummyNested {
	public function __construct(public DummyClass $child) {}

	public function number(): int {
		return 1;
	}
}
