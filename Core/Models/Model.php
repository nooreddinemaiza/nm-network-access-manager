<?php

namespace Core\Models;

use Core\Database\Database;

class Model
{
    protected const TABLE = '';

    protected Database $db;
    public function __construct(?Database $db = null)
    {
        $this->db = $dn ?? new Database();
    }
    public function findById(int|string $id): ?array
    {
        $result = $this->db->select(
            table: self::TABLE,
            columns: 'id',
            conditions: 'id = :id',
            params: [':id' => $id]
        );

        return $result ? $result[0] : null;
    }
    public function exists(int|string $id): bool
    {
        $result = $this->findById($id);
        return $result ? true : false;
    }
}
