<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Config\Facades;

use Closure;
use Omega\Config\ConfigRepository;
use Omega\Facade\AbstractFacade;

/**
 * Facade providing a static interface to the ConfigRepository service.
 *
 * This facade exposes the application's configuration layer through a global, static API,
 * allowing configuration values to be accessed in a simple and expressive way without
 * requiring direct dependency injection.
 *
 * It acts as a read-focused abstraction over the underlying configuration repository,
 * which aggregates configuration files into a unified in-memory structure.
 *
 * The facade is designed for application-wide configuration access, typically used during
 * bootstrapping, service resolution, and runtime feature toggling within the Omega framework.
 *
 * @method static bool has(string $key)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array getMany(array $keys)
 * @method static string string(string $key, Closure|string|null $default = null)
 * @method static int integer(string $key, Closure|int|null $default = null)
 * @method static float float(string $key, Closure|float|null $default = null)
 * @method static bool boolean(string $key, Closure|bool|null $default = null)
 * @method static array array(string $key, Closure|array|null $default = null)
 * @method static array all()
 *
 * @see ConfigRepository
 */
class Config extends AbstractFacade
{
    public static function getFacadeAccessor(): string
    {
        return 'config';
    }
}
