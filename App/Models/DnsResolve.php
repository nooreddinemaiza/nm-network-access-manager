<?php

namespace App\Models;

use Core\Helper\Data;
use Core\Models\Model;
use Core\System\Session;

class DnsResolve extends Model
{
    protected const TABLE = 'dns_logs';

    public function store(array $domains)
    {
        $result = $this->db->table(self::TABLE)
            ->insertMany($domains);
        return $result ?? 0;
    }
}
