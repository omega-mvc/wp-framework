<?php

namespace Omega\Collection;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Omega\Database\Eloquent\AbstractModel;
use ReturnTypeWillChange;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_callable;
use function is_null;
use function is_numeric;
use function is_object;
use function usort;

/**
 * Collection class
 *
 * This class is responsible for handling collections
 *
 * @since 1.0.0
 */
class Collection implements ArrayAccess, IteratorAggregate
{
    public mixed $items = [];

    /**
     * Collection constructor
     *
     * @since 1.0.0
     */
    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Push one or more items onto the end of the collection.
     *
     * @param mixed ...$values
     * @return $this
     */
    public function push($values): static
    {
        if (is_array($values)) {
            foreach ($values as $value) {
                $this->items[] = $value;
            }
        } else {
            $this->items[] = $values;
        }

        return $this;
    }

    public function slice($offset, $length = null): Collection
    {
        $slicedArray = array_slice($this->items, $offset, $length);

        return new self($slicedArray);
    }

    public function toArray(): array
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            if ($item instanceof AbstractModel) {
                $items[$key] = $item->toArray();
            } else {
                $items[$key] = $item;
            }
        }

        return $items;
    }

    public function getAll()
    {
        return $this->items;
    }

    public function pluck($value, $key = null): Collection
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = $this->data_get($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $this->data_get($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        return new self($results);
    }

    /**
     * Map the collection using a callback.
     *
     * @param callable $callback
     *
     * @return array
     */
    public function map(callable $callback): array
    {
        $keys  = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return array_combine($keys, $items);
    }

    public function filter($callback = null): Collection
    {
        $items = array_filter($this->items, $callback);

        return new self($items);
    }


    /**
     * Merge the collection with the given items.
     *
     * @param array|Collection $collection
     *
     * @return Collection
     */
    public function merge(array|Collection $collection): static
    {
        if ($collection instanceof self) {
            $this->items = array_merge($this->items, $collection->getAll());
        } elseif (is_array($collection)) {
            $this->items = array_merge($this->items, $collection);
        }

        return $this;
    }

    public function unique($key): Collection
    {
        $uniqueItems = [];
        $seenKeys    = [];

        foreach ($this->items as $item) {
            $itemKey = $this->data_get($item, $key);

            if (!in_array($itemKey, $seenKeys, true)) {
                $seenKeys[] = $itemKey;
                $uniqueItems[] = $item;
            }
        }

        return new self($uniqueItems);
    }

    /**
     * Get the sum of a given key.
     *
     * @param callable|string $key
     * @return float|int|string
     */
    public function sum(callable|string $key): float|int|string
    {
        $total = 0;

        foreach ($this->items as $item) {
            if (is_callable($key)) {
                $value = $key($item);
            } else {
                $value = $this->data_get($item, $key);
            }

            if (is_numeric($value)) {
                $total += $value;
            }
        }

        return $total;
    }

    public function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $default;
                }

                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!isset($target[$segment])) {
                    return $default;
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return $default;
                }

                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }


    public function firstWhere($key, $value)
    {
        foreach ($this->items as $item) {
            if ($item->$key === $value) {
                return $item;
            }
        }

        return null;
    }

    public function where($key, $value): array
    {
        $items = [];

        foreach ($this->items as $item) {
            if (isset($item->$key) && $item->$key === $value) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function first()
    {
        return $this->items[0] ?? null;
    }

    public function contains($key, $value): bool
    {
        foreach ($this->items as $item) {
            if (isset($item->$key) && $item->$key === $value) {
                return true;
            }
        }

        return false;
    }

    public function sortByDesc($key): Collection
    {
        $items = $this->items;

        usort($items, function ($a, $b) use ($key) {
            return $b->$key <=> $a->$key;
        });

        return new self($items);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
