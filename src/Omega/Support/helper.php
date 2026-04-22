<?php

declare(strict_types=1);

use Omega\Application\Application;

if (!function_exists('app')) {
    function app($service = null) {
        $app = Application::getInstance();

        if (is_null($app)) {
            throw new RuntimeException("Application not initialized.");
        }

        if (is_null($service)) {
            return $app;
        }

        return $app->make($service);
    }
}