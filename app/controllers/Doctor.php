<?php
namespace controllers;

use core\Controller;
use patterns\Factory\ModelFactory;
use patterns\Singleton\DatabaseConnection;
use patterns\Adapter\NotificationService;
use PDO;
use patterns\Adapter\DatabaseNotificationAdapter;
use patterns\Observer\AssignmentSubject;
use patterns\Observer\NotificationObserver;
use patterns\Observer\AuditLogObserver;
use patterns\Decorator\AssignmentDecorator;
use patterns\Builder\AssignmentBuilder;
use models\Doctor as DoctorModel;
use models\Course;
use models\Schedule;
use models\Assignment;
use models\Attendance;
use models\Student;
use models\AuditLog;
use models\Notification;
use models\Material;

class Doctor extends Controller
{
    private DoctorModel $doctorModel;
    private Course $courseModel;
    private Schedule $sectionModel;
    private Assignment $assignmentModel;
    private Attendance $attendanceModel;
    private Student $studentModel;
    private AuditLog $auditLogModel;
    private Notification $notificationModel;
    private Material $materialModel;
    
    // Observer Pattern
    private AssignmentSubject $assignmentSubject;
    
    // Adapter Pattern
    private NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
            $this->redirectTo('auth/login');
        }

        // Factory Method Pattern - Create all models
        $this->doctorModel = ModelFactory::create('Doctor');
        $this->courseModel = ModelFactory::create('Course');
        $this->sectionModel = ModelFactory::create('Schedule');
        $this->assignmentModel = ModelFactory::create('Assignment');
        $this->attendanceModel = ModelFactory::create('Attendance');
        $this->studentModel = ModelFactory::create('Student');
        $this->auditLogModel = ModelFactory::create('AuditLog');
        $this->notificationModel = ModelFactory::create('Notification');
        $this->materialModel = ModelFactory::create('Material');

        // Adapter Pattern - Notification service with database adapter
        $notificationAdapter = new DatabaseNotificationAdapter($this->notificationModel);
        $this->notificationService = new NotificationService($notificationAdapter);

        // Observer Pattern - Setup observers for assignment events
        $this->assignmentSubject = new AssignmentSubject();
        $this->assignmentSubject->attach(new NotificationObserver($this->notificationModel));
        $this->assignmentSubject->attach(new AuditLogObserver($this->auditLogModel));
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
            
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                // Create a temporary doctor record if it doesn't exist
                try {
                    $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                    $stmt = $db->prepare("INSERT INTO doctors (user_id) VALUES (:user_id)");
                    $stmt->execute(['user_id' => $userId]);
                    $doctor = $this->doctorModel->findByUserId($userId);
                } catch (\Exception $e) {
                    error_log("Error creating doctor record: " . $e->getMessage());
                }
                
                if (!$doctor) {
                    $this->view->render('errors/403', [
                        'title' => 'Access Denied', 
                        'message' => 'Doctor profile not found. Please contact administrator to create your doctor profile.'
                    ]);
                    return;
                }
            }

            // Get doctor's sections and assignments
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            // Ensure schedule_id is available in all sections
            foreach ($sections as &$section) {
                if (!isset($section['schedule_id']) && isset($section['section_id'])) {
                    $section['schedule_id'] = $section['section_id'];
                } elseif (isset($section['schedule_id']) && !isset($section['section_id'])) {
                    $section['section_id'] = $section['schedule_id'];
                }
            }
            unset($section); // Break reference
            
            $assignments = $this->assignmentModel->getByDoctor($doctor['doctor_id']);

            $this->view->render('doctor/doctor_dashboard', [
                'title' => 'Doctor Dashboard',
                'doctor' => $doctor,
                'sections' => $sections ?? [],
                'assignments' => $assignments ?? [],
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load dashboard: ' . $e->getMessage()]);
        }
    }

    public function course(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            // Get doctor's courses
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $courses = [];
            foreach ($sections as $section) {
                // Ensure schedule_id is available (it should be from s.* but ensure it's set)
                if (!isset($section['schedule_id']) && isset($section['section_id'])) {
                    $section['schedule_id'] = $section['section_id'];
                } elseif (isset($section['schedule_id']) && !isset($section['section_id'])) {
                    $section['section_id'] = $section['schedule_id'];
                }
                
                $courseId = $section['course_id'];
                if (!isset($courses[$courseId])) {
                    $course = $this->courseModel->findById($courseId);
                    if ($course) {
                        $courses[$courseId] = $course;
                        $courses[$courseId]['sections'] = [];
                    }
                }
                $courses[$courseId]['sections'][] = $section;
            }

            $this->view->render('doctor/doctor_course', [
                'title' => 'My Courses',
                'courses' => array_values($courses),
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Course error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load courses: ' . $e->getMessage()]);
        }
    }

    public function assignments(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            // Handle grade update
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_grade') {
                $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
                $gradeInput = $_POST['grade'] ?? '';
                $grade = $gradeInput !== '' ? (float)$gradeInput : null;
                $feedback = trim($_POST['feedback'] ?? '');

                if ($submissionId > 0 && $grade !== null && $grade >= 0) {
                    // Verify the assignment belongs to this doctor
                    $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                    $checkStmt = $db->prepare("
                        SELECT a.assignment_id, a.doctor_id, a.max_points
                        FROM assignment_submissions sub
                        JOIN assignments a ON sub.assignment_id = a.assignment_id
                        WHERE sub.submission_id = :submission_id
                    ");
                    $checkStmt->execute(['submission_id' => $submissionId]);
                    $assignmentInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($assignmentInfo && $assignmentInfo['doctor_id'] == $doctor['doctor_id']) {
                        // Validate grade doesn't exceed max points
                        $maxPoints = (float)($assignmentInfo['max_points'] ?? 100);
                        if ($grade > $maxPoints) {
                            $grade = $maxPoints; // Cap at max points
                        }

                        if ($this->assignmentModel->updateGrade($submissionId, $grade, $feedback ?: null)) {
                            $message = 'Grade updated successfully';
                            $messageType = 'success';
                            
                            // Notify student about the grade
                            try {
                                $studentStmt = $db->prepare("
                                    SELECT st.user_id, u.first_name, u.last_name, a.title
                                    FROM assignment_submissions sub
                                    JOIN students st ON sub.student_id = st.student_id
                                    JOIN users u ON st.user_id = u.id
                                    JOIN assignments a ON sub.assignment_id = a.assignment_id
                                    WHERE sub.submission_id = :submission_id
                                ");
                                $studentStmt->execute(['submission_id' => $submissionId]);
                                $studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($studentInfo && isset($studentInfo['user_id'])) {
                                    $this->notificationService->notify(
                                        "Assignment Graded",
                                        "Your assignment '{$studentInfo['title']}' has been graded. Grade: {$grade}/{$maxPoints}",
                                        [$studentInfo['user_id']],
                                        'assignment'
                                    );
                                }
                            } catch (\Exception $e) {
                                error_log("Failed to notify student about grade (non-critical): " . $e->getMessage());
                            }
                        } else {
                            $message = 'Failed to update grade';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Access denied or submission not found';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid grade data';
                    $messageType = 'error';
                }
            }

            // Get filter parameters
            $courseFilter = trim($_GET['course'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $typeFilter = trim($_GET['type'] ?? '');
            
            $filters = [];
            if (!empty($courseFilter)) $filters['course_id'] = $courseFilter;
            if (!empty($statusFilter)) $filters['status'] = $statusFilter;
            if (!empty($typeFilter)) $filters['type'] = $typeFilter;

            // Get all assignments from database (for history) - use getAllByDoctor to ensure we get everything
            $allAssignments = $this->assignmentModel->getAllByDoctor($doctor['doctor_id']);
            
            // Apply filters for display
            $filteredAssignments = $allAssignments;
            if (!empty($filters)) {
                $filteredAssignments = [];
                foreach ($allAssignments as $assignment) {
                    $match = true;
                    if (!empty($filters['course_id']) && $assignment['course_id'] != $filters['course_id']) {
                        $match = false;
                    }
                    if (!empty($filters['type']) && ($assignment['assignment_type'] ?? '') != $filters['type']) {
                        $match = false;
                    }
                    // Status filter based on due date
                    if (!empty($filters['status'])) {
                        $dueDate = strtotime($assignment['due_date'] ?? '');
                        $now = time();
                        if ($filters['status'] === 'active' && $dueDate < $now) {
                            $match = false;
                        } elseif ($filters['status'] === 'completed' && $dueDate >= $now) {
                            $match = false;
                        }
                    }
                    if ($match) {
                        $filteredAssignments[] = $assignment;
                    }
                }
            }
            
            // Decorator Pattern - Format ALL assignments for history display
            $allDecoratedAssignments = [];
            foreach ($allAssignments as $assignment) {
                $decorator = new AssignmentDecorator($assignment);
                $assignment['formatted'] = $decorator->format();
                $assignment['status_badge'] = $decorator->getStatusBadge();
                $submissionStats = $this->assignmentModel->getSubmissionCount($assignment['assignment_id']);
                $assignment['submission_stats'] = $submissionStats;
                // Load actual submissions with student details
                $assignment['submissions'] = $this->assignmentModel->getSubmissionsByAssignment($assignment['assignment_id']);
                $allDecoratedAssignments[] = $assignment;
            }
            
            // Decorator Pattern - Format filtered assignments for display
            $decoratedAssignments = [];
            foreach ($filteredAssignments as $assignment) {
                $decorator = new AssignmentDecorator($assignment);
                $assignment['formatted'] = $decorator->format();
                $assignment['status_badge'] = $decorator->getStatusBadge();
                $submissionStats = $this->assignmentModel->getSubmissionCount($assignment['assignment_id']);
                $assignment['submission_stats'] = $submissionStats;
                // Load actual submissions with student details
                $assignment['submissions'] = $this->assignmentModel->getSubmissionsByAssignment($assignment['assignment_id']);
                $decoratedAssignments[] = $assignment;
            }

            // Get doctor's courses for filter
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $courses = [];
            foreach ($sections as $section) {
                $course = $this->courseModel->findById($section['course_id']);
                if ($course && !isset($courses[$course['course_id']])) {
                    $courses[$course['course_id']] = $course;
                }
            }

            $this->view->render('doctor/doctor_assignments', [
                'title' => 'Assignments/Quizzes',
                'assignments' => $decoratedAssignments, // Filtered assignments for main list
                'allAssignments' => $allDecoratedAssignments, // All assignments for history
                'courses' => array_values($courses),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Assignments error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load assignments: ' . $e->getMessage()]);
        }
    }

    public function createAssignment(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Handle file upload
                $filePath = null;
                $fileName = null;
                $fileSize = null;
                
                if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/assignments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
                    $fileName = trim($_POST['file_name'] ?? '') ?: $_FILES['assignment_file']['name'];
                    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                    if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
                        $fileName .= '.' . $fileExtension;
                    }
                    
                    $uniqueFileName = time() . '_' . uniqid() . '_' . $fileName;
                    $targetPath = $uploadDir . $uniqueFileName;
                    
                    if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetPath)) {
                        $filePath = '/uploads/assignments/' . $uniqueFileName;
                        $fileSize = $_FILES['assignment_file']['size'];
                    }
                }
                
                // Get section info for semester/year
                $sectionId = (int)($_POST['section_id'] ?? 0);
                $section = $this->sectionModel->findById($sectionId);
                $semester = $section['semester'] ?? null;
                $academicYear = $section['academic_year'] ?? null;
                
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
                
                // Visibility settings
                $isVisible = isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 1;
                $visibleUntil = !empty($_POST['visible_until']) ? trim($_POST['visible_until']) : null;
                if ($visibleUntil) {
                    // Convert hours/days to datetime
                    $durationType = $_POST['duration_type'] ?? 'hours';
                    $duration = (int)($_POST['duration'] ?? 0);
                    if ($duration > 0) {
                        if ($durationType === 'days') {
                            $visibleUntil = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
                        } else {
                            $visibleUntil = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
                        }
                    }
                }
                $builder->setVisibility($isVisible, $visibleUntil);

                $assignmentData = $builder->build();

                if ($this->assignmentModel->create($assignmentData)) {
                    // Observer Pattern - Notify observers about new assignment
                    $this->assignmentSubject->notify('assignment_created', [
                        'assignment_id' => $this->assignmentModel->getLastInsertId(),
                        'doctor_id' => $doctor['doctor_id'],
                        'title' => $assignmentData['title']
                    ]);

                    $message = 'Assignment created successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Error creating assignment';
                    $messageType = 'error';
                }
            }

            // Get doctor's sections for dropdown
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $courses = [];
            foreach ($sections as $section) {
                $course = $this->courseModel->findById($section['course_id']);
                if ($course && !isset($courses[$course['course_id']])) {
                    $courses[$course['course_id']] = $course;
                    $courses[$course['course_id']]['sections'] = [];
                }
                if ($course) {
                    $courses[$course['course_id']]['sections'][] = $section;
                }
            }

            // Get recent assignments
            $recentAssignments = $this->assignmentModel->getByDoctor($doctor['doctor_id'], ['status' => 'active']);
            $recentAssignments = array_slice($recentAssignments, 0, 5);

            $this->view->render('doctor/create_assignment', [
                'title' => 'Create Assignment/Quiz',
                'courses' => array_values($courses),
                'recentAssignments' => $recentAssignments,
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Create assignment error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to create assignment: ' . $e->getMessage()]);
        }
    }

    public function attendance(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            // Get doctor's sections - ONLY assigned courses
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            
            // Get attendance stats and student counts for each section
            $sectionsWithStats = [];
            foreach ($sections as $section) {
                // Verify doctor has access to this section
                if ($section['doctor_id'] != $doctor['doctor_id']) {
                    continue; // Skip unassigned courses
                }
                
                // Get schedule_id (primary key in schedule table)
                $scheduleId = isset($section['schedule_id']) ? $section['schedule_id'] : (isset($section['section_id']) ? $section['section_id'] : null);
                if (!$scheduleId) {
                    continue; // Skip if no valid ID
                }
                
                // Ensure schedule_id is in the section array for the view
                $section['schedule_id'] = $scheduleId;
                // Also add section_id for backward compatibility in views
                if (!isset($section['section_id'])) {
                    $section['section_id'] = $scheduleId;
                }
                
                $stats = $this->attendanceModel->getAttendanceStats($scheduleId);
                $section['attendance_stats'] = $stats;
                
                // Get student count
                $enrollments = $this->sectionModel->getEnrolledStudents($scheduleId);
                $section['student_count'] = count($enrollments);
                
                // Get course info
                $course = $this->courseModel->findById($section['course_id']);
                $section['course_code'] = $course['course_code'] ?? 'N/A';
                $section['course_name'] = $course['name'] ?? 'N/A';
                
                $sectionsWithStats[] = $section;
            }

            $this->view->render('doctor/doctor_attendance', [
                'title' => 'Attendance Management',
                'sections' => $sectionsWithStats,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Attendance error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load attendance: ' . $e->getMessage()]);
        }
    }

    public function takeAttendance(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $sectionId = (int)($_GET['section_id'] ?? $_GET['schedule_id'] ?? 0);
            if (!$sectionId) {
                $this->view->render('errors/400', ['title' => 'Bad Request', 'message' => 'Section ID required']);
                return;
            }

            $section = $this->sectionModel->findById($sectionId);
            if (!$section || $section['doctor_id'] != $doctor['doctor_id']) {
                $this->view->render('errors/403', ['title' => 'Access Denied', 'message' => 'You do not have access to this section']);
                return;
            }

            // Get the schedule_id from the section (it might be schedule_id or section_id)
            $scheduleId = $section['schedule_id'] ?? $sectionId;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $attendanceDate = trim($_POST['attendance_date'] ?? date('Y-m-d'));
                
                // Validate date
                if (empty($attendanceDate)) {
                    $message = 'Please select an attendance date.';
                    $messageType = 'error';
                } else {
                    $attendanceData = [];
                    
                    // Validate that we have attendance data
                    if (empty($_POST['attendance']) || !is_array($_POST['attendance'])) {
                        $message = 'No attendance data provided.';
                        $messageType = 'error';
                    } else {
                        foreach ($_POST['attendance'] as $studentId => $status) {
                            $studentId = (int)$studentId;
                            $status = trim($status);
                            
                            // Validate status
                            if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
                                error_log("Invalid attendance status: {$status} for student_id: {$studentId}");
                                continue;
                            }
                            
                            $attendanceData[] = [
                                'student_id' => $studentId,
                                'date' => $attendanceDate,
                                'status' => $status,
                                'notes' => trim($_POST['notes'][$studentId] ?? ''),
                            ];
                        }

                        if (empty($attendanceData)) {
                            $message = 'No valid attendance records to save.';
                            $messageType = 'error';
                        } else {
                            if ($this->attendanceModel->recordAttendance($scheduleId, $attendanceData, $doctor['doctor_id'])) {
                                $config = require dirname(__DIR__) . '/config/config.php';
                                $base = rtrim($config['base_url'] ?? '', '/');
                                $target = $base . '/doctor/attendance?success=recorded';
                                header("Location: {$target}");
                                exit;
                            } else {
                                $message = 'Error recording attendance. Please check the error logs for details.';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            }

            // Get enrolled students - ONLY students assigned to this course
            $enrollments = $this->sectionModel->getEnrolledStudents($scheduleId);
            $students = [];
            foreach ($enrollments as $enrollment) {
                $student = $this->studentModel->findById($enrollment['student_id']);
                if ($student) {
                    // Get existing attendance for this date if any
                    $existingAttendance = $this->attendanceModel->getByDate($scheduleId, $attendanceDate ?? date('Y-m-d'));
                    $student['attendance_status'] = null;
                    foreach ($existingAttendance as $att) {
                        if ($att['student_id'] == $student['student_id']) {
                            $student['attendance_status'] = $att['status'];
                            $student['attendance_notes'] = $att['notes'] ?? '';
                            break;
                        }
                    }
                    $students[] = $student;
                }
            }

            $this->view->render('doctor/take_attendance', [
                'title' => 'Take Attendance',
                'section' => $section,
                'students' => $students,
                'message' => $message ?? null,
                'messageType' => $messageType ?? 'info',
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Take attendance error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to take attendance: ' . $e->getMessage()]);
        }
    }

    public function createCourse(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Note: Course creation is typically done by IT Officer, but doctors can request
                // For now, we'll just show a message
                $message = 'Course creation requests should be submitted to IT Officer';
                $messageType = 'info';
            }

            // Get recent courses
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $recentCourses = [];
            foreach (array_slice($sections, 0, 5) as $section) {
                $course = $this->courseModel->findById($section['course_id']);
                if ($course && !isset($recentCourses[$course['course_id']])) {
                    $recentCourses[$course['course_id']] = $course;
                }
            }

            $this->view->render('doctor/create_course', [
                'title' => 'Create Course',
                'recentCourses' => array_values($recentCourses),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Create course error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to create course: ' . $e->getMessage()]);
        }
    }

    public function notifications(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            // Handle mark as read action
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId) {
                    $this->notificationModel->markAsRead($notificationId, $userId);
                }
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $target = $base . '/doctor/notifications';
                header("Location: {$target}");
                exit;
            }

            // Get notifications for the doctor
            $notifications = $this->notificationModel->getByUserId($userId, 50);
            $unreadNotifications = $this->notificationModel->getUnreadByUserId($userId);
            $unreadCount = count($unreadNotifications);
            $unreadNotificationsCount = $unreadCount;

            $this->view->render('doctor/doctor_notifications', [
                'title' => 'Notifications',
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'unreadNotificationsCount' => $unreadNotificationsCount,
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
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
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
                    
                    foreach ($userIds as $userId) {
                        $userId = (int)$userId;
                        if ($userId > 0) {
                            if ($this->notificationModel->create([
                                'user_id' => $userId,
                                'title' => $title,
                                'message' => $content,
                                'type' => $type,
                                'related_id' => $doctor['doctor_id'],
                                'related_type' => 'doctor'
                            ])) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                        }
                    }
                    
                    if ($successCount > 0) {
                        $message = "Notification sent successfully to {$successCount} student(s)";
                        if ($errorCount > 0) {
                            $message .= ". {$errorCount} failed.";
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Error sending notifications';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please select at least one student and fill all required fields';
                    $messageType = 'error';
                }
            }

            // Get doctor's sections to get enrolled students
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $students = [];
            foreach ($sections as $section) {
                $scheduleId = $section['schedule_id'] ?? $section['section_id'] ?? null;
                if (!$scheduleId) {
                    continue;
                }
                $enrollments = $this->sectionModel->getEnrolledStudents($scheduleId);
                foreach ($enrollments as $enrollment) {
                    $student = $this->studentModel->findById($enrollment['student_id']);
                    if ($student && !isset($students[$student['student_id']])) {
                        $students[$student['student_id']] = $student;
                    }
                }
            }

            $this->view->render('doctor/send_notification', [
                'title' => 'Send Notification',
                'students' => array_values($students),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Send notification error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    public function profile(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Update profile
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $department = trim($_POST['department'] ?? '');

                if ($firstName && $lastName) {
                    try {
                        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                        $db->beginTransaction();
                        
                        // Update user info
                        $userStmt = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :id");
                        $userStmt->execute([
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'phone' => $phone,
                            'id' => $userId
                        ]);
                        
                        // Update doctor info
                        $doctorStmt = $db->prepare("UPDATE doctors SET department = :department WHERE doctor_id = :doctor_id");
                        $doctorStmt->execute([
                            'department' => $department,
                            'doctor_id' => $doctor['doctor_id']
                        ]);
                        
                        $db->commit();
                        $message = 'Profile updated successfully';
                        $messageType = 'success';
                        
                        // Reload doctor data
                        $doctor = $this->doctorModel->findByUserId($userId);
                    } catch (\Exception $e) {
                        $db->rollBack();
                        error_log("Profile update error: " . $e->getMessage());
                        $message = 'Error updating profile: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                } else {
                    $message = 'First name and last name are required';
                    $messageType = 'error';
                }
            }

            // Get doctor's sections count
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $assignments = $this->assignmentModel->getByDoctor($doctor['doctor_id']);

            $this->view->render('doctor/doctor_profile', [
                'title' => 'My Profile',
                'doctor' => $doctor,
                'sectionsCount' => count($sections),
                'assignmentsCount' => count($assignments),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Profile error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load profile: ' . $e->getMessage()]);
        }
    }

    public function editAssignment(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $assignmentId = (int)($_GET['id'] ?? 0);
            if (!$assignmentId) {
                $this->redirectTo('doctor/assignments');
                return;
            }

            $assignment = $this->assignmentModel->findById($assignmentId);
            if (!$assignment || $assignment['doctor_id'] != $doctor['doctor_id']) {
                $this->view->render('errors/403', ['title' => 'Access Denied', 'message' => 'You do not have access to this assignment']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $updateData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'due_date' => trim($_POST['due_date'] ?? ''),
                    'max_points' => (float)($_POST['points'] ?? 100),
                    'assignment_type' => trim($_POST['type'] ?? 'homework'),
                ];

                // Handle file upload if new file provided
                if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/assignments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Delete old file if exists
                    if ($assignment['file_path']) {
                        $oldPath = dirname(__DIR__, 2) . '/public' . $assignment['file_path'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    
                    $fileExtension = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
                    $fileName = trim($_POST['file_name'] ?? '') ?: $_FILES['assignment_file']['name'];
                    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                    if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
                        $fileName .= '.' . $fileExtension;
                    }
                    
                    $uniqueFileName = time() . '_' . uniqid() . '_' . $fileName;
                    $targetPath = $uploadDir . $uniqueFileName;
                    
                    if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetPath)) {
                        $updateData['file_path'] = '/uploads/assignments/' . $uniqueFileName;
                        $updateData['file_name'] = $fileName;
                        $updateData['file_size'] = $_FILES['assignment_file']['size'];
                    }
                } elseif (!empty($_POST['file_name']) && $assignment['file_path']) {
                    // Just rename the file
                    $updateData['file_name'] = trim($_POST['file_name']);
                }

                if ($this->assignmentModel->update($assignmentId, $updateData)) {
                    $message = 'Assignment updated successfully';
                    $messageType = 'success';
                    $assignment = $this->assignmentModel->findById($assignmentId); // Reload
                } else {
                    $message = 'Error updating assignment';
                    $messageType = 'error';
                }
            }

            // Get doctor's sections for dropdown
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $courses = [];
            foreach ($sections as $section) {
                $course = $this->courseModel->findById($section['course_id']);
                if ($course && !isset($courses[$course['course_id']])) {
                    $courses[$course['course_id']] = $course;
                    $courses[$course['course_id']]['sections'] = [];
                }
                if ($course) {
                    $courses[$course['course_id']]['sections'][] = $section;
                }
            }

            $this->view->render('doctor/edit_assignment', [
                'title' => 'Edit Assignment/Quiz',
                'assignment' => $assignment,
                'courses' => array_values($courses),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Edit assignment error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to edit assignment: ' . $e->getMessage()]);
        }
    }

    public function uploadMaterial(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/materials/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
                    $fileName = trim($_POST['file_name'] ?? '') ?: $_FILES['material_file']['name'];
                    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                    
                    $uniqueFileName = time() . '_' . uniqid() . '_' . $fileName;
                    $targetPath = $uploadDir . $uniqueFileName;
                    
                    if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                        $materialData = [
                            'course_id' => (int)($_POST['course_id'] ?? 0),
                            'section_id' => !empty($_POST['section_id']) ? (int)$_POST['section_id'] : null,
                            'doctor_id' => $doctor['doctor_id'],
                            'title' => trim($_POST['title'] ?? ''),
                            'description' => trim($_POST['description'] ?? ''),
                            'file_path' => '/uploads/materials/' . $uniqueFileName,
                            'file_name' => $fileName,
                            'file_type' => $fileExtension,
                            'file_size' => $_FILES['material_file']['size'],
                            'material_type' => trim($_POST['material_type'] ?? 'other'),
                        ];

                        if ($this->materialModel->create($materialData)) {
                            $message = 'File uploaded successfully';
                            $messageType = 'success';
                        } else {
                            $message = 'Error uploading file';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Error moving uploaded file';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'No file uploaded or upload error';
                    $messageType = 'error';
                }
            }

            // Get doctor's courses
            $sections = $this->sectionModel->getByDoctor($doctor['doctor_id']);
            $courses = [];
            foreach ($sections as $section) {
                $course = $this->courseModel->findById($section['course_id']);
                if ($course && !isset($courses[$course['course_id']])) {
                    $courses[$course['course_id']] = $course;
                    $courses[$course['course_id']]['sections'] = [];
                }
                if ($course) {
                    $courses[$course['course_id']]['sections'][] = $section;
                }
            }

            $this->view->render('doctor/upload_material', [
                'title' => 'Upload Course Material',
                'courses' => array_values($courses),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Upload material error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to upload material: ' . $e->getMessage()]);
        }
    }

    public function editMaterial(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $doctor = $this->doctorModel->findByUserId($userId);
            
            if (!$doctor) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $materialId = (int)($_GET['id'] ?? 0);
            if (!$materialId) {
                $this->redirectTo('doctor/course');
                return;
            }

            $material = $this->materialModel->findById($materialId);
            if (!$material || $material['doctor_id'] != $doctor['doctor_id']) {
                $this->view->render('errors/403', ['title' => 'Access Denied', 'message' => 'You do not have access to this material']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $updateData = [
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                ];

                // Handle file upload if new file provided
                if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/materials/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Delete old file if exists
                    if ($material['file_path']) {
                        $oldPath = dirname(__DIR__, 2) . '/public' . $material['file_path'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    
                    $fileExtension = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
                    $fileName = trim($_POST['file_name'] ?? '') ?: $_FILES['material_file']['name'];
                    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                    
                    $uniqueFileName = time() . '_' . uniqid() . '_' . $fileName;
                    $targetPath = $uploadDir . $uniqueFileName;
                    
                    if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                        $updateData['file_path'] = '/uploads/materials/' . $uniqueFileName;
                        $updateData['file_name'] = $fileName;
                        $updateData['file_type'] = $fileExtension;
                        $updateData['file_size'] = $_FILES['material_file']['size'];
                    }
                } elseif (!empty($_POST['file_name']) && $material['file_path']) {
                    // Just rename the file
                    $updateData['file_name'] = trim($_POST['file_name']);
                }

                if ($this->materialModel->update($materialId, $updateData)) {
                    $message = 'Material updated successfully';
                    $messageType = 'success';
                    $material = $this->materialModel->findById($materialId); // Reload
                } else {
                    $message = 'Error updating material';
                    $messageType = 'error';
                }
            }

            $this->view->render('doctor/edit_material', [
                'title' => 'Edit Material',
                'material' => $material,
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Edit material error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to edit material: ' . $e->getMessage()]);
        }
    }
}

