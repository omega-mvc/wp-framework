<?php

declare(strict_types=1);

namespace Omega\Routing\Facade;

use Omega\Facade\AbstractFacade;
use Omega\Routing\Router;

/**
 * @method static Router get(string $uri, array|string|callable|null $action = null)
 * @method static Router post(string $uri, array|string|callable|null $action = null)
 * @method static Router put(string $uri, array|string|callable|null $action = null)
 * @method static Router delete(string $uri, array|string|callable|null $action = null)
 * @method static Router patch(string $uri, array|string|callable|null $action = null)
 * @method static Router prefix(string $prefix)
 * @method static Router guards(array $guards)
 * @method static Router page(string $id, $options = [])
 *
 * @see Router
 */
class Route extends AbstractFacade
{
    public static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
