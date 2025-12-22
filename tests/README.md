# Testing Documentation

This directory contains automated unit and integration tests for the Student Information System.

## Setup

### 1. Install Dependencies

```bash
composer install
```

This will install PHPUnit and other testing dependencies.

### 2. Create Test Database

The test suite uses a separate test database (`swe_app_test`). Make sure MySQL is running and create the test database:

```sql
CREATE DATABASE swe_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or run the test setup script:

```bash
php tests/setup.php
```

### 3. Run Tests

```bash
# Run all tests
composer test

# Run with verbose output
composer test-verbose

# Run specific test suite
vendor/bin/phpunit tests/Unit/Models
vendor/bin/phpunit tests/Unit/Controllers
vendor/bin/phpunit tests/Integration

# Run specific test file
vendor/bin/phpunit tests/Unit/Models/UserTest.php

# Run with coverage report
composer test-coverage
```

## Test Structure

```
tests/
├── bootstrap.php          # Test bootstrap file
├── TestCase.php          # Base test case class
├── Unit/                 # Unit tests
│   ├── Models/          # Model tests
│   ├── Controllers/     # Controller tests
│   └── Core/            # Core class tests
└── Integration/         # Integration tests
```

## Writing Tests

### Example Test

```php
<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use models\User;

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
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'user',
        ];

        $result = $this->userModel->createUser($userData);
        $this->assertTrue($result);
    }
}
```

## Test Helpers

The `TestCase` class provides several helper methods:

- `createTestUser(array $data = [])` - Create a test user
- `createTestStudent(array $userData = [], array $studentData = [])` - Create a test student
- `createTestCourse(array $data = [])` - Create a test course
- `mockSession(array $data = [])` - Mock session data
- `clearSession()` - Clear session
- `truncateAllTables()` - Clean up all tables
- `runMigrations()` - Run database migrations

## Test Isolation

Each test runs in a transaction that is rolled back after the test completes. This ensures:

- Tests don't affect each other
- No test data persists between tests
- Fast test execution

## Coverage

To generate a coverage report:

```bash
composer test-coverage
```

This will create an HTML coverage report in the `coverage/` directory.

## Continuous Integration

The test suite is designed to run in CI/CD pipelines. Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: composer test
```

## Best Practices

1. **One assertion per test** - Keep tests focused and clear
2. **Use descriptive test names** - `testCreateUserWithValidData()` not `test1()`
3. **Test edge cases** - Invalid input, empty data, boundary conditions
4. **Mock external dependencies** - Don't rely on external services
5. **Keep tests fast** - Use transactions for database cleanup
6. **Test behavior, not implementation** - Focus on what the code does, not how

## Troubleshooting

### Tests fail with "Database connection failed"

- Check MySQL is running
- Verify database credentials in `app/config/database.php`
- Ensure test database exists: `swe_app_test`

### Tests fail with "Table doesn't exist"

- Run migrations: `$this->runMigrations()` in `setUp()`
- Check `database/schema.sql` exists

### Session errors

- Ensure `session_start()` is called in test setup
- Use `mockSession()` helper method
- Clear session in `tearDown()`

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain or improve code coverage
4. Update this documentation if needed
