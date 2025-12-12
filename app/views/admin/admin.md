# Admin Module Documentation

## Table of Contents
1. [Overview](#overview)
2. [Admin Responsibilities](#admin-responsibilities)
3. [Design Patterns](#design-patterns)
4. [Database Requirements](#database-requirements)
5. [Admin Routes](#admin-routes)
6. [API Endpoints](#api-endpoints)
7. [Security Considerations](#security-considerations)

---

## Overview

The Admin module is the central administrative interface for the Student Information System (SIS). It provides comprehensive user management, system monitoring, and configuration capabilities. The module follows the Model-View-Controller (MVC) architectural pattern and implements several design patterns to ensure maintainability, scalability, and code reusability.

**Location:** `app/controllers/Admin.php`  
**View Directory:** `app/views/admin/`  
**Models:** Various models in `app/models/`

---

## Admin Responsibilities

### 1. User Management
The admin can create, read, update, and delete (CRUD) users across all roles:
- **Students** - Manage student accounts, academic records, and enrollment information
- **Doctors** - Manage faculty members, departments, and teaching assignments
- **Advisors** - Manage academic advisors and their department assignments
- **IT Officers** - Manage IT staff accounts
- **Administrators** - Manage other admin accounts (with self-deletion prevention)
- **General Users** - Manage basic user accounts with 'user' role

### 2. Course Management
- Create and manage courses with prerequisites
- Set course codes, descriptions, credit hours, and departments
- View course statistics and enrollment data

### 3. System Monitoring
- **Dashboard** - View system-wide statistics including:
  - Total students, doctors, courses
  - Monthly/current semester statistics
  - Recent system activity and audit logs
  - User distribution charts
  - Course distribution by department

### 4. Reports & Analytics
- Generate and manage system reports
- View activity logs and system events
- Track user activities and changes

### 5. Calendar Management
- Create and manage university events
- Schedule exams, meetings, and academic deadlines
- Manage event conflicts and notifications

### 6. Profile Management
- Update admin profile information
- Change password with verification
- View personal statistics and activity

### 7. Database Migration
- Detect missing database columns
- Run migrations through web interface
- Manage schema updates

---

## Design Patterns

The Admin module implements several design patterns to ensure clean, maintainable, and scalable code architecture:

### 1. Model-View-Controller (MVC) Pattern

**Type:** Architectural Pattern  
**Purpose:** Separates application logic into three interconnected components

**Implementation:**
- **Controller** (`app/controllers/Admin.php`): Handles HTTP requests, business logic, and coordinates between models and views
- **Models** (`app/models/*.php`): Handle data access and business logic for specific entities
- **Views** (`app/views/admin/*.php`): Render HTML templates and present data to users

**Benefits:**
- Separation of concerns
- Easy to maintain and test
- Code reusability
- Independent development of components

**Example:**
```php
// Controller
public function manageStudent(): void
{
    $students = $this->studentModel->getAll($filters);  // Model
    $this->view->render('admin/admin_manage_student', [ // View
        'students' => $students
    ]);
}
```

---

### 2. Singleton Pattern (Creational)

**Type:** Creational Design Pattern  
**Purpose:** Ensures a class has only one instance and provides global access to it

**Implementation Location:** `app/patterns/Singleton/DatabaseConnection.php`

**Key Features:**
- Private constructor prevents direct instantiation
- Static method `getInstance()` provides single access point
- Lazy initialization - connection created only when needed
- Prevents cloning and unserialization

**Usage in Admin Module:**
```php
// Base Model class uses Singleton for database connection
class Model {
    protected PDO $db;
    
    public function __construct() {
        // Singleton Pattern - Single database connection instance
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
}
```

**Benefits:**
- Prevents multiple database connections
- Reduces resource consumption
- Ensures consistent database state
- Centralized connection management

**Code Example:**
```php
// Direct usage in controller when needed
$db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
$stmt = $db->query("SELECT COUNT(*) FROM students...");
```

---

### 3. Repository Pattern (Structural)

**Type:** Structural Design Pattern  
**Purpose:** Abstracts data access logic and provides a collection-like interface for accessing domain objects

**Implementation:** Each model extends the base `Model` class, which acts as a repository abstraction

**Key Features:**
- Models encapsulate all database operations for their entities
- Consistent interface across all models
- Separation of data access logic from business logic

**Admin Models Acting as Repositories:**
- `Student` model - Manages student data access
- `Doctor` model - Manages doctor data access
- `Advisor` model - Manages advisor data access
- `ItOfficer` model - Manages IT officer data access
- `AdminRole` model - Manages admin data access
- `Course` model - Manages course data access
- `User` model - Manages user data access

**Example:**
```php
// Model acts as repository
class Student extends Model {
    public function getAll(array $filters = []): array { /* ... */ }
    public function findById(int $id): ?array { /* ... */ }
    public function createStudentWithUser(array $userData, array $studentData): bool { /* ... */ }
    public function updateStudent(int $id, array $userData, array $studentData): bool { /* ... */ }
    public function deleteStudent(int $id): bool { /* ... */ }
}
```

**Benefits:**
- Centralized data access logic
- Easy to mock for testing
- Consistent data access patterns
- Easy to swap data sources

---

### 4. Dependency Injection Pattern (Behavioral)

**Type:** Behavioral Design Pattern  
**Purpose:** Provides dependencies to a class rather than creating them internally

**Implementation:** Admin controller instantiates all required models in constructor

**Key Features:**
- Models are injected into controller via constructor
- Controller depends on abstractions (model interfaces), not concretions
- Easy to test by injecting mock objects

**Example:**
```php
class Admin extends Controller {
    private Advisor $advisorModel;
    private User $userModel;
    private Student $studentModel;
    // ... other models
    
    public function __construct() {
        parent::__construct();
        
        // Dependency Injection - Models injected into controller
        $this->advisorModel = new Advisor();
        $this->userModel = new User();
        $this->studentModel = new Student();
        // ... other model initializations
    }
}
```

**Benefits:**
- Loose coupling between components
- Easy to test with mock objects
- Flexible dependency management
- Clear dependency declarations

---

### 5. Template Method Pattern (Behavioral)

**Type:** Behavioral Design Pattern  
**Purpose:** Defines the skeleton of an algorithm in the base class and lets subclasses override specific steps

**Implementation:** Base `Controller` and `Model` classes define common structure

**Example:**
```php
// Base Controller defines structure
abstract class Controller {
    protected View $view;
    
    public function __construct() {
        $this->view = new View();
    }
}

// Admin controller follows the template
class Admin extends Controller {
    // Inherits view property and structure
    public function dashboard(): void {
        // Uses inherited $this->view
        $this->view->render('admin/admin_dashboard', [...]);
    }
}
```

**Benefits:**
- Code reuse through inheritance
- Consistent structure across controllers
- Easy to maintain common functionality

---

### 6. Facade Pattern (Structural)

**Type:** Structural Design Pattern  
**Purpose:** Provides a simplified interface to a complex subsystem

**Implementation:** Controller methods act as facades, hiding complexity of multiple model interactions

**Example:**
```php
public function dashboard(): void {
    // Facade - Simplifies complex operations
    $totalStudents = $this->getTotalStudents();
    $studentsThisMonth = $this->getStudentsThisMonth();
    $totalDoctors = $this->getTotalDoctors();
    // ... aggregates data from multiple sources
    
    $this->view->render('admin/admin_dashboard', [
        // Single unified data structure
    ]);
}
```

**Benefits:**
- Simplifies complex subsystems
- Reduces coupling between client and subsystem
- Provides clean API for operations

---

### 7. Strategy Pattern (Behavioral)

**Type:** Behavioral Design Pattern  
**Purpose:** Defines a family of algorithms, encapsulates each one, and makes them interchangeable

**Implementation:** Used in filtering and validation logic (implicitly)

**Example:**
```php
// Different filtering strategies
public function manageStudent(): void {
    $filters = [];
    if (!empty($search)) $filters['search'] = $search;        // Search strategy
    if (!empty($yearFilter)) $filters['year_enrolled'] = $yearFilter;  // Year filter strategy
    if (!empty($statusFilter)) $filters['status'] = $statusFilter;     // Status filter strategy
    
    $students = $this->studentModel->getAll($filters);  // Strategy applied
}
```

**Benefits:**
- Interchangeable algorithms
- Easy to add new filtering strategies
- Separation of filtering logic

---

## Pattern Integration Summary

The Admin module successfully integrates multiple design patterns to create a robust, maintainable architecture:

| Pattern | Type | Usage | Benefit |
|---------|------|-------|---------|
| **MVC** | Architectural | Overall module structure | Separation of concerns |
| **Singleton** | Creational | Database connection | Resource efficiency |
| **Repository** | Structural | Model classes | Data access abstraction |
| **Dependency Injection** | Behavioral | Controller constructor | Loose coupling |
| **Template Method** | Behavioral | Base Controller/Model | Code reuse |
| **Facade** | Structural | Controller methods | Simplified interfaces |
| **Strategy** | Behavioral | Filtering/validation | Algorithm flexibility |

**Pattern Interaction Flow:**
```
Request → Router → Admin Controller (Facade)
                    ↓
            Uses Dependency Injection
                    ↓
            Models (Repository Pattern)
                    ↓
            DatabaseConnection (Singleton Pattern)
                    ↓
            Returns Data → View (MVC Pattern)
```

---

## Database Requirements

### Base User Table

All roles extend from the `users` table which contains common authentication and profile information:

```sql
CREATE TABLE `users` (
    `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student', 'doctor', 'advisor', 'it', 'user') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
);
```

**Required Fields for All Roles:**
- `first_name` (VARCHAR 100) - Required
- `last_name` (VARCHAR 100) - Required
- `email` (VARCHAR 255) - Required, Unique, Case-insensitive
- `phone` (VARCHAR 20) - Optional
- `password` (VARCHAR 255) - Required, Hashed using `password_hash()`
- `role` (ENUM) - Automatically set based on entity type

---

### Student Role Requirements

**Table:** `students`

**Database Schema:**
```sql
CREATE TABLE `students` (
    `student_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `gpa` DECIMAL(3,2) DEFAULT 0.00,
    `student_number` VARCHAR(50) UNIQUE,
    `admission_date` DATE DEFAULT NULL,
    `major` VARCHAR(100) DEFAULT NULL,
    `minor` VARCHAR(100) DEFAULT NULL,
    `midterm_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for midterm quiz access',
    `final_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for final quiz access',
    `status` ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
    `advisor_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`advisor_id`) REFERENCES `advisors`(`advisor_id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_student_number` (`student_number`),
    INDEX `idx_major` (`major`)
);
```

**Required Fields:**
- Base user fields (from `users` table)
- `student_number` (VARCHAR 50) - Optional but unique if provided
- `gpa` (DECIMAL 3,2) - Default: 0.00
- `status` (ENUM) - Default: 'active'

**Optional Fields:**
- `major` (VARCHAR 100) - Student's major field of study
- `minor` (VARCHAR 100) - Student's minor field of study
- `admission_date` (DATE) - Can be derived from `year_enrolled`
- `midterm_cardinality` (VARCHAR 255) - Password for midterm quiz access
- `final_cardinality` (VARCHAR 255) - Password for final quiz access
- `advisor_id` (INT 11) - Foreign key to `advisors` table

**Creation Process:**
1. Create user record in `users` table with `role = 'student'`
2. Create student record in `students` table with `user_id` reference
3. Transaction ensures both succeed or both fail

---

### Doctor Role Requirements

**Table:** `doctors`

**Database Schema:**
```sql
CREATE TABLE `doctors` (
    `doctor_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `title` VARCHAR(50) DEFAULT NULL,
    `office_location` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
);
```

**Required Fields:**
- Base user fields (from `users` table)

**Optional Fields:**
- `department` (VARCHAR 100) - Doctor's department
- `bio` (TEXT) - Biography or description
- `title` (VARCHAR 50) - Academic title (e.g., "Professor", "Associate Professor")
- `office_location` (VARCHAR 100) - Office location/room number

**Creation Process:**
1. Create user record in `users` table with `role = 'doctor'`
2. Create doctor record in `doctors` table with `user_id` reference
3. Transaction ensures both succeed or both fail

---

### Advisor Role Requirements

**Table:** `advisors`

**Database Schema:**
```sql
CREATE TABLE `advisors` (
    `advisor_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
);
```

**Required Fields:**
- Base user fields (from `users` table)

**Optional Fields:**
- `department` (VARCHAR 100) - Advisor's department

**Creation Process:**
1. Create user record in `users` table with `role = 'advisor'`
2. Create advisor record in `advisors` table with `user_id` reference
3. Transaction ensures both succeed or both fail

---

### IT Officer Role Requirements

**Table:** `it_officers`

**Database Schema:**
```sql
CREATE TABLE `it_officers` (
    `it_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
);
```

**Required Fields:**
- Base user fields (from `users` table)

**Optional Fields:**
- None (minimal role-specific data)

**Creation Process:**
1. Create user record in `users` table with `role = 'it'`
2. Create IT officer record in `it_officers` table with `user_id` reference
3. Transaction ensures both succeed or both fail

---

### Administrator Role Requirements

**Table:** `admins`

**Database Schema:**
```sql
CREATE TABLE `admins` (
    `admin_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
);
```

**Required Fields:**
- Base user fields (from `users` table)

**Optional Fields:**
- None (minimal role-specific data)

**Special Considerations:**
- Admin cannot delete themselves (self-deletion prevention)
- Admin role grants full system access

**Creation Process:**
1. Create user record in `users` table with `role = 'admin'`
2. Create admin record in `admins` table with `user_id` reference
3. Transaction ensures both succeed or both fail

---

### General User Role Requirements

**Table:** `users` only (no extended table)

**Required Fields:**
- Base user fields (from `users` table)
- `role` must be set to `'user'`

**Optional Fields:**
- None (only base user fields)

**Creation Process:**
1. Create user record in `users` table with `role = 'user'`
2. No additional table records needed

---

### Course Requirements

**Table:** `courses`

**Database Schema:**
```sql
CREATE TABLE `courses` (
    `course_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `course_code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `credit_hours` INT(11) NOT NULL DEFAULT 3,
    `department` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_course_code` (`course_code`)
);
```

**Required Fields:**
- `course_code` (VARCHAR 20) - Required, Unique (e.g., "CS101", "MATH201")
- `name` (VARCHAR 255) - Course name/title
- `credit_hours` (INT) - Default: 3

**Optional Fields:**
- `description` (TEXT) - Course description
- `department` (VARCHAR 100) - Department offering the course

---

## Database Relationships

### Foreign Key Constraints

All role-specific tables maintain foreign key relationships with the `users` table:

- `students.user_id` → `users.id` (CASCADE DELETE)
- `doctors.user_id` → `users.id` (CASCADE DELETE)
- `advisors.user_id` → `users.id` (CASCADE DELETE)
- `it_officers.user_id` → `users.id` (CASCADE DELETE)
- `admins.user_id` → `users.id` (CASCADE DELETE)
- `students.advisor_id` → `advisors.advisor_id` (SET NULL on delete)

**Cascade Behavior:**
- When a user is deleted, all related role records are automatically deleted
- When an advisor is deleted, student `advisor_id` fields are set to NULL

---

## Admin Routes

All admin routes are defined in `app/config/routes.php` and follow the RESTful convention where applicable.

### Dashboard Routes

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/dashboard` | `Admin@dashboard` | Display admin dashboard with statistics |

### Management Routes

#### Student Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-student` | `Admin@manageStudent` | Display student management page |
| `POST` | `/admin/manage-student` | `Admin@manageStudent` | Create, update, or delete student |

#### Doctor Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-doctor` | `Admin@manageDoctor` | Display doctor management page |
| `POST` | `/admin/manage-doctor` | `Admin@manageDoctor` | Create, update, or delete doctor |

#### Advisor Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-advisor` | `Admin@manageAdvisor` | Display advisor management page |
| `POST` | `/admin/manage-advisor` | `Admin@manageAdvisor` | Create, update, or delete advisor |

#### IT Officer Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-it` | `Admin@manageIt` | Display IT officer management page |
| `POST` | `/admin/manage-it` | `Admin@manageIt` | Create, update, or delete IT officer |

#### Administrator Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-admin` | `Admin@manageAdmin` | Display admin management page |
| `POST` | `/admin/manage-admin` | `Admin@manageAdmin` | Create, update, or delete admin (prevents self-deletion) |

#### User Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-user` | `Admin@manageUser` | Display general user management page |
| `POST` | `/admin/manage-user` | `Admin@manageUser` | Create, update, or delete general user |

#### Course Management
| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/manage-course` | `Admin@manageCourse` | Display course management page |
| `POST` | `/admin/manage-course` | `Admin@manageCourse` | Create, update, or delete course |

### System Routes

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/reports` | `Admin@reports` | Display reports management page |
| `POST` | `/admin/reports` | `Admin@reports` | Create, update, or delete reports |
| `GET` | `/admin/calendar` | `Admin@calendar` | Display calendar management page |
| `POST` | `/admin/calendar` | `Admin@calendar` | Create, update, or delete calendar events |
| `GET` | `/admin/profile` | `Admin@profile` | Display admin profile page |
| `POST` | `/admin/profile` | `Admin@profile` | Update admin profile or password |

---

## API Endpoints

These endpoints are used for AJAX requests to fetch entity details without page reloads.

| Method | Route | Controller Method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/admin/api/student?id={id}` | `Admin@getStudentDetails` | Get student details by ID (JSON) |
| `GET` | `/admin/api/doctor?id={id}` | `Admin@getDoctorDetails` | Get doctor details by ID (JSON) |
| `GET` | `/admin/api/advisor?id={id}` | `Admin@getAdvisorDetails` | Get advisor details by ID (JSON) |
| `GET` | `/admin/api/it?id={id}` | `Admin@getItOfficerDetails` | Get IT officer details by ID (JSON) |
| `GET` | `/admin/api/admin?id={id}` | `Admin@getAdminDetails` | Get admin details by ID (JSON) |
| `GET` | `/admin/api/user?id={id}` | `Admin@getUserDetails` | Get user details by ID (JSON) |
| `GET` | `/admin/api/course?id={id}` | `Admin@getCourseDetails` | Get course details by ID (JSON) |

**Response Format:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john.doe@example.com",
        // ... other fields
    }
}
```

---

## Security Considerations

### Authentication
- All admin routes require active session with `role = 'admin'`
- Session validation occurs in `Admin::__construct()`
- Unauthorized access redirects to login page

### Authorization
- Only users with `role = 'admin'` can access admin routes
- Self-deletion prevention for admin accounts
- Role-based access control (RBAC) enforced

### Data Validation
- Email validation (case-insensitive, unique check)
- Input sanitization using `trim()` and `htmlspecialchars()`
- Password hashing using `password_hash()` with PASSWORD_DEFAULT algorithm
- SQL injection prevention through PDO prepared statements

### Error Handling
- Try-catch blocks around database operations
- Transaction rollback on errors
- Detailed error logging
- User-friendly error messages (no sensitive data exposure)

### Password Management
- Auto-generation for new users if not provided
- Secure random password generation using `bin2hex(random_bytes(8))`
- Password hashing before storage
- Current password verification for password updates

### Database Security
- Foreign key constraints ensure referential integrity
- CASCADE DELETE maintains data consistency
- Indexed columns for performance and uniqueness
- Transactions ensure atomic operations

---

## Best Practices

1. **Always use transactions** for multi-table operations (e.g., user creation)
2. **Validate email uniqueness** before creating users
3. **Normalize emails** to lowercase before storage and comparison
4. **Log errors** for debugging while showing user-friendly messages
5. **Use prepared statements** for all database queries
6. **Check database columns** before operations (e.g., migration detection)
7. **Implement proper error handling** with rollback capabilities
8. **Follow RESTful conventions** for route naming
9. **Use dependency injection** for testability
10. **Separate concerns** using MVC pattern strictly

---

## Future Enhancements

Potential improvements for the Admin module:

1. **Command Pattern** - Encapsulate CRUD operations as commands for undo/redo functionality
2. **Factory Pattern** - Centralized user creation factory for different roles
3. **Observer Pattern** - Notify other systems when users are created/updated
4. **Bulk Operations** - Batch create/update/delete for multiple users
5. **Export Functionality** - Export user data to CSV/Excel
6. **Advanced Filtering** - More sophisticated search and filter options
7. **Activity Logging** - Track all admin actions for audit trails
8. **Role Permissions** - Fine-grained permissions for different admin levels

---

## Documentation Version

**Version:** 1.0  
**Last Updated:** 2024  
**Author:** Student Information System Development Team

---

## References

- Design Patterns: Elements of Reusable Object-Oriented Software (Gang of Four)
- PHP PSR Standards (PSR-4 Autoloading, PSR-1/PSR-12 Coding Standards)
- MVC Architecture Best Practices
- Database Design Principles and Normalization

