<?php
namespace controllers;

use core\Controller;
use patterns\Factory\ModelFactory;
use patterns\Builder\SectionBuilder;
use patterns\Singleton\DatabaseConnection;
use patterns\Strategy\ConflictDetector;
use patterns\Strategy\TimeSlotConflictStrategy;
use patterns\Strategy\RoomConflictStrategy;
use patterns\Strategy\DoctorAvailabilityStrategy;
use patterns\Adapter\NotificationService;
use patterns\Adapter\DatabaseNotificationAdapter;
use patterns\Observer\EnrollmentSubject;
use patterns\Observer\NotificationObserver;
use patterns\Observer\AuditLogObserver;
use patterns\Decorator\SectionDecorator;
use patterns\Decorator\EnrollmentRequestDecorator;
use models\ItOfficer as ItOfficerModel;
use models\Section;
use models\Course;
use models\Doctor;
use models\EnrollmentRequest;
use models\AuditLog;
use models\Notification;
use models\Student;

class ItOfficer extends Controller
{
    private ItOfficerModel $itOfficerModel;
    private Section $sectionModel;
    private Course $courseModel;
    private Doctor $doctorModel;
    private EnrollmentRequest $enrollmentRequestModel;
    private AuditLog $auditLogModel;
    private Notification $notificationModel;
    private Student $studentModel;
    
    // Observer Pattern
    private EnrollmentSubject $enrollmentSubject;
    
    // Adapter Pattern
    private NotificationService $notificationService;
    
    // Strategy Pattern
    private ConflictDetector $conflictDetector;

    public function __construct()
    {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'it') {
            $this->redirectTo('auth/login');
        }

        // Factory Method Pattern - Create all models
        $this->itOfficerModel = ModelFactory::create('ItOfficer');
        $this->sectionModel = ModelFactory::create('Section');
        $this->courseModel = ModelFactory::create('Course');
        $this->doctorModel = ModelFactory::create('Doctor');
        $this->enrollmentRequestModel = ModelFactory::create('EnrollmentRequest');
        $this->auditLogModel = ModelFactory::create('AuditLog');
        $this->notificationModel = ModelFactory::create('Notification');
        $this->studentModel = ModelFactory::create('Student');

        // Adapter Pattern - Notification service with database adapter
        $notificationAdapter = new DatabaseNotificationAdapter($this->notificationModel);
        $this->notificationService = new NotificationService($notificationAdapter);

        // Observer Pattern - Setup observers for enrollment events
        $this->enrollmentSubject = new EnrollmentSubject();
        $this->enrollmentSubject->attach(new NotificationObserver($this->notificationModel));
        $this->enrollmentSubject->attach(new AuditLogObserver($this->auditLogModel));

        // Strategy Pattern - Initialize conflict detector with default strategy
        $db = DatabaseConnection::getInstance()->getConnection();
        $this->conflictDetector = new ConflictDetector(new TimeSlotConflictStrategy($db));
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
            $userId = $_SESSION['user']['id'];
            $itOfficer = $this->itOfficerModel->findByUserId($userId);
            
            if (!$itOfficer) {
                $this->view->render('errors/403', ['title' => 'Access Denied', 'message' => 'IT Officer profile not found']);
                return;
            }

            // Get statistics from database
            $pendingRequests = $this->enrollmentRequestModel->getPendingRequests();
            $recentLogs = $this->auditLogModel->getAll(10);

            $this->view->render('it/it_dashboard', [
                'title' => 'IT Officer Dashboard',
                'itOfficer' => $itOfficer,
                'pendingRequestsCount' => count($pendingRequests),
                'recentLogs' => $recentLogs,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load dashboard: ' . $e->getMessage()]);
        }
    }

    public function schedule(): void
    {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Builder Pattern - Build section step by step
                $builder = new SectionBuilder();
                $builder->setCourse((int)($_POST['course_id'] ?? 0))
                        ->setDoctor((int)($_POST['doctor_id'] ?? 0))
                        ->setSectionNumber(trim($_POST['section_number'] ?? ''))
                        ->setSemester(trim($_POST['semester'] ?? ''))
                        ->setAcademicYear(trim($_POST['academic_year'] ?? ''))
                        ->setRoom(trim($_POST['room'] ?? ''))
                        ->setTimeSlot(
                            trim($_POST['day_of_week'] ?? ''),
                            trim($_POST['start_time'] ?? ''),
                            trim($_POST['end_time'] ?? '')
                        )
                        ->setCapacity((int)($_POST['capacity'] ?? 30));

                $sectionData = $builder->build();

                // Strategy Pattern - Check conflicts using different strategies
                $db = DatabaseConnection::getInstance()->getConnection();
                
                // Check time slot conflict
                $this->conflictDetector->setStrategy(new TimeSlotConflictStrategy($db));
                if ($this->conflictDetector->detectConflict($sectionData)) {
                    $error = $this->conflictDetector->getErrorMessage();
                }
                // Check room conflict (if room is provided)
                elseif (!empty($sectionData['room'])) {
                    $this->conflictDetector->setStrategy(new RoomConflictStrategy($db));
                    if ($this->conflictDetector->detectConflict($sectionData)) {
                        $error = $this->conflictDetector->getErrorMessage();
                    }
                }
                // Check doctor availability
                if (!$error) {
                    $this->conflictDetector->setStrategy(new DoctorAvailabilityStrategy($db));
                    if ($this->conflictDetector->detectConflict($sectionData)) {
                        $error = $this->conflictDetector->getErrorMessage();
                    }
                }

                if (!$error) {
                    // Create section using builder
                    $success = $builder->create($this->sectionModel);
                    
                    if ($success) {
                        $sectionId = $this->sectionModel->getLastInsertId();
                        
                        // Observer Pattern - Notify observers about section creation
                        $doctor = $this->doctorModel->findById($sectionData['doctor_id']);
                        if ($doctor) {
                            $this->enrollmentSubject->sectionCreated([
                                'user_id' => $doctor['user_id'],
                                'section_id' => $sectionId,
                                'message' => "You have been assigned to section {$sectionData['section_number']}",
                                'entity_type' => 'section',
                                'entity_id' => $sectionId,
                                'details' => json_encode($sectionData),
                            ]);
                        }
                    } else {
                        $error = 'Failed to create section.';
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        // Get current semester/year (default to current)
        $currentSemester = $_GET['semester'] ?? date('n') <= 6 ? 'Spring' : 'Fall';
        $currentYear = $_GET['year'] ?? date('Y');

        // Get sections from database for current semester/year
        $sections = $this->sectionModel->getBySemester($currentSemester, $currentYear);
        
        // Decorator Pattern - Format sections for display
        $decoratedSections = [];
        foreach ($sections as $section) {
            $decorator = new SectionDecorator($section);
            $section['formatted'] = $decorator->format();
            $section['enrollment_status'] = $decorator->getEnrollmentStatus();
            $decoratedSections[] = $section;
        }

        // Get courses and doctors from database for the form
        $courses = $this->courseModel->getAll();
        $doctors = $this->doctorModel->getAll();

        $this->view->render('it/it_schedule', [
            'title' => 'Manage Schedule',
            'sections' => $decoratedSections,
            'courses' => $courses,
            'doctors' => $doctors,
            'currentSemester' => $currentSemester,
            'currentYear' => $currentYear,
            'error' => $error,
            'success' => $success,
            'showSidebar' => true,
        ]);
    }

    public function enrollments(): void
    {
        try {
            // Get all enrollment requests from database
            $requests = $this->enrollmentRequestModel->getAllRequests();
            
            // Decorator Pattern - Format enrollment requests for display
            $decoratedRequests = [];
            foreach ($requests as $request) {
                $decorator = new EnrollmentRequestDecorator($request);
                $request['formatted'] = $decorator->format();
                $request['status_badge'] = $decorator->getStatusBadge();
                $decoratedRequests[] = $request;
            }
            
            $this->view->render('it/it_enrollments', [
                'title' => 'Enrollment Requests',
                'requests' => $decoratedRequests,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Enrollments error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load enrollment requests: ' . $e->getMessage()]);
        }
    }

    public function approveEnrollment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /it/enrollments');
            exit;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $userId = $_SESSION['user']['id'];
        $itOfficer = $this->itOfficerModel->findByUserId($userId);

        if (!$itOfficer || !$requestId) {
            header('Location: /it/enrollments?error=invalid_request');
            exit;
        }

        $request = $this->enrollmentRequestModel->findById($requestId);
        if (!$request) {
            header('Location: /it/enrollments?error=request_not_found');
            exit;
        }

        $success = $this->enrollmentRequestModel->approveRequest($requestId, $itOfficer['it_id']);

        if ($success) {
            // Observer Pattern - Notify observers about enrollment approval
            $student = $this->studentModel->findByStudentId($request['student_id']);
            if ($student && isset($student['user_id'])) {
                $this->enrollmentSubject->enrollmentApproved([
                    'user_id' => $student['user_id'],
                    'request_id' => $requestId,
                    'message' => "Your enrollment request for {$request['course_code']} - Section {$request['section_number']} has been approved.",
                    'entity_type' => 'enrollment_request',
                    'entity_id' => $requestId,
                    'details' => json_encode($request),
                ]);
            }

            header('Location: /it/enrollments?success=approved');
        } else {
            header('Location: /it/enrollments?error=approval_failed');
        }
        exit;
    }

    public function rejectEnrollment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /it/enrollments');
            exit;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = $_SESSION['user']['id'];
        $itOfficer = $this->itOfficerModel->findByUserId($userId);

        if (!$itOfficer || !$requestId) {
            header('Location: /it/enrollments?error=invalid_request');
            exit;
        }

        $request = $this->enrollmentRequestModel->findById($requestId);
        if (!$request) {
            header('Location: /it/enrollments?error=request_not_found');
            exit;
        }

        $success = $this->enrollmentRequestModel->rejectRequest($requestId, $itOfficer['it_id'], $reason);

        if ($success) {
            // Observer Pattern - Notify observers about enrollment rejection
            $student = $this->studentModel->findByStudentId($request['student_id']);
            if ($student && isset($student['user_id'])) {
                $this->enrollmentSubject->enrollmentRejected([
                    'user_id' => $student['user_id'],
                    'request_id' => $requestId,
                    'message' => "Your enrollment request for {$request['course_code']} - Section {$request['section_number']} has been rejected. " . ($reason ? "Reason: {$reason}" : ''),
                    'entity_type' => 'enrollment_request',
                    'entity_id' => $requestId,
                    'details' => json_encode(['reason' => $reason, 'request' => $request]),
                ]);
            }

            header('Location: /it/enrollments?success=rejected');
        } else {
            header('Location: /it/enrollments?error=rejection_failed');
        }
        exit;
    }

    public function course(): void
    {
        $message = null;
        $messageType = 'info';
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'assign-doctor') {
                $courseId = (int)($_POST['course_id'] ?? 0);
                $doctorId = (int)($_POST['doctor_id'] ?? 0);
                
                if ($courseId && $doctorId) {
                    // Check if already assigned
                    $assignedDoctors = $this->courseModel->getAssignedDoctors($courseId);
                    $alreadyAssigned = false;
                    foreach ($assignedDoctors as $doc) {
                        if ($doc['doctor_id'] == $doctorId) {
                            $alreadyAssigned = true;
                            break;
                        }
                    }
                    
                    if ($alreadyAssigned) {
                        $message = 'Doctor is already assigned to this course';
                        $messageType = 'warning';
                    } else {
                        $success = $this->courseModel->assignDoctor($courseId, $doctorId);
                        if ($success) {
                            // Observer Pattern - Log assignment
                            $course = $this->courseModel->findById($courseId);
                            $doctor = $this->doctorModel->findById($doctorId);
                            
                            $this->auditLogModel->create([
                                'user_id' => $_SESSION['user']['id'],
                                'action' => 'doctor_assigned_to_course',
                                'entity_type' => 'course',
                                'entity_id' => $courseId,
                                'details' => json_encode([
                                    'doctor_id' => $doctorId,
                                    'doctor_name' => ($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''),
                                    'course_code' => $course['course_code'] ?? 'N/A',
                                    'course_name' => $course['name'] ?? 'N/A'
                                ])
                            ]);
                            
                            $message = 'Doctor assigned successfully';
                            $messageType = 'success';
                        } else {
                            $message = 'Error assigning doctor';
                            $messageType = 'error';
                        }
                    }
                } else {
                    $message = 'Invalid course or doctor selected';
                    $messageType = 'error';
                }
            } elseif ($action === 'enroll-student') {
                $courseId = (int)($_POST['course_id'] ?? 0);
                $studentIds = is_array($_POST['student_id'] ?? []) ? $_POST['student_id'] : [$_POST['student_id'] ?? 0];
                $status = $_POST['enrollment_status'] ?? 'taking';
                
                if ($courseId && !empty($studentIds)) {
                    $enrolled = 0;
                    $skipped = 0;
                    $errors = [];
                    
                    foreach ($studentIds as $studentId) {
                        $studentId = (int)$studentId;
                        if ($studentId <= 0) continue;
                        
                        // Check if already enrolled
                        $enrolledStudents = $this->courseModel->getEnrolledStudents($courseId);
                        $alreadyEnrolled = false;
                        foreach ($enrolledStudents as $stud) {
                            if ($stud['student_id'] == $studentId) {
                                $alreadyEnrolled = true;
                                break;
                            }
                        }
                        
                        if ($alreadyEnrolled) {
                            $skipped++;
                        } else {
                            $success = $this->courseModel->enrollStudent($courseId, $studentId, $status);
                            if ($success) {
                                $enrolled++;
                                
                                // Observer Pattern - Log enrollment
                                $course = $this->courseModel->findById($courseId);
                                $student = $this->studentModel->findByStudentId($studentId);
                                
                                $this->auditLogModel->create([
                                    'user_id' => $_SESSION['user']['id'],
                                    'action' => 'student_enrolled_in_course',
                                    'entity_type' => 'course',
                                    'entity_id' => $courseId,
                                    'details' => json_encode([
                                        'student_id' => $studentId,
                                        'student_name' => ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''),
                                        'course_code' => $course['course_code'] ?? 'N/A',
                                        'course_name' => $course['name'] ?? 'N/A',
                                        'status' => $status
                                    ])
                                ]);
                            } else {
                                $errors[] = "Error enrolling student ID $studentId";
                            }
                        }
                    }
                    
                    if (count($studentIds) === 1) {
                        if ($enrolled > 0) {
                            $message = 'Student enrolled successfully';
                            $messageType = 'success';
                        } elseif ($skipped > 0) {
                            $message = 'Student is already enrolled in this course';
                            $messageType = 'warning';
                        } else {
                            $message = !empty($errors) ? implode(', ', $errors) : 'Error enrolling student';
                            $messageType = 'error';
                        }
                    } else {
                        $message = "$enrolled student(s) enrolled successfully";
                        if ($skipped > 0) {
                            $message .= ", $skipped already enrolled";
                        }
                        if (!empty($errors)) {
                            $message .= ". Errors: " . implode(', ', $errors);
                        }
                        $messageType = !empty($errors) ? 'warning' : 'success';
                    }
                } else {
                    $message = 'Please select at least one student';
                    $messageType = 'error';
                }
            } elseif ($action === 'remove-doctor') {
                $courseId = (int)($_POST['course_id'] ?? 0);
                $doctorId = (int)($_POST['doctor_id'] ?? 0);
                
                if ($courseId && $doctorId) {
                    // Get info for logging before deletion
                    $course = $this->courseModel->findById($courseId);
                    $doctor = $this->doctorModel->findById($doctorId);
                    
                    $success = $this->courseModel->removeDoctor($courseId, $doctorId);
                    if ($success) {
                        // Observer Pattern - Log removal
                        $this->auditLogModel->create([
                            'user_id' => $_SESSION['user']['id'],
                            'action' => 'doctor_removed_from_course',
                            'entity_type' => 'course',
                            'entity_id' => $courseId,
                            'details' => json_encode([
                                'doctor_id' => $doctorId,
                                'doctor_name' => ($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''),
                                'course_code' => $course['course_code'] ?? 'N/A',
                                'course_name' => $course['name'] ?? 'N/A'
                            ])
                        ]);
                        
                        $message = 'Doctor removed successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Error removing doctor';
                        $messageType = 'error';
                    }
                }
            } elseif ($action === 'remove-student') {
                $courseId = (int)($_POST['course_id'] ?? 0);
                $studentId = (int)($_POST['student_id'] ?? 0);
                
                if ($courseId && $studentId) {
                    // Get info for logging before deletion
                    $course = $this->courseModel->findById($courseId);
                    $student = $this->studentModel->findByStudentId($studentId);
                    
                    $success = $this->courseModel->removeStudent($courseId, $studentId);
                    if ($success) {
                        // Observer Pattern - Log removal
                        $this->auditLogModel->create([
                            'user_id' => $_SESSION['user']['id'],
                            'action' => 'student_removed_from_course',
                            'entity_type' => 'course',
                            'entity_id' => $courseId,
                            'details' => json_encode([
                                'student_id' => $studentId,
                                'student_name' => ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''),
                                'course_code' => $course['course_code'] ?? 'N/A',
                                'course_name' => $course['name'] ?? 'N/A'
                            ])
                        ]);
                        
                        $message = 'Student removed successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Error removing student';
                        $messageType = 'error';
                    }
                }
            }
            
            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/it/course?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
        
        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $departmentFilter = trim($_GET['department'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        
        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($departmentFilter)) $filters['department'] = $departmentFilter;
        
        // Get courses with filters from database
        $courses = $this->courseModel->getCoursesWithFilters($filters);
        
        // Get doctors and students from database
        $doctors = $this->doctorModel->getAll();
        $students = $this->studentModel->getAllStudents();
        
        // Get unique departments from database
        $departments = $this->courseModel->getUniqueDepartments();
        
        // Enhance courses with assigned doctors and enrolled students from database
        // Decorator Pattern - Format courses for display
        foreach ($courses as &$course) {
            $course['assigned_doctors'] = $this->courseModel->getAssignedDoctors($course['course_id']);
            $course['enrolled_students'] = $this->courseModel->getEnrolledStudents($course['course_id']);
        }
        unset($course);
        
        $this->view->render('it/it_course', [
            'title' => 'Course Management',
            'courses' => $courses,
            'doctors' => $doctors,
            'students' => $students,
            'departments' => $departments,
            'search' => $search,
            'departmentFilter' => $departmentFilter,
            'statusFilter' => $statusFilter,
            'message' => $message ?? $_GET['message'] ?? null,
            'messageType' => $messageType ?? $_GET['type'] ?? 'info',
            'showSidebar' => true,
        ]);
    }

    public function logs(): void
    {
        try {
            // Get audit logs from database
            $logs = $this->auditLogModel->getAll(100);
            
            $this->view->render('it/it_logs', [
                'title' => 'Audit Logs',
                'logs' => $logs,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Logs error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load audit logs: ' . $e->getMessage()]);
        }
    }
}

