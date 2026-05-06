<?php

declare(strict_types=1);

namespace Omega\Admin;

use function array_map;
use function esc_url_raw;
use function is_array;
use function rest_sanitize_boolean;
use function sanitize_email;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_unslash;

class Sanitizer
{
    /**
     * Sanitize a boolean value.
     *
     * Accepts true, false, 1, 0, 'true', 'false', '1', '0', 'yes', 'no' etc.
     *
     * @param mixed $value
     * @return bool
     */
    public static function boolean(mixed $value): bool
    {
        return rest_sanitize_boolean($value);
    }

    /**
     * Sanitize a text string (strips tags, extra whitespace).
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function string(mixed $value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        return sanitize_text_field(wp_unslash((string)$value));
    }

    /**
     * Sanitize a textarea value (preserves newlines).
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function textarea(mixed $value, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        return sanitize_textarea_field(wp_unslash((string)$value));
    }

    /**
     * Sanitize an integer value.
     *
     * @param mixed $value
     * @param int $default
     * @return int
     */
    public static function integer(mixed $value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (int)$value;
    }

    /**
     * Sanitize a float value.
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    public static function float(mixed $value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (float)$value;
    }

    /**
     * Sanitize an email address.
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function email(mixed $value, string $default = ''): string
    {
        $sanitized = sanitize_email((string)$value);

        return $sanitized !== '' ? $sanitized : $default;
    }

    /**
     * Sanitize a URL for use in database or redirects (does not escape for HTML output).
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public static function url(mixed $value, string $default = ''): string
    {
        $sanitized = esc_url_raw((string)$value);

        return $sanitized !== '' ? $sanitized : $default;
    }

    /**
     * Sanitize each element of an array as a text string.
     *
     * @param mixed $value
     * @return array
     */
    public static function arrayOfStrings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map(fn($item) => sanitize_text_field(wp_unslash((string)$item)), $value);
    }

    /**
     * Sanitize a value from a request param, applying the given type.
     *
     * @param mixed $value
     * @param string $type 'string' | 'boolean' | 'integer' | 'float' | 'email' | 'url' | 'textarea'
     * @param mixed $default
     * @return mixed
     */
    public static function cast(mixed $value, string $type, mixed $default = null): mixed
    {
        return match ($type) {
            'boolean'  => static::boolean($value),
            'integer'  => static::integer($value, (int)$default),
            'float'    => static::float($value, (float)$default),
            'email'    => static::email($value, (string)$default),
            'url'      => static::url($value, (string)$default),
            'textarea' => static::textarea($value, (string)$default),
            default    => static::string($value, (string)$default),
        };
    }
}
