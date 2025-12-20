# Database Setup Guide

## Quick Setup (Recommended)

**Option 1: Web-Based Migration (Easiest)**
1. Make sure your MySQL server is running (XAMPP Control Panel)
2. Visit: `http://localhost/swe/public/migrate`
3. The migration will automatically create the database and tables

**Option 2: Command Line Migration**
1. Open terminal/command prompt
2. Navigate to your project directory
3. Run: `php database/migrate.php`
   - If PHP is not in PATH, use full path: `C:\xampp\php\php.exe database/migrate.php`

**Option 3: Manual Setup**

### Step 1: Create the Database

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
2. Create a new database named `swe_app`:

```sql
CREATE DATABASE swe_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Import the Users Table

Run the SQL file to create the users table:

```sql
-- Option 1: Using command line
mysql -u root -p swe_app < database/users_table.sql

-- Option 2: Copy and paste the SQL from users_table.sql into your MySQL client
```

Or execute the SQL directly:

```sql
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student', 'doctor', 'it', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Step 3: Configure Database Connection

Update `app/config/database.php` with your database credentials:

```php
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',        // Change if needed
    'port' => 3306,                // Change if needed
    'database' => 'swe_app',        // Your database name
    'username' => 'root',           // Your MySQL username
    'password' => '',               // Your MySQL password
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
```

## Step 4: Test the Connection

After setting up, try signing up a new user. The system will:
- Save the user with role 'user' by default
- Hash the password securely
- Create a session for the new user
- Redirect to the home page

## Notes

- All public signups default to role 'user'
- Only admins can create accounts with other roles (admin, student, doctor, it, user)
- Passwords are hashed using PHP's `password_hash()` function
- Email addresses must be unique

