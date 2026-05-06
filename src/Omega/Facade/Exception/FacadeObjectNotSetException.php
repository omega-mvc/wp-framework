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

namespace Omega\Facade\Exception;

use RuntimeException;

/**
 * Exception thrown when a facade accessor is not defined.
 *
 * This occurs when a concrete facade does not implement the required
 * getFacadeAccessor() method, preventing resolution of the underlying service.
 * @category   Omega
 * @package    Facade
 * @subpackage Exception
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class FacadeObjectNotSetException extends RuntimeException
{
}
