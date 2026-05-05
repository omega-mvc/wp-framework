<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Config;

use function explode;
use function sanitize_text_field;

/**
 * ConfigRepository
 *
 * Provides read-only access to a hierarchical configuration array using dot notation.
 * The configuration is injected at construction time and remains immutable during runtime.
 *
 * This repository is designed for static application configuration such as feature flags,
 * service definitions, and environment-specific constants.
 *
 * @category  Omega
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class ConfigRepository
{
    /**
     * ConfigRepository constructor.
     *
     * Initializes the repository with a static configuration array.
     *
     * @param array<int|string, mixed> $config Initial configuration data used as the source of truth.
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * Retrieve a configuration value using dot notation.
     *
     * Traverses a nested configuration array using a dot-separated key path.
     * If the key does not exist, the provided default value is returned.
     *
     * @param string $name Dot-notated configuration key (e.g. "database.connections.mysql").
     * @param mixed $default Default value returned if the key is not found.
     * @return mixed The resolved configuration value or the default value if not found.
     */
    public function get(string $name, mixed $default = null): mixed
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
     * Retrieve a configuration value and cast it to a sanitized string.
     *
     * The value is passed through WordPress sanitize_text_field() before being returned.
     *
     * @param string $name Dot-notated configuration key.
     * @param string|null $default Default value used if the key is not found.
     * @return string The sanitized string value.
     */
    public function string(string $name, ?string $default = null): string
    {
        return sanitize_text_field($this->get($name, $default));
    }

    /**
     * Retrieve a configuration value and cast it to boolean.
     *
     * Strict comparison is used against true to determine the boolean value.
     *
     * @param string $name Dot-notated configuration key.
     * @param bool|null $default Default value used if the key is not found.
     * @return bool The resolved boolean value.
     */
    public function boolean(string $name, ?bool $default = null): bool
    {
        return $this->get($name, $default) === true;
    }

    /**
     * Retrieve a configuration value and cast it to integer.
     *
     * The value is explicitly cast to int after retrieval.
     *
     * @param string $name Dot-notated configuration key.
     * @param int|null $default Default value used if the key is not found.
     * @return int The resolved integer value.
     */
    public function integer(string $name, ?int $default = null): int
    {
        return (int) $this->get($name, $default);
    }

    /**
     * Retrieve the entire configuration array.
     *
     * @return array<int|string, mixed> The full configuration dataset.
     */
    public function all(): array
    {
        return $this->config;
    }
}
