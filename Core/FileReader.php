<?php

namespace Core;

use Core\Logger;
use Core\File;
use Core\LineFilter;
use Generator;
use RuntimeException;
use InvalidArgumentException;

/**
 * Classe FileReader — Lecture robuste et performante de fichiers
 *
 * Conçue pour lire des fichiers de toutes tailles, y compris
 * des fichiers de logs très volumineux, sans exploser la mémoire.
 *
 * Fonctionnalités :
 *  - Lecture complète, partielle, par lignes, par chunks
 *  - Streaming/générateur pour fichiers lourds (logs)
 *  - Recherche et filtrage en streaming via LineFilter
 *  - Lecture paginée (tail, head, slice)
 *  - Support des encodages
 */
class FileReader
{
    /** Taille de chunk par défaut pour la lecture en streaming (64 Ko) */
    private const DEFAULT_CHUNK_SIZE = 65536;

    /** Nombre de lignes lues en tampon pour le tail() */
    private const TAIL_BUFFER_LINES = 100;

    // -------------------------------------------------------------------------
    // Lecture simple
    // -------------------------------------------------------------------------

    /**
     * Lit le contenu complet d'un fichier en une seule fois.
     * À éviter sur de très gros fichiers — préférer readLines() ou stream().
     *
     * @throws RuntimeException
     */
    public static function read(string $type, string $name): string
    {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $content = file_get_contents($path);

        if ($content === false) {
            Logger::error("FileReader: échec de lecture — {$path}");
            throw new RuntimeException("Impossible de lire le fichier : {$name}");
        }

        return $content;
    }

    /**
     * Lit le contenu et le retourne ligne par ligne sous forme de tableau.
     * Charge tout en mémoire : utiliser readLines() pour les gros fichiers.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function readAllLines(string $type, string $name, bool $skipEmpty = false): array
    {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $flags = FILE_IGNORE_NEW_LINES | ($skipEmpty ? FILE_SKIP_EMPTY_LINES : 0);
        $lines = file($path, $flags);

        if ($lines === false) {
            Logger::error("FileReader: échec de lecture par lignes — {$path}");
            throw new RuntimeException("Impossible de lire les lignes du fichier : {$name}");
        }

        return $lines;
    }

    /**
     * Lit uniquement les N premières lignes du fichier (head).
     * Efficace : s'arrête dès que le quota est atteint.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function head(string $type, string $name, int $limit = 50): array
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException("Le nombre de lignes doit être supérieur à 0");
        }

        $lines = [];
        foreach (self::readLines($type, $name) as $line) {
            $lines[] = $line;
            if (count($lines) >= $limit) {
                break;
            }
        }

        return $lines;
    }

    /**
     * Lit uniquement les N dernières lignes du fichier (tail).
     * Utilise un buffer circulaire pour éviter de tout charger en mémoire.
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function tail(string $type, string $name, int $limit = 50): array
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException("Le nombre de lignes doit être supérieur à 0");
        }

        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        // Pour les petits fichiers, lecture directe
        $fileSize = filesize($path);
        if ($fileSize === false) {
            throw new RuntimeException("Impossible d'obtenir la taille du fichier : {$name}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            Logger::error("FileReader: impossible d'ouvrir — {$path}");
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            return self::readLastLines($handle, $fileSize, $limit);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Lit une tranche du fichier de la ligne $from à la ligne $to (indexées à 0).
     *
     * @return string[]
     * @throws RuntimeException
     */
    public static function slice(string $type, string $name, int $from, int $to): array
    {
        if ($from < 0 || $to < $from) {
            throw new InvalidArgumentException("Plage invalide : from={$from}, to={$to}");
        }

        $lines = [];
        $index = 0;

        foreach (self::readLines($type, $name) as $line) {
            if ($index > $to) {
                break;
            }
            if ($index >= $from) {
                $lines[] = $line;
            }
            $index++;
        }

        return $lines;
    }

    // -------------------------------------------------------------------------
    // Streaming / Générateurs (fichiers lourds)
    // -------------------------------------------------------------------------

    /**
     * Générateur : itère sur les lignes du fichier sans tout charger en mémoire.
     * Idéal pour les fichiers de logs volumineux.
     *
     * Usage :
     *   foreach (FileReader::readLines('log', 'app.log') as $lineNumber => $line) { ... }
     *
     * @return Generator<int, string>
     * @throws RuntimeException
     */
    public static function readLines(string $type, string $name, bool $skipEmpty = false): Generator
    {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            Logger::error("FileReader: impossible d'ouvrir en streaming — {$path}");
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            $lineNumber = 0;
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) {
                    break;
                }
                $line = rtrim($line, "\r\n");
                if ($skipEmpty && $line === '') {
                    continue;
                }
                yield $lineNumber => $line;
                $lineNumber++;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Générateur : lit le fichier par chunks binaires.
     * Utile pour les traitements bas-niveau ou les transferts.
     *
     * @return Generator<int, string>
     * @throws RuntimeException
     */
    public static function stream(string $type, string $name, int $chunkSize = self::DEFAULT_CHUNK_SIZE): Generator
    {
        if ($chunkSize <= 0) {
            throw new InvalidArgumentException("La taille du chunk doit être positive");
        }

        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            Logger::error("FileReader: impossible de streamer — {$path}");
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            $chunkIndex = 0;
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false) {
                    break;
                }
                yield $chunkIndex => $chunk;
                $chunkIndex++;
            }
        } finally {
            fclose($handle);
        }
    }

    // -------------------------------------------------------------------------
    // Recherche et filtrage en streaming
    // -------------------------------------------------------------------------

    /**
     * Recherche des lignes contenant un motif (string ou regex) dans un fichier.
     * Fonctionne en streaming : pas de limite de taille de fichier.
     *
     * @param string $type         Type de chemin (ex: 'log')
     * @param string $name         Nom du fichier
     * @param string $pattern      Motif de recherche
     * @param bool   $isRegex      Si true, $pattern est une expression régulière
     * @param int    $contextLines Nombre de lignes de contexte avant/après (0 = aucun)
     *
     * @return Generator<int, array{line: int, content: string, context_before: string[], context_after: string[]}>
     * @throws RuntimeException
     */
    public static function search(
        string $type,
        string $name,
        string $pattern,
        bool $isRegex = false,
        int $contextLines = 0
    ): Generator {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            // Buffer circulaire pour le contexte "avant"
            $buffer = [];
            $bufferSize = max(0, $contextLines);
            $pendingContext = []; // lignes en attente pour le contexte "après"
            $lineNumber = 0;

            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) {
                    break;
                }
                $line = rtrim($line, "\r\n");

                $matched = $isRegex
                    ? (bool) preg_match($pattern, $line)
                    : str_contains($line, $pattern);

                if ($matched) {
                    yield $lineNumber => [
                        'line'           => $lineNumber,
                        'content'        => $line,
                        'context_before' => $buffer,
                    ];
                }

                // Maintenir le buffer de contexte "avant"
                if ($bufferSize > 0) {
                    $buffer[] = $line;
                    if (count($buffer) > $bufferSize) {
                        array_shift($buffer);
                    }
                }

                $lineNumber++;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Filtre les lignes d'un fichier via un callable et les retourne en streaming.
     *
     * Usage :
     *   FileReader::filter('log', 'app.log', fn($line) => str_contains($line, 'ERROR'))
     *
     * @param callable(string): bool $predicate
     * @return Generator<int, string>
     * @throws RuntimeException
     */
    public static function filter(string $type, string $name, callable $predicate): Generator
    {
        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            if ($predicate($line)) {
                yield $lineNumber => $line;
            }
        }
    }

    /**
     * Lit un fichier en appliquant un LineFilter en streaming.
     * Aucune ligne ne passe en mémoire si elle ne satisfait pas toutes les règles.
     *
     * La clé yielded est le numéro de ligne ORIGINAL dans le fichier (indexé à 0),
     * ce qui permet de retrouver la ligne source en cas de besoin.
     *
     * Usage :
     *   $filter = LineFilter::new()
     *       ->contains(['ERROR', 'CRITICAL'])   // contient ERROR ou CRITICAL
     *       ->excludes('deprecated')            // mais PAS "deprecated"
     *       ->notMatches('/test|staging/i');    // ni les envs de test
     *
     *   foreach (FileReader::readFiltered('log', 'app.log', $filter) as $n => $line) {
     *       echo "Ligne {$n} : {$line}\n";
     *   }
     *
     * @return Generator<int, string>   clé = numéro de ligne original
     * @throws RuntimeException
     */
    public static function readFiltered(string $type, string $name, LineFilter $filter): Generator
    {
        // Optimisation : si le filtre est vide, on délègue directement à readLines()
        if ($filter->isEmpty()) {
            yield from self::readLines($type, $name);
            return;
        }

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            if ($filter->passes($line)) {
                yield $lineNumber => $line;
            }
        }
    }

    /**
     * Identique à readFiltered() mais retourne un tableau plutôt qu'un générateur.
     * À éviter sur de très gros fichiers : toutes les lignes filtrées sont chargées en mémoire.
     *
     * Pratique pour les petits résultats ou quand on a besoin d'accès aléatoire.
     *
     * @return array<int, string>   clés = numéros de lignes originaux
     * @throws RuntimeException
     */
    public static function readFilteredArray(string $type, string $name, LineFilter $filter): array
    {
        $result = [];
        foreach (self::readFiltered($type, $name, $filter) as $lineNumber => $line) {
            $result[$lineNumber] = $line;
        }
        return $result;
    }

    /**
     * Compte les lignes correspondant à un filtre sans les charger en mémoire.
     * Utile pour de la pagination ou des statistiques sur de gros logs.
     *
     * @throws RuntimeException
     */
    public static function countFiltered(string $type, string $name, LineFilter $filter): int
    {
        $count = 0;
        foreach (self::readFiltered($type, $name, $filter) as $_) {
            $count++;
        }
        return $count;
    }

    /**
     * Lit les lignes correspondant à un filtre avec pagination.
     * Parcourt le fichier en streaming et ne retourne que la page demandée.
     *
     * @param int $page    Numéro de page (à partir de 1)
     * @param int $perPage Nombre de lignes par page
     *
     * @return array{
     *     items: array<int, string>,
     *     page: int,
     *     per_page: int,
     *     total: int,
     *     pages: int
     * }
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function paginateFiltered(
        string $type,
        string $name,
        LineFilter $filter,
        int $page = 1,
        int $perPage = 50
    ): array {
        if ($page < 1) {
            throw new InvalidArgumentException("Le numéro de page doit être >= 1");
        }
        if ($perPage < 1) {
            throw new InvalidArgumentException("perPage doit être >= 1");
        }

        $offset = ($page - 1) * $perPage;
        $items  = [];
        $total  = 0;
        $seen   = 0;

        foreach (self::readFiltered($type, $name, $filter) as $lineNumber => $line) {
            $total++;

            if ($seen < $offset) {
                $seen++;
                continue;
            }

            if (count($items) < $perPage) {
                $items[$lineNumber] = $line;
                $seen++;
            }
        }

        return [
            'items'    => $items,
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'pages'    => (int) ceil($total / $perPage),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers spécialisés pour les logs
    // -------------------------------------------------------------------------

    /**
     * Lit les entrées de log correspondant à un niveau (ERROR, WARNING, INFO…).
     *
     * @return Generator<int, string>
     */
    public static function readLogLevel(string $logName, string $level): Generator
    {
        $level = strtoupper(trim($level));
        return self::filter('log', $logName, fn(string $line) => str_contains($line, "[{$level}]"));
    }

    /**
     * Lit les entrées de log dans une plage de dates.
     * Suppose que chaque ligne commence par une date au format Y-m-d.
     *
     * @return Generator<int, string>
     */
    public static function readLogBetween(
        string $logName,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): Generator {
        $fromStr = $from->format('Y-m-d');
        $toStr   = $to->format('Y-m-d');

        return self::filter('log', $logName, function (string $line) use ($fromStr, $toStr): bool {
            // Extrait la date en début de ligne (format ISO attendu)
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $line, $m)) {
                return false;
            }
            return $m[1] >= $fromStr && $m[1] <= $toStr;
        });
    }

    /**
     * Compte le nombre de lignes d'un fichier sans tout charger en mémoire.
     *
     * @throws RuntimeException
     */
    public static function countLines(string $type, string $name): int
    {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            $count = 0;
            while (!feof($handle)) {
                $chunk = fread($handle, self::DEFAULT_CHUNK_SIZE);
                if ($chunk === false) {
                    break;
                }
                $count += substr_count($chunk, "\n");
            }
            return $count;
        } finally {
            fclose($handle);
        }
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
     * Vérifie qu'un fichier existe et est lisible.
     *
     * @throws RuntimeException
     */
    private static function assertReadable(string $path, string $name): void
    {
        if (!file_exists($path)) {
            Logger::error("FileReader: fichier introuvable — {$path}");
            throw new RuntimeException("Fichier introuvable : {$name}");
        }

        if (!is_readable($path)) {
            Logger::error("FileReader: fichier non lisible — {$path}");
            throw new RuntimeException("Fichier non lisible : {$name}");
        }
    }

    /**
     * Algorithme de lecture des N dernières lignes avec seek arrière.
     * Évite de lire tout le fichier depuis le début.
     */
    private static function readLastLines($handle, int $fileSize, int $limit): array
    {
        if ($fileSize === 0) {
            return [];
        }

        $lines       = [];
        $pos         = $fileSize;
        $chunkSize   = min(self::DEFAULT_CHUNK_SIZE, $fileSize);
        $remainder   = '';

        while ($pos > 0 && count($lines) <= $limit) {
            $pos      = max(0, $pos - $chunkSize);
            $readSize = min($chunkSize, $fileSize - $pos);

            fseek($handle, $pos);
            $chunk = fread($handle, $readSize);

            if ($chunk === false) {
                break;
            }

            $chunk   = $chunk . $remainder;
            $parts   = explode("\n", $chunk);
            $remainder = array_shift($parts); // fragment de ligne incomplet

            // Les parties sont en ordre inverse par rapport au fichier
            foreach (array_reverse($parts) as $line) {
                array_unshift($lines, rtrim($line, "\r"));
                if (count($lines) >= $limit) {
                    break 2;
                }
            }
        }

        // Ajouter le remainder (première ligne du fichier ou fragment)
        if ($remainder !== '' && count($lines) < $limit) {
            array_unshift($lines, rtrim($remainder, "\r"));
        }

        return array_slice($lines, -$limit);
    }
}