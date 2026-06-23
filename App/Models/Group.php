<?php

namespace App\Models;

use Core\Helper\Data;
use Core\Models\Model;
use Core\System\Session;

class Group extends Model
{
    protected const TABLE = 'groupes';

    public function list(): ?array
    {
        $result = $this->db->select(
            table: self::TABLE . ' g LEFT JOIN adminGroup ag ON ag.group_id = g.id
                                    LEFT JOIN admins a      ON a.id = ag.admin_id
                                    LEFT JOIN userGroup ug  ON ug.group_id = g.id',
            columns: "g.id,
                g.name,
                g.description,
                g.max_members,
                g.created_at,
                g.updated_at,
                COUNT(DISTINCT ug.user_id) AS members,
                COALESCE(a.id, -1) AS moderator_id,
                COALESCE(a.fullname, '-Sans-' ) AS moderator",
            groupBy: 'g.id,a.id,a.fullname',
        );

        return $result ? $result : [];
    }
    public function listForUse()
    {
        if (Session::getUserType() == 'moderator') {
            $condition = 'a_g.admin_id = :id';
            $params = [
                ':id' => Session::getUserId()
            ];
            $joins = 'JOIN adminGroup a_g ON g.id = a_g.group_id ';
        }
        $result = $this->db->select(
            table: self::TABLE . ' g ',
            joins: $joins ?? '',
            columns: "g.name AS `name`,g.id AS `id` ",
            conditions: $condition ?? '',
            params: $params ?? [],
        );
        return $result ? $result : [];
    }
    public function joinGroup(int|string $user_id, int|string $group_id, bool $has_group)
    {
        if (!$has_group) {
            $result = $this->db->insert(
                table: 'userGroup',
                data: [
                    'user_id' => $user_id,
                    'group_id' => $group_id,
                ],
            );
        } else {
            $result = $this->db->update(
                table: 'userGroup',
                data: [
                    'group_id' => $group_id,
                ],
                conditions: 'user_id = :user_id',
                params: [':user_id'  => $user_id],

            );
        }
        return $result;
    }
    public function getMembersIds(int|string $group_id)
    {
        $result = $this->db->table('userGroup')->where('group_id', $group_id)->select('user_id')->get();

        return $result ? $result : [];
    }
    public function setModerator(int|string $admin_id, int|string $group_id)
    {
        $result = $this->db->insert(
            table: 'adminGroup',
            data: [
                'admin_id' => $admin_id,
                'group_id' => $group_id,
            ],
        );
        return $result;
    }
    public function noGroup(int|string $user_id)
    {
        $result = $this->db->delete(
            table: 'userGroup',
            conditions: 'user_id=:user_id',
            params: [':user_id' => $user_id]

        );
        return $result ? true : false;
    }
    public function noModerator(int|string $group_id)
    {
        $result = $this->db->delete(
            table: 'adminGroup',
            conditions: 'group_id=:group_id',
            params: [
                ':group_id' => $group_id,
            ]

        );
        return $result ? true : false;
    }
    public function hasModerator(int|string $group_id): bool
    {
        $result = $this->db->select(
            table: 'adminGroup',
            columns: 'group_id',
            conditions: 'group_id = :group_id',
            params: [':group_id' => $group_id],
        );
        return $result ? true : false;
    }
    public function getGroup(int|string $user_id, ?string $type = "user")
    {
        $table = $type == 'user' ? 'userGroup' : 'adminGroup';
        $column = $type == 'user' ? 'user_id' : 'admin_id';
        $result = $this->db->select(
            table: $table,
            columns: 'group_id',
            conditions: "$column = :user_id",
            params: [':user_id' => $user_id],
        );
        return $result ? $result[0]['group_id'] : -1;
    }
    public function hasGroup(int|string $user_id, ?string $type = "user")
    {
        $table = $type == 'user' ? 'userGroup' : 'adminGroup';
        $column = $type == 'user' ? 'user_id' : 'admin_id';

        $result = $this->db
            ->table($table)
            ->select('group_id')
            ->where(column: $column, value: $user_id)
            ->get();
        return $result ? true : false;
    }
    public function hasPlace(int|string $group_id): bool
    {
        $result = $this->db->selectOne(
            table: self::TABLE . ' g',
            joins: 'LEFT JOIN userGroup ug ON g.id = ug.group_id ',
            columns: "IFNULL(COUNT(ug.user_id), 0) as members, g.max_members",
            conditions: "g.id = :group_id",
            params: ['group_id' => $group_id],
            groupBy: 'g.id, g.max_members',
        );
        // Vérifiez si le résultat existe et s'il y a de la place
        if (!$result) {
            return false;
        }

        return (int)$result['members'] < (int)$result['max_members'];
    }
    public function getAdminGroups(int|string $admin_id)
    {
        $table =  'adminGroup';
        $result = $this->db->select(
            table: $table,
            joins: '',
            columns: 'group_id as id',
            conditions: "admin_id = :admin_id",
            params: [':admin_id' => $admin_id],
        );
        return $result ? $result : [];
    }
    public function groupExists(int|string $id): bool
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: 'id',
            conditions: 'id = :id',
            params: [':id' => $id],
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
                'name',
                'description',
                'max_members',
                'created_at',
                'updated_at',
            ]),
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function nameExists(string $name)
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: 'name',
            conditions: 'name = :name',
            params: [':name' => $name],
        );
        return $result ? true : false;
    }
    public function add(array $data)
    {
        $result = $this->db->insert(
            table: self::TABLE,
            data: [
                'name' => $data['name'] ?? '',
                'description' => $data['description'],
                'max_members' => $data['max_members'],
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
}
