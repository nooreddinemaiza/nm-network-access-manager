<?php

namespace App\Services;

use RuntimeException;
use Core\Database\Database;
use Core\Database\QueryBuilder;

/**
 * DnsLogRepository — Persistance et lecture des données DNS
 *
 * Tables cibles :
 *   sites            (id, domain, first_seen, last_seen, total_visits)
 *   site_journal (id, site_id, visit_date, visit_count)
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  POURQUOI HYBRIDE (QueryBuilder + SQL brut) ?                   │
 * │                                                                 │
 * │  Écriture batch  → SQL brut avec ON DUPLICATE KEY UPDATE        │
 * │    Le QueryBuilder ne supporte pas LEAST/GREATEST/+= dans       │
 * │    un upsert multi-lignes. Générer 500 placeholders via l'API   │
 * │    serait plus lent et moins lisible que le SQL direct.         │
 * │                                                                 │
 * │  Lecture         → QueryBuilder exclusivement                   │
 * │    Conditions dynamiques, pagination, jointures : c'est         │
 * │    exactement ce pour quoi il a été conçu.                      │
 * │                                                                 │
 * │  Transaction     → ManagesTransactions du QueryBuilder          │
 * └─────────────────────────────────────────────────────────────────┘
 */
final class DnsLogRepository
{
    /** Lignes par batch INSERT — équilibre mémoire / performance */
    private const BATCH_SIZE = 500;

    public function __construct(private readonly Database $db) {}

    // =========================================================================
    // Helpers internes — QueryBuilder
    // =========================================================================

    private function sites(): QueryBuilder
    {
        return new QueryBuilder($this->db, 'sites');
    }

    private function daily(): QueryBuilder
    {
        return new QueryBuilder($this->db, 'site_journal');
    }

    // =========================================================================
    // Écriture — persist()
    // =========================================================================

    /**
     * Persiste le résultat de DNSLogProcessor::process() en base.
     *
     * Étapes dans une transaction unique :
     *   ① Upsert batch → sites           (SQL brut, ON DUPLICATE KEY UPDATE)
     *   ② SELECT IN    → domain → site_id (QueryBuilder)
     *   ③ Upsert batch → site_journal (SQL brut, ON DUPLICATE KEY UPDATE)
     *
     * Idempotent : relancer avec le même log incrémente les compteurs
     * sans créer de doublons (grâce à UNIQUE KEY et l'addition SQL-side).
     *
     * @param array{
     *   sites: array<string, array{domain:string, first_seen:string, last_seen:string, total_visits:int}>,
     *   daily: array<string, array{domain:string, visit_date:string, visit_count:int}>
     * } $data  Retour de DNSLogProcessor::process()
     *
     * @return array{sites_written: int, daily_written: int}
     * @throws RuntimeException
     */
    public function persist(array $data): array
    {
        if (empty($data['sites'])) {
            return ['sites_written' => 0, 'daily_written' => 0];
        }

        // La transaction passe par ManagesTransactions du QueryBuilder
        $this->db->beginTransaction();

        try {
            // ① Upsert domaines
            $sitesWritten = $this->upsertSites(array_values($data['sites']));

            // ② Résolution domain → site_id via QueryBuilder (une seule requête)
            $domainIds = $this->fetchDomainIds(array_column($data['sites'], 'domain'));

            // ③ Upsert compteurs quotidiens
            $dailyWritten = $this->upsertDaily(array_values($data['daily']), $domainIds);

            $this->db->commitTransaction();

            return [
                'sites_written' => $sitesWritten,
                'daily_written' => $dailyWritten,
            ];
        } catch (\Throwable $e) {
            $this->db->rollbackTransaction();
            throw new RuntimeException(
                "Échec de la persistance DNS : " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    // =========================================================================
    // Écriture — upsertSites()  [SQL brut — batch multi-lignes]
    // =========================================================================

    /**
     * INSERT batch avec cumul côté SQL.
     *
     * Si le domaine existe déjà (UNIQUE KEY domain) :
     *   first_seen   → LEAST  (garde la plus ancienne)
     *   last_seen    → GREATEST (garde la plus récente)
     *   total_visits → additionne
     *
     * @param list<array{domain:string, first_seen:string, last_seen:string, total_visits:int}> $sites
     */
    private function upsertSites(array $sites): int
    {
        $written = 0;
        $pdo     = $this->db->getPdo();

        foreach (array_chunk($sites, self::BATCH_SIZE) as $batch) {
            $placeholders = implode(', ', array_fill(0, count($batch), '(?, ?, ?, ?)'));

            $sql = "INSERT INTO sites (domain, first_seen, last_seen, total_visits)
                    VALUES {$placeholders}
                    ON DUPLICATE KEY UPDATE
                        first_seen   = LEAST(first_seen,    VALUES(first_seen)),
                        last_seen    = GREATEST(last_seen,  VALUES(last_seen)),
                        total_visits = total_visits + VALUES(total_visits)";

            $params = [];
            foreach ($batch as $row) {
                $params[] = $row['domain'];
                $params[] = $row['first_seen'];
                $params[] = $row['last_seen'];
                $params[] = $row['total_visits'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $written += count($batch);
        }

        return $written;
    }

    // =========================================================================
    // Lecture — fetchDomainIds()  [QueryBuilder]
    // =========================================================================

    /**
     * Récupère domain → site_id en une seule requête via QueryBuilder.
     *
     * @param  string[]            $domains
     * @return array<string, int>  ['github.com' => 12, …]
     */
    private function fetchDomainIds(array $domains): array
    {
        if (empty($domains)) {
            return [];
        }

        $domains = array_values(array_unique($domains));

        // whereIn + pluck via QueryBuilder
        $rows = $this->sites()
            ->select('id', 'domain')
            ->whereIn('domain', $domains)
            ->get();

        // Transformer en map domain → id
        $map = [];
        foreach ($rows as $row) {
            $map[$row['domain']] = (int) $row['id'];
        }

        return $map;
    }

    // =========================================================================
    // Écriture — upsertDaily()  [SQL brut — batch multi-lignes]
    // =========================================================================

    /**
     * INSERT batch avec cumul du visit_count côté SQL.
     *
     * Si (site_id, visit_date) existe déjà (UNIQUE KEY unique_site_date) :
     *   visit_count → additionne
     *
     * @param list<array{domain:string, visit_date:string, visit_count:int}> $daily
     * @param array<string, int> $domainIds
     */
    private function upsertDaily(array $daily, array $domainIds): int
    {
        // Résoudre site_id et filtrer les éventuelles entrées orphelines
        $resolved = [];
        foreach ($daily as $row) {
            $siteId = $domainIds[$row['domain']] ?? null;
            if ($siteId === null) {
                continue;
            }
            $resolved[] = [
                'site_id'     => $siteId,
                'visit_date'  => $row['visit_date'],
                'visit_count' => $row['visit_count'],
            ];
        }

        if (empty($resolved)) {
            return 0;
        }

        $written = 0;
        $pdo     = $this->db->getPdo();

        foreach (array_chunk($resolved, self::BATCH_SIZE) as $batch) {
            $placeholders = implode(', ', array_fill(0, count($batch), '(?, ?, ?)'));

            $sql = "INSERT INTO site_journal (site_id, visit_date, visit_count)
                    VALUES {$placeholders}
                    ON DUPLICATE KEY UPDATE
                        visit_count = visit_count + VALUES(visit_count)";

            $params = [];
            foreach ($batch as $row) {
                $params[] = $row['site_id'];
                $params[] = $row['visit_date'];
                $params[] = $row['visit_count'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $written += count($batch);
        }

        return $written;
    }

    // =========================================================================
    // Lecture — méthodes de consultation  [QueryBuilder]
    // =========================================================================

    /**
     * Retourne tous les sites triés par nombre de visites décroissant.
     * Pagination intégrée.
     *
     * @return array{data: array[], total: int, per_page: int, current_page: int, last_page: int, …}
     */
    public function getTopSites(int $page = 1, int $perPage = 50): array
    {
        return $this->sites()
            ->select('id', 'domain', 'first_seen', 'last_seen', 'total_visits')
            ->orderByDesc('total_visits')
            ->paginate($page, $perPage);
    }

    /**
     * Retourne un site par son domaine exact.
     *
     * @return array{id:int, domain:string, first_seen:string, last_seen:string, total_visits:int}|null
     */
    public function findByDomain(string $domain): ?array
    {
        return $this->sites()
            ->where('domain', $domain)
            ->first();
    }

    /**
     * Retourne les visites quotidiennes d'un site sur une période donnée.
     *
     * @return array<array{visit_date:string, visit_count:int}>
     */
    public function getDailyVisits(int $siteId, string $from, string $to): array
    {
        return $this->daily()
            ->select('visit_date', 'visit_count')
            ->where('site_id', $siteId)
            ->whereBetween('visit_date', [$from, $to])
            ->orderBy('visit_date')
            ->get();
    }

    /**
     * Retourne les sites les plus actifs sur une date donnée,
     * avec jointure sites ← site_journal.
     *
     * @return array<array{domain:string, visit_count:int}>
     */
    public function getTopSitesByDate(string $date, int $limit = 20): array
    {
        return $this->daily()
            ->select('sites.domain', 'site_journal.visit_count')
            ->join('sites', 'site_journal.site_id', '=', 'sites.id')
            ->where('site_journal.visit_date', $date)
            ->orderByDesc('site_journal.visit_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Retourne les visites quotidiennes agrégées sur une période,
     * toutes les dates confondues.
     *
     * @return array<array{visit_date:string, total:int, unique_sites:int}>
     */
    public function getDailyStats(string $from, string $to): array
    {
        return $this->daily()
            ->select('visit_date')
            ->selectRaw('SUM(visit_count) AS total')
            ->selectRaw('COUNT(DISTINCT site_id) AS unique_sites')
            ->whereBetween('visit_date', [$from, $to])
            ->groupBy('visit_date')
            ->orderBy('visit_date')
            ->get();
    }

    /**
     * Recherche de domaines par mot-clé (LIKE).
     *
     * @return array<array{id:int, domain:string, total_visits:int}>
     */
    public function searchDomains(string $keyword, int $limit = 30): array
    {
        return $this->sites()
            ->select('id', 'domain', 'total_visits')
            ->where('domain', 'LIKE', '%' . $keyword . '%')
            ->orderByDesc('total_visits')
            ->limit($limit)
            ->get();
    }

    /**
     * Retourne les sites dont la dernière visite est plus ancienne que $date.
     * Utile pour du nettoyage ou de l'archivage.
     *
     * @return array<array{id:int, domain:string, last_seen:string, total_visits:int}>
     */
    public function getInactiveSitesBefore(string $date): array
    {
        return $this->sites()
            ->select('id', 'domain', 'last_seen', 'total_visits')
            ->where('last_seen', '<', $date)
            ->orderBy('last_seen')
            ->get();
    }

    /**
     * Nombre total de domaines distincts en base.
     */
    public function countDomains(): int
    {
        return $this->sites()->count();
    }

    /**
     * Nombre total de visites (somme de total_visits).
     */
    public function sumTotalVisits(): int
    {
        return (int) $this->sites()->sum('total_visits');
    }
}