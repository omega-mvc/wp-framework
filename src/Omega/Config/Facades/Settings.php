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
use Omega\Config\SettingsRepository;
use Omega\Facade\AbstractFacade;

/**
 * Facade providing a static interface to the SettingsRepository service.
 *
 * This facade acts as an application-level proxy to the underlying settings storage layer,
 * offering a simplified and globally accessible API for retrieving and mutating persistent
 * configuration values.
 *
 * It abstracts the underlying implementation details of the SettingsRepository, allowing
 * consumers to interact with application settings in a consistent and framework-agnostic way.
 *
 * Designed primarily for WordPress integration, it enables centralized access to plugin or
 * application settings without requiring direct dependency injection.
 *
 * @method static bool has(string $key)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array getMany(array $keys)
 * @method static string string(string $key, Closure|string|null $default = null)
 * @method static int integer(string $key, Closure|int|null $default = null)
 * @method static float float(string $key, Closure|float|null $default = null)
 * @method static bool boolean(string $key, Closure|bool|null $default = null)
 * @method static array array(string $key, Closure|array|null $default = null)
 * @method static void update(string $key, mixed $value)
 * @method static array all()
 *
 * @see SettingsRepository
 */
class Settings extends AbstractFacade
{
    public static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
