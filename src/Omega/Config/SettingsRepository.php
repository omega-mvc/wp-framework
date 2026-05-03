<?php

declare(strict_types=1);

namespace Omega\Config;

use Omega\Application\Application;

use function array_keys;
use function array_reduce;
use function array_reverse;
use function count;
use function end;
use function explode;
use function is_array;
use function is_bool;
use function is_string;
use function sanitize_text_field;
use function update_option;

/**
 * Settings repository.
 *
 * @since   1.0.0
 */
class SettingsRepository
{
    protected array $config;

    public function __construct(protected Application $app, array $defaults = [])
    {
        $saved_config = get_option("{$this->app->getIdAsUnderscore()}_settings", []);
        $this->config = $this->mergeConfig($defaults, $saved_config);
    }

    /**
     * Merge two arrays recursively
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function mergeConfig(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $array_keys = array_keys($value);
                if (isset($array_keys[0]) && is_string($array_keys[0])) {
                    $merged[$key] = $this->mergeConfig($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Update config
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @return bool
     */
    public function update(mixed $name, mixed $value): bool
    {
        $processed_value = $this->processValue($value);

        $keys = explode('.', $name);

        $update_data = count($keys) > 1
            ? $this->addKeyValueRecursively($keys, $processed_value)
            : [$name => $processed_value];

        $settings = $this->mergeConfig($this->config, $update_data);

        $this->config = $settings;

        return $this->save();
    }

    /**
     * Process value for storage
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function processValue(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->processValue($val);
            }
        } elseif (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return $value;
    }

    /**
     * Save config to database
     *
     * @return bool
     */
    public function save(): bool
    {
        return update_option("{$this->app->getIdAsUnderscore()}_settings", $this->config);
    }

    /**
     * Add Recursive Key
     *
     * @param array $keys
     * @param mixed $value
     * @param bool $create Create the key if it doesn't exist
     *
     * @return    array
     */
    private function addKeyValueRecursively(array $keys, mixed $value, bool $create = false): array
    {
        return array_reduce(array_reverse($keys), function ($carry, $key) use ($value) {
            return [$key => $carry ?: $value];
        }, []);
    }

    /**
     * Get the config
     *
     * @param string $name
     * @param string|null $default
     * @return mixed
     * @since 1.0.0
     */
    public function get(string $name, ?string $default = null): mixed
    {
        $names = explode('.', $name);
        $config = $this->config;
        foreach ($names as $name) {
            if (isset($config[$name])) {
                $config = $config[$name];
            } else {
                return $default;
            }
        }
        return $config;
    }


    /**
     * Get the config as sanitized string
     *
     * @param string $name
     * @param string|null $default
     *
     * @return string
     * @since 1.0.0
     *
     */
    public function string(string $name, ?string $default = null): string
    {
        return sanitize_text_field($this->get($name, $default));
    }

    /**
     * Get the config as sanitized boolean
     *
     * @param string $name
     * @param bool $default
     *
     * @return bool
     * @since 1.0.0
     *
     */
    public function boolean(string $name, bool|string $default = false): bool
    {
        $value = $this->get($name, $default);

        if (is_bool($value)) {
            return $value;
        }

        return $value === 'yes' || $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Get the config as sanitized integer
     *
     * @param string $name
     * @param int|null $default
     *
     * @return int
     * @since 1.0.0
     *
     */
    public function integer(string $name, ?int $default = null): int
    {
        return (int)$this->get($name, $default);
    }

    /**
     * Get all config
     *
     * @return array
     * @since 1.0.0
     *
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Delete a config key
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete(string $name): bool
    {
        $keys = explode('.', $name);
        $config = &$this->config;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($config[$keys[$i]]) || !is_array($config[$keys[$i]])) {
                return false;
            }
            $config = &$config[$keys[$i]];
        }

        $lastKey = end($keys);
        if (isset($config[$lastKey])) {
            unset($config[$lastKey]);
            return $this->save();
        }

        return false;
    }

    /**
     * Check if config key exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        $names = explode('.', $name);
        $config = $this->config;

        foreach ($names as $key) {
            if (!isset($config[$key])) {
                return false;
            }
            $config = $config[$key];
        }

        return true;
    }
}
