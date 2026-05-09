<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a ColumnDefinition is constructed with invalid or missing configuration.
 *
 * This exception is used to enforce the integrity of schema column definitions at runtime,
 * ensuring that required attributes such as "type" and "name" are always provided.
 *
 * It acts as a domain-specific wrapper around invalid argument scenarios occurring
 * during schema building.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class ColumnDefinitionException extends InvalidArgumentException
{
}
