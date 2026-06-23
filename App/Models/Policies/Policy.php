<?php

namespace App\Models\Policies;

use Core\Helper\Data;
use Core\Models\Model;
use Core\System\Session;

class Policy extends Model
{
    protected const TABLE = 'policy_presets';

    public function add(array $data)
    {
        $result = $this->db->insert(
            table: self::TABLE,
            data: $data,
        );
        return $result;
    }

    public function list(): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
        );
        return $result ? $result : [];
    }
    public function getUserPolicies(int|string $user_id): ?array
    {
        $result = $this->db->select(
            table: "user_applied_policies",
            columns: 'preset_id as id, assigned_at as at, scope',
            conditions: 'user_id = :user_id',
            params: [
                'user_id' => $user_id
            ],
        );
        return $result ? $result : [];
    }
    public function getGroupPolicies(int|string $group_id): ?array
    {
        $result = $this->db->select(
            table: "group_applied_policies",
            columns: 'preset_id as id, assigned_at as at',
            conditions: 'group_id = :group_id',
            params: [
                'group_id' => $group_id
            ],
        );
        return $result ? $result : [];
    }
    public function removePolicyFromUser(int|string $user_id, int|string $preset_id)
    {
        $result = $this->db->table("user_applied_policies")
            ->where('user_id', $user_id)
            ->where('preset_id', $preset_id)
            ->delete();
        return $result;
    }
    public function removePolicyFromGroup(int|string $group_id, int|string $preset_id)
    {
        $result = $this->db->table("group_applied_policies")
            ->where('group_id', $group_id)
            ->where('preset_id', $preset_id)
            ->delete();
        return $result;
    }
    public function applyPolicyToUser(int|string $user_id, int|string $preset_id)
    {
        $result = $this->db->insert(
            table: "user_applied_policies",
            data: [
                'user_id' => $user_id,
                'preset_id' => $preset_id
            ],
        );
        return $result;
    }

    public function applyPoliciesToUser(array $policies)
    {
        $result = $this->db->insertBatch(
            table: "user_applied_policies",
            rows: $policies,
        );
        return $result;
    }

    public function userSpecialPolicies(array $presets, $scope = 'normal')
    {
        $result = $this->db->table('user_applied_policies')
            ->whereIn('preset_id', $presets)
            ->update([
                'scope' => $scope
            ]);
        return $result;
    }
    public function removeUserPolicies($policies)
    {
        $result = $this->db->deleteIn(
            table: "user_applied_policies",
            column: 'preset_id',
            values: $policies
        );
        return $result ? true : false;
    }
    public function applyPolicyToGroup(int|string $group_id, int|string $preset_id)
    {
        $result = $this->db->insert(
            table: "group_applied_policies",
            data: [
                'group_id' => $group_id,
                'preset_id' => $preset_id
            ],
        );
        return $result;
    }
    public function findById(int|string $id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: implode(',', [
                'id',
                'status',
                'description',
                'name',
                'expires_at',
                'created_at',
            ]),
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function edit(int|string $id, Data $data)
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
        $result = $this->db->table(self::TABLE)
            ->where('id', $id)
            ->delete();
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
