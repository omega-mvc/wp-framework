<?php

namespace Omega\Admin;

use function add_action;
use function array_any;
use function remove_all_actions;

class AdminManager
{
    private array $hiddenPages = [];

    public function __construct()
    {
    }

    public function init(): void
    {
        add_action('in_admin_header', [$this, 'hideNotices'], 99);
    }

    public function maybeHideNotices(): bool
    {
        if (!isset($_GET['page'])) {
            return false;
        }

        $current_page = $_GET['page'];

        return array_any($this->hiddenPages, fn($page) => $current_page === $page);
    }

    public function addHiddenNoticesPage($id): void
    {
        $this->hiddenPages[] = $id;
    }

    public function hideNotices(): void
    {
        if (!$this->maybeHideNotices()) {
            return;
        }

        remove_all_actions('user_admin_notices');
        remove_all_actions('admin_notices');
    }

    public function silenceRender(): void
    {
    }
}
