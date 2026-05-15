<?php

declare(strict_types=1);

namespace Omega\Application;

use Omega\Application\Exception\FileNotFoundException;
use Omega\Application\Exception\MissingParameterException;
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
    /** @var Application[] Omega Application Container. */
    private static array $apps = [];

    /**
     * Initializes the Omega configuration.
     *
     * @param string $id     Required parameter.
     * @param array  $config Optional configuration.
     * @return Application Return Application instance.
     * @throws FileNotFoundException If the file for given id not exists.
     * @throws MissingParameterException if the id or base_path is missing
     */
    public static function create(string $id, array $config = []): Application
    {
        if (empty($id)) {
            throw new MissingParameterException('The "id" parameter is required.');
        }

        if (!isset($config['base_path'])) {
            throw new MissingParameterException('The "base_path" parameter is required.');
        }

        if (!isset($config['plugin_root'])) {
            $config['plugin_root'] = $config['base_path'];
        }

        if (!file_exists($config['plugin_root'] . "/$id.php")) {
            throw new FileNotFoundException(
                sprintf(
                "The plugin file for %s does not exist in the specified plugin root, in ApplicationFactory::create configure plugin_root.",
                    $id
                )
            );
        }

        self::$apps[$id] = new Application(config: $config, id: $id);

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
                        $pluginFile = self::$apps[$id]->getPluginRoot();
                        if (str_contains($frame['file'], $pluginFile)) {
                            $appId = $id;
                            break 2;
                        }
                    }
                }
            }

            if (!$appId) {
                foreach (self::$apps as $id => $app) {
                    $composerJson = $app->getPluginRoot() . '/composer.json';
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
