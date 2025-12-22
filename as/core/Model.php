<?php
namespace core;

use PDO;
use PDOException;
use patterns\Singleton\DatabaseConnection;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        // Singleton Pattern - Use singleton database connection
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
}

