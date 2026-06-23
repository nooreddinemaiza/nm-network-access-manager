<?php

namespace App\Models;

use Core\Models\Model;

class Job extends Model
{

    protected const TABLE = 'jobs';
    public function add(array $data)
    {
        if (!$data) return false;
        $result = $this->db->table(self::TABLE)
            ->insert($data);
        return $result ? true : false;
    }
}
