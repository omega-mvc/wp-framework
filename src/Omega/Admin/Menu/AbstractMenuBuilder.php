<?php

declare(strict_types=1);

namespace Omega\Admin\Menu;

use Omega\Application\Application;

use function add_action;
use function add_menu_page;
use function add_submenu_page;
use function remove_submenu_page;

abstract class AbstractMenuBuilder
{
    protected array $menus = [];

    protected $groupCallback;

    protected $currentMenu;

    abstract public function register(): void;

    public function __construct(protected Application $app)
    {
    }

    public function add($slug, $title): Menu
    {
        $menu = new Menu()->slug($slug)->title($title);
        $this->menus[] = $menu;
        $this->currentMenu = $menu;

        return $menu;
    }

    public function addSubmenu($parentSlug, $slug, $title, $position = 99): Submenu
    {
        $submenu = new Submenu($parentSlug)->slug($slug)->title($title);

        add_action('admin_menu', function () use ($submenu) {
            add_submenu_page(
                $submenu->getParentMenu(),
                $submenu->getTitle(),
                $submenu->getTitle(),
                $submenu->getCapability(),
                $submenu->getSlug(),
                $submenu->getCallback()
            );
        }, $position);

        return $submenu;
    }

    public function create(): void
    {
        foreach ($this->menus as $menu) {
            add_menu_page(
                $menu->getTitle(),
                $menu->getTitle(),
                $menu->getCapability(),
                $menu->getSlug(),
                function () use ($menu) {
                    // Silence, this menu is rendered in routing, we don't need to render it here.
                },
                $menu->getIcon(),
                $menu->getPosition()
            );

            foreach ($menu->getSubmenus() as $submenu) {
                add_submenu_page(
                    $menu->getSlug(),
                    $submenu->getTitle(),
                    $submenu->getTitle(),
                    $submenu->getCapability(),
                    $submenu->getSlug(),
                    function () use ($submenu) {
                        // Silence, this submenu is rendered in routing, we don't need to render it here.
                    }
                );
            }

            remove_submenu_page($menu->getSlug(), $menu->getSlug());
        }
    }
}
