<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Eloquent\Casts;

/**
 * Attribute
 *
 * Represents a dynamic accessor/mutator definition for a model attribute.
 *
 * This class allows defining custom logic for retrieving (get) and
 * modifying (set) attribute values at runtime.
 *
 * It is used to intercept model property access and transformation,
 * enabling computed properties, normalization logic, or controlled
 * mutation of underlying raw data.
 *
 * Additionally, it supports optional caching strategies for performance
 * optimization, including standard value caching and object-level caching.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Eloquent\Casts
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
final class Attribute
{
    /** @var callable|null Attribute accessor used when reading the value */
    public mixed $get;

    /** @var callable|null Attribute mutator used when writing the value */
    public mixed $set;

    /** @var bool Enables caching for the computed attribute value */
    public bool $withCaching = false;

    /** @var bool Enables object-level caching for the attribute result */
    public bool $withObjectCaching = true;

    /**
     * Create a new Attribute instance with optional accessor and mutator.
     *
     * The accessor is invoked when the attribute is read, while the mutator
     * is invoked when the attribute is written.
     *
     * @param callable|null $get Optional getter callback.
     * @param callable|null $set Optional setter callback.
     */
    public function __construct(?callable $get = null, ?callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * Create a new Attribute instance.
     *
     * Convenience factory method equivalent to the constructor, allowing
     * fluent and expressive attribute definitions.
     *
     * @param callable|null $get Optional getter callback.
     * @param callable|null $set Optional setter callback.
     * @return static A new Attribute instance.
     */
    public static function make(?callable $get = null, ?callable $set = null): static
    {
        return new static($get, $set);
    }

    /**
     * Create an attribute with only a getter accessor.
     *
     * Useful for computed or read-only attributes derived from model data.
     *
     * @param callable $get Getter callback executed on attribute access.
     * @return static A new Attribute instance with only accessor defined.
     */
    public static function get(callable $get): static
    {
        return new static($get);
    }

    /**
     * Create an attribute with only a setter mutator.
     *
     * Useful for write-only transformations or normalization logic
     * applied before persisting data.
     *
     * @param callable $set Setter callback executed on attribute assignment.
     * @return static A new Attribute instance with only mutator defined.
     */
    public static function set(callable $set): static
    {
        return new static(null, $set);
    }

    /**
     * Disable object-level caching for this attribute.
     *
     * When disabled, the computed value will not be cached as an object
     * reference, ensuring fresh evaluation on each access.
     *
     * @return static The current Attribute instance for chaining.
     */
    public function withoutObjectCaching(): static
    {
        $this->withObjectCaching = false;

        return $this;
    }

    /**
     * Enable caching for the computed attribute value.
     *
     * When enabled, the result of the accessor will be cached after the
     * first evaluation to improve performance on repeated access.
     *
     * @return static The current Attribute instance for chaining.
     */
    public function shouldCache(): static
    {
        $this->withCaching = true;

        return $this;
    }
}
