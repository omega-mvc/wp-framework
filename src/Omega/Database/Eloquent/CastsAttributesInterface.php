<?php

namespace Omega\Database\Eloquent;

interface CastsAttributesInterface
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param AbstractModel        $model
     * @param string               $key
     * @param mixed                $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function get(AbstractModel $model, string $key, mixed $value, array $attributes): mixed;

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param AbstractModel $model
     * @param string $key
     * @param mixed|null $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function set(AbstractModel $model, string $key, mixed $value, array $attributes): mixed;
}