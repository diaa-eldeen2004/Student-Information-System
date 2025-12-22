# Complete Testing Setup & Usage Guide

This is your complete guide to setting up and using automated unit testing in the Student Information System.

---

## 📋 Table of Contents

1. [Installation Steps](#installation-steps)
2. [Project Structure](#project-structure)
3. [Running Tests](#running-tests)
4. [Writing Tests](#writing-tests)
5. [Test Examples](#test-examples)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

---

## 🚀 Installation Steps

### Step 1: Install Composer

**For Windows (XAMPP):**

1. Download Composer from: https://getcomposer.org/download/
2. Run `Composer-Setup.exe`
3. During installation:
   - It should auto-detect PHP at `C:\xampp\php\php.exe`
   - If not, browse and select it manually
4. Complete the installation

**Verify Installation:**
```bash
composer --version
```

You should see: `Composer version X.X.X`

---

### Step 2: Navigate to Project

Open **Command Prompt** or **PowerShell**:

```bash
cd d:\xampp\htdocs\Student-Information-System
```

---

### Step 3: Install Dependencies

Run the following command:

```bash
composer install
```

This will:
- ✅ Create `vendor/` directory
- ✅ Install PHPUnit 9.5
- ✅ Create `composer.lock` file
- ✅ Generate autoload files

**Expected Output:**
```
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Package operations: X installs, X updates, X removals
  - Installing phpunit/phpunit (9.5.x)
Generating autoload files
```

---

### Step 4: Verify Installation

Test that PHPUnit is installed:

```bash
vendor/bin/phpunit --version
```

You should see: `PHPUnit 9.5.x by Sebastian Bergmann and contributors.`

---

## 📁 Project Structure

After installation, your project structure should look like:

```
Student-Information-System/
├── app/                          # Your application code
├── tests/                        # Test files
│   ├── Unit/                    # Unit tests
│   │   ├── Models/              # Model tests
│   │   │   ├── UserTest.php
│   │   │   └── StudentTest.php
│   │   └── Core/                # Core class tests
│   │       └── ModelTest.php
│   ├── Integration/             # Integration tests
│   │   └── DatabaseConnectionTest.php
│   └── bootstrap.php            # Test bootstrap
├── vendor/                       # Composer dependencies (auto-generated)
├── composer.json                 # Composer configuration
├── composer.lock                 # Lock file (auto-generated)
├── phpunit.xml                   # PHPUnit configuration
└── .gitignore                    # Git ignore file
```

---

## ▶️ Running Tests

### Run All Tests

```bash
vendor/bin/phpunit
```

Or using the composer script:

```bash
composer test
```

### Run Specific Test Suite

**Unit Tests Only:**
```bash
vendor/bin/phpunit tests/Unit
```

**Integration Tests Only:**
```bash
vendor/bin/phpunit tests/Integration
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/Models/UserTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter testFindByEmail
```

### Run with Coverage Report

```bash
composer test-coverage
```

This generates an HTML coverage report in `coverage/` directory.

---

## ✍️ Writing Tests

### Test File Structure

Every test file should:

1. Be in the `tests/` directory
2. Have namespace `Tests\Unit\...` or `Tests\Integration\...`
3. Extend `PHPUnit\Framework\TestCase`
4. Have methods starting with `test` or use `@test` annotation

**Example:**

```php
<?php
namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use models\User;

class UserTest extends TestCase
{
    private User $userModel;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new User();
    }
    
    public function testFindByEmail(): void
    {
        // Your test code here
    }
}
```

### Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);

// Type checking
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertIsInt($value);
$this->assertIsBool($value);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Array
$this->assertArrayHasKey('key', $array);
$this->assertContains($value, $array);

// Instance
$this->assertInstanceOf(ClassName::class, $object);

// Count
$this->assertCount(5, $array);
```

---

## 📝 Test Examples

### Example 1: Testing Model Methods

```php
public function testCreateUser(): void
{
    $userData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john' . time() . '@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'user'
    ];
    
    $result = $this->userModel->createUser($userData);
    
    $this->assertTrue($result, 'User should be created successfully');
}
```

### Example 2: Testing with Database

```php
public function testFindByEmailReturnsUser(): void
{
    // First create a test user
    $email = 'test' . time() . '@example.com';
    $this->userModel->createUser([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => $email,
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'role' => 'user'
    ]);
    
    // Then test finding it
    $user = $this->userModel->findByEmail($email);
    
    $this->assertNotNull($user, 'User should be found');
    $this->assertEquals($email, $user['email']);
}
```

### Example 3: Testing Exceptions

```php
public function testCreateUserWithDuplicateEmail(): void
{
    $email = 'duplicate@example.com';
    
    // Create first user
    $this->userModel->createUser([
        'first_name' => 'First',
        'last_name' => 'User',
        'email' => $email,
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'role' => 'user'
    ]);
    
    // Try to create duplicate
    $result = $this->userModel->createUser([
        'first_name' => 'Second',
        'last_name' => 'User',
        'email' => $email, // Same email
        'password' => password_hash('test', PASSWORD_DEFAULT),
        'role' => 'user'
    ]);
    
    $this->assertFalse($result, 'Duplicate email should fail');
}
```

---

## 🎯 Best Practices

### 1. Test Naming

- Use descriptive names: `testFindByEmailReturnsNullWhenNotFound`
- Start with `test` prefix or use `@test` annotation
- Describe what you're testing

### 2. One Assertion Per Test (when possible)

```php
// Good
public function testUserHasEmail(): void { ... }
public function testUserHasName(): void { ... }

// Avoid
public function testUserProperties(): void {
    // Multiple assertions in one test
}
```

### 3. Use setUp() and tearDown()

```php
protected function setUp(): void
{
    parent::setUp();
    // Initialize test data
    $this->userModel = new User();
}

protected function tearDown(): void
{
    parent::tearDown();
    // Clean up test data
}
```

### 4. Test Isolation

- Each test should be independent
- Don't rely on other tests
- Clean up after yourself

### 5. Use Test Database

For integration tests, consider using a separate test database:

```php
// In phpunit.xml
<env name="DB_NAME" value="swe_app_test"/>
```

### 6. Mock External Dependencies

For unit tests, mock database calls when possible:

```php
// Example with mocking (requires additional setup)
$mockDb = $this->createMock(PDO::class);
```

---

## 🔧 Troubleshooting

### Issue: "composer: command not found"

**Solution:**
- Restart Command Prompt after installing Composer
- Or use full path: `C:\ProgramData\ComposerSetup\bin\composer.bat`

### Issue: "PHP not found"

**Solution:**
- Add PHP to PATH: `C:\xampp\php`
- Or use full path: `C:\xampp\php\php.exe vendor/bin/phpunit`

### Issue: "Class not found"

**Solution:**
```bash
composer dump-autoload
```

### Issue: "Database connection failed"

**Solution:**
- Ensure MySQL is running in XAMPP
- Check database credentials in `app/config/database.php`
- Verify database exists

### Issue: "Tests fail with database errors"

**Solution:**
- Use a separate test database
- Or ensure test data exists
- Or mock database calls

### Issue: "Coverage report not generated"

**Solution:**
- Install Xdebug extension for PHP
- Enable it in `php.ini`:
  ```ini
  zend_extension=xdebug
  xdebug.mode=coverage
  ```

---

## 📊 Test Coverage

### Generate Coverage Report

```bash
composer test-coverage
```

Open `coverage/index.html` in your browser to see coverage report.

### Coverage Goals

- Aim for 70%+ code coverage
- Focus on critical business logic
- Test edge cases and error conditions

---

## 🎓 Next Steps

1. **Write Tests for All Models**
   - User, Student, Doctor, Course, Schedule, etc.

2. **Write Tests for Controllers**
   - Test request handling
   - Test authentication
   - Test authorization

3. **Write Integration Tests**
   - Test database operations
   - Test API endpoints
   - Test workflows

4. **Set Up CI/CD** (Optional)
   - Run tests automatically on commit
   - Generate coverage reports
   - Block commits if tests fail

---

## 📚 Additional Resources

- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Composer Documentation**: https://getcomposer.org/doc/
- **PHPUnit Assertions**: https://phpunit.readthedocs.io/en/9.5/assertions.html

---

## ✅ Quick Reference

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit
# or
composer test

# Run specific test
vendor/bin/phpunit tests/Unit/Models/UserTest.php

# Run with coverage
composer test-coverage

# Regenerate autoload
composer dump-autoload
```

---

**Last Updated**: December 2024  
**PHPUnit Version**: 9.5  
**PHP Version Required**: 7.4+
