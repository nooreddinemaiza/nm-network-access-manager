<?php

namespace Core;

use Core\Logger;
use Core\File;
use RuntimeException;
use InvalidArgumentException;

/**
 * Classe FileWriter — Écriture robuste et sécurisée de fichiers
 *
 * Centralise toutes les opérations d'écriture avec :
 *  - Verrouillage exclusif (LOCK_EX) systématique
 *  - Écriture atomique (via fichier temporaire + rename)
 *  - Support append, prepend, insert, replace
 *  - Rotation de logs
 *  - Invalidation automatique du cache FileReader
 */
class FileWriter
{
    /** Permissions par défaut pour les nouveaux fichiers */
    private const FILE_PERMISSIONS = 0644;

    /** Permissions par défaut pour les nouveaux répertoires */
    private const DIR_PERMISSIONS = 0755;

    /** Taille maximale avant rotation automatique (10 Mo par défaut) */
    private const DEFAULT_ROTATION_SIZE = 10 * 1024 * 1024;

    // -------------------------------------------------------------------------
    // Écriture de base
    // -------------------------------------------------------------------------

    /**
     * Écrit du contenu dans un fichier (écrase si existant).
     * Utilise une écriture atomique : le fichier cible n'est modifié
     * qu'une fois l'écriture complète, évitant les états corrompus.
     *
     * @throws RuntimeException
     */
    public static function write(string $type, string $name, string $content): bool
    {
        $path = self::resolvePath($type, $name);
        self::ensureDirectory(dirname($path));

        return self::writeAtomic($path, $content, $type, $name);
    }

    /**
     * Ajoute du contenu à la fin d'un fichier (append).
     * Crée le fichier s'il n'existe pas.
     *
     * @throws RuntimeException
     */
    public static function append(string $type, string $name, string $content): bool
    {
        $path = self::resolvePath($type, $name);
        self::ensureDirectory(dirname($path));

        $result = file_put_contents($path, $content, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            Logger::error("FileWriter: échec append — {$path}");
            throw new RuntimeException("Impossible d'écrire dans le fichier : {$name}");
        }

        File::clearCache($type, $name);
        return true;
    }

    /**
     * Ajoute une ligne à la fin d'un fichier, avec retour à la ligne.
     * S'assure que la ligne n'est pas déjà présente si $unique = true.
     *
     * @throws RuntimeException
     */
    public static function appendLine(string $type, string $name, string $line, bool $unique = false): bool
    {
        if ($unique && self::containsLine($type, $name, $line)) {
            Logger::info("FileWriter: ligne déjà présente, ignorée — {$name}");
            return false;
        }

        return self::append($type, $name, $line . PHP_EOL);
    }

    /**
     * Insère du contenu au début du fichier (prepend).
     * Utilise une réécriture atomique.
     *
     * @throws RuntimeException
     */
    public static function prepend(string $type, string $name, string $content): bool
    {
        $path = self::resolvePath($type, $name);

        $existing = file_exists($path) ? (file_get_contents($path) ?: '') : '';

        return self::writeAtomic($path, $content . $existing, $type, $name);
    }

    /**
     * Insère une ligne à une position donnée (indexée à 0).
     * Positions spéciales : 'first' et 'last'.
     *
     * @param string|int $position 'first', 'last', ou index entier
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function insertLine(
        string $type,
        string $name,
        string $line,
        string|int $position = 'last'
    ): bool {
        $path = self::resolvePath($type, $name);

        $lines = file_exists($path)
            ? file($path, FILE_IGNORE_NEW_LINES) ?: []
            : [];

        switch ($position) {
            case 'first':
                array_unshift($lines, $line);
                break;

            case 'last':
                $lines[] = $line;
                break;

            default:
                if (!is_int($position) || $position < 0 || $position > count($lines)) {
                    throw new InvalidArgumentException("Position invalide : {$position}");
                }
                array_splice($lines, $position, 0, [$line]);
        }

        return self::writeAtomic($path, implode(PHP_EOL, $lines) . PHP_EOL, $type, $name);
    }

    // -------------------------------------------------------------------------
    // Remplacement et suppression de contenu
    // -------------------------------------------------------------------------

    /**
     * Remplace toutes les occurrences d'une chaîne dans le fichier.
     *
     * @throws RuntimeException
     */
    public static function replace(
        string $type,
        string $name,
        string $search,
        string $replacement
    ): bool {
        $path = self::resolvePath($type, $name);
        self::assertWritable($path, $name);

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Impossible de lire le fichier pour remplacement : {$name}");
        }

        $newContent = str_replace($search, $replacement, $content);

        return self::writeAtomic($path, $newContent, $type, $name);
    }

    /**
     * Remplace toutes les occurrences via une expression régulière.
     *
     * @throws RuntimeException
     */
    public static function replaceRegex(
        string $type,
        string $name,
        string $pattern,
        string $replacement
    ): bool {
        $path = self::resolvePath($type, $name);
        self::assertWritable($path, $name);

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Impossible de lire le fichier pour remplacement : {$name}");
        }

        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent === null) {
            Logger::error("FileWriter: regex invalide — {$pattern}");
            throw new InvalidArgumentException("Expression régulière invalide : {$pattern}");
        }

        return self::writeAtomic($path, $newContent, $type, $name);
    }

    /**
     * Supprime toutes les lignes correspondant à un prédicat callable.
     *
     * @param callable(string): bool $predicate
     * @throws RuntimeException
     */
    public static function removeLines(string $type, string $name, callable $predicate): int
    {
        $path = self::resolvePath($type, $name);
        self::assertWritable($path, $name);

        $lines   = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        $before  = count($lines);
        $kept    = array_values(array_filter($lines, fn($l) => !$predicate($l)));

        self::writeAtomic($path, implode(PHP_EOL, $kept) . PHP_EOL, $type, $name);

        $removed = $before - count($kept);
        Logger::info("FileWriter: {$removed} ligne(s) supprimée(s) — {$name}");
        return $removed;
    }

    /**
     * Tronque le fichier (le vide sans le supprimer).
     *
     * @throws RuntimeException
     */
    public static function truncate(string $type, string $name): bool
    {
        $path = self::resolvePath($type, $name);

        $handle = fopen($path, 'w');
        if ($handle === false) {
            Logger::error("FileWriter: impossible de tronquer — {$path}");
            throw new RuntimeException("Impossible de tronquer le fichier : {$name}");
        }

        fclose($handle);
        File::clearCache($type, $name);

        Logger::info("FileWriter: fichier tronqué — {$name}");
        return true;
    }

    // -------------------------------------------------------------------------
    // Rotation de logs
    // -------------------------------------------------------------------------

    /**
     * Effectue la rotation d'un fichier de log si sa taille dépasse $maxSize.
     * L'ancien fichier est renommé avec un suffixe horodaté.
     * Si $keepVersions > 0, les rotations excédentaires sont supprimées.
     *
     * @param int $maxSize      Seuil en octets (défaut : 10 Mo)
     * @param int $keepVersions Nombre d'archives à conserver (0 = illimité)
     * @return bool true si une rotation a eu lieu
     * @throws RuntimeException
     */
    public static function rotateLog(
        string $logName,
        int $maxSize = self::DEFAULT_ROTATION_SIZE,
        int $keepVersions = 5
    ): bool {
        if (!File::exists('log', $logName)) {
            return false;
        }

        $size = File::getSize('log', $logName);

        if ($size < $maxSize) {
            return false;
        }

        $timestamp  = date('Y-m-d_His');
        $extension  = pathinfo($logName, PATHINFO_EXTENSION);
        $baseName   = pathinfo($logName, PATHINFO_FILENAME);
        $rotatedName = "{$baseName}.{$timestamp}.{$extension}";

        File::move('log', $logName, 'archive', $rotatedName);
        Logger::info("FileWriter: rotation de log — {$logName} -> {$rotatedName}");

        // Nettoyer les archives excédentaires
        if ($keepVersions > 0) {
            self::pruneLogArchives($baseName, $extension, $keepVersions);
        }

        return true;
    }

    /**
     * Écrit une entrée de log formatée avec horodatage et niveau.
     *
     * Format : [2025-01-15 14:32:07] [ERROR] Message
     *
     * @throws RuntimeException
     */
    public static function writeLog(
        string $logName,
        string $message,
        string $level = 'INFO',
        ?array $context = null
    ): bool {
        $timestamp = date('Y-m-d H:i:s');
        $level     = strtoupper($level);
        $entry     = "[{$timestamp}] [{$level}] {$message}";

        if ($context !== null) {
            $entry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return self::appendLine('log', $logName, $entry);
    }

    // -------------------------------------------------------------------------
    // Interne
    // -------------------------------------------------------------------------

    /**
     * Résout le chemin absolu via la classe File centrale.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    private static function resolvePath(string $type, string $name): string
    {
        return File::getPath($type, $name);
    }

    /**
     * Vérifie qu'un fichier existant est accessible en écriture.
     *
     * @throws RuntimeException
     */
    private static function assertWritable(string $path, string $name): void
    {
        if (!file_exists($path)) {
            Logger::error("FileWriter: fichier introuvable — {$path}");
            throw new RuntimeException("Fichier introuvable : {$name}");
        }

        if (!is_writable($path)) {
            Logger::error("FileWriter: fichier non accessible en écriture — {$path}");
            throw new RuntimeException("Fichier non accessible en écriture : {$name}");
        }
    }

    /**
     * Écriture atomique : écrit dans un fichier temporaire, puis rename().
     * Garantit qu'en cas d'erreur, le fichier original n'est pas corrompu.
     *
     * @throws RuntimeException
     */
    private static function writeAtomic(
        string $path,
        string $content,
        string $type,
        string $name
    ): bool {
        $dir     = dirname($path);
        $tmpPath = tempnam($dir, '.tmp_write_');

        if ($tmpPath === false) {
            Logger::error("FileWriter: impossible de créer un fichier temporaire dans {$dir}");
            throw new RuntimeException("Impossible de créer un fichier temporaire pour : {$name}");
        }

        try {
            $result = file_put_contents($tmpPath, $content, LOCK_EX);

            if ($result === false) {
                Logger::error("FileWriter: échec d'écriture atomique — {$path}");
                throw new RuntimeException("Impossible d'écrire dans le fichier : {$name}");
            }

            chmod($tmpPath, self::FILE_PERMISSIONS);

            if (!rename($tmpPath, $path)) {
                Logger::error("FileWriter: échec du rename atomique — {$tmpPath} -> {$path}");
                throw new RuntimeException("Impossible de finaliser l'écriture du fichier : {$name}");
            }

            File::clearCache($type, $name);
            return true;

        } catch (\Throwable $e) {
            // Nettoyage du fichier temporaire en cas d'erreur
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
            throw $e;
        }
    }

    /**
     * Vérifie si un fichier contient déjà une ligne donnée (exact match).
     */
    private static function containsLine(string $type, string $name, string $line): bool
    {
        if (!File::exists($type, $name)) {
            return false;
        }

        $path   = self::resolvePath($type, $name);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            while (!feof($handle)) {
                $current = fgets($handle);
                if ($current === false) {
                    break;
                }
                if (rtrim($current, "\r\n") === $line) {
                    return true;
                }
            }
        } finally {
            fclose($handle);
        }

        return false;
    }

    /**
     * Crée un répertoire de manière récursive si absent.
     *
     * @throws RuntimeException
     */
    private static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, self::DIR_PERMISSIONS, true) && !is_dir($path)) {
            Logger::error("FileWriter: impossible de créer le répertoire — {$path}");
            throw new RuntimeException("Impossible de créer le répertoire : {$path}");
        }
    }

    /**
     * Supprime les archives de log les plus anciennes au-delà du quota.
     */
    private static function pruneLogArchives(string $base, string $ext, int $keep): void
    {
        $archives = File::listFiles('archive', "{$base}.*.{$ext}");

        // Tri chronologique (le nom contient la date)
        sort($archives);

        $toDelete = array_slice($archives, 0, max(0, count($archives) - $keep));

        foreach ($toDelete as $archive) {
            File::delete('archive', $archive);
            Logger::info("FileWriter: archive supprimée — {$archive}");
        }
    }
}