<?php

namespace Core\Routing;

use Core\Routing\CompiledRoute;

/**
 * RouteCompiler - Compiles route patterns into optimized regex patterns
 * 
 * This class handles the compilation of route URIs with parameters into
 * regular expressions for efficient matching and parameter extraction.
 */
class RouteCompiler
{
    /**
     * Compiled route data cache
     */
    protected static array $compiledRoutes = [];

    /**
     * Default parameter patterns
     */
    protected static array $defaultPatterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        'any' => '.*',
        'alpha' => '[a-zA-Z]+',
        'num' => '[0-9]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'year' => '[0-9]{4}',
        'month' => '(0[1-9]|1[0-2])',
        'day' => '(0[1-9]|[12][0-9]|3[01])',
        'hex' => '[a-fA-F0-9]+',
        'base64' => '[a-zA-Z0-9+/=]+',
        'email' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
    ];

    /**
     * Reserved parameter names that cannot be used
     */
    protected static array $reservedNames = [
        'controller',
        'action',
        'method',
        'route',
        'middleware'
    ];

    /**
     * Compile a route pattern into a CompiledRoute object
     */
    public static function compile(Route $route, array $customPatterns = []): CompiledRoute
    {
        $uri = $route->getUri();
        $where = $route->getWhere();
        $defaults = $route->getDefaults();

        // Create cache key
        $cacheKey = md5($uri . serialize($where) . serialize($customPatterns));

        if (isset(self::$compiledRoutes[$cacheKey])) {
            return self::$compiledRoutes[$cacheKey];
        }

        // Merge patterns (custom patterns override defaults)
        $patterns = array_merge(self::$defaultPatterns, $customPatterns, $where);

        // Extract route information
        $routeData = self::extractRouteData($uri, $patterns, $defaults);

        // Create compiled route
        $compiledRoute = new CompiledRoute(
            $routeData['regex'],
            $routeData['variables'],
            $routeData['tokens'],
            $routeData['staticPrefix'],
            $routeData['hostRegex'],
            $routeData['hostTokens'],
            $routeData['hostVariables'],
            $defaults
        );

        // Cache the compiled route
        self::$compiledRoutes[$cacheKey] = $compiledRoute;

        return $compiledRoute;
    }

    /**
     * Extract route data from URI pattern
     */
    protected static function extractRouteData(string $uri, array $patterns, array $defaults): array
    {
        $tokens = self::tokenize($uri);
        $variables = [];
        $regex = '';
        $staticPrefix = '';
        $isStatic = true;

        foreach ($tokens as $token) {
            if ($token['type'] === 'text') {
                $regex .= preg_quote($token['text'], '#');
                if ($isStatic) {
                    $staticPrefix .= $token['text'];
                }
            } elseif ($token['type'] === 'variable') {
                $isStatic = false;
                $varName = $token['name'];

                // Validate variable name
                self::validateVariableName($varName);

                $variables[] = $varName;
                $pattern = $patterns[$varName] ?? '[^/]+';

                if ($token['optional']) {
                    $regex .= '(?:/(' . $pattern . '))?';
                } else {
                    $regex .= '(' . $pattern . ')';
                }
            }
        }

        return [
            'regex' => '#^' . $regex . '$#sD',
            'variables' => $variables,
            'tokens' => $tokens,
            'staticPrefix' => $staticPrefix,
            'hostRegex' => null,
            'hostTokens' => [],
            'hostVariables' => []
        ];
    }

    /**
     * Tokenize URI pattern into components
     */
    protected static function tokenize(string $pattern): array
    {
        $tokens = [];
        $length = strlen($pattern);
        $pos = 0;

        while ($pos < $length) {
            // Look for parameter start
            $paramStart = strpos($pattern, '{', $pos);

            if ($paramStart === false) {
                // No more parameters, add remaining text
                if ($pos < $length) {
                    $tokens[] = [
                        'type' => 'text',
                        'text' => substr($pattern, $pos)
                    ];
                }
                break;
            }

            // Add text before parameter
            if ($paramStart > $pos) {
                $tokens[] = [
                    'type' => 'text',
                    'text' => substr($pattern, $pos, $paramStart - $pos)
                ];
            }

            // Find parameter end
            $paramEnd = strpos($pattern, '}', $paramStart);
            if ($paramEnd === false) {
                throw new \InvalidArgumentException("Unclosed parameter in route pattern: {$pattern}");
            }

            // Extract parameter
            $paramContent = substr($pattern, $paramStart + 1, $paramEnd - $paramStart - 1);
            $optional = str_ends_with($paramContent, '?');

            if ($optional) {
                $paramContent = substr($paramContent, 0, -1);
            }

            // Validate parameter name
            if (empty($paramContent) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $paramContent)) {
                throw new \InvalidArgumentException("Invalid parameter name: {$paramContent}");
            }

            $tokens[] = [
                'type' => 'variable',
                'name' => $paramContent,
                'optional' => $optional
            ];

            $pos = $paramEnd + 1;
        }

        return $tokens;
    }

    /**
     * Validate variable name
     */
    protected static function validateVariableName(string $name): void
    {
        if (in_array($name, self::$reservedNames)) {
            throw new \InvalidArgumentException("Parameter name '{$name}' is reserved and cannot be used");
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid parameter name: {$name}");
        }
    }

    /**
     * Generate URL from compiled route and parameters
     */
    public static function generateUrl(CompiledRoute $compiledRoute, array $parameters = [], array $queryParams = []): string
    {
        $url = '';
        $usedParams = [];

        foreach ($compiledRoute->getTokens() as $token) {
            if ($token['type'] === 'text') {
                $url .= $token['text'];
            } elseif ($token['type'] === 'variable') {
                $name = $token['name'];

                if (isset($parameters[$name])) {
                    $url .= '/' . $parameters[$name];
                    $usedParams[] = $name;
                } elseif (!$token['optional']) {
                    // Check for default value
                    $defaults = $compiledRoute->getDefaults();
                    if (isset($defaults[$name])) {
                        $url .= '/' . $defaults[$name];
                        $usedParams[] = $name;
                    } else {
                        throw new \InvalidArgumentException("Missing required parameter: {$name}");
                    }
                }
            }
        }

        // Add query parameters
        $remainingParams = array_diff_key($parameters, array_flip($usedParams));
        $allQueryParams = array_merge($remainingParams, $queryParams);

        if (!empty($allQueryParams)) {
            $url .= '?' . http_build_query($allQueryParams);
        }

        return $url ?: '/';
    }

    /**
     * Match URI against compiled route
     */
    public static function match(CompiledRoute $compiledRoute, string $uri): ?array
    {
        if (!preg_match($compiledRoute->getRegex(), $uri, $matches)) {
            return null;
        }

        $parameters = $compiledRoute->getDefaults();
        $variables = $compiledRoute->getVariables();

        // Extract matched parameters
        for ($i = 1; $i < count($matches); $i++) {
            if (isset($variables[$i - 1]) && $matches[$i] !== '') {
                $parameters[$variables[$i - 1]] = $matches[$i];
            }
        }

        return $parameters;
    }

    /**
     * Optimize route compilation for production
     */
    public static function optimize(): void
    {
        // In production, you might want to serialize compiled routes to disk
        // This is a placeholder for optimization logic
    }

    /**
     * Clear compilation cache
     */
    public static function clearCache(): void
    {
        self::$compiledRoutes = [];
    }

    /**
     * Get compilation statistics
     */
    public static function getStats(): array
    {
        return [
            'cached_routes' => count(self::$compiledRoutes),
            'memory_usage' => memory_get_usage(),
            'default_patterns' => count(self::$defaultPatterns)
        ];
    }

    /**
     * Add custom pattern
     */
    public static function addPattern(string $name, string $pattern): void
    {
        self::$defaultPatterns[$name] = $pattern;
        // Clear cache since patterns changed
        self::clearCache();
    }

    /**
     * Remove pattern
     */
    public static function removePattern(string $name): void
    {
        unset(self::$defaultPatterns[$name]);
        self::clearCache();
    }

    /**
     * Get all patterns
     */
    public static function getPatterns(): array
    {
        return self::$defaultPatterns;
    }

    /**
     * Validate route pattern syntax
     */
    public static function validatePattern(string $pattern): array
    {
        $errors = [];

        try {
            self::tokenize($pattern);
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        // Check for common issues
        if (strpos($pattern, '//') !== false) {
            $errors[] = 'Pattern contains double slashes';
        }

        if (substr_count($pattern, '{') !== substr_count($pattern, '}')) {
            $errors[] = 'Mismatched braces in pattern';
        }

        return $errors;
    }

    /**
     * Debug compiled route
     */
    public static function debug(CompiledRoute $compiledRoute): array
    {
        return [
            'regex' => $compiledRoute->getRegex(),
            'variables' => $compiledRoute->getVariables(),
            'tokens' => $compiledRoute->getTokens(),
            'static_prefix' => $compiledRoute->getStaticPrefix(),
            'defaults' => $compiledRoute->getDefaults()
        ];
    }
}
