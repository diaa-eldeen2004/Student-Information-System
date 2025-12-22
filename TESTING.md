# Testing Guide

Complete guide for running automated unit tests in the Student Information System.

## Quick Start

### 1. Install Composer (if not installed)

Download from [getcomposer.org](https://getcomposer.org/download/)

### 2. Install Dependencies

```bash
cd d:\xampp\htdocs\Student-Information-System
composer install
```

### 3. Setup Test Database

```bash
php tests/setup.php
```

Or manually create the database:

```sql
CREATE DATABASE swe_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run Tests

```bash
# Run all tests
composer test

# Or directly with PHPUnit
vendor/bin/phpunit
```

## Test Commands

### Run All Tests
```bash
composer test
```

### Run with Verbose Output
```bash
composer test-verbose
```

### Run Specific Test Suite
```bash
# Model tests only
vendor/bin/phpunit tests/Unit/Models

# Controller tests only
vendor/bin/phpunit tests/Unit/Controllers

# Integration tests
vendor/bin/phpunit tests/Integration
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/Unit/Models/UserTest.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit --filter testCreateUser tests/Unit/Models/UserTest.php
```

### Generate Coverage Report
```bash
composer test-coverage
```

This creates an HTML report in `coverage/index.html`

## Test Structure

```
tests/
├── bootstrap.php              # Test environment setup
├── TestCase.php              # Base test class with helpers
├── setup.php                 # Database setup script
├── README.md                 # Detailed testing docs
├── Unit/                     # Unit tests
│   ├── Models/              # Model tests
│   │   ├── UserTest.php
│   │   ├── StudentTest.php
│   │   └── CourseTest.php
│   ├── Controllers/         # Controller tests
│   │   └── AuthTest.php
│   └── Core/                # Core class tests
│       ├── RouterTest.php
│       ├── ModelTest.php
│       └── ViewTest.php
└── Integration/             # Integration tests
    └── DatabaseTest.php
```

## Writing New Tests

### Example: Testing a Model Method

```php
<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use models\YourModel;

class YourModelTest extends TestCase
{
    private YourModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
        $this->model = new YourModel();
    }

    public function testYourMethod(): void
    {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $result = $this->model->yourMethod($data);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### Example: Testing a Controller

```php
<?php
namespace Tests\Unit\Controllers;

use Tests\TestCase;
use controllers\YourController;

class YourControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDatabase();
        $this->runMigrations();
        $this->mockSession(['user' => ['id' => 1, 'role' => 'admin']]);
    }

    public function testYourAction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['key' => 'value'];
        
        $controller = new YourController();
        // Test controller method
    }
}
```

## Test Helpers

The `TestCase` class provides these helper methods:

### Database Helpers
- `createTestUser(array $data = [])` - Create test user
- `createTestStudent(array $userData = [], array $studentData = [])` - Create test student
- `createTestCourse(array $data = [])` - Create test course
- `truncateAllTables()` - Clean all tables
- `runMigrations()` - Run database migrations

### Session Helpers
- `mockSession(array $data = [])` - Set session data
- `clearSession()` - Clear session

### Database Management
- `createTestDatabase()` - Create test database
- `dropTestDatabase()` - Drop test database
- `getTestDatabaseConnection()` - Get PDO connection

## Test Isolation

Each test:
1. Runs in a database transaction
2. Rolls back after completion
3. Doesn't affect other tests
4. Starts with a clean state

## Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Type checking
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertInstanceOf(ClassName::class, $object);

// Null/empty
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertEmpty($array);
$this->assertNotEmpty($array);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Count
$this->assertCount(5, $array);
$this->assertGreaterThan(10, $number);

// String
$this->assertStringContainsString('needle', $haystack);
$this->assertEqualsIgnoringCase('HELLO', 'hello');
```

## Troubleshooting

### "Class not found" errors
- Run `composer dump-autoload`
- Check namespace matches file path

### "Database connection failed"
- Verify MySQL is running
- Check credentials in `app/config/database.php`
- Ensure test database exists: `swe_app_test`

### "Table doesn't exist"
- Run `php tests/setup.php`
- Or manually run migrations

### Session errors
- Ensure `session_start()` in test setup
- Use `mockSession()` helper
- Clear session in `tearDown()`

### Tests are slow
- Use transactions (already implemented)
- Avoid external API calls
- Mock heavy operations

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: swe_app_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: pdo_mysql
      
      - name: Install dependencies
        run: composer install
      
      - name: Setup database
        run: php tests/setup.php
        env:
          DB_HOST: 127.0.0.1
          DB_USER: root
          DB_PASSWORD: root
          DB_DATABASE: swe_app_test
      
      - name: Run tests
        run: composer test
```

## Best Practices

1. **Test one thing per test** - Keep tests focused
2. **Use descriptive names** - `testCreateUserWithValidData()` not `test1()`
3. **Arrange-Act-Assert** - Structure tests clearly
4. **Test edge cases** - Invalid input, boundaries, null values
5. **Mock external dependencies** - Don't rely on real services
6. **Keep tests fast** - Use transactions, avoid I/O
7. **Test behavior, not implementation** - Focus on what, not how
8. **Maintain high coverage** - Aim for 80%+ coverage

## Coverage Goals

- **Models**: 90%+ coverage
- **Controllers**: 70%+ coverage
- **Core Classes**: 85%+ coverage
- **Overall**: 80%+ coverage

## Next Steps

1. Add more model tests (Doctor, Schedule, Assignment, etc.)
2. Add more controller tests (Student, Doctor, Admin, etc.)
3. Add integration tests for workflows
4. Set up CI/CD pipeline
5. Add performance tests
6. Add API tests (if applicable)

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPUnit Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)
