<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Casts;

use Omega\Database\Eloquent\AbstractModel;
use Omega\Database\Eloquent\CastsAttributesInterface;

use function json_decode;
use function wp_json_encode;

class ArrayCast implements CastsAttributesInterface
{
    public function get(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_decode($value, true);
    }

    public function set(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return wp_json_encode($value);
    }
}
