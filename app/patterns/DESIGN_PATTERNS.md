# Design Patterns Implementation Documentation

This document describes all the design patterns implemented in the IT Officer module.

## 1. Strategy Pattern (Behavioral)

**Location:** `app/patterns/Strategy/`

**Purpose:** Allows selecting conflict detection algorithms at runtime.

**Components:**
- `ConflictDetectionStrategy` (Interface)
- `TimeSlotConflictStrategy` (Concrete Strategy)
- `RoomConflictStrategy` (Concrete Strategy)
- `DoctorAvailabilityStrategy` (Concrete Strategy)
- `ConflictDetector` (Context)

**Usage:**
```php
$detector = new ConflictDetector(new TimeSlotConflictStrategy($db));
if ($detector->detectConflict($data)) {
    $error = $detector->getErrorMessage();
}
```

**Benefits:**
- Easy to add new conflict detection strategies
- Runtime algorithm selection
- Separation of concerns

---

## 2. Factory Method Pattern (Creational)

**Location:** `app/patterns/Factory/ModelFactory.php`

**Purpose:** Creates model instances without specifying exact classes.

**Components:**
- `ModelFactory` (Factory)

**Usage:**
```php
$sectionModel = ModelFactory::create('Section');
$courseModel = ModelFactory::create('Course');
```

**Benefits:**
- Centralized object creation
- Easy to swap implementations
- Reduces coupling

---

## 3. Builder Pattern (Creational)

**Location:** `app/patterns/Builder/SectionBuilder.php`

**Purpose:** Constructs complex Section objects step by step.

**Components:**
- `SectionBuilder` (Builder)

**Usage:**
```php
$builder = new SectionBuilder();
$builder->setCourse($courseId)
        ->setDoctor($doctorId)
        ->setSectionNumber('001')
        ->setTimeSlot('Monday', '09:00', '10:30')
        ->setCapacity(30);
$sectionData = $builder->build();
```

**Benefits:**
- Flexible object construction
- Readable code
- Validation before creation

---

## 4. Singleton Pattern (Creational)

**Location:** `app/patterns/Singleton/DatabaseConnection.php`

**Purpose:** Ensures only one database connection instance exists.

**Components:**
- `DatabaseConnection` (Singleton)

**Usage:**
```php
$db = DatabaseConnection::getInstance()->getConnection();
```

**Benefits:**
- Single database connection
- Resource efficiency
- Global access point

---

## 5. Adapter Pattern (Structural)

**Location:** `app/patterns/Adapter/NotificationAdapter.php`

**Purpose:** Allows incompatible notification systems to work together.

**Components:**
- `NotificationAdapter` (Target Interface)
- `DatabaseNotificationAdapter` (Adapter)
- `EmailNotificationAdapter` (Adapter)
- `NotificationService` (Client)

**Usage:**
```php
$adapter = new DatabaseNotificationAdapter($notificationModel);
$service = new NotificationService($adapter);
$service->notify('Title', 'Message', [$userId], 'info');
```

**Benefits:**
- Integrates different notification systems
- Easy to add new adapters (SMS, Push, etc.)
- Client code doesn't change

---

## 6. Decorator Pattern (Structural)

**Location:** `app/patterns/Decorator/ModelDecorator.php`

**Purpose:** Adds new functionality to objects dynamically.

**Components:**
- `ModelDecoratorInterface` (Component)
- `ModelDecorator` (Base Decorator)
- `SectionDecorator` (Concrete Decorator)
- `EnrollmentRequestDecorator` (Concrete Decorator)

**Usage:**
```php
$decorator = new SectionDecorator($sectionData);
$formatted = $decorator->format();
$status = $decorator->getEnrollmentStatus();
```

**Benefits:**
- Add features without modifying original classes
- Flexible composition
- Follows Open/Closed Principle

---

## 7. Observer Pattern (Behavioral)

**Location:** `app/patterns/Observer/`

**Purpose:** Notifies multiple objects about state changes.

**Components:**
- `Subject` (Interface)
- `Observer` (Interface)
- `EnrollmentSubject` (Concrete Subject)
- `NotificationObserver` (Concrete Observer)
- `AuditLogObserver` (Concrete Observer)

**Usage:**
```php
$subject = new EnrollmentSubject();
$subject->attach(new NotificationObserver($notificationModel));
$subject->attach(new AuditLogObserver($auditLogModel));
$subject->enrollmentApproved($data);
```

**Benefits:**
- Loose coupling between subject and observers
- Easy to add/remove observers
- Event-driven architecture

---

## Pattern Categories Summary

### Creational Patterns
1. **Factory Method** - Model creation
2. **Builder** - Section construction
3. **Singleton** - Database connection

### Structural Patterns
1. **Adapter** - Notification system integration
2. **Decorator** - Model formatting enhancement

### Behavioral Patterns
1. **Strategy** - Conflict detection algorithms
2. **Observer** - Event notifications

---

## Integration in IT Officer Controller

The `ItOfficerRefactored` controller demonstrates how all patterns work together:

1. **Factory Method** - Creates all model instances
2. **Singleton** - Provides database connection
3. **Builder** - Constructs section data
4. **Strategy** - Detects conflicts using different algorithms
5. **Observer** - Notifies about enrollment events
6. **Adapter** - Sends notifications through different channels
7. **Decorator** - Formats data for display

---

## Benefits of Using Design Patterns

1. **Maintainability** - Code is easier to understand and modify
2. **Scalability** - Easy to add new features
3. **Testability** - Patterns enable better unit testing
4. **Reusability** - Patterns can be reused across the application
5. **Best Practices** - Follows industry-standard solutions

---

## Future Enhancements

1. **Command Pattern** - Encapsulate enrollment actions as commands
2. **Facade Pattern** - Simplify complex enrollment workflow
3. **Template Method** - Define enrollment process skeleton
4. **Chain of Responsibility** - Handle enrollment validation chain

