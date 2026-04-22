<?php

declare(strict_types=1);

namespace Omega\Application;

use RuntimeException;

class ApplicationInstance
{
    public static function app($service = null)
    {
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