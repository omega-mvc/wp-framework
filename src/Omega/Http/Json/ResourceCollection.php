<?php

declare(strict_types=1);

namespace Omega\Http\Json;

use Omega\Collection\Collection;
use Omega\Paginator\Paginator;

use function array_merge;

class ResourceCollection
{
    /** @var string|null The resource that this resource collects. */
    public ?string $collects = null;

    /** @var Collection The collection of resources. */
    public Collection $collection;

    /** @var array Additional metadata for the resource collection. */
    protected array $meta = [];

    /** @var bool If true, meta attributes will be merged at the top level of the array. */
    public bool $mergeMeta = false;

    /**
     * Constructs a new resource collection.
     *
     * @param Collection|Paginator $collection
     * @param string|null          $collects
     * @param array                $options
     */
    public function __construct(Collection|Paginator $collection, ?string $collects = null, array $options = [])
    {
        if ($collects)
            $this->collects = $collects;

        if ($collection instanceof Paginator) {
            $this->collection = $collection->getCollection();
            $this->meta = $collection->getAttributes();
            $this->mergeMeta = $options['mergeMeta'] ?? true;
        } else {
            $this->collection = $collection;
        }

        if (isset($options['mergeMeta']) && is_bool($options['mergeMeta'])) {
            $this->mergeMeta = $options['mergeMeta'];
        }
    }

    /**
     * Get the collection of resources.
     *
     * @return array
     */
    public function collection(): array
    {
        if ($this->collects) {
            $resourceClass = $this->collects;
            $resources = $this->collection->map(function ($item) use ($resourceClass) {
                return new $resourceClass($item)->toArray();
            });
        } else {
            $resources = $this->collection->toArray();
        }

        return $resources;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function appendMeta($data)
    {
        if (!empty($this->meta)) {
            if ($this->mergeMeta) {
                $data = array_merge($data, $this->meta);
            } else {
                $data['meta'] = $this->meta;
            }
        }

        return $data;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->appendMeta([
            'data' => $this->collection(),
        ]);
    }

    public function __get($name)
    {
        if ($this->resource->keyExists($name)) {
            return $this->resource[$name];
        }

        return null;
    }
}
