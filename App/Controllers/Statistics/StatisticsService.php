<?php


namespace App\Controllers\Statistics;

use App\Models\Statistics\Statistics;
use Core\Controllers\Controller;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use DateTime;
use DateTimeInterface;

class StatisticsService extends Controller
{
    private Statistics $model;
    // Seuil : données > 2 jours utilisent les tables agrégées
    private const AGGREGATION_THRESHOLD_DAYS = 2;

    public function __construct()
    {
        $this->model = new Statistics();
    }

    /**
     * Récupère les statistiques d'un utilisateur avec filtrage par période
     * 
     * @param string|int $user Username ou user_id
     * @param DateTimeInterface|null $startDate Date de début (null = tout l'historique)
     * @param DateTimeInterface|null $endDate Date de fin (null = aujourd'hui)
     * @param string $granularity Granularité : 'day', 'month', 'year'
     * @param array $metrics Métriques à récupérer (null = toutes)
     * @return array
     */
    public function getUserStats(
        string|int $user,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day',
        ?array $metrics = null
    ): array {
        $endDate = $endDate ?? new DateTime();
        $startDate = $startDate ?? new DateTime('-30 days');

        // Normaliser les dates (début de journée / fin de journée)
        $startDate = $this->normalizeStartDate($startDate);
        $endDate = $this->normalizeEndDate($endDate);

        // Déterminer la source de données optimale
        $useAggregated = $this->shouldUseAggregated($endDate);

        switch ($granularity) {
            case 'day':
                return $useAggregated
                    ? $this->getDailyStatsFromAggregated($user, $startDate, $endDate, $metrics)
                    : $this->getDailyStatsFromRadacct($user, $startDate, $endDate, $metrics);

            case 'month':
                return $this->getMonthlyStats($user, $startDate, $endDate, $metrics);

            case 'year':
                return $this->getYearlyStats($user, $startDate, $endDate, $metrics);

            default:
                throw new \InvalidArgumentException("Granularité invalide: $granularity");
        }
    }

    /**
     * Récupère les statistiques d'un groupe
     * 
     * @param int $groupId
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
     * @param string $granularity
     * @return array
     */
    public function getGroupStats(
        int $groupId,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $granularity = 'day'
    ): array {
        $endDate = $endDate ?? new DateTime();
        $startDate = $startDate ?? new DateTime('-30 days');

        $startDate = $this->normalizeStartDate($startDate);
        $endDate = $this->normalizeEndDate($endDate);

        $useAggregated = $this->shouldUseAggregated($endDate);

        if ($granularity === 'day') {
            return $useAggregated
                ? $this->getGroupDailyStatsFromAggregated($groupId, $startDate, $endDate)
                : $this->getGroupDailyStatsFromRadacct($groupId, $startDate, $endDate);
        }

        // Pour les autres granularités, toujours utiliser les données agrégées
        return $this->getGroupStatsAggregated($groupId, $startDate, $endDate, $granularity);
    }

    /**
     * Récupère un résumé global des stats d'un utilisateur (sans période)
     * 
     * @param string|int $user
     * @return array
     */
    public function summary(Request $request)
    {
        $result = $this->model->summary();

        return Response::json($result ?? [
            'total_sessions' => 0,
            'total_time' => 0,
            'total_download' => 0,
            'total_upload' => 0,
            'total_consumption' => 0,
            'unique_devices' => 0,
            'first_login' => null,
            'last_login' => null
        ]);
    }

    /**
     * Compare deux périodes pour un utilisateur
     * 
     * @param string|int $user
     * @param DateTimeInterface $period1Start
     * @param DateTimeInterface $period1End
     * @param DateTimeInterface $period2Start
     * @param DateTimeInterface $period2End
     * @return array
     */
    public function compareUserPeriods(
        string|int $user,
        DateTimeInterface $period1Start,
        DateTimeInterface $period1End,
        DateTimeInterface $period2Start,
        DateTimeInterface $period2End
    ): array {
        $stats1 = $this->getUserStats($user, $period1Start, $period1End, 'day');
        $stats2 = $this->getUserStats($user, $period2Start, $period2End, 'day');

        // Agréger les totaux
        $total1 = $this->aggregateStats($stats1);
        $total2 = $this->aggregateStats($stats2);

        // Calculer les variations
        return [
            'period1' => $total1,
            'period2' => $total2,
            'changes' => [
                'sessions' => $this->calculateChange($total1['total_sessions'], $total2['total_sessions']),
                'time' => $this->calculateChange($total1['total_time'], $total2['total_time']),
                'consumption' => $this->calculateChange($total1['total_consumption'], $total2['total_consumption']),
            ]
        ];
    }

    /**
     * Récupère le top N utilisateurs par métrique sur une période
     * 
     * @param string $metric 'sessions', 'time', 'consumption'
     * @param int $limit
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
     * @return array
     */
    // public function getTopUsers(
    //     string $metric = 'consumption',
    //     int $limit = 10,
    //     ?DateTimeInterface $startDate = null,
    //     ?DateTimeInterface $endDate = null
    // ): array {
    //     $endDate = $endDate ?? new DateTime();
    //     $startDate = $startDate ?? new DateTime('-30 days');

    //     $metricColumn = match ($metric) {
    //         'sessions' => 'total_sessions',
    //         'time' => 'total_time',
    //         'consumption' => 'total_consumption',
    //         'download' => 'total_download',
    //         'upload' => 'total_upload',
    //         default => throw new \InvalidArgumentException("Métrique invalide: $metric")
    //     };

    //     $sql = "SELECT 
    //                 username,
    //                 SUM(total_sessions) as total_sessions,
    //                 SUM(total_time) as total_time,
    //                 SUM(total_download) as total_download,
    //                 SUM(total_upload) as total_upload,
    //                 SUM(total_consumption) as total_consumption
    //             FROM radacct_daily_stats
    //             WHERE stat_date BETWEEN :start AND :end
    //             GROUP BY username
    //             ORDER BY {$metricColumn} DESC
    //             LIMIT :limit";

    //     return $this->db->query($sql, [
    //         'start' => $startDate->format('Y-m-d'),
    //         'end' => $endDate->format('Y-m-d'),
    //         'limit' => $limit
    //     ]);
    // }

    // ========== Méthodes privées ==========

    private function shouldUseAggregated(DateTimeInterface $endDate): bool
    {
        $threshold = new DateTime('-' . self::AGGREGATION_THRESHOLD_DAYS . ' days');
        return $endDate <= $threshold;
    }

    private function normalizeStartDate(DateTimeInterface $date): DateTime
    {
        $normalized = DateTime::createFromInterface($date);
        $normalized->setTime(0, 0, 0);
        return $normalized;
    }

    private function normalizeEndDate(DateTimeInterface $date): DateTime
    {
        $normalized = DateTime::createFromInterface($date);
        $normalized->setTime(23, 59, 59);
        return $normalized;
    }

    private function resolveUsername(string|int $user): string
    {
        if (is_numeric($user)) {
            $result = $this->db->query(
                "SELECT username FROM users WHERE id = :id",
                ['id' => $user]
            );
            return $result[0]['username'] ?? throw new \RuntimeException("User not found: $user");
        }
        return $user;
    }

    private function getDailyStatsFromAggregated(
        string|int $user,
        DateTime $startDate,
        DateTime $endDate,
        ?array $metrics
    ): array {
        $username = $this->resolveUsername($user);

        $columns = $this->buildMetricsColumns($metrics);

        $sql = "SELECT 
                    stat_date as date,
                    {$columns}
                FROM radacct_daily_stats
                WHERE username = :username
                  AND stat_date BETWEEN :start AND :end
                ORDER BY stat_date ASC";

        return $this->db->query($sql, [
            'username' => $username,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ]);
    }

    private function getDailyStatsFromRadacct(
        string|int $user,
        DateTime $startDate,
        DateTime $endDate,
        ?array $metrics
    ): array {
        $username = $this->resolveUsername($user);

        $sql = "SELECT 
                    DATE(acctstarttime) as date,
                    COUNT(*) as total_sessions,
                    IFNULL(SUM(acctsessiontime), 0) as total_time,
                    IFNULL(SUM(acctinputoctets), 0) as total_download,
                    IFNULL(SUM(acctoutputoctets), 0) as total_upload,
                    IFNULL(SUM(acctinputoctets + acctoutputoctets), 0) as total_consumption,
                    COUNT(DISTINCT callingstationid) as unique_devices,
                    MIN(acctstarttime) as first_login,
                    MAX(acctstarttime) as last_login
                FROM radacct
                WHERE username = :username
                  AND acctstarttime BETWEEN :start AND :end
                GROUP BY DATE(acctstarttime)
                ORDER BY DATE(acctstarttime) ASC";

        $result = $this->db->query($sql, [
            'username' => $username,
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s')
        ]);

        return $this->filterMetrics($result, $metrics);
    }

    private function getMonthlyStats(
        string|int $user,
        DateTime $startDate,
        DateTime $endDate,
        ?array $metrics
    ): array {
        $username = $this->resolveUsername($user);

        $columns = $this->buildMetricsColumns($metrics);

        $sql = "SELECT 
                    CONCAT(stat_year, '-', LPAD(stat_month, 2, '0')) as period,
                    {$columns}
                FROM radacct_monthly_stats
                WHERE username = :username
                  AND (stat_year > :start_year OR (stat_year = :start_year AND stat_month >= :start_month))
                  AND (stat_year < :end_year OR (stat_year = :end_year AND stat_month <= :end_month))
                ORDER BY stat_year, stat_month ASC";

        return $this->db->query($sql, [
            'username' => $username,
            'start_year' => (int)$startDate->format('Y'),
            'start_month' => (int)$startDate->format('n'),
            'end_year' => (int)$endDate->format('Y'),
            'end_month' => (int)$endDate->format('n')
        ]);
    }

    private function getYearlyStats(
        string|int $user,
        DateTime $startDate,
        DateTime $endDate,
        ?array $metrics
    ): array {
        $username = $this->resolveUsername($user);

        $columns = $this->buildMetricsColumns($metrics, false);

        $sql = "SELECT 
                    stat_year as period,
                    SUM(total_sessions) as total_sessions,
                    SUM(total_time) as total_time,
                    SUM(total_download) as total_download,
                    SUM(total_upload) as total_upload,
                    SUM(total_consumption) as total_consumption,
                    MAX(unique_devices) as unique_devices
                FROM radacct_monthly_stats
                WHERE username = :username
                  AND stat_year BETWEEN :start_year AND :end_year
                GROUP BY stat_year
                ORDER BY stat_year ASC";

        return $this->db->query($sql, [
            'username' => $username,
            'start_year' => (int)$startDate->format('Y'),
            'end_year' => (int)$endDate->format('Y')
        ]);
    }

    private function getGroupDailyStatsFromAggregated(
        int $groupId,
        DateTime $startDate,
        DateTime $endDate
    ): array {
        $sql = "SELECT 
                    stat_date as date,
                    total_sessions,
                    total_time,
                    total_download,
                    total_upload,
                    total_consumption,
                    active_users,
                    unique_devices
                FROM group_daily_stats
                WHERE group_id = :group_id
                  AND stat_date BETWEEN :start AND :end
                ORDER BY stat_date ASC";

        return $this->db->query($sql, [
            'group_id' => $groupId,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ]);
    }

    private function getGroupDailyStatsFromRadacct(
        int $groupId,
        DateTime $startDate,
        DateTime $endDate
    ): array {
        $sql = "SELECT 
                    DATE(r.acctstarttime) as date,
                    COUNT(*) as total_sessions,
                    IFNULL(SUM(r.acctsessiontime), 0) as total_time,
                    IFNULL(SUM(r.acctinputoctets), 0) as total_download,
                    IFNULL(SUM(r.acctoutputoctets), 0) as total_upload,
                    IFNULL(SUM(r.acctinputoctets + r.acctoutputoctets), 0) as total_consumption,
                    COUNT(DISTINCT u.id) as active_users,
                    COUNT(DISTINCT r.callingstationid) as unique_devices
                FROM radacct r
                JOIN users u ON u.username = r.username COLLATE utf8mb4_unicode_ci
                JOIN userGroup ug ON ug.user_id = u.id
                WHERE ug.group_id = :group_id
                  AND r.acctstarttime BETWEEN :start AND :end
                GROUP BY DATE(r.acctstarttime)
                ORDER BY DATE(r.acctstarttime) ASC";

        return $this->db->query($sql, [
            'group_id' => $groupId,
            'start' => $startDate->format('Y-m-d H:i:s'),
            'end' => $endDate->format('Y-m-d H:i:s')
        ]);
    }

    private function getGroupStatsAggregated(
        int $groupId,
        DateTime $startDate,
        DateTime $endDate,
        string $granularity
    ): array {
        if ($granularity === 'month') {
            $sql = "SELECT 
                        CONCAT(YEAR(stat_date), '-', LPAD(MONTH(stat_date), 2, '0')) as period,
                        SUM(total_sessions) as total_sessions,
                        SUM(total_time) as total_time,
                        SUM(total_download) as total_download,
                        SUM(total_upload) as total_upload,
                        SUM(total_consumption) as total_consumption,
                        AVG(active_users) as avg_active_users
                    FROM group_daily_stats
                    WHERE group_id = :group_id
                      AND stat_date BETWEEN :start AND :end
                    GROUP BY YEAR(stat_date), MONTH(stat_date)
                    ORDER BY YEAR(stat_date), MONTH(stat_date) ASC";
        } else { // year
            $sql = "SELECT 
                        YEAR(stat_date) as period,
                        SUM(total_sessions) as total_sessions,
                        SUM(total_time) as total_time,
                        SUM(total_download) as total_download,
                        SUM(total_upload) as total_upload,
                        SUM(total_consumption) as total_consumption,
                        AVG(active_users) as avg_active_users
                    FROM group_daily_stats
                    WHERE group_id = :group_id
                      AND stat_date BETWEEN :start AND :end
                    GROUP BY YEAR(stat_date)
                    ORDER BY YEAR(stat_date) ASC";
        }

        return $this->db->query($sql, [
            'group_id' => $groupId,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ]);
    }

    private function buildMetricsColumns(?array $metrics, bool $includeDevices = true): string
    {
        if ($metrics === null) {
            $columns = [
                'total_sessions',
                'total_time',
                'total_download',
                'total_upload',
                'total_consumption'
            ];

            if ($includeDevices) {
                $columns[] = 'unique_devices';
            }

            return implode(', ', $columns);
        }

        return implode(', ', array_map(fn($m) => "total_$m", $metrics));
    }

    private function filterMetrics(array $data, ?array $metrics): array
    {
        if ($metrics === null) {
            return $data;
        }

        $allowedKeys = array_merge(['date', 'period'], array_map(fn($m) => "total_$m", $metrics));

        return array_map(function ($row) use ($allowedKeys) {
            return array_intersect_key($row, array_flip($allowedKeys));
        }, $data);
    }

    private function aggregateStats(array $stats): array
    {
        return [
            'total_sessions' => array_sum(array_column($stats, 'total_sessions')),
            'total_time' => array_sum(array_column($stats, 'total_time')),
            'total_download' => array_sum(array_column($stats, 'total_download')),
            'total_upload' => array_sum(array_column($stats, 'total_upload')),
            'total_consumption' => array_sum(array_column($stats, 'total_consumption')),
        ];
    }

    private function calculateChange(float $old, float $new): array
    {
        if ($old == 0) {
            return [
                'absolute' => $new,
                'percentage' => $new > 0 ? 100 : 0
            ];
        }

        $absolute = $new - $old;
        $percentage = ($absolute / $old) * 100;

        return [
            'absolute' => $absolute,
            'percentage' => round($percentage, 2)
        ];
    }

    /**
     * 🆕 Force le recalcul des stats pour une date donnée
     * Utile pour corriger des données ou après import
     * 
     * @param DateTime $date
     * @return bool
     */
    public function recalculateStatsForDate(DateTime $date): bool
    {
        try {
            $this->db->execQuery(
                "CALL update_daily_stats(?)",
                [$date->format('Y-m-d')]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 🆕 Récupère les stats en temps réel (sessions actives uniquement)
     * 
     * @param string|int $user
     * @return array
     */
    public function getLiveStats(string|int $user): array
    {
        $username = $this->resolveUsername($user);

        $sql = "SELECT 
                    COUNT(*) as active_sessions,
                    SUM(TIMESTAMPDIFF(SECOND, acctstarttime, NOW())) as current_total_time,
                    COUNT(DISTINCT callingstationid) as active_devices
                FROM radacct
                WHERE username = :username
                  AND acctstoptime IS NULL";

        $result = $this->db->query($sql, ['username' => $username]);
        return $result[0] ?? [];
    }
}
