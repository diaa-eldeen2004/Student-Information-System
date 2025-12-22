# Quick Start: Automated Testing Setup

## ✅ Step-by-Step Checklist

### Step 1: Install Composer (5 minutes)
- [ ] Download from: https://getcomposer.org/download/
- [ ] Run `Composer-Setup.exe`
- [ ] Verify: Open CMD and run `composer --version`

### Step 2: Install PHPUnit (2 minutes)
- [ ] Open Command Prompt
- [ ] Navigate: `cd d:\xampp\htdocs\Student-Information-System`
- [ ] Run: `composer install`
- [ ] Wait for installation to complete

### Step 3: Verify Installation (1 minute)
- [ ] Run: `vendor/bin/phpunit --version`
- [ ] Should show: `PHPUnit 9.5.x`

### Step 4: Run Your First Test (1 minute)
- [ ] Run: `vendor/bin/phpunit`
- [ ] Check test results

---

## 📝 Files Created

✅ `composer.json` - Dependency configuration  
✅ `phpunit.xml` - PHPUnit configuration  
✅ `tests/bootstrap.php` - Test bootstrap file  
✅ `tests/Unit/Models/UserTest.php` - Example user tests  
✅ `tests/Unit/Models/StudentTest.php` - Example student tests  
✅ `tests/Unit/Core/ModelTest.php` - Example core tests  
✅ `tests/Integration/DatabaseConnectionTest.php` - Integration tests  
✅ `.gitignore` - Updated to exclude vendor/ and coverage/  
✅ `TESTING_GUIDE.md` - Complete documentation  

---

## 🚀 Quick Commands

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/Models/UserTest.php

# Run with coverage (requires Xdebug)
composer test-coverage
```

---

## 📖 Full Documentation

See `TESTING_GUIDE.md` for complete documentation.

---

## ⚠️ Important Notes

1. **First Time Setup**: Run `composer install` only once
2. **Database**: Tests use your existing database (consider creating a test database)
3. **XAMPP**: Ensure MySQL is running before running integration tests
4. **PHP Version**: Requires PHP 7.4 or higher (XAMPP includes this)

---

## 🆘 Need Help?

1. Check `TESTING_GUIDE.md` for detailed instructions
2. Verify Composer is installed: `composer --version`
3. Verify PHP is in PATH: `php --version`
4. Check MySQL is running in XAMPP Control Panel

---

**Ready to test!** 🎉
