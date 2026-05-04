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
        //  add_action( 'admin_enqueue_scripts', function ($hook) use ($hook_suffix) {
        //      if ( $hook === $hook_suffix ) {
        //          $here = 'hero';
        //      }
        //  } );
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
                        print_r($guards);
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
    private function processRequest(array $action, mixed $request = null): mixed
    {
        if ($this->routeType === 'admin') {
            $this->processAdminRequest($action, $request);
            return null;
        }

        return $this->processRestRequest($action, $request);
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
     * Resolve dependencies for the given method using reflection.
     *
     * Supports:
     * - FormRequest (REST only)
     * - WP_REST_Request injection
     * - Container-based dependency resolution
     * - Default parameter values
     *
     * @param ReflectionMethod $method Target method to resolve.
     * @param WP_REST_Request|array|null $request Current request context.
     * @return WP_Error|array Resolved arguments or WP_Error on validation failure.
     * @throws Exception When a dependency cannot be resolved.
     */
    protected function resolveDependencies(
        ReflectionMethod $method,
        WP_REST_Request|array|null $request = null
    ): WP_Error|array {
        $resolved = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();

            // 🔹 Caso: parametro tipizzato (classe)
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();

                /**
                 * 🟢 FormRequest (solo REST)
                 */
                if (is_subclass_of($className, FormRequest::class)) {

                    if (!$request instanceof WP_REST_Request) {
                        return new WP_Error(
                            'invalid_request',
                            "FormRequest requires a WP_REST_Request instance for parameter '{$param->getName()}'."
                        );
                    }

                    $formRequest = new $className($request);
                    $formRequest->validate();

                    if ($formRequest->fails()) {
                        $errors = $formRequest->errors();
                        $firstError = reset($errors) ?: 'Validation error';

                        return new WP_Error('validation_error', $firstError, $errors);
                    }

                    $resolved[] = $formRequest;
                    continue;
                }

                /**
                 * 🔵 WP_REST_Request injection
                 */
                if ($className === WP_REST_Request::class) {
                    if ($request instanceof WP_REST_Request) {
                        $resolved[] = $request;
                        continue;
                    }

                    throw new Exception(
                        "WP_REST_Request requested but no valid request available for parameter '{$param->getName()}'."
                    );
                }

                /**
                 * 🟣 Container resolution (DI)
                 */
                try {
                    $resolved[] = ApplicationInstance::app($className);
                    continue;
                } catch (Exception $e) {
                    throw new Exception(
                        "Cannot resolve dependency '{$className}' for parameter '{$param->getName()}'."
                    );
                }
            }

            /**
             * 🟡 Default value fallback
             */
            if ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
                continue;
            }

            /**
             * 🔴 Fallimento totale
             */
            throw new Exception(
                "Cannot resolve parameter '{$param->getName()}' in method {$method->getName()}."
            );
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
        if ($this->routeType === 'admin' && is_array($guards)) {
            $guards = $guards[0] ?? 'manage_options';
        }

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
        if (empty($this->guardStack)) {
            return [];
        }

        $currentGuards = array_filter($this->guardStack, function ($item) {
            return $item['depth'] < $this->groupDepth;
        });

        $guards = array_map(fn($item) => $item['guards'], $currentGuards);

        // 🔥 FIX: flatten
        return is_array($guards[0] ?? null) ? array_merge(...$guards) : $guards;
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

    /**
     * Handle REST request and return a valid API response.
     *
     * @param array $action Controller class and method.
     * @param WP_REST_Request $request Incoming REST request.
     * @return WP_REST_Response|WP_Error|array
     * @throws Exception
     */
    private function processRestRequest(array $action, WP_REST_Request $request): WP_REST_Response|WP_Error|array
    {
        [$controllerClass, $method] = $action;

        $reflector = new ReflectionClass($controllerClass);

        $instance = $reflector->getConstructor()
            ? $reflector->newInstanceArgs($this->resolveDependencies($reflector->getConstructor()))
            : new $controllerClass();

        $calledMethod = $reflector->getMethod($method);
        $dependencies = $this->resolveDependencies($calledMethod, $request);

        if (is_wp_error($dependencies)) {
            return $dependencies;
        }

        $result = call_user_func_array([$instance, $method], $dependencies);

        // 🔒 mai null nelle API
        return $result ?? [];
    }

    /**
     * Handle admin request (WordPress page rendering).
     *
     * @param array $action Controller class and method.
     * @param mixed $request Optional request data (usually array).
     * @return void
     * @throws Exception
     */
    private function processAdminRequest(array $action, mixed $request = null): void
    {
        [$controllerClass, $method] = $action;

        $reflector = new ReflectionClass($controllerClass);

        $instance = $reflector->getConstructor()
            ? $reflector->newInstanceArgs($this->resolveDependencies($reflector->getConstructor()))
            : new $controllerClass();

        $calledMethod = $reflector->getMethod($method);
        $dependencies = $this->resolveDependencies($calledMethod, $request);

        if (is_wp_error($dependencies)) {
            echo '<div class="error"><p>' . esc_html($dependencies->get_error_message()) . '</p></div>';
            return;
        }

        $result = call_user_func_array([$instance, $method], $dependencies);

        if (is_string($result)) {
            echo $result;
        }

        if (is_array($result)) {
            echo '<pre>' . esc_html(print_r($result, true)) . '</pre>';
        }
    }
}
