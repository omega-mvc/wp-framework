<?php

declare(strict_types=1);

namespace Omega\Admin;

use Omega\Admin\Menu\AbstractMenuBuilder;
use Omega\Container\ServiceProvider;
use ReflectionException;

use function add_action;
use function class_exists;
use function load_plugin_textdomain;

class AdminServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(): void
    {
		$app = $this->app;
		$app->singleton('admin.manager', AdminManager::class);
		$app->singleton(WooCommerce::class, function () use ($app) {
			return new WooCommerce($app);
		});
	}

    /**
     * @throws ReflectionException
     */
    public function boot(): void
    {
		add_action('admin_menu', [$this, 'adminMenu']);
		add_action('admin_init', [$this, 'adminSetup']);
		add_action('init', [$this, 'init']);

		$this->app->make(WooCommerce::class)->init();
		$this->app->make('admin.manager')->init();
	}

    /**
     * @throws ReflectionException
     */
    public function init(): void
	{
		$enable_translation = $this->app->make('config')->boolean('app.enable_translation');

		if ($enable_translation === true) {
			load_plugin_textdomain(
				$this->app->getId(),
				false,
				$this->app->pluginRoot() . '/languages'
			);
		}
	}

    /**
     * @throws ReflectionException
     */
    public function adminSetup(): void
	{
		$setupClass = $this->app->make('config')->string('app.admin_setup_class');

		if (!class_exists($setupClass)) {
			return;
		}

		$setup = new $setupClass();
	}

    /**
     * @throws ReflectionException
     */
    public function adminMenu(): void
    {
		$menuClass = $this->app->make('config')->string('app.admin_menu_class');

		if (!class_exists($menuClass)) {
			return;
		}

		/** @var AbstractMenuBuilder $adminMenu **/
		$adminMenu = new $menuClass($this->app);
		$adminMenu->register();
		$adminMenu->create();
	}
}
