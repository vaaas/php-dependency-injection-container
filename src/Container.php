<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Container {
	/**
	 * @param array<class-string, object> $instances
	 * @param array<class-string, callable(): object> $providers
	 */
	public function __construct(
		private array $instances = [],
		private array $providers = [],
	) {}

	/**
	 * @param class-string $class
	 */
	public function add(string $class, object $instance): Container {
		$this->instances[$class] = $instance;
		return $this;
	}

	/**
	 * @param class-string $class
	 * @param class-string | IProvider | (callable(): object) $provider
	 */
	public function provide(string $class, string | IProvider | callable $provider): Container {
		if (is_callable($provider))
			$this->providers[$class] = $provider;
		else if (method_exists($provider, 'provide'))
			$this->providers[$class] = $provider::provide(...);
		else
			/** @phpstan-ignore-next-line */
			$this->providers[$class] = fn() => $this->construct($provider);
		return $this;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @return T
	 * @throws UnsupportedTypeHint
	 */
	public function construct(string $class): object {
		if (array_key_exists($class, $this->instances))
			/** @var T */
			return $this->instances[$class];
		else if (array_key_exists($class, $this->providers))
			return $this->constructFromProvider($class);
		else
			return $this->constructFromReflection($class);
	}

	public function call(Closure $f): mixed {
		return $f(...$this->resolveParameters(new ReflectionFunction($f)));
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @return T
	 */
	private function constructFromProvider(string $class): object {
		$provider = $this->providers[$class];
		/** @var T */
		$instance = $provider();
		$this->add($class, $instance);
		return $instance;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @return T
	 */
	private function constructFromReflection(string $class): object {
		$constructor = (new ReflectionClass($class))->getConstructor();
		$instance =
			is_null(($constructor))
			? new $class()
			: new $class(...$this->resolveParameters($constructor));
		$this->add($class, $instance);
		return $instance;
	}

	/** @return object[] */
	private function resolveParameters(ReflectionFunction | ReflectionMethod $reflection): array {
		return array_map($this->resolveParameter(...), $reflection->getParameters());
	}

	/** @throws UnsupportedTypeHint */
	private function resolveParameter(ReflectionParameter $x): object {
		$type = $x->getType();
		if (!($type instanceof ReflectionNamedType) || $type->isBuiltin())
			throw new UnsupportedTypeHint();
		/** @var class-string */
		$class = $type->getName();
		return $this->construct($class);
	}
}
