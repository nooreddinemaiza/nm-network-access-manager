<?php

namespace Core\Routing;


use Closure;

use Core\Routing\Route;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\RouteException;

class Router
{
    protected array $routes = [];
    protected array $groups = [];
    protected array $middlewares = [];
    protected array $patterns = [];
    protected ?string $currentGroupPrefix = null;
    protected array $currentGroupMiddleware = [];
    protected array $namedRoutes = [];

    protected bool $globalRedirectEnabled = false;
    protected string $globalRedirectUrl = '';
    protected int $globalRedirectStatus = 302;
    protected array $globalRedirectExceptions = [];
    protected array $globalRedirectExceptGroups = [];
    protected array $globalRedirectExceptPatterns = [];
    protected array $globalRedirectExceptNames = [];

    // Route patterns for common parameters
    protected array $defaultPatterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        'any' => '.*'
    ];

    public function __construct()
    {
        $this->patterns = $this->defaultPatterns;
    }

    /**
     * Factory method
     */
    public static function create(): self
    {
        return new self();
    }

    // === Route Definition Methods ===

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function options(string $uri, mixed $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    public function head(string $uri, mixed $action): Route
    {
        return $this->addRoute(['HEAD'], $uri, $action);
    }

    public function any(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $uri, $action);
    }

    public function match(array $methods, string $uri, mixed $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }

    /**
     * Add a route to the collection
     */
    protected function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $uri = $this->prefix($uri);

        $route = new Route($methods, $uri, $action);

        // Apply group middleware
        if (!empty($this->currentGroupMiddleware)) {
            $route->middleware($this->currentGroupMiddleware);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add prefix to URI
     */
    protected function prefix(string $uri): string
    {
        $uri = '/' . trim($uri, '/');

        if ($this->currentGroupPrefix) {
            $prefix = '/' . trim($this->currentGroupPrefix, '/');
            $uri = $prefix . $uri;
        }

        return $uri === '//' ? '/' : $uri;
    }

    // === Route Groups ===

    public function group(array $attributes, Closure $callback): self
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupMiddleware = $this->currentGroupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->currentGroupPrefix = $this->currentGroupPrefix
                ? $this->currentGroupPrefix . '/' . trim($attributes['prefix'], '/')
                : trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $middleware = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);
        }

        $callback($this);

        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupMiddleware = $previousGroupMiddleware;

        return $this;
    }

    // === Middleware ===

    public function middleware(string $name, mixed $handler): self
    {
        $this->middlewares[$name] = $handler;
        return $this;
    }

    // === Route Patterns ===

    public function pattern(string $key, string $pattern): self
    {
        $this->patterns[$key] = $pattern;
        return $this;
    }

    public function patterns(array $patterns): self
    {
        $this->patterns = array_merge($this->patterns, $patterns);
        return $this;
    }

    // === Named Routes ===

    public function name(string $name, Route $route): self
    {
        $this->namedRoutes[$name] = $route;
        return $this;
    }

    public function route(string $name, array $parameters = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not found.");
        }

        return $this->namedRoutes[$name]->compile($parameters);
    }

    // === Route Resolution ===
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->path();

        // Vérifier la redirection globale
        if ($this->globalRedirectEnabled && !$this->isExceptionRoute($uri)) {
            return $this->redirect($this->globalRedirectUrl, $this->globalRedirectStatus);
        }

        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                $parameters = $route->extractParameters($uri, $this->patterns);

                // Set route parameters as request attributes
                foreach ($parameters as $key => $value) {
                    $request->setAttribute($key, $value);
                }

                return $this->runRoute($route, $request, $parameters);
            }
        }

        // No route found
        return RouteException::handleNotFound($request);
    }

    // =====================================================
    /**
     * Active la redirection globale avec des options améliorées
     */
    public function redirectAll(string $url, int $status = 302, array $options = []): self
    {
        $this->globalRedirectEnabled = true;
        $this->globalRedirectUrl = $url;
        $this->globalRedirectStatus = $status;

        // Support de l'ancien format pour la rétrocompatibilité
        if (isset($options[0]) && is_string($options[0])) {
            $this->globalRedirectExceptions = $options;
        } else {
            // Nouveau format avec options
            $this->globalRedirectExceptions = $options['routes'] ?? [];
            $this->globalRedirectExceptGroups = $options['groups'] ?? [];
            $this->globalRedirectExceptPatterns = $options['patterns'] ?? [];
            $this->globalRedirectExceptNames = $options['names'] ?? [];
        }

        return $this;
    }
    public function maintenance(): self
    {
        $this->globalRedirectEnabled = true;
        $this->globalRedirectUrl = '/maintenance';
        $this->globalRedirectStatus = 503;
        $this->globalRedirectExceptGroups = ['login', 'dashboard', 'assets', 'password', 'email'];
        return $this;
    }
    public function configuration(): self
    {
        $this->globalRedirectEnabled = true;
        $this->globalRedirectUrl = '/configuration/application?token=';
        $this->globalRedirectStatus = 503;
        $this->globalRedirectExceptGroups = ['login', 'dashboard', 'assets', 'configuration', 'password', 'email'];
        return $this;
    }

    /**
     * Désactive la redirection globale
     */
    public function disableGlobalRedirect(): self
    {
        $this->globalRedirectEnabled = false;

        return $this;
    }
    /**
     * Vérifie si l'URL actuelle est dans les exceptions (version améliorée)
     */
    protected function isExceptionRoute(string $uri): bool
    {
        // Normaliser l'URI
        $uri = $this->normalizeUri($uri);

        // 1. Éviter les boucles infinies
        if ($this->isRedirectTarget($uri)) {
            return true;
        }

        // 2. Vérifier les exceptions de routes spécifiques (ancien système)
        if ($this->matchesRouteExceptions($uri)) {
            return true;
        }

        // 3. Vérifier les exceptions par groupes
        if ($this->matchesGroupExceptions($uri)) {
            return true;
        }

        // 4. Vérifier les exceptions par patterns regex
        if ($this->matchesPatternExceptions($uri)) {
            return true;
        }

        // 5. Vérifier les exceptions par noms de routes
        if ($this->matchesNameExceptions($uri)) {
            return true;
        }

        return false;
    }
    /**
     * Normalise une URI
     */
    protected function normalizeUri(string $uri): string
    {
        $uri = '/' . trim($uri, '/');
        return $uri === '//' ? '/' : $uri;
    }

    /**
     * Vérifie si l'URI est la cible de redirection (évite les boucles)
     */
    protected function isRedirectTarget(string $uri): bool
    {
        $redirectPath = parse_url($this->globalRedirectUrl, PHP_URL_PATH) ?: '/';
        $redirectPath = $this->normalizeUri($redirectPath);
        return $uri === $redirectPath;
    }

    /**
     * Vérifie les exceptions de routes spécifiques (ancien système)
     */
    protected function matchesRouteExceptions(string $uri): bool
    {
        foreach ($this->globalRedirectExceptions as $exception) {
            $exception = $this->normalizeUri($exception);

            // Support pour les wildcards
            $pattern = $this->convertToRegexPattern($exception);

            if (preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie les exceptions par groupes
     */
    protected function matchesGroupExceptions(string $uri): bool
    {
        foreach ($this->globalRedirectExceptGroups as $groupPrefix) {
            $groupPrefix = $this->normalizeUri($groupPrefix);

            // Vérifier si l'URI commence par le préfixe du groupe
            if ($groupPrefix === '/' || strpos($uri, $groupPrefix) === 0) {
                // Vérifier que c'est bien le début d'un segment
                if (
                    $groupPrefix === '/' || $uri === $groupPrefix ||
                    (strlen($uri) > strlen($groupPrefix) && $uri[strlen($groupPrefix)] === '/')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifie les exceptions par patterns regex
     */
    protected function matchesPatternExceptions(string $uri): bool
    {
        foreach ($this->globalRedirectExceptPatterns as $pattern) {
            // Si le pattern ne commence pas par un délimiteur, on l'ajoute
            if (!preg_match('/^[\/~#]/', $pattern)) {
                $pattern = '#^' . $pattern . '$#i';
            }

            if (preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie les exceptions par noms de routes
     */
    protected function matchesNameExceptions(string $uri): bool
    {
        foreach ($this->globalRedirectExceptNames as $routeName) {
            if (isset($this->namedRoutes[$routeName])) {
                $route = $this->namedRoutes[$routeName];
                $routeUri = $this->normalizeUri($route->getUri());

                // Pour les routes avec paramètres, on utilise une correspondance de pattern
                if (strpos($routeUri, '{') !== false) {
                    $pattern = $this->convertRouteToRegex($routeUri);
                    if (preg_match($pattern, $uri)) {
                        return true;
                    }
                } else {
                    // Correspondance exacte pour les routes sans paramètres
                    if ($uri === $routeUri) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Convertit un pattern avec wildcards en regex
     */
    protected function convertToRegexPattern(string $pattern): string
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace(['\*\*', '\*'], ['.*', '[^/]*'], $pattern);
        return '#^' . $pattern . '$#';
    }

    /**
     * Convertit une route avec paramètres en pattern regex
     */
    protected function convertRouteToRegex(string $routeUri): string
    {
        $pattern = preg_quote($routeUri, '#');

        // Remplacer les paramètres {param} par des patterns appropriés
        $pattern = preg_replace_callback('/\\\{([^}]+)\\\}/', function ($matches) {
            $paramName = $matches[1];

            // Utiliser les patterns définis ou un pattern par défaut
            if (isset($this->patterns[$paramName])) {
                return '(' . $this->patterns[$paramName] . ')';
            }

            return '([^/]+)'; // Pattern par défaut pour tout sauf les slashes
        }, $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * Réinitialise toutes les exceptions de redirection
     */
    public function clearRedirectExceptions(): self
    {
        $this->globalRedirectExceptions = [];
        $this->globalRedirectExceptGroups = [];
        $this->globalRedirectExceptPatterns = [];
        $this->globalRedirectExceptNames = [];
        return $this;
    }
    /**
     * Ajouter des exceptions par groupe de routes
     */
    public function exceptGroups(array $groups): self
    {
        $this->globalRedirectExceptGroups = array_merge($this->globalRedirectExceptGroups, $groups);
        return $this;
    }

    /**
     * Ajouter des exceptions par patterns regex
     */
    public function exceptPatterns(array $patterns): self
    {
        $this->globalRedirectExceptPatterns = array_merge($this->globalRedirectExceptPatterns, $patterns);
        return $this;
    }

    /**
     * Ajouter des exceptions par noms de routes
     */
    public function exceptNames(array $names): self
    {
        $this->globalRedirectExceptNames = array_merge($this->globalRedirectExceptNames, $names);
        return $this;
    }
    // =====================================================

    /**
     * Execute route with middleware stack
     */
    protected function runRoute(Route $route, Request $request, array $parameters): Response
    {
        $middlewares = $this->resolveMiddlewares($route->getMiddleware());

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($carry, $middleware) {
                return function (Request $request) use ($carry, $middleware) {
                    return $middleware($request, $carry);
                };
            },
            function (Request $request) use ($route, $parameters) {
                return $this->callAction($route->getAction(), $request, $parameters);
            }
        );

        $response = $pipeline($request);

        return $this->prepareResponse($response, $request);
    }

    /**
     * Resolve middleware handlers
     */
    protected function resolveMiddlewares(array $middlewares): array
    {
        $resolved = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && isset($this->middlewares[$middleware])) {
                $resolved[] = $this->middlewares[$middleware];
            } elseif (is_callable($middleware)) {
                $resolved[] = $middleware;
            }
        }

        return $resolved;
    }

    /**
     * Call the route action
     */
    protected function callAction(mixed $action, Request $request, array $parameters): mixed
    {
        if ($action instanceof Closure) {
            return $action($request, ...$parameters);
        }

        if (is_string($action)) {
            // Handle Controller@method format
            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action, 2);

                if (class_exists($controller)) {
                    $instance = new $controller();
                    if (method_exists($instance, $method)) {
                        return $instance->$method($request, ...$parameters);
                    }
                }
            }

            // Handle simple function name
            if (function_exists($action)) {
                return $action($request, ...$parameters);
            }
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;

            if (is_string($controller) && class_exists($controller)) {
                $controller = new $controller();
            }

            if (is_object($controller) && method_exists($controller, $method)) {
                return $controller->$method($request, ...$parameters);
            }
        }

        return RouteException::handleInternalServerError($request);
    }

    /**
     * Prepare response
     */
    protected function prepareResponse(mixed $response, Request $request): Response
    {
        if ($response instanceof Response) {
            return $response->prepare();
        }

        if (is_array($response) || is_object($response)) {
            if ($request->expectsJson()) {
                return Response::json($response)->prepare();
            }
        }

        return Response::create($response)->prepare();
    }

    // === Utility Methods ===

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    // === Built-in Middleware ===

    public function registerBuiltinMiddleware(): self
    {
        // CORS Middleware
        $this->middleware('cors', function (Request $request, Closure $next) {
            $response = $next($request);

            if ($response instanceof Response) {
                $response->withCors();
            }

            return $response;
        });

        // Security Headers Middleware
        $this->middleware('security', function (Request $request, Closure $next) {
            $response = $next($request);

            if ($response instanceof Response) {
                $response->withSecurityHeaders();
            }

            return $response;
        });

        // JSON Response Middleware
        $this->middleware('json', function (Request $request, Closure $next) {
            $response = $next($request);

            if (!($response instanceof Response) && ($request->expectsJson() || $request->isAjax())) {
                return Response::json($response);
            }

            return $response;
        });

        return $this;
    }

    public function redirect($location, int $status = 302, $headers = []): Response
    {
        return Response::redirect($location, $status, $headers);
    }
}
