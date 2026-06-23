<?php

namespace Core\Routing;

/**
 * CompiledRoute - Represents a compiled route with regex and metadata
 */
class CompiledRoute
{
    protected string $regex;
    protected array $variables;
    protected array $tokens;
    protected string $staticPrefix;
    protected ?string $hostRegex;
    protected array $hostTokens;
    protected array $hostVariables;
    protected array $defaults;

    public function __construct(
        string $regex,
        array $variables,
        array $tokens,
        string $staticPrefix,
        ?string $hostRegex = null,
        array $hostTokens = [],
        array $hostVariables = [],
        array $defaults = []
    ) {
        $this->regex = $regex;
        $this->variables = $variables;
        $this->tokens = $tokens;
        $this->staticPrefix = $staticPrefix;
        $this->hostRegex = $hostRegex;
        $this->hostTokens = $hostTokens;
        $this->hostVariables = $hostVariables;
        $this->defaults = $defaults;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getStaticPrefix(): string
    {
        return $this->staticPrefix;
    }

    public function getHostRegex(): ?string
    {
        return $this->hostRegex;
    }

    public function getHostTokens(): array
    {
        return $this->hostTokens;
    }

    public function getHostVariables(): array
    {
        return $this->hostVariables;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Check if route has variables
     */
    public function hasVariables(): bool
    {
        return !empty($this->variables);
    }

    /**
     * Get variable count
     */
    public function getVariableCount(): int
    {
        return count($this->variables);
    }

    /**
     * Check if variable exists
     */
    public function hasVariable(string $name): bool
    {
        return in_array($name, $this->variables);
    }
}
