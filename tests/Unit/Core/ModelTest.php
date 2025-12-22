<?php
namespace Tests\Unit\Core;

use Tests\TestCase;
use core\Model;
use patterns\Singleton\DatabaseConnection;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
    }

    public function testModelHasDatabaseConnection(): void
    {
        $model = new class extends Model {
            public function getDb() {
                return $this->db;
            }
        };

        $db = $model->getDb();
        $this->assertInstanceOf(\PDO::class, $db);
    }

    public function testModelUsesSingletonConnection(): void
    {
        $model1 = new class extends Model {
            public function getDb() {
                return $this->db;
            }
        };

        $model2 = new class extends Model {
            public function getDb() {
                return $this->db;
            }
        };

        $db1 = $model1->getDb();
        $db2 = $model2->getDb();

        // Both should use the same singleton connection
        $this->assertSame($db1, $db2);
    }
}
