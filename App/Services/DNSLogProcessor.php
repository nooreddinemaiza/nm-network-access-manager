<?php

namespace App\Services;

use Core\File;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use RuntimeException;

/**
 * Traitement haute performance de logs DNS Unbound.
 *
 * Ajouts pour le système de Jobs :
 *  - processChunk(string[] $lines, string $date) : traite des lignes déjà lues
 *    par le Worker sans ouvrir de fichier. Retourne le même format que process().
 *  - streamChunk(string[] $lines, string $date) : variante streaming du chunk.
 *  - applyFilters() : logique de filtrage extraite et partagée entre les deux modes.
 *  - buildFilterContext() : pré-calcul des variables de filtrage hors boucle.
 *  - buildResult() : agrégation sites/daily partagée entre process() et processChunk().
 *  - resetStats() : réinitialisation centralisée des compteurs.
 *  - assertValidDate() : validation du paramètre $date passé aux méthodes chunk.
 */
final class DNSLogProcessor
{
    // -------------------------------------------------------------------------
    // Constantes
    // -------------------------------------------------------------------------

    private const MAX_LINE_LENGTH = 8192;

    private const DEFAULT_MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;

    private const LINE_PATTERN = '/
        ^
        (?P<timestamp>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\s]*)
        \s+[^\s]+
        \s+unbound\[\d+\]:\s+\[\d+:[^\]]+\]\s+
        (?P<level>\w+):\s+
        (?P<client_ip>[\d.:a-fA-F]+)\s+
        (?P<domain>[^\s]+)\s+
        (?P<qtype>\w+)\s+
        (?P<qclass>\w+)
    /x';

    private const DNS_LABEL_PATTERN = '/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?$/';

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    private string  $logName;
    private string  $fileType;
    private ?int    $maxFileSize     = self::DEFAULT_MAX_FILE_SIZE;
    private bool    $keepSubdomains  = false;
    private array   $excludedTerms   = [];
    private array   $requiredTerms   = [];
    private array   $excludedIps     = [];
    private array   $allowedIps      = [];
    private array   $allowedQueryTypes = [];
    private array   $allowedLevels   = [];

    private ?DateTimeImmutable $since = null;
    private ?DateTimeImmutable $until = null;

    // -------------------------------------------------------------------------
    // Diagnostic
    // -------------------------------------------------------------------------

    private int $statsLinesRead      = 0;
    private int $statsLinesAccepted  = 0;
    private int $statsLinesRejected  = 0;
    private int $statsInvalidDomains = 0;

    // =========================================================================
    // Construction
    // =========================================================================

    public function __construct(string $logName = 'pfsense_dns_today.log', string $fileType = 'log')
    {
        $this->logName  = $logName;
        $this->fileType = $fileType;
    }

    public static function for(string $logName, string $fileType = 'log'): self
    {
        return new self($logName, $fileType);
    }

    // =========================================================================
    // API fluide (inchangée)
    // =========================================================================

    public function excludeDomains(array $terms): self
    {
        $clone = clone $this;
        $clone->excludedTerms = array_merge($this->excludedTerms, array_map('strtolower', $terms));
        return $clone;
    }

    public function excludeKeywords(array $keywords): self
    {
        return $this->excludeDomains($keywords);
    }

    public function requireAnyOf(array $terms): self
    {
        $clone = clone $this;
        $clone->requiredTerms = array_merge($this->requiredTerms, array_map('strtolower', $terms));
        return $clone;
    }

    public function excludeIps(array $ips): self
    {
        $clone = clone $this;
        $clone->excludedIps = array_merge($this->excludedIps, $ips);
        return $clone;
    }

    public function onlyIps(array $ips): self
    {
        $clone = clone $this;
        $clone->allowedIps = array_merge($this->allowedIps, $ips);
        return $clone;
    }

    public function onlyQueryTypes(array $types): self
    {
        $clone = clone $this;
        $clone->allowedQueryTypes = array_map('strtoupper', $types);
        return $clone;
    }

    public function onlyLevels(array $levels): self
    {
        $clone = clone $this;
        $clone->allowedLevels = array_map('strtolower', $levels);
        return $clone;
    }

    public function keepSubdomains(bool $keep = true): self
    {
        $clone = clone $this;
        $clone->keepSubdomains = $keep;
        return $clone;
    }

    public function maxFileSize(?int $bytes): self
    {
        $clone = clone $this;
        $clone->maxFileSize = $bytes;
        return $clone;
    }

    public function since(DateTimeImmutable $date): self
    {
        $clone = clone $this;
        $clone->since = $date;
        return $clone;
    }

    public function until(DateTimeImmutable $date): self
    {
        $clone = clone $this;
        $clone->until = $date;
        return $clone;
    }

    // =========================================================================
    // Accès aux statistiques
    // =========================================================================

    public function getLinesRead(): int
    {
        return $this->statsLinesRead;
    }
    public function getLinesAccepted(): int
    {
        return $this->statsLinesAccepted;
    }
    public function getLinesRejected(): int
    {
        return $this->statsLinesRejected;
    }
    public function getInvalidDomains(): int
    {
        return $this->statsInvalidDomains;
    }

    // =========================================================================
    // API publique — fichier complet (comportement original inchangé)
    // =========================================================================

    /**
     * Lit et traite le fichier de logs complet.
     *
     * @return array{
     *   sites: array<string, array{domain:string, first_seen:string, last_seen:string, total_visits:int}>,
     *   daily: array<string, array{domain:string, visit_date:string, visit_count:int}>
     * }
     */
    public function process(): array
    {
        return $this->buildResult($this->iterateFiltered());
    }

    /**
     * Mode streaming sur fichier complet.
     *
     * @return Generator<int, array{timestamp:string, client_ip:string, domain:string, qtype:string, qclass:string, level:string}>
     */
    public function stream(): Generator
    {
        yield from $this->iterateFiltered();
    }

    // =========================================================================
    // API publique — chunk (pour le système de Jobs)
    // =========================================================================

    /**
     * Traite un tableau de lignes déjà lues depuis le fichier.
     *
     * Utilisé par ProcessDnsLogsChunkJob qui lit lui-même les lignes
     * (offset + limit) via fgets() et les passe ici sans aucun accès disque.
     *
     * Le paramètre $date est utilisé comme fallback pour la clé visit_date
     * si le timestamp ISO de la ligne est absent ou invalide.
     *
     * @param  string[] $lines Lignes brutes (avec ou sans \n terminal)
     * @param  string   $date  Date du chunk au format Y-m-d
     * @return array{
     *   sites: array<string, array{domain:string, first_seen:string, last_seen:string, total_visits:int}>,
     *   daily: array<string, array{domain:string, visit_date:string, visit_count:int}>
     * }
     *
     * @throws \InvalidArgumentException Si $date n'est pas une date Y-m-d valide
     */
    public function processChunk(array $lines, string $date): array
    {
        $this->assertValidDate($date);
        return $this->buildResult(
            $this->iterateFilteredFromLines($lines, $date),
        );
    }

    /**
     * Mode streaming sur un chunk — utile si le Job veut traiter
     * les entrées une par une sans les accumuler en mémoire.
     *
     * @param  string[] $lines
     * @return Generator<int, array{timestamp:string, client_ip:string, domain:string, qtype:string, qclass:string, level:string}>
     *
     * @throws \InvalidArgumentException Si $date n'est pas une date Y-m-d valide
     */
    public function streamChunk(array $lines, string $date): Generator
    {
        $this->assertValidDate($date);

        yield from $this->iterateFilteredFromLines($lines, $date);
    }

    // =========================================================================
    // Agrégation partagée (process + processChunk)
    // =========================================================================

    /**
     * Construit le tableau sites/daily depuis n'importe quel générateur d'entrées.
     * La clé interne '_date' portée par chaque entrée alimente visit_date.
     *
     * @param Generator<int, array<string, string>> $entries
     * @return array{
     *   sites: array<string, array{domain:string, first_seen:string, last_seen:string, total_visits:int}>,
     *   daily: array<string, array{domain:string, visit_date:string, visit_count:int}>
     * }
     */
    private function buildResult(Generator $entries): array
    {
        $sites = [];
        $daily = [];

        foreach ($entries as $entry) {
            $domain = $entry['domain'];
            $date   = $entry['_date'];

            // ── sites ─────────────────────────────────────────────────────────
            if (!isset($sites[$domain])) {
                $sites[$domain] = [
                    'domain'       => $domain,
                    'first_seen'   => $date,
                    'last_seen'    => $date,
                    'total_visits' => 0,
                ];
            }

            $sites[$domain]['total_visits']++;

            if ($date < $sites[$domain]['first_seen']) {
                $sites[$domain]['first_seen'] = $date;
            }
            if ($date > $sites[$domain]['last_seen']) {
                $sites[$domain]['last_seen'] = $date;
            }

            // ── daily ─────────────────────────────────────────────────────────
            $dailyKey = $domain . '|' . $date;

            if (!isset($daily[$dailyKey])) {
                $daily[$dailyKey] = [
                    'domain'      => $domain,
                    'visit_date'  => $date,
                    'visit_count' => 0,
                ];
            }

            $daily[$dailyKey]['visit_count']++;
        }

        return ['sites' => $sites, 'daily' => $daily];
    }

    // =========================================================================
    // Boucle interne — fichier complet (inchangée dans sa logique)
    // =========================================================================

    /**
     * @return Generator<int, array<string, string>>
     */
    private function iterateFiltered(): Generator
    {
        $this->resetStats();

        $path = File::getPath($this->fileType, $this->logName);

        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException("Fichier introuvable ou illisible : {$this->logName}");
        }

        if ($this->maxFileSize !== null) {
            $fileSize = filesize($path);
            if ($fileSize !== false && $fileSize > $this->maxFileSize) {
                throw new RuntimeException(sprintf(
                    'Fichier trop volumineux : %s octets (max : %s). ' .
                        'Utilisez maxFileSize(null) pour désactiver cette limite.',
                    number_format($fileSize),
                    number_format($this->maxFileSize)
                ));
            }
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Impossible d'ouvrir : {$this->logName}");
        }

        $ctx = $this->buildFilterContext();

        try {
            while (!feof($handle)) {
                $line = fgets($handle, self::MAX_LINE_LENGTH);
                if ($line === false || $line === '') {
                    continue;
                }

                $this->statsLinesRead++;

                $entry = $this->applyFilters($line, $ctx);
                if ($entry === null) {
                    continue;
                }

                // Date extraite du timestamp ISO (Y-m-d)
                $entry['_date'] = substr($entry['timestamp'], 0, 10);

                $this->statsLinesAccepted++;
                yield $entry;
            }
        } finally {
            fclose($handle);
        }
    }

    // =========================================================================
    // Boucle interne — chunk de lignes (nouveau, pour les Jobs)
    // =========================================================================

    /**
     * Même logique de filtrage que iterateFiltered() mais sur un tableau
     * de lignes déjà en mémoire — aucun accès disque.
     *
     * Injection de '_date' :
     *  - Si la ligne contient un timestamp ISO valide → on l'utilise (précis)
     *  - Sinon → $chunkDate comme fallback (date du fichier de logs)
     *
     * @param  string[] $lines
     * @return Generator<int, array<string, string>>
     */
    private function iterateFilteredFromLines(array $lines, string $chunkDate): Generator
    {
        $this->resetStats();

        $ctx = $this->buildFilterContext();

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            // Normalise la fin de ligne : fgets() l'ajoute, un tableau non.
            if ($line[-1] !== "\n") {
                $line .= "\n";
            }

            $this->statsLinesRead++;

            $entry = $this->applyFilters($line, $ctx);
            if ($entry === null) {
                continue;
            }

            // Priorité au timestamp ISO, fallback sur la date du chunk.
            $entry['_date'] = isset($entry['timestamp']) && strlen($entry['timestamp']) >= 10
                ? substr($entry['timestamp'], 0, 10)
                : $chunkDate;

            $this->statsLinesAccepted++;
            yield $entry;
        }
    }

    // =========================================================================
    // Filtrage — extrait et partagé entre les deux boucles
    // =========================================================================

    /**
     * Pré-calcule toutes les variables de filtrage une seule fois hors boucle.
     * Le tableau retourné est passé à chaque appel de applyFilters().
     *
     * @return array<string, mixed>
     */
    private function buildFilterContext(): array
    {
        $sinceTs = $this->since !== null
            ? $this->since->setTimezone(new DateTimeZone(TIME_ZONE))->format('Y-m-d\TH:i:sP')
            : null;

        $untilTs = $this->until !== null
            ? $this->until->setTimezone(new DateTimeZone(TIME_ZONE))->format('Y-m-d\TH:i:sP')
            : null;

        $levelNeedles = !empty($this->allowedLevels)
            ? array_map(fn(string $l) => " {$l}: ", $this->allowedLevels)
            : [];

        return [
            'sinceTs'        => $sinceTs,
            'untilTs'        => $untilTs,
            'levelNeedles'   => $levelNeedles,
            'excludedTerms'  => $this->excludedTerms,
            'requiredTerms'  => $this->requiredTerms,
            'hasExcluded'    => !empty($this->excludedTerms),
            'hasRequired'    => !empty($this->requiredTerms),
            'excludedIps'    => $this->excludedIps,
            'allowedIps'     => $this->allowedIps,
            'allowedQtypes'  => $this->allowedQueryTypes,
            'hasExcludedIps' => !empty($this->excludedIps),
            'hasAllowedIps'  => !empty($this->allowedIps),
            'hasQtypeFilter' => !empty($this->allowedQueryTypes),
            'keepSubdomains' => $this->keepSubdomains,
        ];
    }

    /**
     * Applique la chaîne de filtres à une ligne brute.
     * Retourne l'entrée parsée (sans '_date') ou null si rejetée.
     *
     * Ordre des filtres (du moins coûteux au plus coûteux) :
     *  ① strpos('unbound')     — filtre rapide sur chaîne brute
     *  ② level needles         — strpos sur chaîne brute
     *  ③ mots-clés             — strtolower + strpos (coût moyen)
     *  ④ preg_match            — regex complète (coût élevé)
     *  ④b normalisation domaine — RFC 1035
     *  ⑤ filtres IP / qtype    — comparaisons exactes post-parsing
     *  ⑥ filtre date           — comparaison ISO timezone-safe
     *
     * @param  array<string, mixed> $ctx
     * @return array<string, string>|null
     */
    private function applyFilters(string $line, array $ctx): ?array
    {
        // ① unbound
        if (strpos($line, 'unbound') === false) {
            $this->statsLinesRejected++;
            return null;
        }

        // ② niveau de log
        if (!empty($ctx['levelNeedles'])) {
            $found = false;
            foreach ($ctx['levelNeedles'] as $needle) {
                if (strpos($line, $needle) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->statsLinesRejected++;
                return null;
            }
        }

        // ③ mots-clés exclus / requis
        if ($ctx['hasExcluded'] || $ctx['hasRequired']) {
            $lower = strtolower($line);

            if ($ctx['hasExcluded']) {
                foreach ($ctx['excludedTerms'] as $term) {
                    if (strpos($lower, $term) !== false) {
                        $this->statsLinesRejected++;
                        return null;
                    }
                }
            }

            if ($ctx['hasRequired']) {
                $found = false;
                foreach ($ctx['requiredTerms'] as $term) {
                    if (strpos($lower, $term) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->statsLinesRejected++;
                    return null;
                }
            }
        }

        // ④ parsing regex
        if (!preg_match(self::LINE_PATTERN, $line, $m)) {
            $this->statsLinesRejected++;
            return null;
        }

        // ④b validation domaine
        $domain = $ctx['keepSubdomains']
            ? $this->normalizeFullDomain($m['domain'])
            : $this->normalizeDomain($m['domain']);

        if ($domain === null) {
            $this->statsInvalidDomains++;
            $this->statsLinesRejected++;
            return null;
        }

        $entry = [
            'timestamp' => $m['timestamp'],
            'client_ip' => $m['client_ip'],
            'domain'    => $domain,
            'qtype'     => strtoupper($m['qtype']),
            'qclass'    => strtoupper($m['qclass']),
            'level'     => $m['level'],
        ];

        // ⑤ filtres IP et qtype
        if ($ctx['hasExcludedIps'] && in_array($entry['client_ip'], $ctx['excludedIps'], true)) {
            $this->statsLinesRejected++;
            return null;
        }
        if ($ctx['hasAllowedIps'] && !in_array($entry['client_ip'], $ctx['allowedIps'], true)) {
            $this->statsLinesRejected++;
            return null;
        }
        if ($ctx['hasQtypeFilter'] && !in_array($entry['qtype'], $ctx['allowedQtypes'], true)) {
            $this->statsLinesRejected++;
            return null;
        }

        // ⑥ filtre de date timezone-safe
        if ($ctx['sinceTs'] !== null && $entry['timestamp'] < $ctx['sinceTs']) {
            $this->statsLinesRejected++;
            return null;
        }
        if ($ctx['untilTs'] !== null && $entry['timestamp'] > $ctx['untilTs']) {
            $this->statsLinesRejected++;
            return null;
        }

        return $entry;
    }

    // =========================================================================
    // Normalisation de domaine (inchangée)
    // =========================================================================

    private function normalizeDomain(string $raw): ?string
    {
        $domain = strtolower(rtrim($raw, '.'));

        if ($domain === '' || strlen($domain) > 253) {
            return null;
        }

        $parts = explode('.', $domain);
        $count = count($parts);

        if ($count < 2) {
            return null;
        }

        $tld = $parts[$count - 1];
        $sld = $parts[$count - 2];

        if (!$this->isValidLabel($tld) || !$this->isValidLabel($sld)) {
            return null;
        }

        return $sld . '.' . $tld;
    }

    private function normalizeFullDomain(string $raw): ?string
    {
        $domain = strtolower(rtrim($raw, '.'));

        if ($domain === '' || strlen($domain) > 253) {
            return null;
        }

        $parts = explode('.', $domain);

        if (count($parts) < 2) {
            return null;
        }

        foreach ($parts as $label) {
            if (!$this->isValidLabel($label)) {
                return null;
            }
        }

        return $domain;
    }

    private function isValidLabel(string $label): bool
    {
        if ($label === '' || strlen($label) > 63) {
            return false;
        }

        return (bool) preg_match(self::DNS_LABEL_PATTERN, $label);
    }

    // =========================================================================
    // Helpers internes
    // =========================================================================

    private function resetStats(): void
    {
        $this->statsLinesRead      = 0;
        $this->statsLinesAccepted  = 0;
        $this->statsLinesRejected  = 0;
        $this->statsInvalidDomains = 0;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertValidDate(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date format "%s": expected Y-m-d.', $date)
            );
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date value "%s".', $date)
            );
        }
    }
}
