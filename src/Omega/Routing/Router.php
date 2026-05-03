<?php

declare(strict_types=1);

namespace Omega\Routing;

use Exception;
use Omega\Application\ApplicationInstance;
use Omega\Http\FormRequest;
use Omega\Http\Json\JsonResource;
use Omega\Http\Json\ResourceCollection;
use ReflectionClass;
use ReflectionMethod;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function add_submenu_page;
use function array_any;
use function array_filter;
use function array_map;
use function call_user_func;
use function call_user_func_array;
use function current_user_can;
use function is_array;
use function is_callable;
use function is_string;
use function is_subclass_of;
use function is_wp_error;
use function preg_match_all;
use function register_rest_route;
use function reset;
use function rest_ensure_response;
use function str_replace;
use function trim;

class Router
{
    protected array $routes = [];

    protected array $prefixStack = [];

    protected array $guardStack = [];

    protected string $routeType = 'rest';

    protected $page;

    protected int $groupDepth = 0;

    protected array $pageOptions = [];

    /**
     * Router constructor.
     *
     * @param RouterBuilder $routerBuilder
     * @param Router|null $parentRouter
     */
    public function __construct(protected RouterBuilder $routerBuilder, protected ?Router $parentRouter = null)
    {
    }

    /**
     * @param $httpMethod
     * @param $uri
     * @param $action
     * @return array
     * @throws Exception
     */
    public function addRoute($httpMethod, $uri, $action): array
    {

        $uri = $this->parseUriParameters($uri);
        $prefix = trim($this->applyPrefix(), '/');
        $guards = $this->applyGuards();

        if ($this->routeType === 'admin') {
            $this->registerAdminRoute($action, "{$prefix}{$uri}");
        } elseif ($this->routeType === 'rest') {
            $this->registerRestRoute($prefix, $uri, $action, $guards, $httpMethod);
        }

        return $this->routes[] = [
            'method' => $httpMethod,
            'uri'    => $uri,
            'action' => $action,
            'guards' => $guards,
        ];
    }

    /**
     * @param $action
     * @param $path
     * @return void
     * @throws Exception
     */
    protected function registerAdminRoute($action, $path): void
    {
        $firstGuard = 'manage_options';
        $currentGuards = $this->applyGuards();
        if (!empty($currentGuards)) {
            $firstGuard = $currentGuards[0];
        }

        add_submenu_page(
            "omega-hidden-page",
            $this->page,
            $this->page,
            $firstGuard,
            $this->page,
            function () use ($action, $path) {
                if (!isset($_GET['path']) || trim($_GET['path'], "/") === trim($path, "/") || $path === '*') {
                    $this->processRequest($action, []);
                } else {
                    return new WP_Error('not_found', 'Page not found', ['status' => 404]);
                }
            }
        );

        // add_action( 'load-' . $hook_suffix, function () use ($hook_suffix) {
        // 	add_action( 'admin_enqueue_scripts', function ($hook) use ($hook_suffix) {
        // 		if ( $hook === $hook_suffix ) {
        // 			$here = 'hero';
        // 		}
        // 	} );
        // } );
    }

    protected function registerRestRoute($prefix, $uri, $action, $guards, $httpMethod = 'GET'): void
    {
        register_rest_route(
            $prefix,
            $uri,
            [
                'methods'  => $httpMethod,
                'callback' => function (WP_REST_Request $request) use ($action) {
                    try {
                        $response = $this->processRequest($action, $request);
                        if ($response instanceof ResourceCollection || $response instanceof JsonResource) {
                            return rest_ensure_response($response->toArray());
                        }
                        return rest_ensure_response($response);

                    } catch (Exception $e) {
                        return new WP_Error('server_error', $e->getMessage(), ['status' => 500]);
                    }
                },
                'permission_callback' => function () use ($guards) {
                    foreach ($guards as $guard) {
                        if (is_callable($guard)) {
                            if (!call_user_func($guard)) {
                                return false;
                            }
                        } elseif (is_string($guard)) {
                            if (!current_user_can($guard)) {
                                return false;
                            }
                        } elseif (is_array($guard)) {
                            if (array_any($guard, fn($g) => is_string($g) && !current_user_can($g))) {
                                return false;
                            }
                        }
                    }
                    return true;
                }
            ]
        );
    }

    /**
     * Process the request and call the appropriate controller method.
     *
     * @param array $action
     * @param mixed $request
     *
     * @return WP_REST_Response|WP_Error|array
     * @throws Exception
     */
    private function processRequest(array $action, mixed $request = []): WP_Error|WP_REST_Response|array
    {
        [$controllerClass, $method] = $action;

        $reflector = new ReflectionClass($controllerClass);

        $constructor = $reflector->getConstructor();

        if ($constructor) {
            $dependencies = $this->resolveDependencies($constructor);
            $instance = $reflector->newInstanceArgs($dependencies);
        } else {
            $instance = new $controllerClass();
        }

        $called_method = $reflector->getMethod($method);
        $method_dependencies = $this->resolveDependencies($called_method, $request);

        if (is_wp_error($method_dependencies)) {
            return $method_dependencies;
        }

        return call_user_func_array([$instance, $method], $method_dependencies);
    }

    protected function parseUriParameters($uri)
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches);

        foreach ($matches[1] as $param) {
            $uri = str_replace('{' . $param . '}', '(?P<' . $param . '>[^/]+)', $uri);
        }

        return $uri;
    }

    /**
     * Resolve dependencies for the given method.
     *
     * @param ReflectionMethod $method
     * @param WP_REST_Request|null $request
     * @return array|WP_Error
     * @throws Exception
     */
    protected function resolveDependencies(ReflectionMethod $method, ?WP_REST_Request $request = null): WP_Error|array
    {
        $resolved = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                if (is_subclass_of($className, FormRequest::class) && $request) {
                    $form_request = new $className($request);
                    $form_request->validate();

                    if ($form_request->fails()) {
                        $errors = $form_request->errors();
                        $firstError = reset($errors) ?: 'Validation error';
                        return new WP_Error('validation_error', $firstError, $errors);
                    }

                    $resolved[] = $form_request;
                } elseif ($className === '\\WP_REST_Request' || $className === 'WP_REST_Request') {
                    if ($request) {
                        $resolved[] = $request;
                    } else {
                        throw new Exception("WP_REST_Request requested but no request available for parameter: {$param->getName()}");
                    }
                } else {
                    $resolved[] = ApplicationInstance::app($className);
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
            } else {
                throw new Exception("Cannot resolve dependency: {$param->getName()}");
            }
        }

        return $resolved;
    }


    public function prefix($prefix): static
    {
        $this->prefixStack[$this->groupDepth] = [
            'prefix' => trim($prefix, '/'),
            'depth'  => $this->groupDepth
        ];
        return $this;
    }

    public function group(callable $callback): static
    {
        $this->routerBuilder->increaseGroupDepth();
        $this->groupDepth++;

        $this->parentRouter?->setPage($this->page);


        $callback($this);

        // Remove prefixes and guards from current depth
        $this->prefixStack = array_filter($this->prefixStack, function ($item) {
            return $item['depth'] < $this->groupDepth;
        });

        $this->guardStack = array_filter($this->guardStack, function ($item) {
            return $item['depth'] < $this->groupDepth;
        });

        $this->parentRouter?->setPage(null);

        $this->routerBuilder->decreaseGroupDepth();
        $this->groupDepth--;

        return $this;
    }

    public function guards($guards): static
    {
        //TODO: fix this pass others Routes
        $this->guardStack[$this->groupDepth] = [
            'guards' => $guards,
            'depth'  => $this->groupDepth
        ];

        return $this;
    }

    public function setPage($page): static
    {
        $this->page = $page;
        $this->admin();
        $this->parentRouter?->setPage($page);

        return $this;
    }

    public function rest(): static
    {
        $this->routeType = 'rest';

        return $this;
    }

    public function admin(): static
    {
        $this->routeType = 'admin';

        return $this;
    }

    protected function applyPrefix(): string
    {
        if (!empty($this->prefixStack)) {
            // Filter prefixes that are at or below the current depth
            $currentPrefixes = array_filter($this->prefixStack, function ($item) {
                return $item['depth'] < $this->groupDepth;
            });

            $prefixes = array_map(function ($item) {
                return $item['prefix'];
            }, $currentPrefixes);

            $fullPrefix = implode('/', $prefixes);
            return '/' . $fullPrefix;
        }

        return '/';
    }

    protected function applyGuards(): array
    {
        if (!empty($this->guardStack)) {
            // Filter guards that are at or below the current depth
            $currentGuards = array_filter($this->guardStack, function ($item) {
                return $item['depth'] < $this->groupDepth;
            });

            return array_map(function ($item) {
                return $item['guards'];
            }, $currentGuards);
        }

        return [];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function page($id, array $options = []): Router
    {
        $instance = new self($this->routerBuilder, $this);
        $instance->setPage($id);

        return $instance;
    }
}