<?php
namespace models;

use core\Model;

class Sample extends Model
{
    public function all(): array
    {
        $statement = $this->db->query('SELECT NOW() as current_time');
        return $statement->fetchAll();
    }
}

