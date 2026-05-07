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

namespace Omega\Admin\Features;

/**
 * Contract for admin feature integrations.
 *
 * Feature integrations are responsible for bootstrapping optional
 * WordPress or third-party plugin functionality within the admin layer.
 *
 * @category   Omega
 * @package    Admin
 * @subpackage Features
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
interface FeaturesInterface
{
    /**
     * Initialize the feature integration.
     *
     * Registers hooks, compatibility declarations, or other
     * bootstrapping logic required by the feature.
     *
     * @return void
     */
    public function init(): void;
}
