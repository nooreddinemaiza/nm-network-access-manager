<?php

namespace Core\System;

use Core\File;
use Exception;

class Environment
{
    private string $filePath;
    private array $variables = [];
    private array $comments = [];
    private bool $loaded = false;

    public function __construct(?string $filePath = null)
    {
        if (is_null($filePath) && !File::exists('config', '.env')) {
            File::copy('config', 'env.backup', 'config', '.env');
        }
        $this->filePath = $filePath ?? $this->getDefaultPath();
    }

    /**
     * Charge les variables depuis le fichier .env
     */
    public function load(): self
    {
        if (!file_exists($this->filePath)) {
            throw new Exception("Le fichier {$this->filePath} n'existe pas");
        }

        if (!is_readable($this->filePath)) {
            throw new Exception("Le fichier {$this->filePath} n'est pas accessible en lecture");
        }

        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new Exception("Impossible de lire le fichier {$this->filePath}");
        }

        $this->variables = [];
        $this->comments = [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Préserver les commentaires
            if (empty($line) || strpos($line, '#') === 0) {
                $this->comments[$lineNumber] = $line;
                continue;
            }

            // Parser la ligne
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Validation du nom de variable
                if (!$this->isValidVariableName($name)) {
                    throw new Exception("Nom de variable invalide à la ligne " . ($lineNumber + 1) . ": $name");
                }

                // Supprimer les guillemets et gérer l'échappement
                $value = $this->parseValue($value);

                $this->variables[$name] = $value;

                // Définir dans $_ENV et putenv()
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }

        $this->loaded = true;
        return $this;
    }

    /**
     * Récupère une variable d'environnement
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->variables[$key] ?? $default;
    }

    /**
     * Définit une variable d'environnement
     * FIX: Charge d'abord les variables existantes avant d'ajouter/modifier
     */
    public function set(string $key, string $value): self
    {
        if (!$this->loaded) {
            $this->load();
        }

        if (!$this->isValidVariableName($key)) {
            throw new Exception("Nom de variable invalide: $key");
        }

        $this->variables[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");

        return $this;
    }

    /**
     * Vérifie si une variable existe
     */
    public function has(string $key): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        return isset($this->variables[$key]);
    }

    /**
     * Récupère toutes les variables
     */
    public function all(): array
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->variables;
    }

    /**
     * Sauvegarde les variables dans le fichier .env
     * FIX: Préserve les commentaires et les variables existantes
     */
    public function save(): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        $content = '';

        // Reconstruire le fichier en préservant les commentaires
        $processedVars = [];

        // D'abord, réintégrer les commentaires et variables existantes dans l'ordre
        $maxLine = max(array_merge(array_keys($this->comments), [0]));

        for ($i = 0; $i <= $maxLine; $i++) {
            if (isset($this->comments[$i])) {
                $content .= $this->comments[$i] . "\n";
            }
        }

        // Ajouter toutes les variables
        foreach ($this->variables as $key => $value) {
            // Échapper et ajouter des guillemets si nécessaire
            $formattedValue = $this->formatValue($value);
            $content .= "$key=$formattedValue\n";
            $processedVars[] = $key;
        }

        // Créer un backup avant d'écrire
        if (File::exists('config', '.env')) {
            $backupPath = $this->filePath . '.backup';
            File::copy(
                'config',
                '.env',
                'backup',
                '.env.backup'
            );
        }

        $result = file_put_contents($this->filePath, $content, LOCK_EX);

        if ($result === false) {
            // Restaurer le backup en cas d'erreur
            if (isset($backupPath) && file_exists($backupPath)) {
                copy($backupPath, $this->filePath);
                unlink($backupPath);
            }
            throw new Exception("Impossible d'écrire dans le fichier {$this->filePath}");
        }

        // Supprimer le backup si tout s'est bien passé
        if (isset($backupPath) && file_exists($backupPath)) {
            unlink($backupPath);
        }

        return true;
    }

    /**
     * Supprime une variable
     */
    public function delete(string $key): self
    {
        if (!$this->loaded) {
            $this->load();
        }

        unset($this->variables[$key]);
        unset($_ENV[$key]);
        putenv($key);

        return $this;
    }

    /**
     * Recharge le fichier .env
     */
    public function reload(): self
    {
        $this->variables = [];
        $this->comments = [];
        $this->loaded = false;
        return $this->load();
    }

    /**
     * Crée un nouveau fichier .env
     */
    public function create(array $variables = []): bool
    {
        $this->variables = $variables;
        $this->loaded = true;
        return $this->save();
    }

    /**
     * Parse une valeur en gérant les guillemets et l'échappement
     */
    private function parseValue(string $value): string
    {
        $value = trim($value);

        // Gérer les valeurs entre guillemets doubles
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return stripcslashes($matches[1]);
        }

        // Gérer les valeurs entre guillemets simples (pas d'échappement)
        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        // Supprimer les commentaires inline
        $commentPos = strpos($value, '#');
        if ($commentPos !== false) {
            $value = trim(substr($value, 0, $commentPos));
        }

        return $value;
    }

    /**
     * Formate une valeur pour l'écriture dans le fichier
     */
    private function formatValue(string $value): string
    {
        // Si la valeur contient des caractères spéciaux, utiliser des guillemets
        if ($this->needsQuotes($value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }

        return $value;
    }

    /**
     * Détermine si une valeur nécessite des guillemets
     */
    private function needsQuotes(string $value): bool
    {
        return preg_match('/[\s#"\'\\\]/', $value) === 1;
    }

    /**
     * Valide le nom d'une variable
     */
    private function isValidVariableName(string $name): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) === 1;
    }

    /**
     * Obtient le chemin par défaut du fichier .env
     */
    private function getDefaultPath(): string
    {
        return File::getPath('config', '.env');
    }

    /**
     * Récupère le chemin du fichier
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Vérifie si le fichier existe
     */
    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * Importe des variables depuis un tableau sans écraser les existantes
     */
    public function merge(array $variables): self
    {
        if (!$this->loaded) {
            $this->load();
        }

        foreach ($variables as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Export des variables vers un tableau
     */
    public function toArray(): array
    {
        return $this->all();
    }

    /**
     * Vide toutes les variables en mémoire
     */
    public function clear(): self
    {
        foreach (array_keys($this->variables) as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }

        $this->variables = [];
        $this->comments = [];
        $this->loaded = false;

        return $this;
    }
}
