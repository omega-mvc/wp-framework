<?php

declare(strict_types=1);

namespace Omega\Facade;

interface FacadeInterface
{
    public static function getFacadeAccessor(): string;
}