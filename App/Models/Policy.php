<?php

namespace App\Models;

use Core\Helper\Data;
use Core\Models\Model;
use Core\System\Session;

class Policy extends Model
{
    protected const TABLE = 'groupInvites';

    public function list(): ?array
    {
        $result = $this->db->select(
            table: self::TABLE . ' gi left join groupes g on gi.group_id = g.id LEFT JOIN admins a on a.id = gi.created_by',
            columns: "gi.id,
                    g.name as 'group',
                    g.id as 'group_id',
                    COALESCE(a.fullname, '-' ) as creator,
                    gi.token,
                    gi.max_uses,
                    gi.used_count as uses,
                    gi.expires_at,
                    gi.status,
                    gi.created_by,
                    gi.created_at,
                    gi.last_used_at",
        );
        return $result ? $result : [];
    }
    public function listForAdmin(array $ids): ?array
    {
        $result = $this->db->select(
            table: self::TABLE . ' gi left join groupes g on gi.group_id = g.id LEFT JOIN admins a on a.id = gi.created_by',
            columns: "gi.id,
            g.id as 'group_id',
            g.name as 'group',
            COALESCE(a.fullname, '-' ) as creator,
            gi.token,
            gi.max_uses,
            gi.used_count as uses,
            gi.expires_at,
            gi.status,
            gi.created_by,
            gi.created_at,
            gi.last_used_at",
            conditions: "gi.group_id IN (:ids)",
            params: ["ids" => $ids],
        );
        return $result ? $result : [];
    }
    public function listForGroup(int|string $group_id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE . ' gi left join groupes g on gi.group_id = g.id LEFT JOIN admins a on a.id = gi.created_by',
            columns: "gi.id,
                    g.name as 'group',
                    COALESCE(a.fullname, '-' ) as creator,
                    gi.token,
                    gi.max_uses,
                    gi.used_count as uses,
                    gi.expires_at,
                    gi.status,
                    gi.created_by,
                    gi.created_at,
                    gi.last_used_at",
            conditions: 'gi.group_id=:group_id',
            params: [
                ':group_id' => $group_id
            ]
        );
        return $result ? $result : [];
    }
    public function countGroupLinks(int|string $group_id)
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: "COUNT(id) as 'count'",
            conditions: 'group_id=:group_id',
            params: [
                ':group_id' => $group_id
            ],
        );
        return $result ? $result[0]['count'] : 0;
    }
    public function upateUses($data)
    {
        $result = $this->db->update(
            table: self::TABLE,
            data: [
                'used_count' => (int)$data['uses'] + 1
            ],
            conditions: 'id=:id',
            params: [
                ':id' => $data['id']
            ],
        );
        return $result;
    }
    public function getByToken(string $token)
    {
        $result = $this->db->select(
            table: self::TABLE . ' gi left join groupes g on gi.group_id = g.id LEFT JOIN admins a on a.id = gi.created_by',
            columns: "gi.id,
                    g.name as 'group',
                    g.description as 'description',
                    g.id as 'group_id',
                    COALESCE(a.fullname, '-' ) as creator,
                    gi.token,
                    gi.max_uses,
                    gi.used_count as uses,
                    gi.expires_at,
                    gi.status,
                    gi.created_by,
                    gi.created_at,
                    gi.last_used_at",
            conditions: 'token=:token',
            params: [':token' => $token],
        );
        return $result ? $result[0] : [];
    }
    public function validToken(string $token): bool
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: 'id',
            conditions: 'token=:token',
            params: [':token' => $token],
        );
        return $result ? true : false;
    }
    /**
     * Trouve un admin par ID
     */
    public function findById(int|string $id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: implode(',', [
                'id',
                'token',
                'max_uses',
                'used_count',
                'expires_at',
                'status',
                'created_by',
                'created_at',
                'last_used_at',
            ]),
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function add(array $data)
    {
        $result = $this->db->insert(
            table: self::TABLE,
            data: [
                'status' => $data['status'] ?? '',
                'max_uses' => $data['max_uses'],
                'expires_at' => $data['expires_at'],
                'group_id' => $data['group'],
                'token' => $data['token'],
                'created_by' => Session::getUserId(),
            ],
        );
        return $result;
    }
    public function edit(string $id, Data $data)
    {
        $result = $this->db->update(
            table: self::TABLE,
            data: $data->all(),
            conditions: 'id=:id',
            params: [
                ':id' => $id
            ],
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
}
