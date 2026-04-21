<?php

namespace Omega\Facades;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\Database\Migrations\Migrator migrator()
 * @method static \Omega\Database\Eloquent\QueryBuilder table( string $table )
 *
 * @see \Omega\Database\Database
 */
class DB extends Facade {

	protected static function getFacadeAccessor() {
		return 'database';
	}
}