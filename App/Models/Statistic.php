<?php

namespace App\Models;

use Core\Models\Model;
use Core\System\Session;

class Statistic extends Model
{
    private const DAILY_TABLE = 'user_stats_with_online';
    private const MONTHLY_TABLE = 'user_monthly_stats_with_online';
    private const GROUPS_DAILY = 'group_daily_stats';

    public function list(int $page = 1, int $perPage = 15, array $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        $req = $this->db->table(self::DAILY_TABLE . ' us')
            ->select(
                'us.user_id                 as id',
                'u.username',
                'u.fullname',
                'u.status                   as account_status',
                'u.expires_at',
                'u.created_at',
                'SUM(us.total_sessions)     as total_sessions',
                'SUM(us.total_time)         as total_time',
                'SUM(us.total_download)     as total_download',
                'SUM(us.total_upload)       as total_upload',
                'SUM(us.total_consumption)  as total_consumption',
                'MAX(us.unique_devices)     as unique_devices',
                'MAX(us.current_duration)   AS current_session_duration',
                'MAX(us.mac_address)        as current_mac',
                'MAX(us.ip_address)         as current_ip',
                'MAX(us.is_online)          as is_online',
                'MAX(us.active_sessions_count) as active_sessions',
                'rds_last.last_login_at',
                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS group_name',
                'GROUP_CONCAT(DISTINCT g.id) AS group_id',
            )
            ->leftJoin('users u', 'u.id', '=', 'us.user_id')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'us.user_id')
            ->leftJoin('groupes g', 'u_g.group_id', '=', 'g.id')
            ->joinRaw('LEFT JOIN (
                        SELECT 
                            user_id,
                            MAX(last_login) AS last_login_at
                        FROM radacct_daily_stats
                        GROUP BY user_id
                    ) rds_last
                    ON rds_last.user_id = us.user_id')
            ->groupBy('us.user_id');

        if (Session::getUserType() === 'moderator') {
            $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', Session::getUserId());
        }
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $search = '%' . $filters['search'] . '%';
            $req->where(function ($q) use ($search) {
                $q->where('u.username', 'LIKE', $search)
                    ->orWhere('u.fullname', 'LIKE', $search)
                    ->orWhere('us.ip_address', 'LIKE', $search);
            });
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $req->where('u.status', '=', $filters['status']);
        }
        if (isset($filters['online']) && $filters['online'] !== 'all') {
            $req->where('us.is_online', '=', $filters['online'] === 'online' ? 1 : 0);
        }

        if (!empty($filters['group']) && $filters['group'] !== 'all') {
            $req->having('group_name', 'LIKE', '%' . $filters['group'] . '%');
        }
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $req->where('us.stat_year', '=', (int) $filters['year']);
        }

        if (!empty($filters['month']) && $filters['month'] !== 'all') {
            $req->where('us.stat_month', '=', (int) $filters['month']);
        }
        $allowedSorts = [
            'total_consumption',
            'total_time',
            'total_sessions',
            'total_download',
            'total_upload'
        ];

        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts)
            ? $filters['sort_by']
            : 'total_consumption';

        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $req->orderBy($sortBy, $sortOrder);

        $total = count((clone $req)->get());
        $results = $req->limit($perPage)
            ->offset($offset)
            ->get();
        return [
            'data'        => $results ?? [],
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
    public function daily(int $page = 1, int $perPage = 15, array $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        $req = $this->db->table(self::DAILY_TABLE . ' us')
            ->groupBy('us.user_id')
            ->select(
                'SUM(total_sessions) as total_sessions',
                'SUM(total_time) as total_time',
                'SUM(total_download) as total_download',
                'SUM(total_upload) as total_upload',
                'SUM(total_consumption) as total_consumption',
                'MAX(unique_devices) as unique_devices',
                'MAX(last_login) as last_login_at',
                'MAX(current_duration) AS current_session_duration',
                'us.username',
                'us.mac_address as current_ip',
                'us.ip_address as current_mac',
                'us.user_id as id',
                'us.is_online',
                'us.active_sessions_count as active_sessions',
                'u.fullname',
                'u.status as account_status',
                'u.expires_at',
                'u.created_at',
                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS `group_name`',
                'GROUP_CONCAT(DISTINCT g.id) AS group_id',
            )
            ->leftJoin('users u', 'u.id', '=', 'us.user_id')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'us.user_id')
            ->leftJoin('groupes g', 'u_g.group_id', '=', 'g.id');

        if (Session::getUserType() == 'moderator') {
            $req = $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', Session::getUserId());
        }

        // Filtre recherche (min 3 caractères côté serveur aussi)
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $search = '%' . $filters['search'] . '%';
            $req = $req->where(function ($q) use ($search) {
                $q->where('us.username', 'LIKE', $search)
                    ->orWhere('u.fullname', 'LIKE', $search)
                    ->orWhere('us.ip_address', 'LIKE', $search);
            });
        }

        // Filtre statut
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $req = $req->where('u.status', '=', $filters['status']);
        }

        // Filtre en ligne
        if (isset($filters['online']) && $filters['online'] !== 'all') {
            $req = $req->where('us.is_online', '=', $filters['online'] === 'online' ? 1 : 0);
        }

        // Filtre groupe
        if (!empty($filters['group']) && $filters['group'] !== 'all') {
            $req = $req->having('group_name', 'LIKE', '%' . $filters['group'] . '%');
        }

        if (!empty($filters['day']) && $filters['day'] !== 'all') {
            $req->where('us.stat_date', '=',  $filters['day']);
        }
        // Tri
        $allowedSorts = ['total_consumption', 'total_time', 'total_sessions', 'total_download', 'total_upload', 'last_login_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'total_consumption';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        $req = $req->orderBy($sortBy, $sortOrder);

        // Compter le total (clone avant limit/offset)
        $total = count((clone $req)->get());

        // Pagination
        $results = $req->limit($perPage)->offset($offset)->get();

        return [
            'data'         => $results ?? [],
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => (int) ceil($total / $perPage),
        ];
    }
    public function range(int $page = 1, int $perPage = 15, array $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        // Valider les dates
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo   = $filters['date_to'] ?? '';

        // Détermine si on utilise la table daily ou monthly
        // Si la plage est <= 31 jours → daily, sinon → monthly agrégé
        $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
        $useDaily = $daysDiff <= 31;

        if ($useDaily) {
            $table = self::DAILY_TABLE . ' us';
            $dateCol = 'us.stat_date';
        } else {
            // Pour monthly, on filtre sur year+month
            $table = self::MONTHLY_TABLE . ' us';
            $dateCol = null; // filtrage par year/month ci-dessous
        }

        $req = $this->db->table($table)
            ->groupBy('us.user_id')
            ->select(
                'us.user_id                 as id',
                'u.username',
                'u.fullname',
                'u.status                   as account_status',
                'u.expires_at',
                'u.created_at',
                'SUM(us.total_sessions)     as total_sessions',
                'SUM(us.total_time)         as total_time',
                'SUM(us.total_download)     as total_download',
                'SUM(us.total_upload)       as total_upload',
                'SUM(us.total_consumption)  as total_consumption',
                'MAX(us.unique_devices)     as unique_devices',
                'MAX(us.is_online)          as is_online',
                'MAX(us.active_sessions_count) as active_sessions',
                'MAX(us.current_duration)   as current_session_duration',
                'MAX(us.mac_address)        as current_mac',
                'MAX(us.ip_address)         as current_ip',
                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS group_name',
                'GROUP_CONCAT(DISTINCT g.id) AS group_id',
            )
            ->leftJoin('users u', 'u.id', '=', 'us.user_id')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'us.user_id')
            ->leftJoin('groupes g', 'u_g.group_id', '=', 'g.id');

        if ($useDaily) {
            $req->where('us.stat_date', '>=', $dateFrom)
                ->where('us.stat_date', '<=', $dateTo);
        } else {
            // Filtrer par plage year-month
            $fromYear  = date('Y', strtotime($dateFrom));
            $fromMonth = date('m', strtotime($dateFrom));
            $toYear    = date('Y', strtotime($dateTo));
            $toMonth   = date('m', strtotime($dateTo));

            $req->whereRaw(
                "(us.stat_year > ? OR (us.stat_year = ? AND us.stat_month >= ?))",
                [$fromYear, $fromYear, (int)$fromMonth]
            )->whereRaw(
                "(us.stat_year < ? OR (us.stat_year = ? AND us.stat_month <= ?))",
                [$toYear, $toYear, (int)$toMonth]
            );
        }

        // Filtres communs
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $search = '%' . $filters['search'] . '%';
            $req->where(function ($q) use ($search) {
                $q->where('u.username', 'LIKE', $search)
                    ->orWhere('u.fullname', 'LIKE', $search);
            });
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $req->where('u.status', '=', $filters['status']);
        }
        if (isset($filters['online']) && $filters['online'] !== 'all') {
            $req->where('us.is_online', '=', $filters['online'] === 'online' ? 1 : 0);
        }
        if (!empty($filters['group']) && $filters['group'] !== 'all') {
            $req->having('group_name', 'LIKE', '%' . $filters['group'] . '%');
        }
        if (Session::getUserType() === 'moderator') {
            $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', Session::getUserId());
        }

        $allowedSorts = ['total_consumption', 'total_time', 'total_sessions', 'total_download', 'total_upload'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'total_consumption';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $req->orderBy($sortBy, $sortOrder);

        $total   = count((clone $req)->get());
        $results = $req->limit($perPage)->offset($offset)->get();

        return [
            'data'        => $results ?? [],
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
    public function groups(?array $ids = [])
    {
        $req = $this->db->table(self::GROUPS_DAILY . ' gd')
            ->select(
                'SUM(gd.total_sessions) as total_sessions',
                'SUM(gd.total_time) as total_time',
                'SUM(gd.total_download) as total_download',
                'SUM(gd.total_upload) as total_upload',
                'SUM(gd.total_consumption) as total_consumption',
                'SUM(gd.active_users) as active_users',
                'SUM(gd.unique_devices) as unique_devices',
                'g.name as group_name',
                'g.id group_id',
            )
            ->leftJoin('groupes g', 'gd.group_id', '=', 'g.id')
            ->groupBy('group_id');
        if ($ids) {
            $req->whereIn('group_id', $ids);
        }
        return $req->get();
    }
    public function totalGroups(?array $ids = null): int
    {
        $req = $this->db->table(self::GROUPS_DAILY . ' gd')
            ->select("COUNT(DISTINCT gd.group_id) AS total_groups");

        if (!empty($ids)) {
            $req->whereIn('gd.group_id', $ids);
        }

        $result = $req->first();
        return (int) ($result['total_groups'] ?? 0);
    }
    private function buildPeriodSubquery(array $filters): array
    {
        $where = '';
        $params = [];

        $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
        $hasYear      = !empty($filters['year']) && $filters['year'] !== 'all';
        $hasMonth     = !empty($filters['month']) && $filters['month'] !== 'all';

        if ($hasDateRange) {
            $daysDiff = (strtotime($filters['date_to']) - strtotime($filters['date_from'])) / 86400;
            if ($daysDiff <= 31) {
                $where    = "WHERE stat_date >= ? AND stat_date <= ?";
                $params   = [$filters['date_from'], $filters['date_to']];
            } else {
                $fromYear  = date('Y', strtotime($filters['date_from']));
                $fromMonth = (int) date('m', strtotime($filters['date_from']));
                $toYear    = date('Y', strtotime($filters['date_to']));
                $toMonth   = (int) date('m', strtotime($filters['date_to']));
                $where     = "WHERE (stat_year > ? OR (stat_year = ? AND stat_month >= ?))
                            AND (stat_year < ? OR (stat_year = ? AND stat_month <= ?))";
                $params    = [$fromYear, $fromYear, $fromMonth, $toYear, $toYear, $toMonth];
            }
        } elseif ($hasYear && $hasMonth) {
            $where  = "WHERE stat_year = ? AND stat_month = ?";
            $params = [(int) $filters['year'], (int) $filters['month']];
        } elseif ($hasYear) {
            $where  = "WHERE stat_year = ?";
            $params = [(int) $filters['year']];
        }

        return ['clause' => $where, 'params' => $params];
    }

    public function listTotal(?array $ids = null, array $filters = [], ?int $groupId = null)
    {
        $req = $this->db->table('users u')
            ->leftJoin(self::DAILY_TABLE . ' uc', 'uc.user_id', '=', 'u.id')
            ->select(
                "COUNT(DISTINCT u.id) AS total_users",
                "COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) AS active_users",
                "COUNT(DISTINCT CASE WHEN u.status = 'suspended' THEN u.id END) AS suspended_users",
                "COUNT(DISTINCT CASE WHEN u.status = 'expired' THEN u.id END) AS expired_users",
                "COUNT(DISTINCT CASE WHEN uc.is_online = 1 THEN u.id END) AS online_users",
                "COALESCE(SUM(uc.total_consumption), 0) AS total_consumption_bytes",
                "COALESCE(SUM(uc.total_upload), 0) AS total_upload_bytes",
                "COALESCE(SUM(uc.total_download), 0) AS total_download_bytes",
                "COALESCE(SUM(uc.total_time), 0) AS total_time_seconds",
                "COALESCE(SUM(uc.total_sessions), 0) AS total_sessions",
                "COALESCE(MAX(uc.unique_devices), 0) AS total_unique_devices",
            );

        // Filtre période
        $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
        $hasYear      = !empty($filters['year']) && $filters['year'] !== 'all';
        $hasMonth     = !empty($filters['month']) && $filters['month'] !== 'all';

        if ($hasDateRange) {
            $daysDiff = (strtotime($filters['date_to']) - strtotime($filters['date_from'])) / 86400;
            if ($daysDiff <= 31) {
                $req->where('uc.stat_date', '>=', $filters['date_from'])
                    ->where('uc.stat_date', '<=', $filters['date_to']);
            } else {
                $fromYear  = date('Y', strtotime($filters['date_from']));
                $fromMonth = (int) date('m', strtotime($filters['date_from']));
                $toYear    = date('Y', strtotime($filters['date_to']));
                $toMonth   = (int) date('m', strtotime($filters['date_to']));
                $req->whereRaw(
                    "(uc.stat_year > ? OR (uc.stat_year = ? AND uc.stat_month >= ?))",
                    [$fromYear, $fromYear, $fromMonth]
                )->whereRaw(
                    "(uc.stat_year < ? OR (uc.stat_year = ? AND uc.stat_month <= ?))",
                    [$toYear, $toYear, $toMonth]
                );
            }
        } elseif ($hasYear) {
            $req->where('uc.stat_year', '=', (int) $filters['year']);
            if ($hasMonth) {
                $req->where('uc.stat_month', '=', (int) $filters['month']);
            }
        }

        // Filtre groupe
        if ($groupId !== null) {
            $req->join('userGroup ug2', 'ug2.user_id', '=', 'u.id')
                ->where('ug2.group_id', '=', $groupId);
        } elseif (!empty($ids)) {
            $req->join('userGroup ug', 'ug.user_id', '=', 'u.id')
                ->whereIn('ug.group_id', $ids);
        }

        return $req->get() ?? [];
    }

    public function mostConsumer(?array $ids = null, array $filters = [], ?int $groupId = null)
    {
        $period = $this->buildPeriodSubquery($filters);
        $whereClause = $period['clause'];
        $params      = $period['params'];

        $req = $this->db->table('users u')
            ->joinRaw("LEFT JOIN (SELECT 
                        user_id,
                        SUM(total_consumption) AS total_consumption,
                        SUM(total_upload) AS total_upload,
                        SUM(total_download) AS total_download,
                        SUM(total_sessions) AS total_sessions,
                        MAX(last_login) AS last_activity_at
                    FROM " . self::DAILY_TABLE . "
                    {$whereClause}
                    GROUP BY user_id) uc ON uc.user_id = u.id", $params)
            ->select(
                "u.id",
                "u.fullname",
                "u.username",
                "IFNULL(uc.total_consumption, 0) AS total_consumption",
                "IFNULL(uc.total_upload, 0) AS total_download",
                "IFNULL(uc.total_download, 0) AS total_upload",
                "IFNULL(uc.total_sessions, 0) AS total_sessions",
                "uc.last_activity_at"
            );

        if ($groupId !== null) {
            $req->join('userGroup ug2', 'ug2.user_id', '=', 'u.id')
                ->where('ug2.group_id', '=', $groupId);
        } elseif (!empty($ids)) {
            $req->join('userGroup ug', 'ug.user_id', '=', 'u.id')
                ->whereIn('ug.group_id', $ids);
        }

        $req->orderByDesc('total_consumption');
        return $req->first() ?? [];
    }

    public function mostConsumerGroup(?array $ids = null, array $filters = [], ?int $groupId = null)
    {
        $period = $this->buildPeriodSubquery($filters);
        $whereClause = $period['clause'];
        $params      = $period['params'];

        $req = $this->db->table('groupes g')
            ->joinRaw("LEFT JOIN (SELECT 
                        group_id,
                        SUM(total_consumption) AS total_consumption,
                        SUM(total_upload) AS total_upload,
                        SUM(total_download) AS total_download,
                        SUM(total_sessions) AS total_sessions
                    FROM " . self::GROUPS_DAILY . "
                    {$whereClause}
                    GROUP BY group_id) gs ON gs.group_id = g.id", $params)
            ->joinRaw("LEFT JOIN (SELECT 
                        group_id,
                        COUNT(DISTINCT user_id) AS total_users
                    FROM userGroup
                    GROUP BY group_id) ug ON ug.group_id = g.id")
            ->select(
                'g.id AS group_id',
                'g.name AS group_name',
                'IFNULL(gs.total_consumption, 0) AS total_consumption',
                'IFNULL(gs.total_upload, 0) AS total_download',
                'IFNULL(gs.total_download, 0) AS total_upload',
                'IFNULL(gs.total_sessions, 0) AS total_sessions',
                'IFNULL(ug.total_users, 0) AS total_users'
            )
            ->orderByDesc('total_consumption');

        if ($groupId !== null) {
            $req->where('g.id', '=', $groupId);
        } elseif (!empty($ids)) {
            $req->whereIn('g.id', $ids);
        }

        return $req->first() ?? [];
    }
    public function mostVisitedSite()
    {
        $query = $this->db->table('sites')
            ->select(['domain', 'total_visits'])
            ->orderBy('total_visits', 'DESC')
            ->limit(1);
        return $query->first();
    }
    public function sites(int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $req = $this->db->table('sites s')
            ->select(
                's.id',
                's.domain',
                's.total_visits',
                's.first_seen  AS first_visit',
                's.last_seen   AS last_visit',
            );

        // Recherche (min 3 chars)
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $req->where('s.domain', 'LIKE', '%' . $filters['search'] . '%');
        }

        // Tri
        $allowedSorts = ['domain', 'total_visits', 'first_seen', 'last_seen'];
        $sortBy    = in_array($filters['sort_by'] ?? '', $allowedSorts)
            ? $filters['sort_by']
            : 'total_visits';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $req->orderBy($sortBy, $sortOrder);

        $total   = count((clone $req)->get());
        $results = $req->limit($perPage)->offset($offset)->get();

        return [
            'data'        => $results ?? [],
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
    public function delete(string $type, ?int $targetId, array $filters = []): bool
    {
        match ($type) {
            'users'  => $this->deleteUsers($targetId, $filters),
            'groups' => $this->deleteGroups($targetId, $filters),
            'sites'  => $this->deleteSites($filters),
            default  => throw new \InvalidArgumentException("Type invalide : {$type}"),
        };

        return true;
    }

    // ─── Users ────────────────────────────────────────────────────────────────────

    private function deleteUsers(int $userId, array $filters): void
    {
        $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
        $hasDay       = !empty($filters['day']) && $filters['day'] !== 'all';
        $hasYear      = !empty($filters['year']) && $filters['year'] !== 'all';
        $hasMonth     = !empty($filters['month']) && $filters['month'] !== 'all';

        if ($hasDay) {
            $this->db->table('radacct_daily_stats')
                ->where('user_id', '=', $userId)
                ->where('stat_date', '=', $filters['day'])
                ->delete();
        } elseif ($hasDateRange) {
            $this->db->table('radacct_daily_stats')
                ->where('user_id', '=', $userId)
                ->where('stat_date', '>=', $filters['date_from'])
                ->where('stat_date', '<=', $filters['date_to'])
                ->delete();

            $daysDiff = (strtotime($filters['date_to']) - strtotime($filters['date_from'])) / 86400;
            if ($daysDiff > 31) {
                [$fromYear, $fromMonth] = $this->parseYearMonth($filters['date_from']);
                [$toYear,   $toMonth]   = $this->parseYearMonth($filters['date_to']);

                $this->db->table('radacct_monthly_stats')
                    ->where('user_id', '=', $userId)
                    ->whereRaw("(stat_year > ? OR (stat_year = ? AND stat_month >= ?))", [$fromYear, $fromYear, $fromMonth])
                    ->whereRaw("(stat_year < ? OR (stat_year = ? AND stat_month <= ?))", [$toYear,   $toYear,   $toMonth])
                    ->delete();
            }
        } elseif ($hasYear) {
            $this->db->table('radacct_daily_stats')
                ->where('user_id', '=', $userId)
                ->whereRaw('YEAR(stat_date) = ?', [(int) $filters['year']])
                ->when($hasMonth, fn($q) => $q->whereRaw('MONTH(stat_date) = ?', [(int) $filters['month']]))
                ->delete();

            $this->db->table('radacct_monthly_stats')
                ->where('user_id', '=', $userId)
                ->where('stat_year', '=', (int) $filters['year'])
                ->when($hasMonth, fn($q) => $q->where('stat_month', '=', (int) $filters['month']))
                ->delete();
        } else {
            $this->db->table('radacct_daily_stats')
                ->where('user_id', '=', $userId)
                ->delete();

            $this->db->table('radacct_monthly_stats')
                ->where('user_id', '=', $userId)
                ->delete();
        }
    }

    // ─── Groups ───────────────────────────────────────────────────────────────────

    private function deleteGroups(int $groupId, array $filters): void
    {
        $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
        $hasDay       = !empty($filters['day']) && $filters['day'] !== 'all';
        $hasYear      = !empty($filters['year']) && $filters['year'] !== 'all';
        $hasMonth     = !empty($filters['month']) && $filters['month'] !== 'all';

        // group_daily_stats : seule table disponible (pas de monthly pour les groupes)
        $req = $this->db->table('group_daily_stats')
            ->where('group_id', '=', $groupId);

        if ($hasDay) {
            $req->where('stat_date', '=', $filters['day']);
        } elseif ($hasDateRange) {
            $req->where('stat_date', '>=', $filters['date_from'])
                ->where('stat_date', '<=', $filters['date_to']);
        } elseif ($hasYear) {
            $req->whereRaw('YEAR(stat_date) = ?', [(int) $filters['year']]);
            if ($hasMonth) {
                $req->whereRaw('MONTH(stat_date) = ?', [(int) $filters['month']]);
            }
        }
        // Sinon : suppression totale du groupe (pas de clause date)

        $req->delete();
    }

    // ─── Sites ────────────────────────────────────────────────────────────────────

    private function deleteSites(array $filters): void
    {
        $hasDateRange = !empty($filters['date_from']) && !empty($filters['date_to']);
        $hasDay       = !empty($filters['day']) && $filters['day'] !== 'all';
        $hasYear      = !empty($filters['year']) && $filters['year'] !== 'all';
        $hasMonth     = !empty($filters['month']) && $filters['month'] !== 'all';

        $req = $this->db->table('sites');

        if ($hasDay) {
            $req->where('last_seen', '=', $filters['day']);
        } elseif ($hasDateRange) {
            $req->where('last_seen', '>=', $filters['date_from'])
                ->where('last_seen', '<=', $filters['date_to']);
        } elseif ($hasYear) {
            $req->whereRaw('YEAR(last_seen) = ?', [(int) $filters['year']]);
            if ($hasMonth) {
                $req->whereRaw('MONTH(last_seen) = ?', [(int) $filters['month']]);
            }
        }
        // Sinon : suppression totale de tous les sites

        $req->delete();
    }

    // ─── Utilitaire ───────────────────────────────────────────────────────────────

    private function parseYearMonth(string $date): array
    {
        return [
            (int) date('Y', strtotime($date)),
            (int) date('m', strtotime($date)),
        ];
    }
}
