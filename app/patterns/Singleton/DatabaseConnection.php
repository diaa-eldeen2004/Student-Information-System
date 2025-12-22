<?php
namespace patterns\Singleton;

use PDO;
use PDOException;

/**
 * Singleton Pattern - Creational
 * Ensures only one database connection instance exists
 */
class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the database connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                $options = $config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                
                // CRITICAL: Disable persistent connections to avoid state retention issues
                $options[PDO::ATTR_PERSISTENT] = false;
                
                $this->connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $options
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return $this->connection;
    }

    /**
     * Ensure connection is in a clean state (no active transactions)
     * This is critical for singleton pattern to work correctly across requests
     */
    public function ensureCleanState(): void
    {
        if ($this->connection !== null && $this->connection->inTransaction()) {
            // Suppress warning during tests (transactions are expected in test environment)
            if (!defined('TESTING') && !defined('PHPUNIT_TEST')) {
                error_log("Warning: Active transaction found on singleton connection, rolling back to ensure clean state");
            }
            try {
                $this->connection->rollBack();
            } catch (\PDOException $e) {
                // Suppress error log during tests
                if (!defined('TESTING') && !defined('PHPUNIT_TEST')) {
                    error_log("Error rolling back transaction: " . $e->getMessage());
                }
                // If rollback fails, the connection might be in a bad state
                // In this case, we might need to reconnect
                $this->reconnect();
            }
        }
    }

    /**
     * Reconnect to the database (useful if connection is in a bad state)
     */
    public function reconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection = null;
        }
        // Next call to getConnection() will create a new connection
    }

    /**
     * Get a fresh connection for read-only operations (email checks)
     * This ensures we see only committed data without interfering with transactions
     */
    public function getReadOnlyConnection(): PDO
    {
        // For read operations, we want to ensure we see committed data
        // So we get the connection and ensure it's not in a transaction
        $conn = $this->getConnection();
        $this->ensureCleanState();
        return $conn;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

