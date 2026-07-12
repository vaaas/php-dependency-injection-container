<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase {
	public function testOfLiftsPlainValue(): void {
		$reader = Reader::of(fn() => 42);
		$this->assertSame(42, $reader->run(new Container()));
	}

	public function testOfInjectsDependenciesViaReflection(): void {
		$container = new Container();
		$dependency = new DummyClass();
		$container->add(DummyClass::class, $dependency);

		$injected = Reader::of(fn(DummyClass $x) => $x)->run($container);

		$this->assertSame($dependency, $injected);
	}

	public function testMapTransformsInjectedValue(): void {
		$container = new Container();

		$result = Reader::of(fn(DummyNested $d) => $d)
			->map(function ($d) {
				assert($d instanceof DummyNested);
				return $d->number();
			})
			->run($container);

		$this->assertSame(1, $result);
	}

	public function testFlatMapInjectsDependenciesViaReflection(): void {
		$container = new Container();
		$dependency = new DummyClass();
		$container->add(DummyClass::class, $dependency);

		$injected = Reader::of(fn() => null)
			->flatMap(fn($previous) => fn(DummyClass $x) => $x)
			->run($container);

		$this->assertSame($dependency, $injected);
	}

	public function testFlatMapThreadsPreviousResultIntoInjectedComputation(): void {
		$container = new Container();

		$result = Reader::of(fn() => 10)
			->flatMap(fn($previous) => fn(DummyNested $d) => [$previous, $d->number()])
			->run($container);

		$this->assertSame([10, 1], $result);
	}

	public function testDefersExecutionUntilRun(): void {
		$runs = 0;
		$reader = Reader::of(function () use (&$runs) {
			$runs++;
			return 1;
		})->map(fn($x) => $x);

		$this->assertSame(0, $runs);
		$reader->run(new Container());
		$this->assertSame(1, $runs);
	}
}
