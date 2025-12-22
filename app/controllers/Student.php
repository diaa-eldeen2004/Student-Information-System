<?php
namespace controllers;

use core\Controller;
use patterns\Factory\ModelFactory;
use patterns\Adapter\NotificationService;
use patterns\Adapter\DatabaseNotificationAdapter;
use PDO;
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
use models\User;
use models\Doctor;

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
    private User $userModel;
    private Doctor $doctorModel;
    
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
        $this->userModel = ModelFactory::create('User');
        $this->doctorModel = ModelFactory::create('Doctor');

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
            
            // Get recently graded assignments (with grades and feedback)
            $recentGrades = [];
            foreach ($allAssignments as $assignment) {
                if (!empty($assignment['grade']) && !empty($assignment['submission_id'])) {
                    // Assignment has been graded
                    $recentGrades[] = $assignment;
                }
            }
            // Sort by graded_at or submitted_at (most recent first)
            usort($recentGrades, function($a, $b) {
                $dateA = $a['graded_at'] ?? $a['submitted_at'] ?? '';
                $dateB = $b['graded_at'] ?? $b['submitted_at'] ?? '';
                return strtotime($dateB) - strtotime($dateA);
            });
            
            $this->view->render('student/student_dashboard', [
                'title' => 'Student Dashboard',
                'student' => $student,
                'gpa' => $gpa,
                'enrolledCourses' => $enrolledCourses,
                'notifications' => $notifications,
                'upcomingAssignments' => array_slice($upcomingAssignments, 0, 5),
                'recentGrades' => array_slice($recentGrades, 0, 5),
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
            
            // Get student's schedule - this now returns all expanded sessions
            $schedule = $this->studentModel->getStudentSchedule($studentId, $semester, $academicYear);
            
            // Get available sections for enrollment
            $availableSections = $this->studentModel->getAvailableSectionsForEnrollment($semester, $academicYear);
            
            // Get existing enrollment requests - only PENDING ones
            $enrollmentRequests = $this->studentModel->getEnrollmentRequests($studentId);
            $requestedSectionIds = [];
            foreach ($enrollmentRequests as $request) {
                // Only include PENDING requests
                if (($request['status'] ?? '') === 'pending') {
                    $scheduleId = $request['schedule_id'] ?? $request['section_id'] ?? null;
                    if ($scheduleId) {
                        $requestedSectionIds[] = $scheduleId;
                    }
                }
            }
            
            // Get enrolled schedule IDs to exclude them from available sections
            $enrolledScheduleIds = [];
            foreach ($schedule as $entry) {
                $enrolledScheduleId = $entry['schedule_id'] ?? null;
                if ($enrolledScheduleId) {
                    $enrolledScheduleIds[] = $enrolledScheduleId;
                }
            }
            
            // Check if student is already enrolled in any schedule for this semester/year
            $isEnrolledInAnySchedule = $this->studentModel->isEnrolledInAnySchedule($studentId, $semester, $academicYear);
            
            // Organize by day for weekly view - now includes all sessions from all schedules
            $weeklySchedule = [
                'Monday' => [],
                'Tuesday' => [],
                'Wednesday' => [],
                'Thursday' => [],
                'Friday' => [],
                'Saturday' => [],
                'Sunday' => [],
            ];
            
            // Process all schedule entries (now properly expanded with all sessions)
            foreach ($schedule as $entry) {
                $day = $entry['day_of_week'] ?? '';
                // Normalize day name
                $dayLower = strtolower(trim($day));
                $dayMap = [
                    'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday',
                    'sunday' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
                    'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
                ];
                $dayCapitalized = $dayMap[$dayLower] ?? ucfirst($dayLower);
                
                if ($dayCapitalized && isset($weeklySchedule[$dayCapitalized])) {
                    $weeklySchedule[$dayCapitalized][] = $entry;
                }
            }
            
            // Sort each day by start time
            foreach ($weeklySchedule as $day => &$dayEntries) {
                usort($dayEntries, function($a, $b) {
                    $timeA = $a['start_time'] ?? '';
                    $timeB = $b['start_time'] ?? '';
                    return strcmp($timeA, $timeB);
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
                'enrolledScheduleIds' => $enrolledScheduleIds,
                'isEnrolledInAnySchedule' => $isEnrolledInAnySchedule,
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
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set JSON header first to prevent any output before JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Log that the method was called
        error_log("previewTimetable method called. GET params: " . json_encode($_GET));
        error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        
        try {
            // Start output buffering to catch any unexpected output
            ob_start();
            
            $userId = $_SESSION['user']['id'] ?? null;
            
            if (!$userId) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
            
            $scheduleId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
            
            if (!$scheduleId || $scheduleId <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Invalid schedule ID: ' . ($_GET['schedule_id'] ?? 'not provided')]);
                exit;
            }
            
            // Get the schedule entry
            $schedule = $this->sectionModel->findById($scheduleId);
            if (!$schedule) {
                ob_clean();
                error_log("Schedule not found for ID: " . $scheduleId);
                echo json_encode(['success' => false, 'error' => 'Schedule not found for ID: ' . $scheduleId]);
                exit;
            }
            
            // Get timetable for this specific schedule
            $timetable = $this->sectionModel->getScheduleTimetable($scheduleId);
            
            // Ensure timetable is an array
            if (!is_array($timetable)) {
                $timetable = [];
            }
            
            // Clean any output buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'timetable' => $timetable,
                'schedule' => $schedule
            ], JSON_PRETTY_PRINT);
            exit;
        } catch (\Exception $e) {
            ob_clean();
            error_log("Preview timetable error: " . $e->getMessage());
            error_log("Preview timetable trace: " . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'An error occurred: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], JSON_PRETTY_PRINT);
            exit;
        } catch (\Error $e) {
            ob_clean();
            error_log("Preview timetable fatal error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'A fatal error occurred: ' . $e->getMessage()
            ], JSON_PRETTY_PRINT);
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
            
            try {
                // Begin transaction for data integrity
                $db->beginTransaction();
                
                // Check if submission already exists
                $existing = $this->studentModel->getSubmission($studentId, $assignmentId);
                
                if ($existing) {
                    // Delete old file
                    $oldPath = dirname(__DIR__, 2) . '/public' . $existing['file_path'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                    
                    // Update submission - clear grade if resubmitting
                    $stmt = $db->prepare("
                        UPDATE assignment_submissions 
                        SET file_path = :file_path, file_name = :file_name,
                            submitted_at = NOW(), status = 'submitted', 
                            grade = NULL, graded_at = NULL, feedback = NULL
                        WHERE submission_id = :submission_id
                    ");
                    $result = $stmt->execute([
                        'submission_id' => $existing['submission_id'],
                        'file_path' => $relativePath,
                        'file_name' => $file['name'],
                    ]);
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log("Assignment submission UPDATE failed: " . json_encode($errorInfo));
                        throw new \Exception("Failed to update submission: " . ($errorInfo[2] ?? 'Unknown error'));
                    }
                    
                    if ($stmt->rowCount() == 0) {
                        throw new \Exception("No rows were updated. Submission may not exist.");
                    }
                    
                    $submissionId = $existing['submission_id'];
                    error_log("Assignment submission UPDATED: submission_id = {$submissionId}, student_id = {$studentId}, assignment_id = {$assignmentId}");
                } else {
                    // Create new submission
                    $stmt = $db->prepare("
                        INSERT INTO assignment_submissions 
                        (student_id, assignment_id, file_path, file_name, status, submitted_at)
                        VALUES (:student_id, :assignment_id, :file_path, :file_name, 'submitted', NOW())
                    ");
                    $result = $stmt->execute([
                        'student_id' => $studentId,
                        'assignment_id' => $assignmentId,
                        'file_path' => $relativePath,
                        'file_name' => $file['name'],
                    ]);
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log("Assignment submission INSERT failed: " . json_encode($errorInfo));
                        throw new \Exception("Failed to insert submission: " . ($errorInfo[2] ?? 'Unknown error'));
                    }
                    
                    if ($stmt->rowCount() == 0) {
                        throw new \Exception("No rows were inserted. Check database constraints.");
                    }
                    
                    $submissionId = (int)$db->lastInsertId();
                    
                    if (!$submissionId || $submissionId == 0) {
                        // Try to get the submission ID another way
                        $checkStmt = $db->prepare("SELECT submission_id FROM assignment_submissions WHERE student_id = :student_id AND assignment_id = :assignment_id ORDER BY submission_id DESC LIMIT 1");
                        $checkStmt->execute(['student_id' => $studentId, 'assignment_id' => $assignmentId]);
                        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        if ($checkResult && isset($checkResult['submission_id'])) {
                            $submissionId = (int)$checkResult['submission_id'];
                        } else {
                            throw new \Exception("Failed to get submission ID after insert. lastInsertId returned: " . ($db->lastInsertId() ?: '0/false'));
                        }
                    }
                    
                    error_log("Assignment submission INSERTED: submission_id = {$submissionId}, student_id = {$studentId}, assignment_id = {$assignmentId}");
                }
                
                // Commit transaction before notifications (so submission is saved even if notification fails)
                $db->commit();
                
                // Log action (non-critical, don't fail if this fails)
                try {
                    $this->auditLogModel->create([
                        'user_id' => $userId,
                        'action' => 'assignment_submitted',
                        'entity_type' => 'assignment_submission',
                        'entity_id' => $submissionId,
                        'description' => "Submitted assignment: {$assignment['title']}"
                    ]);
                } catch (\Exception $e) {
                    error_log("Failed to log assignment submission (non-critical): " . $e->getMessage());
                }
                
                // Notify doctor (non-critical, don't fail if this fails)
                try {
                    // Get doctor's user_id from doctor_id
                    $doctorUserIdStmt = $db->prepare("SELECT user_id FROM doctors WHERE doctor_id = :doctor_id LIMIT 1");
                    $doctorUserIdStmt->execute(['doctor_id' => $assignment['doctor_id']]);
                    $doctorUser = $doctorUserIdStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($doctorUser && isset($doctorUser['user_id'])) {
                        $this->notificationService->notify(
                            "New Assignment Submission",
                            "Student {$student['first_name']} {$student['last_name']} submitted assignment: {$assignment['title']}",
                            [$doctorUser['user_id']],
                            'assignment'
                        );
                        error_log("Notification sent to doctor user_id: {$doctorUser['user_id']} for assignment_id: {$assignmentId}");
                    } else {
                        error_log("Doctor user_id not found for doctor_id: {$assignment['doctor_id']}");
                    }
                } catch (\Exception $e) {
                    error_log("Failed to notify doctor (non-critical): " . $e->getMessage());
                }
                
                $_SESSION['success'] = 'Assignment submitted successfully!';
                error_log("Assignment submission completed successfully: submission_id = {$submissionId}, student_id = {$studentId}, assignment_id = {$assignmentId}");
            } catch (\Exception $e) {
                // Rollback transaction on error
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                
                // Delete uploaded file on error
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                error_log("Assignment submission error: " . $e->getMessage());
                error_log("Assignment submission error trace: " . $e->getTraceAsString());
                $_SESSION['error'] = 'Failed to save submission: ' . $e->getMessage();
            }
            
            $this->redirectTo('student/assignments');
        } catch (\Exception $e) {
            error_log("Upload assignment error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while uploading assignment.';
            $this->redirectTo('student/assignments');
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
            
            $studentId = $student['student_id'];
            
            // Get calendar events
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            
            $events = [];
            $upcomingEvents = [];
            
            // Get calendar events if table exists
            if ($this->calendarEventModel->tableExists()) {
                $calendarEvents = $this->calendarEventModel->getEventsForMonth($month, $year);
                $upcomingCalendarEvents = $this->calendarEventModel->getUpcomingEvents(30, 10);
                $events = array_merge($events, $calendarEvents);
                $upcomingEvents = array_merge($upcomingEvents, $upcomingCalendarEvents);
            }
            
            // Get assignments for this student and convert them to calendar events
            $assignments = $this->studentModel->getAssignmentsForStudent($studentId);
            $assignmentEvents = [];
            $upcomingAssignmentEvents = [];
            
            foreach ($assignments as $assignment) {
                if (empty($assignment['due_date'])) {
                    continue;
                }
                
                $dueDate = strtotime($assignment['due_date']);
                $assignmentMonth = (int)date('n', $dueDate);
                $assignmentYear = (int)date('Y', $dueDate);
                
                // Check if assignment is in the selected month
                if ($assignmentMonth == $month && $assignmentYear == $year) {
                    $assignmentEvents[] = [
                        'id' => 'assignment_' . $assignment['assignment_id'],
                        'title' => 'Assignment: ' . ($assignment['title'] ?? 'Untitled'),
                        'description' => $assignment['description'] ?? '',
                        'event_type' => 'assignment',
                        'status' => 'active',
                        'start_date' => $assignment['due_date'],
                        'end_date' => $assignment['due_date'],
                        'location' => $assignment['course_code'] ?? 'N/A',
                        'department' => null,
                        'course_id' => $assignment['course_id'] ?? null,
                        'course_code' => $assignment['course_code'] ?? '',
                        'course_name' => $assignment['course_name'] ?? '',
                        'assignment_id' => $assignment['assignment_id'],
                        'max_points' => $assignment['max_points'] ?? 0,
                        'is_submitted' => !empty($assignment['submission_id']),
                        'is_graded' => !empty($assignment['grade']),
                    ];
                }
                
                // Check if assignment is upcoming (within 30 days)
                if ($dueDate >= time() && $dueDate <= strtotime('+30 days')) {
                    $upcomingAssignmentEvents[] = [
                        'id' => 'assignment_' . $assignment['assignment_id'],
                        'title' => 'Assignment: ' . ($assignment['title'] ?? 'Untitled'),
                        'description' => $assignment['description'] ?? '',
                        'event_type' => 'assignment',
                        'status' => 'active',
                        'start_date' => $assignment['due_date'],
                        'end_date' => $assignment['due_date'],
                        'location' => $assignment['course_code'] ?? 'N/A',
                        'department' => null,
                        'course_id' => $assignment['course_id'] ?? null,
                        'course_code' => $assignment['course_code'] ?? '',
                        'course_name' => $assignment['course_name'] ?? '',
                        'assignment_id' => $assignment['assignment_id'],
                        'max_points' => $assignment['max_points'] ?? 0,
                        'is_submitted' => !empty($assignment['submission_id']),
                        'is_graded' => !empty($assignment['grade']),
                    ];
                }
            }
            
            // Sort assignment events by due date
            usort($assignmentEvents, function($a, $b) {
                return strtotime($a['start_date']) - strtotime($b['start_date']);
            });
            
            usort($upcomingAssignmentEvents, function($a, $b) {
                return strtotime($a['start_date']) - strtotime($b['start_date']);
            });
            
            // Combine calendar events and assignment events
            $events = array_merge($events, $assignmentEvents);
            $upcomingEvents = array_merge($upcomingEvents, $upcomingAssignmentEvents);
            
            // Sort all events by date
            usort($events, function($a, $b) {
                return strtotime($a['start_date']) - strtotime($b['start_date']);
            });
            
            usort($upcomingEvents, function($a, $b) {
                return strtotime($a['start_date']) - strtotime($b['start_date']);
            });
            
            // Limit upcoming events to 10
            $upcomingEvents = array_slice($upcomingEvents, 0, 10);
            
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
            $unreadNotificationsCount = count($unread);
            
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
                'unreadNotificationsCount' => $unreadNotificationsCount,
                'byType' => $byType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Notifications error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load notifications: ' . $e->getMessage()]);
        }
    }

    public function sendNotification(): void
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

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userIds = $_POST['user_ids'] ?? [];
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['message'] ?? '');
                $type = trim($_POST['type'] ?? 'info');

                if (!empty($userIds) && $title && $content) {
                    $successCount = 0;
                    $errorCount = 0;
                    
                    foreach ($userIds as $targetUserId) {
                        $targetUserId = (int)$targetUserId;
                        if ($targetUserId > 0) {
                            if ($this->notificationModel->create([
                                'user_id' => $targetUserId,
                                'title' => $title,
                                'message' => $content,
                                'type' => $type,
                                'related_id' => $student['student_id'],
                                'related_type' => 'student'
                            ])) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                        }
                    }
                    
                    if ($successCount > 0) {
                        $message = "Message sent successfully to {$successCount} recipient(s)";
                        if ($errorCount > 0) {
                            $message .= ". {$errorCount} failed.";
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Error sending messages';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please select at least one recipient and fill all required fields';
                    $messageType = 'error';
                }
            }

            // Get doctors from student's enrolled courses
            $studentId = $student['student_id'];
            $enrolledCourses = $this->studentModel->getEnrolledCourses($studentId);
            $doctors = [];
            $doctorUserIds = [];

            foreach ($enrolledCourses as $course) {
                $courseId = $course['course_id'] ?? null;
                if ($courseId) {
                    // Get doctors assigned to this course
                    $courseDoctors = $this->courseModel->getAssignedDoctors($courseId);
                    foreach ($courseDoctors as $doctor) {
                        $doctorId = $doctor['doctor_id'] ?? null;
                        if ($doctorId) {
                            // Get doctor record to get user_id
                            $doctorRecord = $this->doctorModel->findById($doctorId);
                            if ($doctorRecord && isset($doctorRecord['user_id'])) {
                                $doctorUserId = $doctorRecord['user_id'];
                                if (!in_array($doctorUserId, $doctorUserIds)) {
                                    $doctors[] = [
                                        'user_id' => $doctorUserId,
                                        'first_name' => $doctor['first_name'] ?? $doctorRecord['first_name'] ?? '',
                                        'last_name' => $doctor['last_name'] ?? $doctorRecord['last_name'] ?? '',
                                        'email' => $doctor['email'] ?? $doctorRecord['email'] ?? '',
                                        'role' => 'doctor',
                                        'doctor_id' => $doctorId,
                                        'course_code' => $course['course_code'] ?? '',
                                        'course_name' => $course['name'] ?? ''
                                    ];
                                    $doctorUserIds[] = $doctorUserId;
                                }
                            }
                        }
                    }
                }
            }

            // Also allow sending to admins
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as $admin) {
                if (!in_array($admin['id'], $doctorUserIds)) {
                    $doctors[] = [
                        'user_id' => $admin['id'],
                        'first_name' => $admin['first_name'] ?? '',
                        'last_name' => $admin['last_name'] ?? '',
                        'email' => $admin['email'] ?? '',
                        'role' => 'admin'
                    ];
                }
            }

            $this->view->render('student/student_send_notification', [
                'title' => 'Send Message',
                'student' => $student,
                'recipients' => $doctors,
                'message' => $message,
                'messageType' => $messageType,
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
            
            // Check if student is already enrolled in ANY schedule for this semester/year
            $semester = $schedule['semester'] ?? '';
            $academicYear = $schedule['academic_year'] ?? '';
            if ($this->studentModel->isEnrolledInAnySchedule($studentId, $semester, $academicYear)) {
                $_SESSION['error'] = 'You are already enrolled in a schedule for this semester. You cannot enroll in multiple schedules.';
                $this->redirectTo('student/schedule');
                return;
            }
            
            // Check if already enrolled in this specific schedule (redundant check, but kept for safety)
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
