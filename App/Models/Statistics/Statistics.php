<?php

namespace App\Models\Statistics;

use Core\Models\Model;
use Core\System\Session;

class Statistics extends Model
{
    private const DAILY_TABLE = 'user_stats_with_online';
    private const MONTHLY_TABLE = 'radacct_monthly_stats';
    public function summary()
    {
        $req = $this->db->table(self::DAILY_TABLE . ' us')
            ->groupBy('us.user_id')
            ->select(
                "SUM(total_sessions) as total_sessions,
                     SUM(total_time) as total_time,
                     SUM(total_download) as total_download,
                     SUM(total_upload) as total_upload,
                     SUM(total_consumption) as total_consumption,
                     MAX(unique_devices) as unique_devices,
                     MAX(last_login) as last_login_at,
                     MAX(current_duration) AS current_session_duration,
                     us.username,
                     us.mac_address as current_ip,
                     us.ip_address as current_mac,
                     us.user_id as id,
                     us.is_online,
                     us.active_sessions_count as active_sessions,
                     u.fullname,
                     u.status as account_status,
                     u.expires_at,
                     u.created_at,
                     GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS `group_name`,
                     GROUP_CONCAT(DISTINCT g.id) AS group_id",
            )
            ->leftJoin('users u', 'u.id', '=', 'us.user_id')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'us.user_id')
            ->leftJoin('groupes g', 'u_g.group_id', '=', 'g.id');
        if (Session::getUserType() == 'moderator') {
            $req = $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', Session::getUserId());
        }
        $result = $req->get();
        return $result ?? [];
    }
    


    public function listForAdminA()
    {
        $req = $this->db->table(self::DAILY_TABLE . ' us')
            ->groupBy('us.user_id')
            ->select(
                "SUM(total_sessions) as total_sessions,
                     SUM(total_time) as total_time,
                     SUM(total_download) as total_download,
                     SUM(total_upload) as total_upload,
                     SUM(total_consumption) as total_consumption,
                     MAX(unique_devices) as unique_devices,
                     MAX(last_login) as last_login_at,
                     MAX(current_duration) AS current_session_duration,
                     us.username,
                     us.mac_address as current_ip,
                     us.ip_address as current_mac,
                     us.user_id as id,
                     us.is_online,
                     us.active_sessions_count as active_sessions,
                     u.fullname,
                     u.status as account_status,
                     u.expires_at,
                     u.created_at,
                     GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS `group_name`,
                     GROUP_CONCAT(DISTINCT g.id) AS group_id",
            )
            ->leftJoin('users u', 'u.id', '=', 'us.user_id')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'us.user_id')
            ->leftJoin('groupes g', 'u_g.group_id', '=', 'g.id')
            ->orderBy('total_consumption', 'DESC');
        if (Session::getUserType() == 'moderator') {
            $req = $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', Session::getUserId());
        }
        $result = $req->get();
        return $result ?? [];
    }

    public function listTotals()
    {
        $tablelist = 'users u';
        $joins = '
            LEFT JOIN user_stats us ON us.id = u.id
            LEFT JOIN user_consumption uc 
            ON uc.username COLLATE utf8mb4_0900_ai_ci = u.username COLLATE utf8mb4_0900_ai_ci
        ';

        $columns = "
            COUNT(DISTINCT u.id) AS total_users,
            COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) AS active_users,
            COUNT(DISTINCT CASE WHEN us.is_online = 1 THEN u.id END) AS online_users,
            SUM(uc.total_consumption) AS total_consumption_bytes,
            SUM(uc.total_upload) AS total_upload_bytes,
            SUM(uc.total_download) AS total_download_bytes,
            SUM(us.unique_devices) AS total_unique_devices
        ";

        $result = $this->db->select(
            table: $tablelist,
            columns: $columns,
            joins: $joins,
        );
        if ($result) {
            if (isset($result[0]['total_connection_seconds'])) {
                $result[0]['total_connection_hours'] = round($result[0]['total_connection_seconds'] / 3600, 2);
            }
            if (isset($result[0]['avg_session_seconds'])) {
                $result[0]['avg_session_hours'] = round($result[0]['avg_session_seconds'] / 3600, 2);
            }
        }

        return $result ?? [];
    }
    public function listTotalAdmin(array $ids)
    {
        $tablelist = 'users u';
        $joins = '
        LEFT JOIN user_stats us ON us.id = u.id
        LEFT JOIN user_consumption uc 
        ON uc.username COLLATE utf8mb4_0900_ai_ci = u.username COLLATE utf8mb4_0900_ai_ci
        LEFT JOIN userGroup ug ON ug.user_id = u.id
        LEFT JOIN groupes g ON g.id = ug.group_id
        ';

        $columns = "
        COUNT(DISTINCT u.id) AS total_users,
        COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) AS active_users,
        COUNT(DISTINCT CASE WHEN us.is_online = 1 THEN u.id END) AS online_users,
        SUM(uc.total_consumption) AS total_consumption_bytes,
        SUM(uc.total_upload) AS total_upload_bytes,
        SUM(uc.total_download) AS total_download_bytes,

        SUM(us.unique_devices) AS total_unique_devices
        ";

        $conditions = " g.id IN (:ids)";
        $params = ['ids' => $ids];
        $result = $this->db->select(
            table: $tablelist,
            columns: $columns,
            joins: $joins,
            conditions: $conditions,
            params: $params,
        );
        return $result ? $result : [];
    }
    public function groupA(?array $ids = [])
    {
        $conditions = '';
        $params = [];
        if ($ids) {
            $conditions = " group_id IN (:ids)";
            $params = ['ids' => $ids];
        }
        $result = $this->db->select(
            table: 'group_stats',
            conditions: $conditions,
            params: $params,
        );
        return $result ? $result : [];
    }

    public function consumerA(?array $ids = null)
    {
        $tablelist = 'users u';
        $joins = 'LEFT JOIN radacct r ON r.username COLLATE utf8mb4_unicode_ci = u.username COLLATE utf8mb4_unicode_ci';

        $columns = "
        u.id,
        u.fullname,
        u.username,
        SUM(IFNULL(r.acctinputoctets, 0) + IFNULL(r.acctoutputoctets, 0)) AS total_consumption,
        SUM(IFNULL(r.acctinputoctets, 0)) AS total_upload,
        SUM(IFNULL(r.acctoutputoctets, 0)) AS total_download,
        COUNT(r.acctsessionid) AS total_sessions,
        MAX(r.acctstarttime) AS last_activity_at,
        ";

        $conditions = '';
        $params = [];

        // Si on filtre par groupes
        if ($ids && count($ids) > 0) {
            $joins .= ' 
            INNER JOIN userGroup ug ON ug.user_id = u.id
        ';
            // Préparer un placeholder par id pour la requête paramétrée
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $conditions = "ug.group_id IN ($placeholders)";
            $params = $ids;
        }

        // Lancer la requête
        $result = $this->db->select(
            table: $tablelist,
            columns: $columns,
            joins: $joins,
            groupBy: 'u.id, u.fullname, u.username',
            conditions: $conditions,
            orderBy: 'total_consumption DESC',
            params: $params,
            limit: 1,
        );

        return $result ? $result[0] : [];
    }
    public function mostConsumerGroupA(?array $ids = null)
    {
        $tablelist = 'groupes g';

        // JOINS avec COLLATE pour éviter les erreurs de collation
        $joins = '
        INNER JOIN userGroup ug ON ug.group_id = g.id
        INNER JOIN users u ON u.id = ug.user_id
        LEFT JOIN radacct r ON r.username COLLATE utf8mb4_unicode_ci = u.username COLLATE utf8mb4_unicode_ci
        ';

        $columns = "
            g.id AS group_id,
            g.name AS group_name,
            SUM(IFNULL(r.acctinputoctets, 0) + IFNULL(r.acctoutputoctets, 0)) AS total_consumption,
            SUM(IFNULL(r.acctinputoctets, 0)) AS total_upload,
            SUM(IFNULL(r.acctoutputoctets, 0)) AS total_download,
            COUNT(DISTINCT u.id) AS total_users,
            COUNT(r.acctsessionid) AS total_sessions
        ";

        $conditions = '';
        $params = [];

        // Filtrer par plusieurs groupes si fournis
        if ($ids && count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $conditions = "ug.group_id IN ($placeholders)";
            $params = $ids;
        }

        $result = $this->db->select(
            table: $tablelist,
            columns: $columns,
            joins: $joins,
            groupBy: 'g.id, g.name',
            conditions: $conditions,
            orderBy: 'total_consumption DESC',
            params: $params,
            limit: 1
        );

        return $result ? $result[0] : [];
    }
}
