<?php

declare(strict_types=1);

namespace Omega\Database\Utils;

use ReflectionClass;
use ReflectionException;

use function array_key_exists;

class Reflection
{
    /**
     * @throws ReflectionException
     */
    public static function getDefaultValue($className, $propertyName, $default = null)
    {
        $reflectionClass = new ReflectionClass($className);
        $defaultProperties = $reflectionClass->getDefaultProperties();
        return array_key_exists($propertyName, $defaultProperties)
            ? $defaultProperties[$propertyName]
            : $default;
    }
}
