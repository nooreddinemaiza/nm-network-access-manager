<?php

namespace Core\Helper;

use Core\File;
use Core\Helper\LineFilter;
use Core\Logger;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use RuntimeException;

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
 *  - Conversion générateur → tableau
 *  - Lecture par plages de lignes (ranges)
 *  - Lecture par plage + filtrage LineFilter (readRangeFiltered, readRangesFiltered, paginateRangeFiltered)
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
    // 🆕 Lecture par plages de lignes (ranges)
    // -------------------------------------------------------------------------

    /**
     * Lit plusieurs plages de lignes en une seule passe.
     * Optimisé pour éviter de relire le fichier plusieurs fois.
     *
     * @param string $type Type de fichier
     * @param string $name Nom du fichier
     * @param array<array{from: int, to: int}> $ranges Plages à lire [['from' => 0, 'to' => 9], ['from' => 50, 'to' => 59]]
     * @param bool $preserveKeys Si true, conserve les numéros de lignes originaux comme clés
     *
     * @return array<int, array<int, string>> Tableau indexé par plage contenant les lignes
     * @throws RuntimeException|InvalidArgumentException
     *
     * Usage :
     *   $ranges = [
     *       ['from' => 0, 'to' => 9],     // 10 premières lignes
     *       ['from' => 100, 'to' => 109], // lignes 100-109
     *   ];
     *   $result = FileReader::readRanges('log', 'app.log', $ranges);
     *   // $result[0] = lignes 0-9
     *   // $result[1] = lignes 100-109
     */
    public static function readRanges(
        string $type,
        string $name,
        array $ranges,
        bool $preserveKeys = false
    ): array {
        if (empty($ranges)) {
            return [];
        }

        // Valider et trier les plages
        $sortedRanges = self::validateAndSortRanges($ranges);

        $result = array_fill(0, count($ranges), []);
        $currentRangeIdx = 0;
        $currentRange = $sortedRanges[0];

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            // Si on a dépassé toutes les plages, on arrête
            if ($currentRangeIdx >= count($sortedRanges)) {
                break;
            }

            // Si on est avant la plage courante, on continue
            if ($lineNumber < $currentRange['from']) {
                continue;
            }

            // Si on est dans la plage courante, on collecte
            if ($lineNumber <= $currentRange['to']) {
                $key = $preserveKeys ? $lineNumber : count($result[$currentRange['index']]);
                $result[$currentRange['index']][$key] = $line;
            }

            // Si on a dépassé la plage courante, on passe à la suivante
            if ($lineNumber >= $currentRange['to']) {
                $currentRangeIdx++;
                if ($currentRangeIdx < count($sortedRanges)) {
                    $currentRange = $sortedRanges[$currentRangeIdx];
                }
            }
        }

        return $result;
    }

    /**
     * Lit une seule plage de lignes (version simplifiée de readRanges).
     *
     * @param string $type Type de fichier
     * @param string $name Nom du fichier
     * @param int $from Ligne de début (incluse, indexée à 0)
     * @param int $to Ligne de fin (incluse, indexée à 0)
     * @param bool $preserveKeys Si true, conserve les numéros de lignes originaux
     *
     * @return array<int, string>
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function readRange(
        string $type,
        string $name,
        int $from,
        int $to,
        bool $preserveKeys = false
    ): array {
        if ($from < 0 || $to < $from) {
            throw new InvalidArgumentException("Plage invalide : from={$from}, to={$to}");
        }

        $lines = [];

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            if ($lineNumber > $to) {
                break;
            }
            if ($lineNumber >= $from) {
                $key = $preserveKeys ? $lineNumber : count($lines);
                $lines[$key] = $line;
            }
        }

        return $lines;
    }

    // -------------------------------------------------------------------------
    // 🆕 Lecture par plage de lignes + filtrage LineFilter
    // -------------------------------------------------------------------------

    /**
     * Lit les lignes entre $from et $to (incluses) en appliquant un LineFilter.
     * Une seule passe du fichier : les lignes hors plage sont ignorées sans être évaluées.
     *
     * La clé yielded est le numéro de ligne ORIGINAL dans le fichier (indexé à 0).
     *
     * Usage :
     *   $filter = LineFilter::new()
     *       ->contains('ERROR')
     *       ->excludes('staging')
     *       ->compile();
     *
     *   foreach (FileReader::readRangeFiltered('log', 'app.log', 100, 499, $filter) as $n => $line) {
     *       echo "Ligne {$n} : {$line}\n";
     *   }
     *
     * @param int $from Ligne de début (incluse, indexée à 0)
     * @param int $to   Ligne de fin   (incluse, indexée à 0)
     *
     * @return Generator<int, string>  clé = numéro de ligne original
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function readRangeFiltered(
        string $type,
        string $name,
        int $from,
        int $to,
        LineFilter $filter
    ): Generator {
        if ($from < 0 || $to < $from) {
            throw new InvalidArgumentException("Plage invalide : from={$from}, to={$to}");
        }

        // Optimisation : si le filtre est vide, on délègue directement à readRange()
        if ($filter->isEmpty()) {
            foreach (self::readRange($type, $name, $from, $to, preserveKeys: true) as $n => $line) {
                yield $n => $line;
            }
            return;
        }

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            if ($lineNumber > $to) {
                break; // sortie anticipée : inutile de continuer
            }
            if ($lineNumber < $from) {
                continue; // avant la plage : on passe sans évaluer le filtre
            }

            if ($filter->passes($line)) {
                yield $lineNumber => $line;
            }
        }
    }

    /**
     * Identique à readRangeFiltered() mais retourne un tableau.
     * À utiliser seulement si le résultat est de taille raisonnable.
     *
     * @return array<int, string>  clés = numéros de lignes originaux
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function readRangeFilteredArray(
        string $type,
        string $name,
        int $from,
        int $to,
        LineFilter $filter
    ): array {
        return self::toArray(
            self::readRangeFiltered($type, $name, $from, $to, $filter),
            preserveKeys: true
        );
    }

    /**
     * Compte les lignes qui passent le filtre dans la plage [$from, $to].
     * Ne charge rien en mémoire.
     *
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function countRangeFiltered(
        string $type,
        string $name,
        int $from,
        int $to,
        LineFilter $filter
    ): int {
        return self::count(self::readRangeFiltered($type, $name, $from, $to, $filter));
    }

    /**
     * Lit et filtre plusieurs plages de lignes en une seule passe du fichier.
     * Chaque plage peut partager le même filtre.
     *
     * Le résultat est indexé par rang de plage (0, 1, 2…) dans l'ordre d'entrée.
     * Les clés internes sont les numéros de lignes ORIGINAUX.
     *
     * Usage :
     *   $filter = LineFilter::new()->contains('CRITICAL')->compile();
     *
     *   $ranges = [
     *       ['from' => 0,   'to' => 999],
     *       ['from' => 5000, 'to' => 5999],
     *   ];
     *
     *   $result = FileReader::readRangesFiltered('log', 'app.log', $ranges, $filter);
     *   // $result[0] = lignes CRITICAL des 1 000 premières lignes
     *   // $result[1] = lignes CRITICAL des lignes 5000-5999
     *
     * @param array<array{from: int, to: int}> $ranges
     *
     * @return array<int, array<int, string>>
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function readRangesFiltered(
        string $type,
        string $name,
        array $ranges,
        LineFilter $filter
    ): array {
        if (empty($ranges)) {
            return [];
        }

        $sortedRanges = self::validateAndSortRanges($ranges);

        // Initialise les buckets de résultat dans l'ordre d'entrée original
        $result = array_fill(0, count($ranges), []);

        $currentRangeIdx = 0;
        $currentRange    = $sortedRanges[0];

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            // Plus aucune plage à traiter : sortie anticipée
            if ($currentRangeIdx >= count($sortedRanges)) {
                break;
            }

            // Avant la plage courante : aucun traitement, pas d'évaluation du filtre
            if ($lineNumber < $currentRange['from']) {
                continue;
            }

            // Dans la plage courante : appliquer le filtre
            if ($lineNumber <= $currentRange['to']) {
                if ($filter->isEmpty() || $filter->passes($line)) {
                    $result[$currentRange['index']][$lineNumber] = $line;
                }
            }

            // Fin de la plage courante : passer à la suivante
            // Note : une même ligne peut appartenir à des plages qui se chevauchent,
            //        on avance seulement quand on dépasse le 'to'.
            if ($lineNumber >= $currentRange['to']) {
                $currentRangeIdx++;
                if ($currentRangeIdx < count($sortedRanges)) {
                    $currentRange = $sortedRanges[$currentRangeIdx];
                }
            }
        }

        return $result;
    }

    /**
     * Pagine les lignes filtrées à l'intérieur d'une plage [$from, $to].
     * Parcourt uniquement la portion de fichier concernée.
     *
     * @param int $from    Ligne de début de la plage source (indexée à 0)
     * @param int $to      Ligne de fin de la plage source (indexée à 0)
     * @param int $page    Numéro de page (commence à 1)
     * @param int $perPage Lignes par page
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
    public static function paginateRangeFiltered(
        string $type,
        string $name,
        int $from,
        int $to,
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

        foreach (self::readRangeFiltered($type, $name, $from, $to, $filter) as $lineNumber => $line) {
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
    // 🆕 Conversion générateur → tableau
    // -------------------------------------------------------------------------

    /**
     * Convertit n'importe quel générateur de lignes en tableau.
     * Utile pour matérialiser le résultat d'un streaming.
     *
     * @param Generator<int, string> $generator
     * @param bool $preserveKeys Si true, conserve les clés du générateur
     * @param int|null $limit Limite le nombre d'éléments à collecter (null = tout)
     *
     * @return array<int, string>
     *
     * Usage :
     *   $generator = FileReader::readLines('log', 'app.log');
     *   $array = FileReader::toArray($generator, preserveKeys: true, limit: 100);
     */
    public static function toArray(
        Generator $generator,
        bool $preserveKeys = true,
        ?int $limit = null
    ): array {
        if ($limit !== null && $limit <= 0) {
            throw new InvalidArgumentException("La limite doit être > 0 ou null");
        }

        $result = [];
        $count = 0;

        foreach ($generator as $key => $value) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            if ($preserveKeys) {
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }

            $count++;
        }

        return $result;
    }

    /**
     * Convertit un générateur filtré en tableau avec les numéros de lignes originaux.
     * Alias pratique pour toArray() avec preserveKeys=true.
     *
     * @param Generator<int, string> $generator
     * @param int|null $limit
     * @return array<int, string> Clés = numéros de lignes originaux
     */
    public static function collectLines(Generator $generator, ?int $limit = null): array
    {
        return self::toArray($generator, preserveKeys: true, limit: $limit);
    }

    /**
     * Compte le nombre d'éléments dans un générateur sans les stocker.
     *
     * @param Generator $generator
     * @param int|null $limit Arrête le comptage à cette limite (null = compte tout)
     * @return int
     */
    public static function count(Generator $generator, ?int $limit = null): int
    {
        $count = 0;
        foreach ($generator as $_) {
            $count++;
            if ($limit !== null && $count >= $limit) {
                break;
            }
        }
        return $count;
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
     * Lit un nombre spécifique de lignes depuis le début ou la fin du fichier.
     *
     * @param string $type Type de fichier (log, cache, etc.)
     * @param string $name Nom du fichier
     * @param bool $skipEmpty Ignorer les lignes vides
     * @param int|null $maxLines Nombre maximum de lignes à lire (null = toutes les lignes)
     * @param bool $fromEnd Lire depuis la fin du fichier (pour les dernières lignes)
     * @return Generator<int, string>
     * @throws RuntimeException
     */
    public static function readNLines(
        string $type,
        string $name,
        bool $skipEmpty = false,
        ?int $maxLines = null,
        bool $fromEnd = false
    ): Generator {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            Logger::error("FileReader: impossible d'ouvrir en streaming — {$path}");
            throw new RuntimeException("Impossible d'ouvrir le fichier : {$name}");
        }

        try {
            if ($fromEnd && $maxLines !== null) {
                $fileSize = filesize($path);
                if ($fileSize === false) {
                    throw new RuntimeException("Impossible d'obtenir la taille du fichier : {$name}");
                }
                // Lecture depuis la fin
                $lines = self::readLastLines($handle, $fileSize, $maxLines);
                foreach ($lines as $index => $line) {
                    if (!$skipEmpty || $line !== '') {
                        yield $index => $line;
                    }
                }
            } else {
                // Lecture normale depuis le début
                yield from self::readFromBeginning($handle, $skipEmpty, $maxLines);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Lit les lignes depuis le début du fichier
     */
    private static function readFromBeginning($handle, bool $skipEmpty, ?int $maxLines): Generator
    {
        $lineNumber = 0;
        $linesRead = 0;

        while (!feof($handle)) {
            if ($maxLines !== null && $linesRead >= $maxLines) {
                break;
            }

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
            $linesRead++;
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
    // 🆕 Méthodes utilitaires sur générateurs
    // -------------------------------------------------------------------------

    /**
     * Applique une transformation à chaque ligne d'un générateur.
     *
     * @param Generator<int, string> $generator
     * @param callable(string, int): string $mapper
     * @return Generator<int, string>
     *
     * Usage :
     *   $lines = FileReader::readLines('log', 'app.log');
     *   $uppercase = FileReader::map($lines, fn($line) => strtoupper($line));
     */
    public static function map(Generator $generator, callable $mapper): Generator
    {
        foreach ($generator as $key => $value) {
            yield $key => $mapper($value, $key);
        }
    }

    /**
     * Applique un filtre personnalisé sur un générateur.
     *
     * @param Generator<int, string> $generator
     * @param callable(string, int): bool $predicate
     * @return Generator<int, string>
     */
    public static function filterGenerator(Generator $generator, callable $predicate): Generator
    {
        foreach ($generator as $key => $value) {
            if ($predicate($value, $key)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Prend les N premiers éléments d'un générateur.
     *
     * @param Generator<int, string> $generator
     * @param int $n
     * @return Generator<int, string>
     */
    public static function take(Generator $generator, int $n): Generator
    {
        if ($n <= 0) {
            return;
        }

        $count = 0;
        foreach ($generator as $key => $value) {
            if ($count >= $n) {
                break;
            }
            yield $key => $value;
            $count++;
        }
    }

    /**
     * Saute les N premiers éléments d'un générateur.
     *
     * @param Generator<int, string> $generator
     * @param int $n
     * @return Generator<int, string>
     */
    public static function skip(Generator $generator, int $n): Generator
    {
        $count = 0;
        foreach ($generator as $key => $value) {
            if ($count >= $n) {
                yield $key => $value;
            }
            $count++;
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
        return self::toArray(self::readFiltered($type, $name, $filter), preserveKeys: true);
    }

    /**
     * Compte les lignes correspondant à un filtre sans les charger en mémoire.
     * Utile pour de la pagination ou des statistiques sur de gros logs.
     *
     * @throws RuntimeException
     */
    public static function countFiltered(string $type, string $name, LineFilter $filter): int
    {
        return self::count(self::readFiltered($type, $name, $filter));
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
    // 🆕 Méthodes de statistiques et analyse
    // -------------------------------------------------------------------------

    /**
     * Retourne des statistiques sur un fichier.
     *
     * @return array{
     *     size: int,
     *     lines: int,
     *     non_empty_lines: int,
     *     avg_line_length: float,
     *     max_line_length: int,
     *     min_line_length: int
     * }
     */
    public static function getStats(string $type, string $name): array
    {
        $path = self::resolvePath($type, $name);
        self::assertReadable($path, $name);

        $fileSize = filesize($path);
        $totalLines = 0;
        $nonEmptyLines = 0;
        $totalLength = 0;
        $maxLength = 0;
        $minLength = PHP_INT_MAX;

        foreach (self::readLines($type, $name) as $line) {
            $totalLines++;
            $length = mb_strlen($line);
            $totalLength += $length;

            if ($line !== '') {
                $nonEmptyLines++;
                $maxLength = max($maxLength, $length);
                $minLength = min($minLength, $length);
            }
        }

        return [
            'size'             => $fileSize,
            'lines'            => $totalLines,
            'non_empty_lines'  => $nonEmptyLines,
            'avg_line_length'  => $totalLines > 0 ? $totalLength / $totalLines : 0,
            'max_line_length'  => $maxLength === 0 ? 0 : $maxLength,
            'min_line_length'  => $minLength === PHP_INT_MAX ? 0 : $minLength,
        ];
    }

    /**
     * Trouve les N lignes les plus longues d'un fichier.
     *
     * @return array<array{line: int, length: int, content: string}>
     */
    public static function findLongestLines(string $type, string $name, int $n = 10): array
    {
        if ($n <= 0) {
            throw new InvalidArgumentException("N doit être > 0");
        }

        $longest = [];

        foreach (self::readLines($type, $name) as $lineNumber => $line) {
            $length = mb_strlen($line);

            $longest[] = [
                'line'    => $lineNumber,
                'length'  => $length,
                'content' => $line,
            ];

            // Trier et garder seulement les N plus longs
            usort($longest, fn($a, $b) => $b['length'] <=> $a['length']);
            if (count($longest) > $n) {
                array_pop($longest);
            }
        }

        return $longest;
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
        DateTimeInterface $from,
        DateTimeInterface $to
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
     * Valide et trie les plages pour readRanges().
     *
     * @param array<array{from: int, to: int}> $ranges
     * @return array<array{from: int, to: int, index: int}>
     */
    private static function validateAndSortRanges(array $ranges): array
    {
        $validated = [];

        foreach ($ranges as $index => $range) {
            if (!isset($range['from']) || !isset($range['to'])) {
                throw new InvalidArgumentException("Chaque plage doit avoir 'from' et 'to'");
            }

            $from = $range['from'];
            $to = $range['to'];

            if (!is_int($from) || !is_int($to)) {
                throw new InvalidArgumentException("'from' et 'to' doivent être des entiers");
            }

            if ($from < 0 || $to < $from) {
                throw new InvalidArgumentException("Plage invalide : from={$from}, to={$to}");
            }

            $validated[] = [
                'from'  => $from,
                'to'    => $to,
                'index' => $index,
            ];
        }

        // Trier par ordre croissant de 'from'
        usort($validated, fn($a, $b) => $a['from'] <=> $b['from']);

        return $validated;
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