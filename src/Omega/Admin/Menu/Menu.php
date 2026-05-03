<?php

declare(strict_types=1);

namespace Omega\Admin\Menu;

class Menu extends AbstractMenuItem
{
    protected array $submenus = [];

    public function addSubmenu($title, $slug = null): Submenu
    {
        $submenu = new Submenu($this)->slug($slug ?? $this->getSlug())->title($title);
        $this->submenus[] = $submenu;

        return $submenu;
    }

    public function getSubmenus(): array
    {
        return $this->submenus;
    }

    public function hasSubmenus(): bool
    {
        return !empty($this->submenus);
    }
}