# Doctor Module - Complete Functionality Analysis

## Overview
The Doctor module is a comprehensive academic management system that allows doctors (professors/instructors) to manage courses, assignments, attendance, materials, and student interactions. The module follows the same architectural patterns as the IT Officer module, using 6 design patterns for maintainability and extensibility.

---

## Table of Contents
1. [Core Functionality](#core-functionality)
2. [Design Patterns Used](#design-patterns-used)
3. [Database Schema](#database-schema)
4. [All Routes](#all-routes)
5. [Controller Methods](#controller-methods)
6. [Views and User Interface](#views-and-user-interface)
7. [Models and Data Access](#models-and-data-access)
8. [Key Features Breakdown](#key-features-breakdown)

---

## Core Functionality

### 1. **Dashboard** (`dashboard()`)
- **Purpose**: Main landing page showing doctor's overview
- **Features**:
  - Displays statistics: Active Sections, Total Assignments, Total Students, Pending Gradings
  - Shows recent sections (last 5)
  - Shows recent assignments (last 5)
  - Quick navigation links to major sections
- **Data Retrieved**:
  - Doctor profile information (from `doctors` and `users` tables)
  - Sections assigned to the doctor
  - Assignments created by the doctor
- **Special Logic**:
  - Auto-creates doctor record if it doesn't exist (for backward compatibility)
  - Handles missing doctor profile gracefully with error messages

### 2. **Course Management** (`course()`)
- **Purpose**: View and manage assigned courses with all related data
- **Features**:
  - Lists all courses assigned to the doctor (grouped by course)
  - Shows course details: code, name, department, credit hours
  - Displays sections for each course with schedule information
  - **Course Materials Management**:
    - View all uploaded materials for each course
    - Upload new materials
    - Edit existing materials
  - **Assignment Management**:
    - View all assignments for each course
    - See student submissions for each assignment
    - **Grade Student Submissions**:
      - Input grades (points-based)
      - Add feedback comments
      - Save grades (submissions table updated)
  - **Student Enrollment**:
    - View enrolled students count per course
    - Access to take attendance for each section
- **Key Relationships**:
  - Doctor → Sections → Courses
  - Courses → Assignments → Submissions → Students
  - Courses → Materials

### 3. **Assignment Management** (`assignments()`)
- **Purpose**: Manage all assignments across all courses
- **Features**:
  - List all assignments with filtering options:
    - Filter by course
    - Filter by status (active/completed)
    - Filter by type (homework/project/quiz/exam)
    - Filter by semester/year
  - View assignment details:
    - Title, description, due date
    - Points, type, course/section
    - Submission statistics (submitted/total)
    - File attachments
  - **Visibility Control**:
    - Show/hide assignments from students
    - Set visibility duration (hours/days)
    - Auto-hide after specified time
  - Edit assignments
  - Create new assignments (redirects to create page)
- **Uses Decorator Pattern**: Formats assignments with status badges and formatted strings

### 4. **Create Assignment** (`createAssignment()`)
- **Purpose**: Create new assignments for courses/sections
- **Features**:
  - **Required Fields**:
    - Course selection (dropdown populated with doctor's courses)
    - Section selection (dynamic based on selected course)
    - Assignment title
    - Assignment type (homework, project, quiz, lab, exam)
    - Due date & time
    - Points (max score)
    - Description
  - **Optional Fields**:
    - File upload (PDF, DOC, DOCX, TXT, ZIP, RAR)
    - Custom file name
  - **Visibility Settings**:
    - Visible/hidden toggle
    - Duration (hours or days) before auto-hiding
  - **Uses Builder Pattern**: Step-by-step construction of assignment data
  - **Uses Observer Pattern**: Notifies observers when assignment is created:
    - Sends notifications to enrolled students
    - Logs audit trail
- **File Handling**:
  - Files uploaded to `/public/uploads/assignments/`
  - Unique filename generation: `timestamp_uniqid_originalname`
  - File validation and sanitization

### 5. **Edit Assignment** (`editAssignment()`)
- **Purpose**: Modify existing assignments
- **Features**:
  - Edit all assignment fields (same as create form)
  - Update or replace assignment file
  - Rename existing file without re-uploading
  - Access control: Only doctor who created the assignment can edit
- **Validation**: Ensures assignment belongs to logged-in doctor

### 6. **Attendance Management** (`attendance()`)
- **Purpose**: Overview of attendance for all assigned sections
- **Features**:
  - Lists all sections assigned to doctor
  - Shows attendance statistics per section:
    - Present count
    - Absent count
    - Late count
    - Total students
  - Quick access to "Take Attendance" for each section
- **Data Aggregation**: Combines attendance records from `attendance` table

### 7. **Take Attendance** (`takeAttendance()`)
- **Purpose**: Record attendance for a specific section on a specific date
- **Features**:
  - Select attendance date (defaults to today)
  - Lists all enrolled students for the section
  - Record status for each student:
    - Present
    - Absent
    - Late
    - Excused
  - Add notes per student
  - Save attendance (supports updating existing records for same date)
- **Business Logic**:
  - Only shows students enrolled in the specific section
  - Pre-fills existing attendance if already recorded for the date
  - Validates doctor has access to the section

### 8. **Notifications** (`notifications()`)
- **Purpose**: View and manage received notifications
- **Features**:
  - Lists all notifications for the doctor (last 50)
  - Shows notification types with icons:
    - Success (green check)
    - Warning (yellow triangle)
    - Error (red X)
    - Info (blue circle)
  - Mark notifications as read
  - Unread count indicator
  - Notification details: title, message, timestamp

### 9. **Send Notification** (`sendNotification()`)
- **Purpose**: Send notifications to enrolled students
- **Features**:
  - Select recipients (students enrolled in doctor's sections)
  - Compose notification:
    - Title
    - Message/content
    - Type (success, warning, error, info)
  - Send to individual students or multiple students
  - Success/error feedback
- **Recipient Selection**: Only students enrolled in doctor's assigned sections

### 10. **Profile Management** (`profile()`)
- **Purpose**: Manage doctor's personal information
- **Features**:
  - View profile statistics (sections count, assignments count)
  - Edit personal information:
    - First name, last name
    - Phone number
    - Department
  - Email is read-only (cannot be changed)
  - Doctor ID display (read-only)
- **Update Logic**: Updates both `users` and `doctors` tables in a transaction

### 11. **Upload Course Material** (`uploadMaterial()`)
- **Purpose**: Upload files for courses (lectures, handouts, etc.)
- **Features**:
  - Select course and section (optional)
  - Material details:
    - Title
    - Description
    - Material type (lecture, handout, reference, syllabus, other)
  - File upload (any file type)
  - Files stored in `/public/uploads/materials/`
  - Unique filename generation
- **Material Types**:
  - Lecture notes/slides
  - Handouts
  - Reference materials
  - Course syllabus
  - Other documents

### 12. **Edit Material** (`editMaterial()`)
- **Purpose**: Modify uploaded course materials
- **Features**:
  - Edit title and description
  - Replace file (optional)
  - Rename file without re-uploading
  - Access control: Only doctor who uploaded can edit

### 13. **Create Course** (`createCourse()`)
- **Purpose**: Request course creation (placeholder)
- **Current Status**: Informational only
- **Note**: Course creation is typically done by IT Officer, but doctors can view this page to see recent courses

---

## Design Patterns Used

### 1. **Factory Method Pattern** (Creational)
- **Location**: `app/patterns/Factory/ModelFactory.php`
- **Used in**: `app/controllers/Doctor.php` constructor
- **Purpose**: Centralized creation of all model instances
- **Models Created**:
  - Doctor, Course, Section, Assignment, Attendance, Student
  - AuditLog, Notification, Material
- **Benefits**: Consistent model creation, easy to modify creation logic

### 2. **Builder Pattern** (Creational)
- **Location**: `app/patterns/Builder/AssignmentBuilder.php`
- **Used in**: `createAssignment()` method
- **Purpose**: Step-by-step construction of complex assignment objects
- **Features**:
  - Fluent interface (method chaining)
  - Validates required fields before building
  - Handles optional fields (file, visibility, semester)
- **Benefits**: Readable code, flexible construction, validation

### 3. **Decorator Pattern** (Structural)
- **Location**: `app/patterns/Decorator/AssignmentDecorator.php`
- **Used in**: `assignments()` method
- **Purpose**: Enhance assignment data with formatting and display properties
- **Features**:
  - `format()`: Creates formatted string representation
  - `getStatusBadge()`: Generates HTML badge based on due date/status
- **Benefits**: Separation of display logic from business logic, extensible

### 4. **Observer Pattern** (Behavioral)
- **Location**: `app/patterns/Observer/AssignmentSubject.php`
- **Used in**: `createAssignment()` method
- **Purpose**: Event-driven notifications and audit logging
- **Observers**:
  - `NotificationObserver`: Sends notifications to students when assignments are created
  - `AuditLogObserver`: Logs assignment creation events
- **Benefits**: Loose coupling, easy to add new observers (email, SMS, etc.)

### 5. **Singleton Pattern** (Creational)
- **Location**: `app/patterns/Singleton/DatabaseConnection.php`
- **Used in**: Multiple places for database access
- **Purpose**: Ensure single database connection throughout application
- **Benefits**: Resource management, performance, global access

### 6. **Adapter Pattern** (Structural)
- **Location**: `app/patterns/Adapter/NotificationService.php` and `DatabaseNotificationAdapter.php`
- **Used in**: Constructor for notification service setup
- **Purpose**: Adapt Notification model to work with NotificationService interface
- **Benefits**: Flexibility to swap adapters (Database, Email, SMS), abstraction

---

## Database Schema

### Core Tables Used

#### `doctors` Table
```sql
- doctor_id (PK, AUTO_INCREMENT)
- user_id (FK → users.id)
- department (VARCHAR)
- bio (TEXT) - Added for doctor bio field
- created_at, updated_at
```

#### `users` Table
```sql
- id (PK, AUTO_INCREMENT)
- first_name, last_name
- email (UNIQUE)
- phone
- password
- role ('doctor')
- created_at, updated_at
```

#### `courses` Table
```sql
- course_id (PK, AUTO_INCREMENT)
- course_code (UNIQUE)
- name
- description
- department
- credit_hours
- created_at, updated_at
```

#### `sections` Table
```sql
- section_id (PK, AUTO_INCREMENT)
- course_id (FK → courses.course_id)
- doctor_id (FK → doctors.doctor_id)
- section_number
- day_of_week
- start_time, end_time
- room
- semester, academic_year
- status
- created_at, updated_at
```

#### `assignments` Table
```sql
- assignment_id (PK, AUTO_INCREMENT)
- course_id (FK → courses.course_id)
- section_id (FK → sections.section_id)
- doctor_id (FK → doctors.doctor_id)
- title, description
- due_date
- max_points
- assignment_type (ENUM: homework, project, quiz, exam, lab)
- file_path, file_name, file_size
- is_visible (BOOLEAN)
- visible_until (DATETIME)
- semester, academic_year
- created_at, updated_at
```

#### `assignment_submissions` Table
```sql
- submission_id (PK, AUTO_INCREMENT)
- assignment_id (FK → assignments.assignment_id)
- student_id (FK → students.student_id)
- file_path, file_name
- submission_text
- grade (DECIMAL)
- feedback (TEXT)
- submitted_at
- graded_at
- status (ENUM: submitted, graded, late)
- created_at, updated_at
- UNIQUE KEY (assignment_id, student_id)
```

#### `attendance` Table
```sql
- attendance_id (PK, AUTO_INCREMENT)
- section_id (FK → sections.section_id)
- student_id (FK → students.student_id)
- attendance_date (DATE)
- status (ENUM: present, absent, late, excused)
- notes (TEXT)
- recorded_by (INT) - doctor_id
- created_at, updated_at
- UNIQUE KEY (section_id, student_id, attendance_date)
```

#### `materials` Table
```sql
- material_id (PK, AUTO_INCREMENT)
- course_id (FK → courses.course_id)
- section_id (FK → sections.section_id, nullable)
- doctor_id (FK → doctors.doctor_id)
- title, description
- file_path, file_name, file_type, file_size
- material_type (ENUM: lecture, handout, reference, syllabus, other)
- created_at, updated_at
```

#### `notifications` Table
```sql
- notification_id (PK, AUTO_INCREMENT)
- user_id (FK → users.id)
- title, message
- type (ENUM: info, success, warning, error)
- is_read (BOOLEAN)
- related_id, related_type
- created_at, updated_at
```

#### `enrollments` Table
```sql
- enrollment_id (PK, AUTO_INCREMENT)
- section_id (FK → sections.section_id)
- student_id (FK → students.student_id)
- status (ENUM: pending, approved, rejected, taking, completed)
- enrollment_date
- final_grade
- created_at, updated_at
```

---

## All Routes

### GET Routes
```php
['GET', '/doctor/dashboard', 'Doctor@dashboard']
['GET', '/doctor/course', 'Doctor@course']
['GET', '/doctor/assignments', 'Doctor@assignments']
['GET', '/doctor/create-assignment', 'Doctor@createAssignment']
['GET', '/doctor/attendance', 'Doctor@attendance']
['GET', '/doctor/take-attendance', 'Doctor@takeAttendance']
['GET', '/doctor/notifications', 'Doctor@notifications']
['GET', '/doctor/send-notification', 'Doctor@sendNotification']
['GET', '/doctor/profile', 'Doctor@profile']
['GET', '/doctor/create-course', 'Doctor@createCourse']
['GET', '/doctor/edit-assignment', 'Doctor@editAssignment']
['GET', '/doctor/upload-material', 'Doctor@uploadMaterial']
['GET', '/doctor/edit-material', 'Doctor@editMaterial']
```

### POST Routes
```php
['POST', '/doctor/create-assignment', 'Doctor@createAssignment']
['POST', '/doctor/take-attendance', 'Doctor@takeAttendance']
['POST', '/doctor/notifications', 'Doctor@notifications'] // Mark as read
['POST', '/doctor/send-notification', 'Doctor@sendNotification']
['POST', '/doctor/profile', 'Doctor@profile']
['POST', '/doctor/create-course', 'Doctor@createCourse']
['POST', '/doctor/edit-assignment', 'Doctor@editAssignment']
['POST', '/doctor/update-grade', 'Doctor@updateGrade'] // TODO: Implement
['POST', '/doctor/toggle-visibility', 'Doctor@toggleVisibility'] // TODO: Implement
['POST', '/doctor/upload-material', 'Doctor@uploadMaterial']
['POST', '/doctor/edit-material', 'Doctor@editMaterial']
```

**Note**: The routes `updateGrade` and `toggleVisibility` are defined but may need implementation in the controller. The grading functionality is currently handled through the `course()` view's form submission.

---

## Controller Methods

### Main Methods

1. **`dashboard()`** - Display dashboard with stats and recent items
2. **`course()`** - Display courses with materials, assignments, submissions, and grading interface
3. **`assignments()`** - List all assignments with filters
4. **`createAssignment()`** - GET: Show form | POST: Process creation
5. **`editAssignment()`** - GET: Show edit form | POST: Process update
6. **`attendance()`** - Display attendance overview for all sections
7. **`takeAttendance()`** - GET: Show form | POST: Record attendance
8. **`notifications()`** - GET: Display notifications | POST: Mark as read
9. **`sendNotification()`** - GET: Show form | POST: Send notification
10. **`profile()`** - GET: Show profile | POST: Update profile
11. **`uploadMaterial()`** - GET: Show form | POST: Upload file
12. **`editMaterial()`** - GET: Show form | POST: Update material
13. **`createCourse()`** - Informational page (course creation by IT only)

### Helper Methods

- **`redirectTo($path)`**: Custom redirect helper that respects base_url config

### Missing/To-Be-Implemented Methods

Based on routes, these methods may need implementation:
- **`updateGrade()`** - Currently handled in `course()` view, but route exists
- **`toggleVisibility()`** - Currently handled in `assignments()` view, but route exists

---

## Views and User Interface

### View Files

1. **`doctor_dashboard.php`**
   - Stats cards (Sections, Assignments, Students, Pending Gradings)
   - Recent sections list
   - Recent assignments list
   - Modern, responsive design with gradient backgrounds

2. **`doctor_course.php`**
   - Course cards with details
   - Course materials section with upload/edit
   - Assignments section with student submissions table
   - Grading form (inline in table)
   - Sections list with attendance links
   - Material and assignment counts

3. **`doctor_assignments.php`**
   - Filter bar (course, status, type, semester, year)
   - Assignment cards with details
   - Visibility toggle controls
   - Edit buttons
   - Submission statistics
   - Status badges (Active, Completed, Due Soon)

4. **`create_assignment.php`**
   - Two-column form layout
   - Course/section dropdowns (dynamic)
   - File upload with custom name
   - Visibility settings with duration
   - Recent assignments sidebar

5. **`doctor_attendance.php`**
   - Section cards with stats
   - Attendance statistics (Present, Absent, Late, Total)
   - Quick "Take Attendance" buttons

6. **`take_attendance.php`**
   - Date selector
   - Student list with status radio buttons
   - Notes field per student
   - Save button

7. **`doctor_notifications.php`**
   - Notification list with icons
   - Unread indicator
   - Mark as read functionality
   - Notification type styling

8. **`send_notification.php`**
   - Student selection (checkboxes)
   - Notification composition form
   - Type selector

9. **`doctor_profile.php`**
   - Profile stats cards
   - Edit form for personal info
   - Read-only fields (email, doctor ID)

10. **`upload_material.php`** / **`edit_material.php`**
    - Course/section selection
    - Material details form
    - File upload/replace

11. **`create_course.php`**
    - Informational message
    - Recent courses display

---

## Models and Data Access

### Doctor Model (`app/models/Doctor.php`)
- **`findById($doctorId)`**: Get doctor by ID with user info
- **`findByUserId($userId)`**: Get doctor by user ID
- **`getAll($filters)`**: Get all doctors with filters (search, department)
- **`createDoctorWithUser($userData, $doctorData)`**: Create doctor with user account
- **`updateDoctor($doctorId, $userData, $doctorData)`**: Update doctor and user info
- **`deleteDoctor($doctorId)`**: Delete doctor (cascade deletes user)
- **`isAvailable($doctorId, $dayOfWeek, $startTime, $endTime, $semester, $academicYear)`**: Check doctor availability for scheduling

### Assignment Model (`app/models/Assignment.php`)
- **`findById($assignmentId)`**: Get assignment with related data
- **`getByDoctor($doctorId, $filters)`**: Get assignments for doctor with filters
- **`create($data)`**: Create new assignment
- **`update($assignmentId, $data)`**: Update assignment
- **`toggleVisibility($assignmentId, $isVisible, $visibleUntil)`**: Show/hide assignment
- **`getSubmissionsByAssignment($assignmentId)`**: Get all submissions for an assignment
- **`updateGrade($submissionId, $grade, $feedback)`**: Grade a submission
- **`getSubmissionCount($assignmentId)`**: Get submission statistics

### Attendance Model (`app/models/Attendance.php`)
- **`recordAttendance($sectionId, $attendanceData, $recordedBy)`**: Record attendance for multiple students
- **`getBySection($sectionId, $date)`**: Get attendance records for section
- **`getByDate($sectionId, $date)`**: Get attendance for specific date
- **`getStudentAttendance($studentId, $sectionId)`**: Get attendance history for student
- **`getAttendanceStats($sectionId)`**: Get aggregated statistics

### Course Model (`app/models/Course.php`)
- **`findById($courseId)`**: Get course details
- **`getAll($filters)`**: Get all courses
- **`assignDoctor($courseId, $doctorId)`**: Assign doctor to course (creates section)
- **`getAssignedDoctors($courseId)`**: Get doctors assigned to course
- **`enrollStudent($courseId, $studentId)`**: Enroll student in course

### Section Model (`app/models/Section.php`)
- **`getByDoctor($doctorId)`**: Get all sections assigned to doctor
- **`getEnrolledStudents($sectionId)`**: Get students enrolled in section
- **`findById($sectionId)`**: Get section details

### Material Model (`app/models/Material.php`)
- **`create($data)`**: Upload material
- **`update($materialId, $data)`**: Update material
- **`findById($materialId)`**: Get material details
- **`getByCourse($courseId)`**: Get materials for course

### Notification Model (`app/models/Notification.php`)
- **`create($data)`**: Create notification
- **`getByUserId($userId, $limit)`**: Get notifications for user
- **`getUnreadByUserId($userId)`**: Get unread notifications
- **`markAsRead($notificationId, $userId)`**: Mark notification as read

---

## Key Features Breakdown

### 1. Assignment Lifecycle
1. **Create**: Doctor creates assignment using Builder Pattern
2. **Notify**: Observer Pattern triggers notifications to students
3. **Visibility**: Doctor controls when assignment is visible
4. **Submission**: Students submit work (handled by Student module)
5. **Grading**: Doctor grades submissions inline in course view
6. **Feedback**: Doctor provides feedback comments

### 2. Attendance Workflow
1. **Overview**: Doctor views all sections with attendance stats
2. **Select Section**: Choose section to take attendance
3. **Record**: Mark each student as Present/Absent/Late/Excused
4. **Notes**: Add optional notes per student
5. **Save**: Records saved/updated in database
6. **View Stats**: Aggregated statistics displayed

### 3. Material Management
1. **Upload**: Doctor uploads file to course/section
2. **Categorize**: Assign material type (lecture, handout, etc.)
3. **Edit**: Update title, description, or replace file
4. **Access**: Students can view/download (via Student module)

### 4. Notification System
1. **Receive**: Doctor receives notifications from system/IT Officer
2. **View**: List all notifications with type indicators
3. **Mark Read**: Update read status
4. **Send**: Doctor can send notifications to enrolled students

### 5. Course Management Flow
1. **View Courses**: Doctor sees all assigned courses
2. **Sections**: View all sections for each course
3. **Materials**: Manage course materials
4. **Assignments**: View and grade student work
5. **Students**: See enrolled students per course
6. **Attendance**: Quick access to take attendance

---

## Security and Access Control

### Authentication
- All methods check `$_SESSION['user']['role'] === 'doctor'`
- Redirects to login if not authenticated

### Authorization
- Doctors can only access their own assignments
- Doctors can only view sections assigned to them
- Edit operations validate ownership
- Students can only access materials/assignments for enrolled courses

### Data Validation
- Email normalization (lowercase, trimmed)
- File upload validation (type, size)
- SQL injection prevention (prepared statements)
- XSS prevention (htmlspecialchars in views)

---

## File Storage

### Upload Directories
- **Assignments**: `/public/uploads/assignments/`
- **Materials**: `/public/uploads/materials/`

### File Naming
- Format: `timestamp_uniqid_originalname`
- Sanitization: Removes special characters
- Extension preservation

---

## Error Handling

### Try-Catch Blocks
- All controller methods wrapped in try-catch
- Errors logged to PHP error log
- User-friendly error pages displayed

### Transaction Management
- Critical operations use database transactions
- Rollback on errors
- Atomic operations (e.g., doctor creation with user)

---

## Integration Points

### With IT Officer Module
- IT Officer assigns doctors to courses (creates sections)
- IT Officer creates courses
- IT Officer manages enrollment approvals

### With Student Module
- Students view assignments (when visible)
- Students submit assignments
- Students view materials
- Students view attendance

### With Admin Module
- Admin creates doctor accounts
- Admin manages doctor profiles
- Admin views all system data

---

## Future Enhancements (Potential)

1. **Gradebook**: Comprehensive grade tracking and calculation
2. **Announcements**: Course-wide announcements separate from assignments
3. **Discussion Forums**: Course discussion boards
4. **Video Lectures**: Integration with video platforms
5. **Online Quizzes**: Automated quiz creation and grading
6. **Calendar Integration**: Sync assignments/deadlines
7. **Email Notifications**: Email alerts for important events
8. **Mobile App**: Mobile-friendly interface

---

## Summary

The Doctor module is a feature-rich academic management system that provides:
- **Course Management**: Full lifecycle from creation to grading
- **Assignment System**: Creation, visibility control, and grading
- **Attendance Tracking**: Comprehensive attendance management
- **Material Distribution**: File upload and management
- **Communication**: Notification system
- **Professional Design**: Modern UI with responsive layouts
- **Clean Architecture**: Design patterns ensure maintainability and extensibility

All functionality follows MVC pattern with clear separation of concerns, making the codebase easy to understand, maintain, and extend.

