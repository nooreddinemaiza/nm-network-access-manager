<?php

namespace Core\Routing;


use Closure;
use Core\Routing\Http\Request;

/**
 * Route class for individual route management
 * Handles route definition, matching, parameter extraction, and execution
 */
class Route
{
    private static array $publicRoutes = [];
    private static array $privateRoutes = [];
    protected array $methods;
    protected string $uri;
    protected mixed $action;
    protected array $middleware = [];
    protected ?string $name = null;
    protected array $where = [];
    protected ?string $compiledRegex = null;
    protected array $parameterNames = [];
    protected array $defaults = [];
    protected ?string $domain = null;
    protected array $schemes = ['http', 'https'];

    // Default patterns for common route parameters
    protected array $defaultPatterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        'any' => '.*',
        'alpha' => '[a-zA-Z]+',
        'num' => '[0-9]+',
        'alphanum' => '[a-zA-Z0-9]+'
    ];

    public function __construct(array $methods, string $uri, mixed $action)
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $this->normalizeUri($uri);
        $this->action = $action;
    }

    /**
     * Factory method for creating Route instances
     */
    public static function create(array $methods, string $uri, mixed $action): self
    {
        return new self($methods, $uri, $action);
    }

    /**
     * Normalize URI by ensuring it starts with / and removing trailing /
     */
    protected function normalizeUri(string $uri): string
    {
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : $uri;
    }

    // === Route Configuration Methods ===

    /**
     * Add middleware to the route
     */
    public function middleware(mixed $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Set route name for URL generation
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set parameter constraints using regex patterns
     */
    public function where(mixed $name, ?string $pattern = null): self
    {
        if (is_array($name)) {
            $this->where = array_merge($this->where, $name);
        } else {
            $this->where[$name] = $pattern;
        }

        // Clear compiled regex when constraints change
        $this->compiledRegex = null;
        return $this;
    }

    /**
     * Set default values for optional parameters
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    /**
     * Set domain constraint for the route
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Set allowed schemes (http, https)
     */
    public function scheme(string|array $schemes): self
    {
        $this->schemes = is_array($schemes) ? $schemes : [$schemes];
        return $this;
    }

    /**
     * Shortcut to allow only HTTPS
     */
    public function secure(): self
    {
        return $this->scheme('https');
    }

    // === Route Matching Methods ===

    /**
     * Check if the route matches the given request
     */
    public function matches(string $method, string $uri, string $domain = null, string $scheme = 'http'): bool
    {
        // Check HTTP method
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        // Check domain constraint
        if ($this->domain && $domain && !$this->matchesDomain($domain)) {
            return false;
        }

        // Check scheme constraint
        if (!in_array($scheme, $this->schemes)) {
            return false;
        }

        // Check URI pattern
        $pattern = $this->getCompiledRegex();
        return preg_match($pattern, $uri) === 1;
    }

    /**
     * Check if domain matches the constraint
     */
    protected function matchesDomain(string $domain): bool
    {
        if (!$this->domain) {
            return true;
        }

        // Support wildcard domains like *.example.com
        $pattern = str_replace('*', '([^.]+)', preg_quote($this->domain, '/'));
        return preg_match("/^{$pattern}$/i", $domain) === 1;
    }

    /**
     * Extract parameters from the matched URI
     */
    public function extractParameters(string $uri): array
    {
        $compiled = RouteCompiler::compile($this);
        return RouteCompiler::match($compiled, $uri) ?? $this->defaults;
    }

    /**
     * Get compiled regex pattern for route matching
     */
    protected function getCompiledRegex(): string
    {
        if ($this->compiledRegex === null) {
            $compiled = RouteCompiler::compile($this);
            $this->compiledRegex = $compiled->getRegex();
        }
        return $this->compiledRegex;
    }

    /**
     * Get parameter names from route URI
     */
    protected function getParameterNames(): array
    {
        if (!empty($this->parameterNames)) {
            return $this->parameterNames;
        }

        preg_match_all('/\{([^}?]+)\??}/', $this->uri, $matches);
        $this->parameterNames = $matches[1] ?? [];

        return $this->parameterNames;
    }

    // === URL Generation ===

    /**
     * Generate URL from route parameters
     */
    public function url(array $parameters = [], bool $absolute = false): string
    {
        $uri = $this->uri;

        // Replace required parameters
        foreach ($parameters as $key => $value) {
            $uri = str_replace(['{' . $key . '}', '{' . $key . '?}'], $value, $uri);
        }

        // Remove optional parameters that weren't provided
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        // Clean up double slashes
        $uri = preg_replace('#/+#', '/', $uri);

        if ($absolute) {
            $scheme = in_array('https', $this->schemes) ? 'https' : 'http';
            $domain = $this->domain ?: $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $scheme . '://' . $domain . $uri;
        }

        return $uri;
    }

    /**
     * Alias for url() method
     */
    public function compile(array $parameters = []): string
    {
        return $this->url($parameters);
    }

    // === Route Execution ===

    /**
     * Execute the route action
     */
    public function run(Request $request, array $parameters = []): mixed
    {
        return $this->callAction($this->action, $request, $parameters);
    }

    /**
     * Call the route action with proper parameter injection
     */
    protected function callAction(mixed $action, Request $request, array $parameters): mixed
    {
        // Closure action
        if ($action instanceof Closure) {
            return $this->callClosure($action, $request, $parameters);
        }

        // String action (Controller@method or function name)
        if (is_string($action)) {
            return $this->callStringAction($action, $request, $parameters);
        }

        // Array action [Controller::class, 'method'] or [$instance, 'method']
        if (is_array($action) && count($action) === 2) {
            return $this->callArrayAction($action, $request, $parameters);
        }

        // Callable action
        if (is_callable($action)) {
            return $action($request, ...$parameters);
        }

        throw new \InvalidArgumentException('Invalid route action: ' . gettype($action));
    }

    /**
     * Call closure action
     */
    protected function callClosure(Closure $closure, Request $request, array $parameters): mixed
    {
        // Use reflection to inject parameters by name if possible
        $reflection = new \ReflectionFunction($closure);
        $args = $this->resolveParameters($reflection, $request, $parameters);

        return $closure(...$args);
    }

    /**
     * Call string action (Controller@method or function)
     */
    protected function callStringAction(string $action, Request $request, array $parameters): mixed
    {
        // Handle Controller@method format
        if (str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action, 2);

            if (!class_exists($controller)) {
                throw new \InvalidArgumentException("Controller class {$controller} not found");
            }

            $instance = new $controller();

            if (!method_exists($instance, $method)) {
                throw new \InvalidArgumentException("Method {$method} not found in {$controller}");
            }

            $reflection = new \ReflectionMethod($instance, $method);
            $args = $this->resolveParameters($reflection, $request, $parameters);

            return $instance->$method(...$args);
        }

        // Handle simple function name
        if (function_exists($action)) {
            $reflection = new \ReflectionFunction($action);
            $args = $this->resolveParameters($reflection, $request, $parameters);

            return $action(...$args);
        }

        throw new \InvalidArgumentException("Action {$action} is not callable");
    }

    /**
     * Call array action [Controller, method]
     */
    protected function callArrayAction(array $action, Request $request, array $parameters): mixed
    {
        [$controller, $method] = $action;

        // Instantiate controller if it's a class name
        if (is_string($controller)) {
            if (!class_exists($controller)) {
                throw new \InvalidArgumentException("Controller class {$controller} not found");
            }
            $controller = new $controller();
        }

        if (!is_object($controller)) {
            throw new \InvalidArgumentException('Invalid controller instance');
        }

        if (!method_exists($controller, $method)) {
            throw new \InvalidArgumentException("Method {$method} not found in controller");
        }

        $reflection = new \ReflectionMethod($controller, $method);
        $args = $this->resolveParameters($reflection, $request, $parameters);

        return $controller->$method(...$args);
    }

    /**
     * Resolve method parameters using reflection
     */
    protected function resolveParameters(\ReflectionFunctionAbstract $reflection, Request $request, array $parameters): array
    {
        $args = [];
        $reflectionParameters = $reflection->getParameters();

        foreach ($reflectionParameters as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Inject Request object
            if ($type && $type->getName() === Request::class) {
                $args[] = $request;
                continue;
            }

            // Inject route parameters by name
            if (isset($parameters[$name])) {
                $args[] = $this->castParameter($parameters[$name], $type);
                continue;
            }

            // Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Parameter is required but not provided
            if (!$param->isOptional()) {
                throw new \InvalidArgumentException("Required parameter {$name} not provided");
            }

            $args[] = null;
        }

        return $args;
    }

    /**
     * Cast parameter to appropriate type
     */
    protected function castParameter(mixed $value, ?\ReflectionNamedType $type): mixed
    {
        if (!$type || $type->allowsNull() && $value === null) {
            return $value;
        }

        $typeName = $type->getName();

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value
        };
    }

    // === Validation Methods ===

    /**
     * Validate route parameters against constraints
     */
    public function validateParameters(array $parameters): array
    {
        $errors = [];

        foreach ($this->where as $param => $pattern) {
            if (isset($parameters[$param])) {
                if (!preg_match("/^{$pattern}$/", $parameters[$param])) {
                    $errors[$param] = "Parameter {$param} does not match required pattern";
                }
            }
        }

        return $errors;
    }

    // === Getters ===

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): mixed
    {
        return $this->action;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWhere(): array
    {
        return $this->where;
    }
    public function public(?string $priority = null, ?string $changefreq = null): self
    {
        self::$publicRoutes[$this->url()] =  [
            'url' => $this->url(),
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];
        return $this;
    }
    public function private(): self
    {
        self::$privateRoutes[$this->url()] =  [
            'url' => $this->url(),
        ];
        return $this;
    }
    public function isPrivate(): bool
    {
        return in_array($this->url(), self::$privateRoutes);
    }
    public function isPublic(): bool
    {
        return in_array($this->url(), self::$publicRoutes);
    }
    public function rmPublic()
    {
        if ($this->isPublic()) {
            $key = array_search($this->url(), self::$publicRoutes);
            if ($key !== false) {
                unset(self::$publicRoutes[$key]);
            }
        }
    }
    public function rmPrivate()
    {
        if ($this->isPrivate()) {
            $key = array_search($this->url(), self::$privateRoutes);
            if ($key !== false) {
                unset(self::$privateRoutes[$key]);
            }
        }
    }
    public static function getPrivateRoutes(): array
    {
        return self::$privateRoutes;
    }
    public static function getPublicRoutes(): array
    {
        return self::$publicRoutes;
    }
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getSchemes(): array
    {
        return $this->schemes;
    }

    // === Utility Methods ===

    /**
     * Check if route has specific middleware
     */
    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->middleware);
    }

    /**
     * Check if route accepts specific HTTP method
     */
    public function acceptsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods);
    }

    /**
     * Get route signature for debugging
     */
    public function getSignature(): string
    {
        $methods = implode('|', $this->methods);
        $name = $this->name ? " ({$this->name})" : '';
        return "[{$methods}] {$this->uri}{$name}";
    }

    // === Magic Methods ===

    public function __toString(): string
    {
        return $this->getSignature();
    }

    public function __debugInfo(): array
    {
        return [
            'methods' => $this->methods,
            'uri' => $this->uri,
            'name' => $this->name,
            'middleware' => $this->middleware,
            'where' => $this->where,
            'defaults' => $this->defaults,
            'domain' => $this->domain,
            'schemes' => $this->schemes,
            'action' => is_object($this->action) ? get_class($this->action) : $this->action
        ];
    }
}
