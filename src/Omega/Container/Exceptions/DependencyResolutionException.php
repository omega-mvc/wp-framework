<?php

/**
 * Part of Omega - Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Container\Exceptions;

use ReflectionParameter;

use function sprintf;

/**
 * Thrown when the container cannot resolve a constructor or method dependency.
 *
 * @category   Omega
 * @package    Container
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class DependencyResolutionException extends AbstractContainerException
{
    /**
     * Create a new exception instance.
     *
     * @param ReflectionParameter $dependency The unresolved dependency.
     * @return void
     */
    public function __construct(ReflectionParameter $dependency)
    {
        $class  = $dependency->getDeclaringClass();
        $method = $dependency->getDeclaringFunction()->getName();

        if ($class !== null) {
            $method = $class->getName() . '::' . $method;
        }

        parent::__construct(
            sprintf(
                'Unable to resolve dependency "$%s" while invoking %s.',
                $dependency->getName(),
                $method
            )
        );
    }
}
