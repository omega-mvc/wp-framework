<?php

declare(strict_types=1);

namespace Omega\Database\Facade;

use Omega\Database\Database;
use Omega\Database\Eloquent\QueryBuilder;
use Omega\Database\Migrations\Migrator;
use Omega\Facade\AbstractFacade;

/**
 * @method static Migrator migrator()
 * @method static QueryBuilder table(string $table)
 *
 * @see Database
 */
class DB extends AbstractFacade
{
	public static function getFacadeAccessor(): string
    {
		return 'database';
	}
}
