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

use Omega\Application\Application;

/**
 * Base class for admin feature integrations.
 *
 * Provides access to the current application instance and serves
 * as the foundation for WordPress or third-party admin integrations.
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
abstract class AbstractFeatures implements FeaturesInterface
{
    /**
     * Create a new WooCommerce integration instance.
     *
     * @param Application $app The current application instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    abstract public function init(): void;
}
