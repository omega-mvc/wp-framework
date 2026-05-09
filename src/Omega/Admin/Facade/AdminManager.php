<?php

/**
 * Part of Omega - Admin Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Admin\Facade;

use Omega\Facade\AbstractFacade;

/**
 * Facade for the Admin subsystem of the Omega framework.
 *
 * Provides a static interface to the underlying AdminManager service,
 * allowing convenient access to administrative features such as hidden
 * notices pages registration and other admin-related utilities.
 *
 * This facade acts as a proxy to the service container binding
 * identified by "admin.manager", enabling a clean and expressive API
 * without requiring direct dependency injection.
 *
 * @category   Omega
 * @package    Admin
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    1.0.0
 *
 * @method static void addHiddenNoticesPage(string $id)
 *
 * @see \Omega\Admin\AdminManager
 */
class AdminManager extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'admin.manager';
    }
}
