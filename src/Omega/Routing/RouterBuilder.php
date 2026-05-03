<?php

declare(strict_types=1);

namespace Omega\Routing;

use Exception;
use Omega\Application\Application;
use ReflectionException;

use function end;

class RouterBuilder
{
    protected array $instances = [];

    protected int $groupDepth = 0;

    public function __construct(protected Application $app)
    {
    }

    /**
     * Get instance
     *
     * @return Router
     */
    protected function getInstance(): Router
    {
        if ($this->groupDepth > 0 && !empty($this->instances)) {
            return end($this->instances);
        } else {
            $instance = new Router($this);
            $this->instances[] = $instance;
            return $instance;
        }
    }

    public function prefix($prefix): Router
    {
        $instance = $this->getInstance();
        $instance->prefix($prefix);

        return $instance;
    }


    /**
     * @throws ReflectionException
     */
    public function page($id, $options = []): Router
    {
        $instance = $this->getInstance();

        $this->app->make('admin.manager')->addHiddenNoticesPage($id);

        $instance->page($id, $options);

        return $instance;
    }

    /**
     * @throws Exception
     */
    public function get($uri, $action = null): array
    {
        $instance = $this->getInstance();

        return $instance->addRoute('GET', $uri, $action);
    }

    /**
     * @throws Exception
     */
    public function post($uri, $action = null): array
    {
        $instance = $this->getInstance();

        return $instance->addRoute('POST', $uri, $action);
    }

    /**
     * @throws Exception
     */
    public function put($uri, $action = null): array
    {
        $instance = $this->getInstance();

        return $instance->addRoute('PUT', $uri, $action);
    }

    /**
     * @throws Exception
     */
    public function patch($uri, $action = null): array
    {
        $instance = $this->getInstance();

        return $instance->addRoute('PATCH', $uri, $action);
    }

    /**
     * @throws Exception
     */
    public function delete($uri, $action = null): array
    {
        $instance = $this->getInstance();

        return $instance->addRoute('DELETE', $uri, $action);
    }

    public function increaseGroupDepth(): void
    {
        $this->groupDepth++;
    }

    public function decreaseGroupDepth(): void
    {
        $this->groupDepth--;
    }
}
