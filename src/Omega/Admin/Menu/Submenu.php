<?php

declare(strict_types=1);

namespace Omega\Admin\Menu;

class Submenu extends AbstractMenuItem
{
    public mixed $callback;

    public function __construct(protected mixed $parentMenu = null)
    {
    }

    public function getParentMenu(): mixed
    {
        return $this->parentMenu;
    }

    public function setParentMenu(mixed $parentMenu): static
    {
        $this->parentMenu = $parentMenu;

        return $this;
    }

    public function setCallback(callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function getSlug(): string
    {
        if ($this->getPath()) {
            return $this->slug . '&path=' . $this->getPath();
        }

        return $this->slug;
    }
}
