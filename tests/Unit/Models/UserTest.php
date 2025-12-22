<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use models\User;
use PDOException;

class UserTest extends TestCase
{
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
        $this->userModel = new User();
    }

    public function testCreateUser(): void
    {
        $uniqueEmail = 'john.doe' . (int)(microtime(true) * 1000000) . '@example.com';
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $uniqueEmail,
            'phone' => '1234567890',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
        ];

        // Commit test transaction before calling model method (which manages its own transaction)
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $result = $this->userModel->createUser($userData);
        $this->assertTrue($result);

        // Commit to make user visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $user = $this->userModel->findByEmail($uniqueEmail);
        $this->assertNotNull($user);
        $this->assertEquals('John', $user['first_name']);
        $this->assertEquals('Doe', $user['last_name']);
        $this->assertEquals('user', $user['role']);
    }

    public function testFindByEmail(): void
    {
        $uniqueEmail = 'findme' . (int)(microtime(true) * 1000000) . '@example.com';
        $user = $this->createTestUser(['email' => $uniqueEmail]);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $found = $this->userModel->findByEmail($uniqueEmail);
        $this->assertNotNull($found);
        $this->assertEquals($user['id'], $found['id']);
        $this->assertEquals($uniqueEmail, $found['email']);
    }

    public function testFindByEmailCaseInsensitive(): void
    {
        $uniqueId = (int)(microtime(true) * 1000000);
        $user = $this->createTestUser(['email' => 'CaseTest' . $uniqueId . '@Example.com']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();

        $found = $this->userModel->findByEmail('casetest' . $uniqueId . '@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($user['id'], $found['id']);
    }

    public function testFindByEmailNotFound(): void
    {
        $found = $this->userModel->findByEmail('nonexistent@example.com');
        $this->assertNull($found);
    }

    public function testFindById(): void
    {
        $user = $this->createTestUser();

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $found = $this->userModel->findById($user['id']);
        $this->assertNotNull($found);
        $this->assertEquals($user['id'], $found['id']);
        $this->assertEquals($user['email'], $found['email']);
    }

    public function testFindByIdNotFound(): void
    {
        $found = $this->userModel->findById(99999);
        $this->assertNull($found);
    }

    public function testUpdateUser(): void
    {
        $user = $this->createTestUser();
        $uniqueEmail = 'updated' . (int)(microtime(true) * 1000000) . '@example.com';

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $uniqueEmail,
            'phone' => '9876543210',
        ];

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $result = $this->userModel->updateUser($user['id'], $updateData);
        $this->assertTrue($result);

        // Commit to make update visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $updated = $this->userModel->findById($user['id']);
        $this->assertEquals('Updated', $updated['first_name']);
        $this->assertEquals('Name', $updated['last_name']);
        $this->assertEquals($uniqueEmail, $updated['email']);
    }

    public function testUpdateUserPassword(): void
    {
        $user = $this->createTestUser();

        $updateData = [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'password' => 'newpassword123',
        ];

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $result = $this->userModel->updateUser($user['id'], $updateData);
        $this->assertTrue($result);

        // Commit to make update visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $updated = $this->userModel->findById($user['id']);
        $this->assertTrue(password_verify('newpassword123', $updated['password']));
    }

    public function testDeleteUser(): void
    {
        $user = $this->createTestUser();

        // Commit test transaction before calling model method
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $result = $this->userModel->deleteUser($user['id']);
        $this->assertTrue($result);

        // Commit to make deletion visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $deleted = $this->userModel->findById($user['id']);
        $this->assertNull($deleted);
    }

    public function testVerifyPassword(): void
    {
        $password = 'testpassword123';
        $user = $this->createTestUser(['password' => password_hash($password, PASSWORD_DEFAULT)]);

        // Commit to make user visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $this->assertTrue($this->userModel->verifyPassword($user['id'], $password));
        $this->assertFalse($this->userModel->verifyPassword($user['id'], 'wrongpassword'));
    }

    public function testGetAll(): void
    {
        $this->createTestUser(['role' => 'user']);
        $this->createTestUser(['role' => 'user']);
        $this->createTestUser(['role' => 'admin']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $users = $this->userModel->getAll();
        $this->assertGreaterThanOrEqual(2, count($users)); // Only 'user' role by default, but may have more from other tests

        $allUsers = $this->userModel->getAll(['role' => '']);
        $this->assertGreaterThanOrEqual(3, count($allUsers));
    }

    public function testGetAllWithSearch(): void
    {
        $uniqueId1 = (int)(microtime(true) * 1000000);
        $uniqueId2 = (int)(microtime(true) * 1000000) + 1;
        $this->createTestUser(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john' . $uniqueId1 . '@example.com']);
        $this->createTestUser(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane' . $uniqueId2 . '@example.com']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $results = $this->userModel->getAll(['search' => 'John']);
        $this->assertGreaterThanOrEqual(1, count($results));
        $this->assertStringContainsStringIgnoringCase('John', $results[0]['first_name']);
    }

    public function testGetCount(): void
    {
        $this->createTestUser(['role' => 'user']);
        $this->createTestUser(['role' => 'user']);
        $this->createTestUser(['role' => 'admin']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $count = $this->userModel->getCount();
        // Note: May have more users from other tests, but should have at least 2
        $this->assertGreaterThanOrEqual(2, $count);

        $allCount = $this->userModel->getCount(['role' => '']);
        $this->assertGreaterThanOrEqual(3, $allCount);
    }

    public function testGetThisMonthCount(): void
    {
        $this->createTestUser(['role' => 'user']);

        // Commit to make data visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $count = $this->userModel->getThisMonthCount();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $uniqueEmail = 'duplicate' . (int)(microtime(true) * 1000000) . '@example.com';
        $this->createTestUser(['email' => $uniqueEmail]);

        // Commit to make first user visible and sync connection
        $this->commitAndSync();
        $this->refreshModelConnections($this->userModel);

        $this->expectException(\RuntimeException::class);
        $this->userModel->createUser([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $uniqueEmail,
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'user',
        ]);
    }
}
