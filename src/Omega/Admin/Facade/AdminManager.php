<?php

declare(strict_types=1);

namespace Omega\Admin\Facade;

use Omega\Admin\Page;
use Omega\Facade\AbstractFacade;

/**
 * @category   Omega
 * @package    Admin
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 *
 * @method static Page addMenu(array $args)
 * @method static Page addSubMenu(array $args)
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
