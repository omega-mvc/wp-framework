<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Casts;

final class Attribute
{
    /** @var callable The attribute accessor. */
    public mixed $get;

    /** @var callable The attribute mutator. */
    public mixed $set;

    /** @var bool Indicates if caching is enabled for this attribute. */
    public bool $withCaching = false;

    /** @var bool Indicates if caching of objects is enabled for this attribute. */
    public bool $withObjectCaching = true;

    /**
     * Create a new attribute accessor / mutator.
     *
     * @param callable|null $get
     * @param callable|null $set
     */
    public function __construct(?callable $get = null, ?callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * Create a new attribute accessor / mutator.
     *
     * @param callable|null $get
     * @param callable|null $set
     * @return static
     */
    public static function make(?callable $get = null, ?callable $set = null): static
    {
        return new static($get, $set);
    }

    /**
     * Create a new attribute accessor.
     *
     * @param callable $get
     * @return static
     */
    public static function get(callable $get): static
    {
        return new static($get);
    }

    /**
     * Create a new attribute mutator.
     *
     * @param callable $set
     * @return static
     */
    public static function set(callable $set): static
    {
        return new static(null, $set);
    }

    /**
     * Disable object caching for the attribute.
     *
     * @return static
     */
    public function withoutObjectCaching(): static
    {
        $this->withObjectCaching = false;

        return $this;
    }

    /**
     * Enable caching for the attribute.
     *
     * @return static
     */
    public function shouldCache(): static
    {
        $this->withCaching = true;

        return $this;
    }
}
