# Automated Unit Testing Setup Guide

This guide will help you set up PHPUnit for automated unit testing in your Student Information System.

---

## Prerequisites

- XAMPP installed (PHP 7.4+)
- Command Prompt or PowerShell access
- Internet connection (for downloading dependencies)

---

## Step 1: Install Composer

Composer is a dependency manager for PHP. We'll use it to install PHPUnit.

### For Windows (XAMPP):

1. **Download Composer**:
   - Visit: https://getcomposer.org/download/
   - Download `Composer-Setup.exe`
   - Run the installer

2. **During Installation**:
   - It will detect your PHP installation automatically
   - If it doesn't, browse to: `C:\xampp\php\php.exe`
   - Complete the installation

3. **Verify Installation**:
   - Open Command Prompt or PowerShell
   - Run: `composer --version`
   - You should see: `Composer version X.X.X`

**Alternative (Manual)**:
If the installer doesn't work, download `composer.phar` and place it in your project root.

---

## Step 2: Navigate to Your Project

Open Command Prompt or PowerShell and navigate to your project:

```bash
cd d:\xampp\htdocs\Student-Information-System
```

---

## Step 3: Initialize Composer

Create a `composer.json` file in your project root:

```bash
composer init
```

**Or** we'll create it manually (see Step 4).

---

## Step 4: Create composer.json

I'll create this file for you with the necessary dependencies.

---

## Step 5: Install PHPUnit

After creating `composer.json`, run:

```bash
composer install
```

This will:
- Create a `vendor/` directory
- Install PHPUnit and its dependencies
- Create `composer.lock` file

---

## Step 6: Create PHPUnit Configuration

Create `phpunit.xml` in your project root (I'll create this for you).

---

## Step 7: Create Test Directory Structure

Create the following structure:
```
tests/
├── Unit/
│   ├── Models/
│   ├── Controllers/
│   └── Core/
├── Integration/
└── bootstrap.php
```

---

## Step 8: Create Example Tests

I'll create example test files for you to follow.

---

## Step 9: Run Tests

After setup, run tests with:

```bash
vendor/bin/phpunit
```

Or:

```bash
php vendor/bin/phpunit
```

---

## Next Steps

After completing the setup, you can:
1. Write tests for your models
2. Write tests for your controllers
3. Write tests for your core classes
4. Run tests before committing code
5. Integrate with CI/CD (optional)

---

## Troubleshooting

### PHP Not Found
- Add PHP to PATH: `C:\xampp\php`
- Or use full path: `C:\xampp\php\php.exe vendor/bin/phpunit`

### Composer Not Found
- Restart Command Prompt after installing Composer
- Or use full path to composer

### Autoload Issues
- Run `composer dump-autoload` to regenerate autoload files

---

Let me now create the necessary files for you!
