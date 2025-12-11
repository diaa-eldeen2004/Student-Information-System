<?php
namespace core;

use PDO;
use PDOException;

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $settings = require dirname(__DIR__) . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $settings['driver'],
            $settings['host'],
            $settings['port'],
            $settings['database'],
            $settings['charset']
        );

        try {
            $this->db = new PDO($dsn, $settings['username'], $settings['password'], $settings['options']);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
}

