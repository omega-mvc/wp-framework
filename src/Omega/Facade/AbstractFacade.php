<?php

/**
 * Part of Omega - Facade Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Facade;

use Omega\Application\ApplicationInstance;
use Omega\Facade\Exception\FacadeObjectNotSetException;
use RuntimeException;

/**
 * Base facade implementation providing static access to container-bound services.
 *
 * Facades act as static proxies to underlying objects resolved from the application
 * container, allowing expressive and convenient access to services without requiring
 * explicit dependency injection.
 *
 * This implementation maintains an internal cache of resolved instances to improve
 * performance and avoid repeated container lookups.
 *
 * Concrete facades must implement the getFacadeAccessor() method, which defines
 * the container binding key used to resolve the underlying instance.
 *
 * @category  Omega
 * @package   Facade
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
abstract class AbstractFacade implements FacadeInterface
{
    /** @var array<string, mixed> Cached resolved instances indexed by their facade accessor. */
    protected static array $resolvedInstance = [];

    /**
     * Handle dynamic static method calls and proxy them to the underlying instance.
     *
     * @param string $method The method name being called.
     * @param array<int, mixed> $args The arguments passed to the method.
     * @return mixed The result of the proxied method call.
     * @throws RuntimeException If no facade root instance has been resolved.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed The resolved instance from the container.
     */
    public static function getFacadeRoot(): mixed
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        throw new FacadeObjectNotSetException('Facade does not define a facade accessor.');
    }

    /**
     * Resolve a facade instance from the container or cache.
     *
     * @param string $name The container binding key.
     * @return mixed The resolved instance.
     */
    protected static function resolveFacadeInstance(string $name): mixed
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        return static::$resolvedInstance[$name] = ApplicationInstance::app($name);
    }

    /**
     * Remove a specific resolved instance from the cache.
     *
     * @param string $name The container binding key.
     * @return void
     */
    public static function clearResolvedInstance(string $name): void
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all resolved facade instances from the cache.
     *
     * @return void
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstance = [];
    }
}
