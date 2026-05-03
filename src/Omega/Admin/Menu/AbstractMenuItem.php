<?php

declare(strict_types=1);

namespace Omega\Admin\Menu;

abstract class AbstractMenuItem
{
    protected string $slug;

    protected string $title;

    protected string $capability = 'manage_options';

    protected string $icon;

    protected string $path;

    protected string $view;

    protected mixed $position;

    protected array $scripts = [];

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function capability(string $capability): static
    {
        $this->capability = $capability;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function position(mixed $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getCapability(): string
    {
        return $this->capability;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getPosition(): mixed
    {
        return $this->position;
    }

    /**
     * Registered scripts to be enqueued for this menu item.
     *
     * @param array $scripts
     * @return static
     */
    public function scripts(array $scripts): static
    {
        $this->scripts = $scripts;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'slug'       => $this->slug,
            'title'      => $this->title,
            'capability' => $this->capability,
            'icon'       => $this->icon,
            'view'       => $this->view,
        ];
    }
}
