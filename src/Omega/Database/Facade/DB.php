<?php

declare(strict_types=1);

namespace Omega\Database\Facade;

use Omega\Database\Database;
use Omega\Database\Eloquent\QueryBuilder;
use Omega\Database\Migrations\Migrator;
use Omega\Facade\AbstractFacade;

/**
 *
 * @category   Omega
 * @package    Database
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 *
 * @method static Migrator migrator()
 * @method static QueryBuilder table(string $table)
 *
 * @see Database
 */
class DB extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'database';
    }
}
