<?php

/**
 * Part of Omega - Routing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Routing;

/**
 * Base controller for handling HTTP requests within the WordPress runtime.
 *
 * This abstract controller acts as the foundation for all application controllers,
 * providing a structured entry point for handling incoming requests routed through
 * the framework layer.
 *
 * It is designed to operate within the WordPress execution environment while
 * abstracting request handling into a controller-based architecture similar to
 * modern MVC patterns.
 *
 * Concrete controllers extending this class are responsible for implementing
 * request handling logic for both REST and admin contexts where applicable.
 *
 * @category  Omega
 * @package   Routing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
abstract class AbstractController
{
}
