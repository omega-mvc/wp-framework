<?php

declare(strict_types=1);

namespace Omega\Config;

use function explode;
use function sanitize_text_field;

/**
 * Config repository.
 * 
 * @since   1.0.0
 */
class ConfigRepository
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Get the config
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     * @since 1.0.0
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
     * @param bool|null $default
     *
     * @return bool
     * @since 1.0.0
     *
     */
    public function boolean(string $name, ?bool $default = null): bool
    {
        return $this->get($name, $default) === true;
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
}
