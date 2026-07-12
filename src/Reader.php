<?php
declare(strict_types=1);

namespace Vas\DependencyInjectionContainer;

use Closure;

/** @template X */
class Reader
{
    /** @param Closure(Container): X $f */
    private function __construct(private readonly Closure $f) {}

    /**
     * @param Closure $f the closure's parameters are injected via the container
     * @return self<mixed>
     */
    public static function of(Closure $f): self
    {
        return new self(function (Container $container) use ($f) {
            return $container->call($f);
        });
    }

    /** @return X */
    public function run(Container $container)
    {
        return ($this->f)($container);
    }

    /**
     * @template Y
     * @param Closure(X): Y $f
     * @return self<Y>
     */
    public function map(Closure $f): self
    {
        return new self(function (Container $container) use ($f) {
            return $f($this->run($container));
        });
    }

    /**
     * @param Closure(X): Closure $f the returned closure's parameters are injected via the container
     * @return self<mixed>
     */
    public function flatMap(Closure $f): self
    {
        return new self(function (Container $container) use ($f) {
            return $container->call($f($this->run($container)));
        });
    }
}
