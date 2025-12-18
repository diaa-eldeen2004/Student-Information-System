<?php
namespace controllers;

use core\Controller;
use patterns\Factory\ModelFactory;
use patterns\Adapter\NotificationService;
use patterns\Adapter\DatabaseNotificationAdapter;
use patterns\Observer\EnrollmentSubject;
use patterns\Observer\NotificationObserver;
use patterns\Observer\AuditLogObserver;
use models\Student as StudentModel;
use models\Course;
use models\Schedule;
use models\Assignment;
use models\Attendance;
use models\EnrollmentRequest;
use models\AuditLog;
use models\Notification;
use models\Material;
use models\CalendarEvent;

class Student extends Controller
{
    private StudentModel $studentModel;
    private Course $courseModel;
    private Schedule $sectionModel;
    private Assignment $assignmentModel;
    private Attendance $attendanceModel;
    private EnrollmentRequest $enrollmentRequestModel;
    private AuditLog $auditLogModel;
    private Notification $notificationModel;
    private Material $materialModel;
    private CalendarEvent $calendarEventModel;
    
    // Observer Pattern
    private EnrollmentSubject $enrollmentSubject;
    
    // Adapter Pattern
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['user'])) {
            $this->redirectTo('auth/login');
        }
        
        $userRole = $_SESSION['user']['role'] ?? '';
        
        // Only allow users with student role
        if ($userRole !== 'student') {
            $this->redirectTo('auth/login');
        }

        // Factory Method Pattern - Create all models
        $this->studentModel = ModelFactory::create('Student');
        $this->courseModel = ModelFactory::create('Course');
        $this->sectionModel = ModelFactory::create('Schedule');
        $this->assignmentModel = ModelFactory::create('Assignment');
        $this->attendanceModel = ModelFactory::create('Attendance');
        $this->enrollmentRequestModel = ModelFactory::create('EnrollmentRequest');
        $this->auditLogModel = ModelFactory::create('AuditLog');
        $this->notificationModel = ModelFactory::create('Notification');
        $this->materialModel = ModelFactory::create('Material');
        $this->calendarEventModel = ModelFactory::create('CalendarEvent');

        // Adapter Pattern - Notification service with database adapter
        $notificationAdapter = new DatabaseNotificationAdapter($this->notificationModel);
        $this->notificationService = new NotificationService($notificationAdapter);

        // Observer Pattern - Setup observers for enrollment events
        $this->enrollmentSubject = new EnrollmentSubject();
        $this->enrollmentSubject->attach(new NotificationObserver($this->notificationModel));
        $this->enrollmentSubject->attach(new AuditLogObserver($this->auditLogModel));
    }

    /**
     * Redirect helper that respects base_url config
     */
    private function redirectTo(string $path): void
    {
        $config = require dirname(__DIR__) . '/config/config.php';
        $base = rtrim($config['base_url'] ?? '', '/');
        $target = $base . '/' . ltrim($path, '/');
        header("Location: {$target}");
        exit;
    }

    public function dashboard(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied', 'message' => 'User not logged in']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            
            if (!$student) {
                // Try to create a minimal student record automatically
                try {
                    $created = $this->studentModel->createStudent([
                        'user_id' => $userId,
                        'student_number' => null,
                        'gpa' => 0.00,
                        'admission_date' => date('Y-m-d'),
                        'major' => null,
                        'minor' => null,
                        'status' => 'active',
                    ]);
                    
                    if ($created) {
                        // Retry fetching the student record
                        $student = $this->studentModel->findByUserId($userId);
                    }
                } catch (\Exception $e) {
                    error_log("Auto-create student record failed: " . $e->getMessage());
                }
                
                // If still no student record, show error
                if (!$student) {
                    $this->view->render('errors/404', [
                        'title' => 'Student Record Not Found', 
                        'message' => 'Your student record has not been created yet. Please contact the administrator to set up your student account.'
                    ]);
                    return;
                }
            }
            
            $studentId = $student['student_id'];
            
            // Get current semester and year (default to current)
            $currentSemester = date('n') >= 1 && date('n') <= 5 ? 'Spring' : 'Fall';
            $currentYear = date('Y');
            
            // Get enrolled courses for current semester
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId, $currentSemester, $currentYear);
            
            // Get GPA
            $gpa = $student['gpa'] ?? 0.00;
            
            // Get recent notifications
            $notifications = $this->notificationModel->getUnreadByUserId($userId);
            
            // Get upcoming assignments (due in next 7 days)
            $upcomingAssignments = [];
            $allAssignments = $this->studentModel->getAssignmentsForStudent($studentId);
            $now = new \DateTime();
            foreach ($allAssignments as $assignment) {
                if (!empty($assignment['due_date'])) {
                    $dueDate = new \DateTime($assignment['due_date']);
                    $daysUntilDue = $now->diff($dueDate)->days;
                    if ($daysUntilDue <= 7 && $daysUntilDue >= 0 && empty($assignment['submission_id'])) {
                        $upcomingAssignments[] = $assignment;
                    }
                }
            }
            
            // Get attendance summary
            $attendanceSummary = [];
            foreach ($enrolledCourses as $course) {
                $sectionId = $course['section_id'];
                $percentage = $this->attendanceModel->getStudentAttendancePercentage($studentId, $sectionId);
                $attendanceSummary[$sectionId] = [
                    'course_code' => $course['course_code'],
                    'course_name' => $course['course_name'],
                    'percentage' => $percentage
                ];
            }
            
            $this->view->render('student/student_dashboard', [
                'title' => 'Student Dashboard',
                'student' => $student,
                'gpa' => $gpa,
                'enrolledCourses' => $enrolledCourses,
                'notifications' => $notifications,
                'upcomingAssignments' => array_slice($upcomingAssignments, 0, 5),
                'attendanceSummary' => $attendanceSummary,
                'currentSemester' => $currentSemester,
                'currentYear' => $currentYear,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student dashboard error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading the dashboard.'
            ]);
        }
    }

    public function course(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            
            // Get all enrolled courses
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId);
            
            // If course_id is specified, get course details
            $selectedCourse = null;
            $materials = [];
            $assignments = [];
            
            if ($courseId) {
                // Verify student is enrolled in this course
                $isEnrolled = false;
                foreach ($enrolledCourses as $course) {
                    if ($course['course_id'] == $courseId) {
                        $isEnrolled = true;
                        $selectedCourse = $course;
                        break;
                    }
                }
                
                if ($isEnrolled) {
                    // Get materials for this course
                    $materials = $this->materialModel->getByCourse($courseId);
                    
                    // Get assignments for this course
                    $assignments = $this->studentModel->getAssignmentsForStudent($studentId, $courseId);
                } else {
                    $this->view->render('errors/403', [
                        'title' => 'Access Denied',
                        'message' => 'You are not enrolled in this course.'
                    ]);
                    return;
                }
            }
            
            $this->view->render('student/student_course', [
                'title' => 'My Courses',
                'student' => $student,
                'enrolledCourses' => $enrolledCourses,
                'selectedCourse' => $selectedCourse,
                'materials' => $materials,
                'assignments' => $assignments,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student course error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading course information.'
            ]);
        }
    }

    public function schedule(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            
            // Get semester and year from query params or use current
            $semester = $_GET['semester'] ?? (date('n') >= 1 && date('n') <= 5 ? 'Spring' : 'Fall');
            $academicYear = $_GET['year'] ?? date('Y');
            
            // Get student's schedule
            $schedule = $this->studentModel->getStudentSchedule($studentId, $semester, $academicYear);
            
            // Get available sections for enrollment
            $availableSections = $this->studentModel->getAvailableSectionsForEnrollment($semester, $academicYear);
            
            // Get existing enrollment requests
            $enrollmentRequests = $this->studentModel->getEnrollmentRequests($studentId);
            $requestedSectionIds = [];
            foreach ($enrollmentRequests as $request) {
                $scheduleId = $request['schedule_id'] ?? $request['section_id'] ?? null;
                if ($scheduleId) {
                    $requestedSectionIds[] = $scheduleId;
                }
            }
            
            // Organize by day for weekly view
            $weeklySchedule = [
                'Monday' => [],
                'Tuesday' => [],
                'Wednesday' => [],
                'Thursday' => [],
                'Friday' => [],
                'Saturday' => [],
                'Sunday' => [],
            ];
            
            foreach ($schedule as $entry) {
                $day = $entry['day_of_week'] ?? '';
                if ($day && isset($weeklySchedule[$day])) {
                    $weeklySchedule[$day][] = $entry;
                }
            }
            
            // Sort each day by start time
            foreach ($weeklySchedule as $day => &$dayEntries) {
                usort($dayEntries, function($a, $b) {
                    return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
                });
            }
            
            $this->view->render('student/student_schedule', [
                'title' => 'My Schedule',
                'student' => $student,
                'schedule' => $schedule,
                'weeklySchedule' => $weeklySchedule,
                'availableSections' => $availableSections,
                'enrollmentRequests' => $enrollmentRequests,
                'requestedSectionIds' => $requestedSectionIds,
                'showSidebar' => true,
                'semester' => $semester,
                'academicYear' => $academicYear,
            ]);
        } catch (\Exception $e) {
            error_log("Student schedule error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading schedule.'
            ]);
        }
    }

    public function previewTimetable(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
            
            $scheduleId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
            
            if (!$scheduleId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid schedule ID']);
                exit;
            }
            
            // Get the schedule entry
            $schedule = $this->sectionModel->findById($scheduleId);
            if (!$schedule) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Schedule not found']);
                exit;
            }
            
            // Get timetable for this specific schedule
            $timetable = $this->sectionModel->getScheduleTimetable($scheduleId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'timetable' => $timetable,
                'schedule' => $schedule
            ]);
            exit;
        } catch (\Exception $e) {
            error_log("Preview timetable error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'An error occurred']);
            exit;
        }
    }

    public function assignments(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            
            // Get all assignments for student
            $assignments = $this->studentModel->getAssignmentsForStudent($studentId);
            
            // Organize by status
            $pending = [];
            $submitted = [];
            $graded = [];
            $overdue = [];
            
            $now = new \DateTime();
            foreach ($assignments as $assignment) {
                $dueDate = !empty($assignment['due_date']) ? new \DateTime($assignment['due_date']) : null;
                
                if (!empty($assignment['submission_id'])) {
                    if (!empty($assignment['grade'])) {
                        $graded[] = $assignment;
                    } else {
                        $submitted[] = $assignment;
                    }
                } else {
                    if ($dueDate && $dueDate < $now) {
                        $overdue[] = $assignment;
                    } else {
                        $pending[] = $assignment;
                    }
                }
            }
            
            $this->view->render('student/student_assignments', [
                'title' => 'My Assignments',
                'student' => $student,
                'assignments' => $assignments,
                'pending' => $pending,
                'submitted' => $submitted,
                'graded' => $graded,
                'overdue' => $overdue,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student assignments error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading assignments.'
            ]);
        }
    }

    public function uploadAssignment(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectTo('student/assignments');
                return;
            }
            
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            $assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
            
            if (!$assignmentId) {
                $_SESSION['error'] = 'Invalid assignment ID.';
                $this->redirectTo('student/assignments');
                return;
            }
            
            // Get assignment details
            $assignment = $this->assignmentModel->findById($assignmentId);
            if (!$assignment) {
                $_SESSION['error'] = 'Assignment not found.';
                $this->redirectTo('student/assignments');
                return;
            }
            
            // Check if student is enrolled in the course
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId);
            $isEnrolled = false;
            foreach ($enrolledCourses as $course) {
                if ($course['course_id'] == $assignment['course_id']) {
                    $isEnrolled = true;
                    break;
                }
            }
            
            if (!$isEnrolled) {
                $_SESSION['error'] = 'You are not enrolled in this course.';
                $this->redirectTo('student/assignments');
                return;
            }
            
            // Check if assignment is still open
            if (!empty($assignment['due_date'])) {
                $dueDate = new \DateTime($assignment['due_date']);
                $now = new \DateTime();
                if ($dueDate < $now) {
                    $_SESSION['error'] = 'Assignment deadline has passed.';
                    $this->redirectTo('student/assignments');
                    return;
                }
            }
            
            // Handle file upload
            if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Please select a file to upload.';
                $this->redirectTo('student/assignments');
                return;
            }
            
            $file = $_FILES['submission_file'];
            $maxSize = 10 * 1024 * 1024; // 10MB
            $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar'];
            
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File size exceeds maximum allowed (10MB).';
                $this->redirectTo('student/assignments');
                return;
            }
            
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
                $this->redirectTo('student/assignments');
                return;
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = 'assignment_' . $assignmentId . '_student_' . $studentId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $_SESSION['error'] = 'Failed to upload file.';
                $this->redirectTo('student/assignments');
                return;
            }
            
            // Save submission to database
            $relativePath = '/uploads/assignments/' . $fileName;
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $db->beginTransaction();
            
            try {
                // Check if submission already exists
                $existing = $this->studentModel->getSubmission($studentId, $assignmentId);
                
                if ($existing) {
                    // Delete old file
                    $oldPath = dirname(__DIR__, 2) . '/public' . $existing['file_path'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                    
                    // Update submission
                    $stmt = $db->prepare("
                        UPDATE assignment_submissions 
                        SET file_path = :file_path, file_name = :file_name, file_size = :file_size,
                            submitted_at = NOW(), status = 'submitted'
                        WHERE submission_id = :submission_id
                    ");
                    $stmt->execute([
                        'submission_id' => $existing['submission_id'],
                        'file_path' => $relativePath,
                        'file_name' => $file['name'],
                        'file_size' => $file['size'],
                    ]);
                    $submissionId = $existing['submission_id'];
                } else {
                    // Create new submission
                    $stmt = $db->prepare("
                        INSERT INTO assignment_submissions 
                        (student_id, assignment_id, file_path, file_name, file_size, status, submitted_at)
                        VALUES (:student_id, :assignment_id, :file_path, :file_name, :file_size, 'submitted', NOW())
                    ");
                    $stmt->execute([
                        'student_id' => $studentId,
                        'assignment_id' => $assignmentId,
                        'file_path' => $relativePath,
                        'file_name' => $file['name'],
                        'file_size' => $file['size'],
                    ]);
                    $submissionId = $db->lastInsertId();
                }
                
                $db->commit();
                
                // Log action
                $this->auditLogModel->create([
                    'user_id' => $userId,
                    'action' => 'assignment_submitted',
                    'entity_type' => 'assignment_submission',
                    'entity_id' => $submissionId,
                    'description' => "Submitted assignment: {$assignment['title']}"
                ]);
                
                // Notify doctor
                $this->notificationService->notify(
                    "New Assignment Submission",
                    "Student {$student['first_name']} {$student['last_name']} submitted assignment: {$assignment['title']}",
                    [$assignment['doctor_id']],
                    'info'
                );
                
                $_SESSION['success'] = 'Assignment submitted successfully!';
            } catch (\Exception $e) {
                $db->rollBack();
                // Delete uploaded file on error
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                error_log("Assignment submission error: " . $e->getMessage());
                $_SESSION['error'] = 'Failed to save submission.';
            }
            
            $this->redirectTo('student/assignments');
        } catch (\Exception $e) {
            error_log("Upload assignment error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while uploading assignment.';
            $this->redirectTo('student/assignments');
        }
    }

    public function attendance(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            
            // Get enrolled courses
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId);
            
            // Get attendance for each section
            $attendanceData = [];
            foreach ($enrolledCourses as $course) {
                $sectionId = $course['section_id'];
                $attendance = $this->attendanceModel->getStudentAttendance($studentId, $sectionId);
                $percentage = $this->attendanceModel->getStudentAttendancePercentage($studentId, $sectionId);
                
                $attendanceData[$sectionId] = [
                    'course' => $course,
                    'attendance' => $attendance,
                    'percentage' => $percentage,
                ];
            }
            
            $this->view->render('student/student_attendance', [
                'title' => 'My Attendance',
                'student' => $student,
                'attendanceData' => $attendanceData,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student attendance error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading attendance.'
            ]);
        }
    }

    public function calendar(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            // Get calendar events
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            
            $events = $this->calendarEventModel->getEventsForMonth($month, $year);
            $upcomingEvents = $this->calendarEventModel->getUpcomingEvents(30, 10);
            
            $this->view->render('student/student_calendar', [
                'title' => 'Calendar',
                'student' => $student,
                'events' => $events,
                'upcomingEvents' => $upcomingEvents,
                'month' => $month,
                'year' => $year,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student calendar error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading calendar.'
            ]);
        }
    }

    public function notifications(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            // Mark as read if requested
            if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
                $this->notificationModel->markAsRead((int)$_GET['mark_read'], $userId);
                $this->redirectTo('student/notifications');
                return;
            }
            
            // Get all notifications
            $notifications = $this->notificationModel->getByUserId($userId, 100);
            $unread = $this->notificationModel->getUnreadByUserId($userId);
            
            // Organize by type
            $byType = [
                'info' => [],
                'success' => [],
                'warning' => [],
                'error' => [],
            ];
            
            foreach ($notifications as $notification) {
                $type = $notification['type'] ?? 'info';
                if (isset($byType[$type])) {
                    $byType[$type][] = $notification;
                } else {
                    $byType['info'][] = $notification;
                }
            }
            
            $this->view->render('student/student_notification', [
                'title' => 'Notifications',
                'student' => $student,
                'notifications' => $notifications,
                'unread' => $unread,
                'byType' => $byType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student notifications error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading notifications.'
            ]);
        }
    }

    public function profile(): void
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $this->view->render('student/student_profile', [
                'title' => 'My Profile',
                'student' => $student,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Student profile error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'An error occurred while loading profile.'
            ]);
        }
    }

    public function enroll(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectTo('student/schedule');
                return;
            }
            
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }
            
            $student = $this->studentModel->findByUserId($userId);
            if (!$student) {
                $this->view->render('errors/404', ['title' => 'Not Found']);
                return;
            }
            
            $studentId = $student['student_id'];
            $scheduleId = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : (isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0);
            
            if (!$scheduleId) {
                $_SESSION['error'] = 'Invalid schedule ID.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Get schedule details
            $schedule = $this->sectionModel->findById($scheduleId);
            if (!$schedule) {
                $_SESSION['error'] = 'Schedule not found.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check if schedule is available (status check)
            if (($schedule['status'] ?? '') === 'cancelled') {
                $_SESSION['error'] = 'This schedule is cancelled.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check capacity
            if (!$this->sectionModel->hasCapacity($scheduleId)) {
                $_SESSION['error'] = 'This schedule is full.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check prerequisites - use first course_id for now (or check all if course_ids exists)
            $courseId = $schedule['course_id'];
            if (!empty($schedule['course_ids'])) {
                $courseIds = json_decode($schedule['course_ids'], true);
                if (is_array($courseIds) && !empty($courseIds)) {
                    $courseId = $courseIds[0]; // Check prerequisites for first course
                }
            }
            
            if (!$this->courseModel->checkPrerequisites($studentId, $courseId)) {
                $_SESSION['error'] = 'You have not completed the prerequisites for this course.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check schedule conflict - handle weekly schedules
            $hasConflict = false;
            if (!empty($schedule['is_weekly']) && !empty($schedule['weekly_schedule'])) {
                $weeklySchedule = json_decode($schedule['weekly_schedule'], true);
                if (is_array($weeklySchedule)) {
                    foreach ($weeklySchedule as $day => $sessions) {
                        if (is_array($sessions)) {
                            foreach ($sessions as $session) {
                                if ($this->sectionModel->checkStudentScheduleConflict(
                                    $studentId,
                                    $day,
                                    $session['start_time'] ?? '',
                                    $session['end_time'] ?? '',
                                    $schedule['semester'] ?? '',
                                    $schedule['academic_year'] ?? ''
                                )) {
                                    $hasConflict = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } else {
                // Single day schedule
                $hasConflict = $this->sectionModel->checkStudentScheduleConflict(
                    $studentId,
                    $schedule['day_of_week'] ?? '',
                    $schedule['start_time'] ?? '',
                    $schedule['end_time'] ?? '',
                    $schedule['semester'] ?? '',
                    $schedule['academic_year'] ?? ''
                );
            }
            
            if ($hasConflict) {
                $_SESSION['error'] = 'This schedule conflicts with your existing schedule.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check if already enrolled or has pending request
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId);
            foreach ($enrolledCourses as $course) {
                $enrolledScheduleId = $course['schedule_id'] ?? $course['section_id'] ?? null;
                if ($enrolledScheduleId == $scheduleId) {
                    $_SESSION['error'] = 'You are already enrolled in this schedule.';
                    $this->redirectTo('student/schedule');
                    return;
                }
            }
            
            $existingRequests = $this->studentModel->getEnrollmentRequests($studentId);
            foreach ($existingRequests as $request) {
                $requestScheduleId = $request['schedule_id'] ?? $request['section_id'] ?? null;
                if ($requestScheduleId == $scheduleId && $request['status'] == 'pending') {
                    $_SESSION['error'] = 'You already have a pending enrollment request for this schedule.';
                    $this->redirectTo('student/schedule');
                    return;
                }
            }
            
            // Create enrollment request (section_id column stores schedule_id)
            $success = $this->enrollmentRequestModel->createRequest($studentId, $scheduleId);
            
            if ($success) {
                // Get the request ID
                $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                $requestId = $db->lastInsertId();
                
                // Log action
                $courseCode = $schedule['course_code'] ?? 'Unknown';
                $sectionNumber = $schedule['section_number'] ?? 'N/A';
                $this->auditLogModel->create([
                    'user_id' => $userId,
                    'action' => 'enrollment_request_created',
                    'entity_type' => 'enrollment_request',
                    'entity_id' => $requestId,
                    'description' => "Requested enrollment in schedule: {$courseCode} - {$sectionNumber}"
                ]);
                
                // Notify IT officer (using observer pattern)
                $this->enrollmentSubject->enrollmentRequested([
                    'student_id' => $studentId,
                    'schedule_id' => $scheduleId,
                    'section_id' => $scheduleId, // For backward compatibility
                    'course_code' => $courseCode,
                    'section_number' => $sectionNumber,
                ]);
                
                $_SESSION['success'] = 'Enrollment request submitted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to submit enrollment request. You may already have a pending request.';
            }
            
            $this->redirectTo('student/schedule');
        } catch (\Exception $e) {
            error_log("Enrollment request error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while submitting enrollment request.';
            $this->redirectTo('student/schedule');
        }
    }
}
