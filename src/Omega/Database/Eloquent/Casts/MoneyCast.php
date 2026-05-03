<?php

declare(strict_types=1);

namespace Omega\Database\Eloquent\Casts;

use Omega\Database\Eloquent\AbstractModel;
use Omega\Database\Eloquent\CastsAttributesInterface;

use function round;

class MoneyCast implements CastsAttributesInterface
{
    public function get(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value / 100;
    }

    public function set(AbstractModel $model, string $key, mixed $value, array $attributes): mixed
    {
        return (int)round($value * 100);
    }
}
