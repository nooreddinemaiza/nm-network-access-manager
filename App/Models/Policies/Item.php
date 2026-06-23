<?php

namespace App\Models\Policies;

use Core\Helper\Data;
use Core\Models\Model;

class Item extends Model
{
    protected const TABLE = 'policy_preset_items';

    public function add(array $data)
    {
        $result = $this->db->insertBatch(
            table: self::TABLE,
            rows: $data,
        );
        return $result;
    }
    public function removeItems($ids)
    {
        $result = $this->db->deleteIn(
            table: self::TABLE,
            column: 'id',
            values: $ids
        );
        return $result ? true : false;
    }
    public function list(): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
        );
        return $result ? $result : [];
    }
    public function findById(int|string $id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: implode(',', [
                'id',
                'attribute',
                'value',
                'priority',
                'enabled',
                'preset_id',
            ]),
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function getItems(int|string $preset_id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: implode(',', [
                'id',
                'attribute',
                'value',
                'priority',
                'enabled',
            ]),
            conditions: 'preset_id = :preset_id',
            params: ['preset_id' => $preset_id],
        );

        return $result ? $result : [];
    }
    public function edit(int|string $id, array $data)
    {
        unset($data['id']);
        $result = $this->db->table(self::TABLE)
            ->where('id', $id)
            ->update($data);
        return $result;
    }
    public function remove(int|string|array $id)
    {
        $result = $this->db->deleteIn(
            table: self::TABLE,
            column: 'id',
            values: $id
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
