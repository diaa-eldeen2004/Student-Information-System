# Design Patterns Used in Doctor Module

## Overview
The Doctor module implementation uses **6 design patterns** following the same architecture as the IT Officer module. Here's where each pattern is used and why:

---

## 1. **Factory Method Pattern** (Creational)
**Location:** `app/patterns/Factory/ModelFactory.php`  
**Used in:** `app/controllers/Doctor.php` (lines 55-64)

### Where it's used:
```php
// Factory Method Pattern - Create all models
$this->doctorModel = ModelFactory::create('Doctor');
$this->courseModel = ModelFactory::create('Course');
$this->sectionModel = ModelFactory::create('Section');
$this->assignmentModel = ModelFactory::create('Assignment');
$this->attendanceModel = ModelFactory::create('Attendance');
$this->studentModel = ModelFactory::create('Student');
$this->auditLogModel = ModelFactory::create('AuditLog');
$this->notificationModel = ModelFactory::create('Notification');
$this->materialModel = ModelFactory::create('Material');
```

### Why:
- **Centralized object creation** - All models are created through a single factory
- **Consistency** - Ensures all models follow the same creation pattern
- **Maintainability** - Easy to modify model creation logic in one place
- **Singleton integration** - Factory combines with Singleton to reuse model instances

---

## 2. **Builder Pattern** (Creational)
**Location:** `app/patterns/Builder/AssignmentBuilder.php`  
**Used in:** `app/controllers/Doctor.php` (lines 279-304)

### Where it's used:
```php
// Builder Pattern - Build assignment step by step
$builder = new AssignmentBuilder();
$builder->setCourse((int)($_POST['course_id'] ?? 0))
        ->setSection($sectionId)
        ->setDoctor($doctor['doctor_id'])
        ->setTitle(trim($_POST['title'] ?? ''))
        ->setDescription(trim($_POST['description'] ?? ''))
        ->setDueDate(trim($_POST['due_date'] ?? ''))
        ->setMaxPoints((float)($_POST['points'] ?? 100))
        ->setType(trim($_POST['type'] ?? 'homework'));

if ($filePath) {
    $builder->setFile($filePath, $fileName, $fileSize);
}

if ($semester && $academicYear) {
    $builder->setSemester($semester, $academicYear);
}

$builder->setVisibility($isVisible, $visibleUntil);

$assignmentData = $builder->build();
```

### Why:
- **Complex object construction** - Assignments have many optional fields (file, visibility, semester)
- **Fluent interface** - Method chaining makes code readable
- **Validation** - Builder validates required fields before building
- **Flexibility** - Can build assignments with different combinations of fields

---

## 3. **Decorator Pattern** (Structural)
**Location:** `app/patterns/Decorator/AssignmentDecorator.php`  
**Used in:** `app/controllers/Doctor.php` (lines 198-204)

### Where it's used:
```php
// Decorator Pattern - Format assignments for display
$decoratedAssignments = [];
foreach ($assignments as $assignment) {
    $decorator = new AssignmentDecorator($assignment);
    $assignment['formatted'] = $decorator->format();
    $assignment['status_badge'] = $decorator->getStatusBadge();
    $submissionStats = $this->assignmentModel->getSubmissionCount($assignment['assignment_id']);
    $assignment['submission_stats'] = $submissionStats;
    $decoratedAssignments[] = $assignment;
}
```

### Why:
- **Separation of concerns** - Formatting logic separated from business logic
- **Extensibility** - Easy to add new formatting methods without modifying models
- **Reusability** - Same decorator can be used in different views
- **Dynamic behavior** - Adds display behavior (formatting, badges) to assignment data

---

## 4. **Observer Pattern** (Behavioral)
**Location:** `app/patterns/Observer/AssignmentSubject.php`  
**Used in:** `app/controllers/Doctor.php` (lines 70-73, 318-323)

### Where it's used:

**Setup (Constructor):**
```php
// Observer Pattern - Setup observers for assignment events
$this->assignmentSubject = new AssignmentSubject();
$this->assignmentSubject->attach(new NotificationObserver($this->notificationModel));
$this->assignmentSubject->attach(new AuditLogObserver($this->auditLogModel));
```

**Notification (createAssignment method):**
```php
if ($this->assignmentModel->create($assignmentData)) {
    // Observer Pattern - Notify observers about new assignment
    $this->assignmentSubject->notify('assignment_created', [
        'assignment_id' => $this->assignmentModel->getLastInsertId(),
        'doctor_id' => $doctor['doctor_id'],
        'title' => $assignmentData['title']
    ]);
    // ...
}
```

### Why:
- **Loose coupling** - Assignment creation doesn't need to know about notifications/logging
- **Extensibility** - Easy to add new observers (email, SMS, etc.) without changing assignment code
- **Event-driven** - Automatically triggers notifications and audit logs when assignments are created
- **Separation of concerns** - Assignment logic separate from notification/logging logic

---

## 5. **Singleton Pattern** (Creational)
**Location:** `app/patterns/Singleton/DatabaseConnection.php`  
**Used in:** `app/controllers/Doctor.php` (lines 103, 799)

### Where it's used:
```php
// Singleton Pattern - Get database connection
$db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
$stmt = $db->prepare("INSERT INTO doctors (user_id) VALUES (:user_id)");
$stmt->execute(['user_id' => $userId]);
```

### Why:
- **Single database connection** - Ensures only one connection exists throughout the application
- **Resource management** - Prevents multiple connections that could exhaust database resources
- **Global access** - Available anywhere in the application
- **Performance** - Reuses existing connection instead of creating new ones

---

## 6. **Adapter Pattern** (Structural)
**Location:** `app/patterns/Adapter/NotificationService.php` and `DatabaseNotificationAdapter.php`  
**Used in:** `app/controllers/Doctor.php` (lines 66-68)

### Where it's used:
```php
// Adapter Pattern - Notification service with database adapter
$notificationAdapter = new DatabaseNotificationAdapter($this->notificationModel);
$this->notificationService = new NotificationService($notificationAdapter);
```

### Why:
- **Interface compatibility** - Adapts the Notification model to work with NotificationService interface
- **Flexibility** - Can easily swap adapters (Database, Email, SMS) without changing service code
- **Abstraction** - NotificationService doesn't need to know about database implementation
- **Future-proofing** - Easy to add new notification channels (email, SMS) by creating new adapters

---

## Pattern Summary Table

| Pattern | Type | Purpose | Location in Doctor Controller |
|---------|------|---------|------------------------------|
| **Factory Method** | Creational | Centralized model creation | Constructor (lines 55-64) |
| **Builder** | Creational | Step-by-step assignment construction | `createAssignment()` (lines 279-304) |
| **Decorator** | Structural | Format assignments for display | `assignments()` (lines 198-204) |
| **Observer** | Behavioral | Notify about assignment events | Constructor (70-73), `createAssignment()` (318-323) |
| **Singleton** | Creational | Single database connection | `dashboard()` (103), `profile()` (799) |
| **Adapter** | Structural | Adapt notification model to service | Constructor (lines 66-68) |

---

## Benefits of Using Design Patterns

1. **Maintainability** - Code is organized and follows established patterns
2. **Extensibility** - Easy to add new features (e.g., new observers, new adapters)
3. **Testability** - Patterns make it easier to mock and test components
4. **Consistency** - Same patterns used across IT and Doctor modules
5. **Code Reusability** - Patterns can be reused in other modules
6. **Separation of Concerns** - Each pattern handles a specific responsibility

---

## Pattern Relationships

- **Factory + Singleton**: Factory uses Singleton pattern internally to reuse model instances
- **Builder + Factory**: Builder creates data structures that Factory-created models use
- **Observer + Adapter**: Observers use Adapter pattern to send notifications through different channels
- **Decorator + Factory**: Decorators wrap Factory-created model data for display

All patterns work together to create a cohesive, maintainable, and extensible system! 🎯

