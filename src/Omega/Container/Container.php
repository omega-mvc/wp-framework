<?php

/**
 * Part of Omega - Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Container;

use Closure;
use ReflectionException;
use ReflectionFunction;

use function is_string;

/**
 * Simple dependency injection container.
 *
 * This container provides basic service registration and resolution capabilities,
 * supporting both transient and shared (singleton) bindings.
 *
 * It allows binding abstractions to concrete implementations, storing pre-built
 * instances, and resolving dependencies at runtime using a minimal reflection-based
 * factory mechanism.
 *
 * Designed for lightweight frameworks, it does not perform automatic constructor
 * injection but supports Closure-based factories for manual dependency resolution.
 *
 * @category  Omega
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class Container
{
    /** @var array<string, mixed> Registered shared instances keyed by their abstract identifier. */
    protected array $instances = [];

    /** @var array<string, array{concrete: mixed, shared: bool}> Service bindings with their concrete definitions. */
    protected array $bindings = [];

    /**
     * Register or retrieve a shared instance in the container.
     *
     * @param string $abstract The abstract identifier of the service.
     * @param mixed|null $instance The instance to store, or null to retrieve an existing one.
     * @return mixed|null The stored instance when retrieving, or null if not found.
     */
    public function instance(string $abstract, mixed $instance = null): mixed
    {
        if ($instance === null) {
            return $this->instances[$abstract] ?? null;
        }

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Bind a transient service to the container.
     *
     * @param string $abstract The abstract identifier of the service.
     * @param mixed|null $concrete The concrete implementation or factory, defaults to the abstract.
     * @return void
     */
    public function bind(string $abstract, mixed $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared'   => false,
        ];
    }

    /**
     * Bind a shared (singleton) service to the container.
     *
     * @param string $abstract The abstract identifier of the service.
     * @param mixed|null $concrete The concrete implementation or factory, defaults to the abstract.
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared'   => true,
        ];
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $abstract The abstract identifier of the service.
     * @return mixed The resolved instance.
     * @throws ReflectionException If the concrete cannot be reflected or instantiated.
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];

            if ($binding['shared']) {
                $instance = $this->build($binding['concrete']);
                $this->instances[$abstract] = $instance;
                return $instance;
            } else {
                return $this->build($binding['concrete']);
            }
        }

        return $this->build($abstract);
    }

    /**
     * Build a concrete instance from a binding definition.
     *
     * @param mixed $concrete The concrete class name, Closure factory, or pre-built instance.
     * @return mixed The instantiated object or resolved value.
     * @throws ReflectionException If reflection fails when resolving a Closure.
     */
    protected function build(mixed $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            $reflection = new ReflectionFunction($concrete);
            if ($reflection->getNumberOfParameters() > 0) {
                return $concrete($this);
            }
            return $concrete();
        }

        if (is_string($concrete)) {
            return new $concrete();
        }

        return $concrete;
    }
}
