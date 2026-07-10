<?php

declare(strict_types=1);

namespace Omega\Application;

use Omega\Str\Str;
use ReflectionException;

use function array_key_first;
use function array_keys;
use function count;
use function debug_backtrace;
use function file_exists;
use function json_decode;
use function sprintf;
use function str_contains;

class ApplicationFactory
{
    /** @var array<string, ApplicationPlugin|ApplicationTheme> Omega Application Container. */
    private static array $apps = [];

    /**
     * Create and initialize a new Plugin application instance.
     *
     * This method is responsible for constructing an ApplicationPlugin instance,
     * registering it in the internal applications registry, and executing its
     * full bootstrap process.
     *
     * The plugin application represents a WordPress plugin environment and
     * expects the provided base path to contain a valid plugin structure,
     * including the main plugin entry file.
     *
     * After creation, the application is immediately bootstrapped, meaning
     * all service providers, bindings, and core framework components are
     * registered and made available.
     *
     * @param string $id Unique identifier of the plugin application.
     *                   This is typically the plugin directory name and must
     *                   match the plugin entry file name.
     *
     * @param string $basePath Absolute path to the root directory of the plugin.
     *
     * @return ApplicationPlugin Fully initialized and bootstrapped plugin application instance.
     */
    public static function createPlugin(string $id, string $basePath): ApplicationPlugin
    {
        self::$apps[$id] = new ApplicationPlugin(id: $id, basePath: $basePath);

        self::$apps[$id]->bootstrap();

        return self::$apps[$id];
    }

    /**
     * Create and initialize a new Theme application instance.
     *
     * This method constructs an ApplicationTheme instance, registers it in the
     * internal applications registry, and triggers its bootstrap process.
     *
     * The theme application represents a WordPress theme environment and expects
     * the provided base path to contain a valid theme structure, including the
     * required style.css file used as the theme entry point.
     *
     * After instantiation, the application is immediately bootstrapped, which
     * registers all service providers, container bindings, and framework core
     * services required for runtime execution.
     *
     * @param string $id Unique identifier of the theme application.
     *                   This is typically the theme directory name and must
     *                   correspond to the WordPress theme folder structure.
     *
     * @param string $basePath Absolute path to the root directory of the theme.
     *
     * @return ApplicationTheme Fully initialized and bootstrapped theme application instance.
     */
    public static function createTheme(string $id, string$basePath): ApplicationTheme
    {
        self::$apps[$id] = new ApplicationTheme(id: $id, basePath: $basePath);

        self::$apps[$id]->bootstrap();

        return self::$apps[$id];
    }

    /**
     * Get an app instance or a service from a specific app.
     *
     * @param string|null $service Service name.
     * @param string|null $appId Application ID.
     * @return mixed
     * @throws ReflectionException
     */
    public static function app(?string $service = null, ?string $appId = null): mixed
    {

        if (!$appId && count(self::$apps) > 1 && \class_exists('Omega\Console\ConsoleApplication')) {
            $trace = debug_backtrace();
            foreach ($trace as $frame) {
                if (isset($frame['file'])) {
                    foreach (array_keys(self::$apps) as $id) {
                        $pluginFile = self::$apps[$id]->getAppRoot();
                        if (str_contains($frame['file'], $pluginFile)) {
                            $appId = $id;
                            break 2;
                        }
                    }
                }
            }

            if (!$appId) {
                foreach (self::$apps as $id => $app) {
                    $composerJson = $app->getAppRoot() . '/composer.json';
                    if (file_exists($composerJson)) {
                        $data = json_decode(\file_get_contents($composerJson), true);
                        $psr4 = array_keys($data['autoload']['psr-4'] ?? []);
                        if (isset($psr4[0]) && $service && Str::startsWith($service, $psr4[0])) {
                            $appId = $id;
                            break;
                        }
                    }
                }
            }
        }

        if (!$appId) {
            $appId = array_key_first(self::$apps);
        }

        if (!$service) {
            return self::$apps[$appId];
        }

        return self::$apps[$appId]->make($service);
    }
}
