<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Application;

use ReflectionException;

use function is_null;

/**
 * Provides convenient static access to the application container instance.
 *
 * This helper acts as a lightweight global accessor for the framework
 * application container and its registered services.
 *
 * When called without arguments, the current Application instance
 * is returned. When a service identifier is provided, the service
 * is resolved directly from the container.
 *
 * This class is primarily intended for internal framework helpers,
 * bootstrapping logic, and situations where dependency injection
 * is not available or practical.
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class ApplicationInstance
{
    /**
     * Retrieve the application instance or resolve a service from the container.
     *
     * If no service identifier is provided, the current Application instance
     * is returned.
     *
     * If a service identifier is provided, the method resolves and returns
     * the corresponding service from the application container.
     *
     * @param string|null $service Optional service identifier or class name to resolve.
     * @return mixed Returns the Application instance or the resolved service instance.
     * @throws ReflectionException Thrown when the requested service cannot be resolved or instantiated.
     */
    public static function app(?string $service = null): mixed
    {
        $app = Application::getInstance();

        if (is_null($service)) {
            return $app;
        }

        return $app->make($service);
    }
}
