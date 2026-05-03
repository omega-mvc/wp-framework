<?php

declare(strict_types=1);

namespace Omega\Http\Json;

use BadMethodCallException;
use Omega\Database\Eloquent\AbstractModel;

use function call_user_func_array;
use function get_class;
use function method_exists;
use function sprintf;

class JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param AbstractModel $resource
     * @param array         $options
     */
    public function __construct(public AbstractModel $resource, public array $options = [])
    {
    }

    public static function collection($collection, array $options = []): ResourceCollection
    {
        return new ResourceCollection($collection, static::class, $options);
    }

    public function toArray(): array
    {
        return [];
    }

    public function __get($name)
    {
        if (isset($this->resource) && isset($this->resource[$name])) {
            return $this->resource[$name];
        }

        return null;
    }

    public function __call(string $method, array $arguments): mixed
    {
        if (isset($this->resource) && method_exists($this->resource, $method)) {
            return call_user_func_array([$this->resource, $method], $arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s does not exist on %s or its resource.',
            $method,
            get_class($this)
        ));
    }
}