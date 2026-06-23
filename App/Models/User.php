<?php

namespace App\Models;

use Core\Exception\ConnectionException;
use Core\Helper\Data;
use Core\Helper\Helper;
use Core\Models\Model;
use Core\Routing\RouteException;
use Core\Security\Encrypter;
use Core\System\Session;

class User extends Model
{
    protected const TABLE = 'users';
    private const DAILY_TABLE = 'user_stats_with_online';

    public function getConnected(?int $groupId = null, ?array $ids = [], array $filters = []): array
    {
        $req = $this->db->table('radacct ra')
            ->select(
                'u.id                        AS user_id',
                'u.username',
                'u.fullname',
                'u.status                    AS account_status',
                'ra.AcctSessionId            AS session_id',
                'ra.FramedIPAddress          AS ip_address',
                'ra.CallingStationId         AS mac_address',
                'ra.AcctStartTime            AS session_start',
                'ra.AcctSessionTime          AS session_duration',
                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS group_name',
                'GROUP_CONCAT(DISTINCT g.id)                   AS group_id',
            )
            ->join('users u',         'u.username',    '=', 'ra.UserName')
            ->leftJoin('userGroup u_g', 'u_g.user_id', '=', 'u.id')
            ->leftJoin('groupes g',     'g.id',         '=', 'u_g.group_id')
            // Seules les sessions encore ouvertes (AcctStopTime non renseigné)
            ->whereNull('ra.AcctStopTime')
            ->groupBy(
                'ra.AcctSessionId',
                'u.id',
                'u.username',
                'u.fullname',
                'u.status',
                'ra.FramedIPAddress',
                'ra.CallingStationId',
                'ra.AcctStartTime',
                'ra.AcctSessionTime',
            );

        // ── Restriction par groupe ──────────────────────────────────────────────
        if ($groupId !== null) {
            // Un groupe précis a été demandé
            $req->where('u_g.group_id', '=', $groupId);
        } elseif (!empty($ids)) {
            // Modérateur : seulement ses groupes
            $req->whereIn('u_g.group_id', $ids);
        }

        // ── Filtre recherche (username, fullname) ───────────────────────────────
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $search = '%' . $filters['search'] . '%';
            $req->where(function ($q) use ($search) {
                $q->where('u.username', 'LIKE', $search)
                    ->orWhere('u.fullname', 'LIKE', $search)
                    ->orWhere('ra.FramedIPAddress', 'LIKE', $search);
            });
        }

        // ── Filtre groupe (par nom) ─────────────────────────────────────────────
        if (!empty($filters['group']) && $filters['group'] !== 'all') {
            $req->having('group_name', 'LIKE', '%' . $filters['group'] . '%');
        }

        $req->orderBy('ra.AcctStartTime', 'DESC');

        return $req->get() ?? [];
    }
    public function list()
    {
        if (Session::getUserType() == 'moderator') {
            $table = 'userGroup u_g
                 JOIN users u ON u.id = u_g.user_id
                 JOIN groupes g ON g.id = u_g.group_id
                 JOIN adminGroup a_g ON a_g.group_id = g.id';

            $conditions = 'a_g.admin_id = :admin_id';
            $params = [':admin_id' => Session::getUserId()];

            $columns = "u.id, u.username, u.fullname, u.status, 
                    g.name AS `group`,u_g.group_id AS `group_id`, u.expires_at, u.created_at";
        } else {
            // Pour administrateur
            $table = 'users u
                 LEFT JOIN userGroup u_g ON u.id = u_g.user_id
                 LEFT JOIN groupes g ON g.id = u_g.group_id';

            $conditions = '';
            $params = [];

            $columns = "u.id, u.username, u.fullname, u.status, 
                    COALESCE(g.name, '-Sans-') AS `group`,u_g.group_id AS `group_id`, u.expires_at, u.created_at";
        }

        $result = $this->db->select(
            table: $table,
            columns: $columns,
            conditions: $conditions,
            params: $params,
        );
        return $result ? $result : null;
    }
    public function listed(int $page = 1, int $perPage = 15, array $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        // ================================================================
        // Requête principale
        // ================================================================
        $req = $this->db->table('users u')
            ->select(
                'u.id                           as id',
                'u.username',
                'u.fullname',
                'u.status                       as account_status',
                'u.expires_at',
                'u.created_at',
                'COALESCE(SUM(us.total_sessions),    0) as total_sessions',
                'COALESCE(SUM(us.total_time),        0) as total_time',
                'COALESCE(SUM(us.total_download),    0) as total_download',
                'COALESCE(SUM(us.total_upload),      0) as total_upload',
                'COALESCE(SUM(us.total_consumption), 0) as total_consumption',
                'COALESCE(MAX(us.unique_devices),    0) as unique_devices',
                'MAX(us.current_duration)           as current_session_duration',
                'MAX(us.mac_address)                as current_mac',
                'MAX(us.ip_address)                 as current_ip',
                'COALESCE(MAX(us.is_online),        0) as is_online',
                'COALESCE(MAX(us.active_sessions_count), 0) as active_sessions',
                'MAX(rds_last.last_login_at)        as last_login_at',
                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) as group_name',
                'GROUP_CONCAT(DISTINCT g.id)                   as group_id',
            )
            ->leftJoin('user_stats_with_online us', 'us.user_id', '=', 'u.id')
            ->leftJoin('userGroup u_g',  'u_g.user_id',  '=', 'u.id')
            ->leftJoin('groupes g',      'u_g.group_id',  '=', 'g.id')
            ->joinRaw('LEFT JOIN (
            SELECT user_id, MAX(last_login) AS last_login_at
            FROM radacct_daily_stats
            GROUP BY user_id
        ) rds_last ON rds_last.user_id = u.id')
            ->groupBy('u.id');
        // ----------------------------------------------------------------
        // Filtre modérateur — restreint aux groupes dont il est responsable
        // ----------------------------------------------------------------
        if (Session::getUserType() === 'moderator') {
            $req->leftJoin('adminGroup a_g', 'a_g.group_id', '=', 'g.id')
                ->where('a_g.admin_id', '=', Session::getUserId());
        }

        // ----------------------------------------------------------------
        // Filtre recherche
        // ----------------------------------------------------------------
        if (!empty($filters['search']) && strlen($filters['search']) >= 3) {
            $search = '%' . $filters['search'] . '%';
            $req->where(function ($q) use ($search) {
                $q->where('u.username', 'LIKE', $search)
                    ->orWhere('u.fullname', 'LIKE', $search)
                    ->orWhere('us.ip_address', 'LIKE', $search);
            });
        }

        // ----------------------------------------------------------------
        // Filtre statut compte
        // ----------------------------------------------------------------
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $req->where('u.status', '=', $filters['status']);
        }

        // ----------------------------------------------------------------
        // Filtre online 
        // ----------------------------------------------------------------
        if (isset($filters['online']) && $filters['online'] !== 'all') {
            if ($filters['online'] === 'online') {
                $req->whereRaw(
                    'EXISTS (
                SELECT 1 FROM radacct r_online
                WHERE r_online.username = u.username COLLATE utf8mb4_unicode_ci
                AND r_online.acctstoptime IS NULL
            )'
                );
            } else {
                $req->whereRaw(
                    'NOT EXISTS (
                SELECT 1 FROM radacct r_online
                WHERE r_online.username = u.username COLLATE utf8mb4_unicode_ci
                AND r_online.acctstoptime IS NULL
            )'
                );
            }
        }
        // ----------------------------------------------------------------
        // Filtre groupe
        // ----------------------------------------------------------------
        if (!empty($filters['group']) && $filters['group'] !== 'all') {
            // Si $filters['group'] est un ID numérique
            if (is_numeric($filters['group'])) {
                $req->where('g.id', '=', (int) $filters['group']);
            } else {
                // Sinon recherche par nom exact
                $req->where('g.name', '=', $filters['group']);
            }
        }

        // ----------------------------------------------------------------
        // Tri
        // ----------------------------------------------------------------
        $allowedSorts = ['last_login_at', 'id', 'username'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true)
            ? $filters['sort_by']
            : 'u.id';

        if ($sortBy === 'id') {
            $sortBy = 'u.id';
        }
        if ($sortBy === 'username') {
            $sortBy = 'u.fullname';
        }

        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $req->orderBy($sortBy, $sortOrder);

        // ================================================================
        // COUNT — via count(QueryBuilder) de l'AggregateBuilder,
        // qui modifie temporairement les colonnes sans toucher aux WHERE/JOIN.
        // On clone pour ne pas perturber les colonnes/limit/offset de $req.
        // ================================================================
        $countReq   = clone $req;
        $innerSql   = $countReq->toSql();
        $bindings   = $countReq->getBindings();

        $countResult = $this->db->query(
            "SELECT COUNT(*) as total FROM ($innerSql) as count_wrapper",
            $bindings
        );

        $total = (int) ($countResult[0]['total'] ?? 0);

        // ================================================================
        // Résultats paginés
        // ================================================================
        $results = $req
            ->limit($perPage)
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
    public function usernameExists(string $username)
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: 'username',
            conditions: 'username = :username',
            params: [':username' => $username],
        );
        return $result ? true : false;
    }
    public function add(array $data)
    {
        $result = $this->db->insert(
            table: self::TABLE,
            data: [
                'fullname' => $data['fullname'] ?? '',
                'username' => $data['username'],
                'password_hash' => Encrypter::radiusCryptPassword($data['password']),
                'status' => $data['status'],
            ],
        );
        return $result;
    }
    /**
     * Trouve un admin par ID
     */
    public function findById(int|string $id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: implode(',',  [
                'id',
                'fullname',
                'username',
                'status',
                'expires_at',
                'created_at',
                'updated_at',
            ]),
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function toggleStatus(string $id, string $newStatus)
    {
        $result = $this->db->update(
            table: self::TABLE,
            data: ['status' => $newStatus],
            conditions: 'id=:id',
            params: [
                ':id' => $id
            ],
        );
        return $result;
    }
    public function edit(string $id, Data $data)
    {
        $params = [
            ':id' => $id
        ];
        if ($data->has('password')) {
            $data['password_hash'] = Encrypter::radiusCryptPassword($data['password']);
            $data->remove('password');
        }
        $result = $this->db->update(
            table: self::TABLE,
            data: $data->all(),
            conditions: 'id=:id',
            params: $params,
        );
        return $result;
    }
    public function remove(int|string $id)
    {
        $result = $this->db->delete(
            table: self::TABLE,
            conditions: 'id=:id',
            params: [
                ':id' => $id
            ]
        );
        return $result;
    }
    public function removeIn(array $members)
    {
        $result = $this->db->table(self::TABLE)
            ->whereIn('id', $members)
            ->delete();
        return $result;
    }

    //home and user space methods
    
    public function statistic(string $username)
    {
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
                'MAX(us.current_duration)   as current_session_duration',
                'MAX(us.mac_address)        as current_mac',
                'MAX(us.ip_address)         as current_ip',
                'MAX(us.is_online)          as is_online',
                'MAX(us.active_sessions_count) as active_sessions',

                'rds_last.last_login_at',

                'GROUP_CONCAT(DISTINCT g.name ORDER BY g.name) AS group_name',
                'GROUP_CONCAT(DISTINCT g.id) AS group_id'
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

            ->where('u.username', '=', $username)

            ->groupBy('us.user_id');
        $result = $req->get();
        return $result ? $result[0] : [];
    }

    /**
     * Connexion d'un administrateur
     * Retourne toutes les informations nécessaires pour la session
     */
    public function connect(Data $data): ?Data
    {
        // Récupérer l'admin par username uniquement d'abord
        $user = $this->get($data['username']);
        if (!$user) {
            throw new ConnectionException(errors: [
                'Nom d\'utilisateur ou mot de passe incorrect.'
            ]);
        }

        // Vérifier si le compte est verrouillé
        if ($this->isLocked($user)) {
            throw new RouteException;
        }

        // Vérifier le mot de passe
        if (!$this->verifyPassword($data['password'], $user['password_hash'])) {
            throw new ConnectionException(errors: [
                'Nom d\'utilisateur ou mot de passe incorrect.'
            ]);
        }
        // Vérifier le statut du compte
        if ($user['expires_at'] and !Helper::isExpired($user['expires_at'])) {
            throw new ConnectionException(errors: [
                'Votre compte est expiré. Veuillez contacter l\'administrateur.'
            ]);
        }

        // Retourner les données complètes
        return $this->formatUserData($user);
    }
    /**
     * Formate les données de l'admin pour la session
     */
    private function formatUserData(array $user): Data
    {
        return Data::create([
            'id' => $user['id'],
            'fullname' => $user['fullname'] ?? '',
            'username' => $user['username'] ?? '',
            'type' => $user['type'] ?? 'user',
            'status' => $user['status'] ?? 'inactive',
            'created_at' => $user['created_at'] ?? null,
        ]);
    }
    /**
     * Vérifie le mot de passe
     */
    private function verifyPassword(string $inputPassword, string $hashedPassword): bool
    {
        return (new Encrypter)->verifyRadiusPassword($inputPassword, $hashedPassword);
    }
    /**
     * Vérifie si un compte est verrouillé
     */
    private function isLocked(array $user): bool
    {
        return $user['status'] !== 'active';
    }

    public function get(string $username): ?array
    {
        $result =  $this->db->table(self::TABLE)
            ->select()
            ->where('username', $username)
            ->first();
        return $result ? $result : null;
    }
}
