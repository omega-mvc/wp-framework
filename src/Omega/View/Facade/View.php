<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\View\Facade;

use Omega\Facade\AbstractFacade;
use Omega\View\View as ViewClass;

/**
 * Provide static access to the view rendering service.
 *
 * This facade acts as a static proxy to the underlying
 * view service registered in the application container.
 *
 * It allows views to be rendered through a concise API
 * without manually resolving the service from the container.
 *
 * Example:
 *
 * View::make('dashboard.index', ['user' => $user]);
 *
 * @method static void make(string $view, array $data = [])
 *
 * @see ViewClass
 *
 * @category   Omega
 * @package    View
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class View extends AbstractFacade
{
    /**
     * Get the container binding key for the view service.
     *
     * This value is used by the base facade class to resolve
     * the underlying concrete instance from the application container.
     *
     * @return string The registered container service identifier.
     */
    public static function getFacadeAccessor(): string
    {
        return 'view';
    }
}
