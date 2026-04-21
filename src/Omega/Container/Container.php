<?php

declare(strict_types=1);

namespace Omega\Container;

class Container
{
    protected array $instances = [];
    protected array $bindings = [];

    public function instance($abstract, $instance = null)
    {
        if ($instance === null) {
            return $this->instances[$abstract] ?? null;
        }

        $this->instances[$abstract] = $instance;
    }

    public function bind($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared' => false,
        ];
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?: $abstract,
            'shared' => true,
        ];
    }

    public function make( $abstract ) {
        if ( isset( $this->instances[ $abstract ] ) ) {
            return $this->instances[ $abstract ];
        }

        if ( isset( $this->bindings[ $abstract ] ) ) {
            $binding = $this->bindings[ $abstract ];

            if ( $binding['shared'] ) {
                $instance = $this->build( $binding['concrete'] );
                $this->instances[ $abstract ] = $instance;
                return $instance;
            } else {
                return $this->build( $binding['concrete'] );
            }
        }

        return $this->build( $abstract );
    }

    protected function build( $concrete ) {
        if ( $concrete instanceof \Closure ) {
            $reflection = new \ReflectionFunction( $concrete );
            if ( $reflection->getNumberOfParameters() > 0 ) {
                return $concrete( $this );
            }
            return $concrete();
        }

        if ( is_string( $concrete ) ) {
            return new $concrete();
        }

        return $concrete;
    }
}