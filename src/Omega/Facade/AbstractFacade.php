<?php

declare(strict_types=1);

namespace Omega\Facade;

use Omega\Application\ApplicationInstance;
use Omega\Facade\Exception\FacadeObjectNotSetException;
use RuntimeException;

abstract class AbstractFacade implements FacadeInterface
{
    protected static array $resolvedInstance = [];

    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }

    public static function getFacadeRoot(): mixed
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    public static function getFacadeAccessor(): string
    {
        throw new FacadeObjectNotSetException('Facade does not define a facade accessor.');
    }

    protected static function resolveFacadeInstance(string $name): mixed
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        return static::$resolvedInstance[$name] = ApplicationInstance::app($name);
    }

    public static function clearResolvedInstance($name): void
    {
        unset(static::$resolvedInstance[$name]);
    }

    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstance = [];
    }
}
