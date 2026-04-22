<?php

namespace Omega\Config\Facades;

use Omega\Facade\AbstractFacade;

defined( 'ABSPATH' ) || exit;

/**
 * @method static bool has(string $key)
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array getMany(array $keys)
 * @method static string string(string $key, \Closure|string|null $default = null)
 * @method static int integer(string $key, \Closure|int|null $default = null)
 * @method static float float(string $key, \Closure|float|null $default = null)
 * @method static bool boolean(string $key, \Closure|bool|null $default = null)
 * @method static array array(string $key, \Closure|array|null $default = null)
 * @method static array all()
 *
 * @see \Omega\Config\ConfigRepository
 */
class Config extends AbstractFacade {

	public static function getFacadeAccessor() {
		return 'config';
	}
}