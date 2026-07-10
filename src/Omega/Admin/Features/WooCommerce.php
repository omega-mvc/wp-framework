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

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Omega\Config\Facades\Config;

use function add_action;
use function class_exists;

/**
 * Provides integration hooks and compatibility declarations for WooCommerce.
 *
 * This class centralizes WooCommerce-specific bootstrapping logic used by
 * the framework, including compatibility registration for modern WooCommerce
 * features such as High-Performance Order Storage (HPOS) and the product
 * block editor.
 *
 * Compatibility features are enabled conditionally through application
 * configuration values.
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
class WooCommerce extends AbstractFeatures
{
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        if (Config::boolean('features.wc.compatibility')) {
            add_action('before_woocommerce_init', [$this, 'registerFeatures']);
        }
    }

    /**
     * Declare compatibility with supported WooCommerce features.
     *
     * Registers compatibility flags for WooCommerce features such as:
     * - High-Performance Order Storage (custom order tables)
     * - Product block editor support
     *
     * Compatibility is declared only when WooCommerce feature utilities
     * are available.
     *
     * @return void
     */
    public function registerFeatures(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', $this->app->getAppFile(), true);
            FeaturesUtil::declare_compatibility('product_block_editor', $this->app->getAppFile(), true);
        }
    }
}
