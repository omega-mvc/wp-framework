<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Casts;

use Omega\Database\Eloquent\AbstractModel;
use Omega\Database\Eloquent\CastsAttributesInterface;

class BooleanCast implements CastsAttributesInterface
{
    public function get(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return (bool)$value;
    }

    public function set(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return (int)$value;
    }
}
