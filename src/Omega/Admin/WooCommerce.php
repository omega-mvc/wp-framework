<?php

declare(strict_types=1);

namespace Omega\Admin;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Omega\Application\Application;
use Omega\Config\Facades\Config;

use function add_action;
use function class_exists;

class WooCommerce
{
    /**
     * Constructor to initialize WooCommerce compatibility.
     *
     * @param Application $app The application instance.
     */
    public function __construct(protected Application $app)
    {
    }

    public function init(): void
    {
        if (Config::boolean('app.woocommerce_declare_compatibility')) {
            add_action('before_woocommerce_init', [$this, 'woocommerceDeclareCompatibility']);
        }
    }

    public function woocommerceDeclareCompatibility(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', $this->app->getPluginFile(), true);
            FeaturesUtil::declare_compatibility('product_block_editor', $this->app->getPluginFile(), true);
        }
    }
}
