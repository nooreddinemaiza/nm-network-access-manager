<?php

namespace Core;

use Core\Logger;
use RuntimeException;

/**
 * Classe File pour la manipulation de fichiers et chemins de l'application
 * 
 * Gestion centralisée et sécurisée des opérations sur les fichiers
 * avec support de chemins typés et validation de sécurité
 */
class File
{
    private static ?string $baseDir = null;
    private static array $cache = [];

    private const PATHS = [
        'log' => '/Storage/Logs/',
        'file' => '/Storage/Files/',
        'archive' => '/Storage/archives/',
        'backup' => '/Storage/Backup/',
        'config' => '/Config/',
        'views' => '/Resources/Views/',
        'admin_views' => '/Resources/Views/Admin/',
        'users_views' => '/Resources/Views/User/',
        'layouts' => '/Resources/Layouts/',
        'components' => '/Resources/Components/',
        'partials' => '/Resources/Parts/',
        'styles' => '/Public/Assets/styles/',
        'scripts' => '/Public/Assets/scripts/',
        'images' => '/Public/Assets/images/',
        'routes' => '/Routes/',
    ];

    /**
     * Initialise le répertoire de base de l'application
     */
    public static function init(string $baseDir): void
    {
        if (self::$baseDir !== null) {
            return;
        }
        self::$baseDir = rtrim($baseDir, '/\\');
    }

    /**
     * Récupère le répertoire de base
     */
    public static function getBaseDir(): string
    {
        if (self::$baseDir === null) {
            throw new RuntimeException('File::init() doit être appelé avant toute opération');
        }
        return self::$baseDir;
    }

    /**
     * Construit le chemin absolu d'un fichier
     */
    public static function getPath(string $type, string $name): string
    {
        if (!isset(self::PATHS[$type])) {
            Logger::error("Type de chemin invalide : {$type}");
            throw new \InvalidArgumentException("Type de chemin non supporté : {$type}");
        }

        $basePath = self::getBaseDir() . self::PATHS[$type];
        $fullPath = $basePath . ltrim($name, '/\\');

        self::validatePath($fullPath);

        return $fullPath;
    }

    /**
     * Récupère le chemin d'un répertoire
     */
    public static function getDirectoryPath(string $type): string
    {
        if (!isset(self::PATHS[$type])) {
            Logger::error("Type de répertoire invalide : {$type}");
            throw new \InvalidArgumentException("Type de répertoire non supporté : {$type}");
        }

        $directoryPath = self::getBaseDir() . self::PATHS[$type];

        self::validatePath($directoryPath);

        return $directoryPath;
    }

    /**
     * Valide qu'un chemin est sécurisé (évite les attaques par traversée)
     */
    private static function validatePath(string $path): void
    {
        $realBase = realpath(self::getBaseDir());
        $realPath = realpath($path) ?: realpath(dirname($path));

        if ($realPath === false || strpos($realPath, $realBase) !== 0) {
            Logger::error("Tentative d'accès non autorisé : {$path}");
            throw new RuntimeException("Accès au chemin non autorisé");
        }
    }

    /**
     * Vérifie l'existence d'un fichier
     */
    public static function exists(string $type, string $name): bool
    {
        try {
            return file_exists(self::getPath($type, $name));
        } catch (\Exception $e) {
            Logger::error("Erreur lors de la vérification d'existence : {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Lit le contenu d'un fichier
     */
    public static function read(string $type, string $name): string
    {
        $path = self::getPath($type, $name);

        if (!is_readable($path)) {
            Logger::error("Fichier non lisible : {$path}");
            throw new RuntimeException("Impossible de lire le fichier : {$name}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            Logger::error("Échec de lecture : {$path}");
            throw new RuntimeException("Erreur lors de la lecture du fichier : {$name}");
        }

        return $content;
    }
    public static function is_readable(string $type, string $name): bool
    {
        $path = self::getPath($type, $name);

        return file_exists($path) && is_readable($path);
    }
    /**
     * Charge un fichier PHP et retourne sa valeur de retour
     * Idéal pour les fichiers de configuration qui retournent un tableau
     * 
     * @param string $type Type de chemin
     * @param string $name Nom du fichier
     * @param bool $cache Mettre en cache le résultat
     * @return mixed La valeur retournée par le fichier
     * @throws RuntimeException Si le fichier n'existe pas ou n'est pas lisible
     */
    public static function require(string $type, string $name, bool $cache = true): mixed
    {
        $path = self::getPath($type, $name);
        $cacheKey = $type . ':' . $name;

        // Vérifier le cache si activé
        if ($cache && isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        if (!is_readable($path)) {
            Logger::error("Fichier non lisible pour require : {$path}");
            throw new RuntimeException("Impossible de charger le fichier : {$name}");
        }

        try {
            $result = require $path;

            // Mettre en cache si demandé
            if ($cache) {
                self::$cache[$cacheKey] = $result;
            }

            return $result;
        } catch (\Throwable $e) {
            Logger::error("Erreur lors du chargement du fichier {$path} : {$e->getMessage()}");
            throw new RuntimeException("Erreur lors du chargement du fichier : {$name}", 0, $e);
        }
    }

    /**
     * Charge un fichier PHP et retourne sa valeur en tant que tableau
     * Lance une exception si le résultat n'est pas un tableau
     * 
     * @param string $type Type de chemin
     * @param string $name Nom du fichier
     * @param bool $cache Mettre en cache le résultat
     * @return array Le tableau retourné par le fichier
     * @throws RuntimeException Si le fichier ne retourne pas un tableau
     */
    public static function requireArray(string $type, string $name, bool $cache = true): array
    {
        $result = self::require($type, $name, $cache);

        if (!is_array($result)) {
            Logger::error("Le fichier {$name} ne retourne pas un tableau");
            throw new RuntimeException("Le fichier {$name} doit retourner un tableau");
        }

        return $result;
    }

    /**
     * Vide le cache des fichiers chargés
     * 
     * @param string|null $type Type de chemin spécifique (optionnel)
     * @param string|null $name Nom du fichier spécifique (optionnel)
     */
    public static function clearCache(?string $type = null, ?string $name = null): void
    {
        if ($type === null && $name === null) {
            self::$cache = [];
            Logger::info("Cache des fichiers vidé complètement");
            return;
        }

        if ($type !== null && $name !== null) {
            $cacheKey = $type . ':' . $name;
            unset(self::$cache[$cacheKey]);
            Logger::info("Cache vidé pour : {$cacheKey}");
            return;
        }

        if ($type !== null) {
            $prefix = $type . ':';
            foreach (array_keys(self::$cache) as $key) {
                if (str_starts_with($key, $prefix)) {
                    unset(self::$cache[$key]);
                }
            }
            Logger::info("Cache vidé pour le type : {$type}");
        }
    }

    /**
     * Écrit du contenu dans un fichier
     */
    public static function write(string $type, string $name, string $content, bool $append = false): bool
    {
        $path = self::getPath($type, $name);

        self::ensureDirectory(dirname($path));

        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;
        $result = file_put_contents($path, $content, $flags);

        if ($result === false) {
            Logger::error("Échec d'écriture : {$path}");
            throw new RuntimeException("Impossible d'écrire dans le fichier : {$name}");
        }

        // Vider le cache pour ce fichier
        self::clearCache($type, $name);

        return true;
    }

    /**
     * Supprime un fichier
     */
    public static function delete(string $type, string $name): bool
    {
        $path = self::getPath($type, $name);

        if (!file_exists($path)) {
            Logger::warning("Tentative de suppression d'un fichier inexistant : {$path}");
            return false;
        }

        if (!unlink($path)) {
            Logger::error("Échec de suppression : {$path}");
            return false;
        }

        // Vider le cache pour ce fichier
        self::clearCache($type, $name);

        Logger::info("Fichier supprimé : {$name}");
        return true;
    }

    /**
     * Renomme un fichier avec un hash MD5
     */
    public static function renameWithHash(string $type, string $oldName): string
    {
        $oldPath = self::getPath($type, $oldName);

        if (!file_exists($oldPath)) {
            Logger::error("Fichier introuvable pour renommage : {$oldPath}");
            throw new RuntimeException("Fichier introuvable : {$oldName}");
        }

        $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
        $newName = md5($oldName . time() . random_bytes(8)) . '.' . $extension;
        $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $newName;

        if (!rename($oldPath, $newPath)) {
            Logger::error("Échec de renommage : {$oldPath} -> {$newPath}");
            throw new RuntimeException("Impossible de renommer le fichier : {$oldName}");
        }

        // Vider le cache pour l'ancien fichier
        self::clearCache($type, $oldName);

        Logger::info("Fichier renommé : {$oldName} -> {$newName}");
        return $newName;
    }

    /**
     * Inclut un fichier avec des variables
     */
    public static function include(string $type, string $name, array $data = []): void
    {
        $path = self::getPath($type, $name);

        if (!is_readable($path)) {
            Logger::error("Fichier non lisible pour inclusion : {$path}");
            throw new RuntimeException("Impossible d'inclure le fichier : {$name}");
        }

        extract($data, EXTR_SKIP);
        include $path;
    }

    /**
     * Ajoute une ligne à un fichier à une position donnée
     */
    public static function addLine(string $type, string $name, string $line, string|int $position = 'last'): bool
    {
        $path = self::getPath($type, $name);

        if (!file_exists($path)) {
            Logger::error("Fichier introuvable pour ajout de ligne : {$path}");
            throw new RuntimeException("Fichier introuvable : {$name}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if (in_array($line, $lines, true)) {
            Logger::info("Ligne déjà présente dans le fichier : {$name}");
            return false;
        }

        $newLines = ['', $line, ''];

        switch ($position) {
            case 'first':
                array_unshift($lines, ...$newLines);
                break;

            case 'last':
                array_push($lines, ...$newLines);
                break;

            default:
                if (!is_int($position) || $position < 0 || $position > count($lines)) {
                    Logger::error("Position invalide : {$position}");
                    throw new \InvalidArgumentException("Position invalide : {$position}");
                }
                array_splice($lines, $position, 0, $newLines);
        }

        return self::write($type, $name, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /**
     * Copie un fichier
     */
    public static function copy(string $sourceType, string $sourceName, string $destType, string $destName): bool
    {
        $sourcePath = self::getPath($sourceType, $sourceName);
        $destPath = self::getPath($destType, $destName);

        if (!file_exists($sourcePath)) {
            Logger::error("Fichier source introuvable : {$sourcePath}");
            throw new RuntimeException("Fichier source introuvable : {$sourceName}");
        }

        self::ensureDirectory(dirname($destPath));

        if (!copy($sourcePath, $destPath)) {
            Logger::error("Échec de copie : {$sourcePath} -> {$destPath}");
            return false;
        }

        Logger::info("Fichier copié : {$sourceName} -> {$destName}");
        return true;
    }

    /**
     * Déplace un fichier
     */
    public static function move(string $sourceType, string $sourceName, string $destType, string $destName): bool
    {
        $sourcePath = self::getPath($sourceType, $sourceName);
        $destPath = self::getPath($destType, $destName);

        if (!file_exists($sourcePath)) {
            Logger::error("Fichier source introuvable : {$sourcePath}");
            throw new RuntimeException("Fichier source introuvable : {$sourceName}");
        }

        self::ensureDirectory(dirname($destPath));

        if (!rename($sourcePath, $destPath)) {
            Logger::error("Échec de déplacement : {$sourcePath} -> {$destPath}");
            return false;
        }

        // Vider le cache pour le fichier source
        self::clearCache($sourceType, $sourceName);

        Logger::info("Fichier déplacé : {$sourceName} -> {$destName}");
        return true;
    }

    /**
     * Récupère les informations d'un fichier
     */
    public static function getInfo(string $type, string $name): array
    {
        $path = self::getPath($type, $name);

        if (!file_exists($path)) {
            Logger::error("Fichier introuvable : {$path}");
            throw new RuntimeException("Fichier introuvable : {$name}");
        }

        return [
            'name' => basename($path),
            'path' => $path,
            'size' => filesize($path),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'mime_type' => mime_content_type($path),
            'modified' => filemtime($path),
            'created' => filectime($path),
            'is_readable' => is_readable($path),
            'is_writable' => is_writable($path),
        ];
    }

    /**
     * Obtient la taille d'un fichier
     */
    public static function getSize(string $type, string $name): int
    {
        try {
            $filePath = self::getPath($type, $name);
            if (!self::exists($type, $name)) {
                $error = "File not found: $filePath";
                Logger::error('Get file size failed - ' . $error);
                throw new RuntimeException($error);
            }

            $size = filesize($filePath);
            if ($size === false) {
                $error = "Failed to get file size: $filePath";
                Logger::error($error);
                throw new RuntimeException($error);
            }

            return $size;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Obtient la taille d'un fichier formatée (KB, MB, GB)
     */
    public static function getFormattedSize(string $type, string $name, int $precision = 2): string
    {
        $bytes = self::getSize($type, $name);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Liste les fichiers d'un répertoire
     */
    public static function listFiles(string $type, string $pattern = '*', bool $fullPath = false): array
    {
        $dirPath = self::getDirectoryPath($type);

        if (!is_dir($dirPath)) {
            Logger::warning("Répertoire introuvable : {$dirPath}");
            return [];
        }

        $files = glob($dirPath . $pattern);
        $files = array_filter($files, 'is_file');

        if (!$fullPath) {
            $files = array_map('basename', $files);
        }

        return array_values($files);
    }

    /**
     * Liste les répertoires d'un répertoire
     */
    public static function listDirectories(string $type, bool $fullPath = false): array
    {
        $dirPath = self::getDirectoryPath($type);

        if (!is_dir($dirPath)) {
            Logger::warning("Répertoire introuvable : {$dirPath}");
            return [];
        }

        $items = glob($dirPath . '*', GLOB_ONLYDIR);

        if (!$fullPath) {
            $items = array_map('basename', $items);
        }

        return array_values($items);
    }

    /**
     * Assure qu'un répertoire existe
     */
    private static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            Logger::error("Impossible de créer le répertoire : {$path}");
            throw new RuntimeException("Impossible de créer le répertoire");
        }
    }

    /**
     * Crée un répertoire
     */
    public static function createDirectory(string $type, string $name = ''): bool
    {
        $path = empty($name)
            ? self::getDirectoryPath($type)
            : self::getPath($type, $name);

        try {
            self::ensureDirectory($path);
            Logger::info("Répertoire créé : {$path}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Supprime un répertoire (vide uniquement)
     */
    public static function deleteDirectory(string $type, string $name = ''): bool
    {
        $path = empty($name)
            ? self::getDirectoryPath($type)
            : self::getPath($type, $name);

        if (!is_dir($path)) {
            Logger::warning("Répertoire introuvable : {$path}");
            return false;
        }

        if (!rmdir($path)) {
            Logger::error("Impossible de supprimer le répertoire (non vide ?) : {$path}");
            return false;
        }

        Logger::info("Répertoire supprimé : {$path}");
        return true;
    }

    /**
     * Supprime récursivement un répertoire et son contenu
     */
    public static function deleteDirectoryRecursive(string $type, string $name = ''): bool
    {
        $path = empty($name)
            ? self::getDirectoryPath($type)
            : self::getPath($type, $name);

        if (!is_dir($path)) {
            Logger::warning("Répertoire introuvable : {$path}");
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        if (!rmdir($path)) {
            Logger::error("Impossible de supprimer le répertoire : {$path}");
            return false;
        }

        Logger::info("Répertoire supprimé récursivement : {$path}");
        return true;
    }

    /**
     * Vérifie si un répertoire est vide
     */
    public static function isDirectoryEmpty(string $type, string $name = ''): bool
    {
        $path = empty($name)
            ? self::getDirectoryPath($type)
            : self::getPath($type, $name);

        if (!is_dir($path)) {
            throw new RuntimeException("Le chemin n'est pas un répertoire : {$path}");
        }

        $items = scandir($path);
        return count($items) <= 2; // . et ..
    }
}
