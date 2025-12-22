<?php
namespace Tests\Unit\Patterns;

use Tests\TestCase;
use patterns\Singleton\DatabaseConnection;

class SingletonTest extends TestCase
{
    public function testSingletonReturnsSameInstance(): void
    {
        $instance1 = DatabaseConnection::getInstance();
        $instance2 = DatabaseConnection::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testSingletonReturnsPDOConnection(): void
    {
        $instance = DatabaseConnection::getInstance();
        $connection = $instance->getConnection();

        $this->assertInstanceOf(\PDO::class, $connection);
    }

    public function testEnsureCleanState(): void
    {
        $instance = DatabaseConnection::getInstance();
        $connection = $instance->getConnection();

        // Start a transaction
        $connection->beginTransaction();
        $this->assertTrue($connection->inTransaction());

        // Ensure clean state should rollback
        $instance->ensureCleanState();
        $this->assertFalse($connection->inTransaction());
    }

    public function testReconnect(): void
    {
        $instance = DatabaseConnection::getInstance();
        $connection1 = $instance->getConnection();

        $instance->reconnect();
        $connection2 = $instance->getConnection();

        // Should be different connections after reconnect
        $this->assertNotSame($connection1, $connection2);
    }
}
