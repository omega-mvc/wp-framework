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

namespace Omega\Admin\Menu;

/**
 * Represents a top-level admin menu item in the WordPress dashboard.
 *
 * This class extends the base AbstractMenuItem and adds support for
 * hierarchical submenu management.
 *
 * A Menu acts as a container for one or more Submenu instances and
 * is used by the menu builder system to construct the full WordPress
 * admin navigation structure.
 *
 * Submenus are attached fluently and stored internally until the
 * menu structure is rendered via the AbstractMenuBuilder.
 *
 * @category   Omega
 * @package    Admin
 * @subpackage Menu
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class Menu extends AbstractMenuItem
{
    /**
     * Collection of submenu items associated with this menu.
     *
     * Each submenu represents a child entry under this top-level menu.
     *
     * @var array<int, Submenu>
     */
    protected array $submenus = [];

    /**
     * Add a submenu item to this menu.
     *
     * Creates a new Submenu instance, associates it with this menu,
     * and assigns slug and title values. If no slug is provided,
     * the parent menu slug is used as fallback.
     *
     * @param string $title Display title of the submenu item.
     * @param string|null $slug Optional unique identifier for the submenu.
     *
     * @return Submenu The newly created submenu instance.
     */
    public function addSubmenu(string $title, ?string $slug = null): Submenu
    {
        $submenu = new Submenu($this)
            ->slug($slug ?? $this->getSlug())
            ->title($title);

        $this->submenus[] = $submenu;

        return $submenu;
    }

    /**
     * Retrieve all registered submenu items.
     *
     * @return array<int, Submenu> List of submenu instances.
     */
    public function getSubmenus(): array
    {
        return $this->submenus;
    }

    /**
     * Determine whether this menu has any associated submenus.
     *
     * @return bool True if at least one submenu exists, false otherwise.
     */
    public function hasSubmenus(): bool
    {
        return !empty($this->submenus);
    }
}
