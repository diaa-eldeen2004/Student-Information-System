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
            
            // Get additional statistics from database
            $totalCourses = count($this->courseModel->getAll());
            $totalSections = count($this->sectionModel->getAll());
            $totalDoctors = count($this->doctorModel->getAll());
            $totalStudents = count($this->studentModel->getAllStudents());
            $totalEnrollments = $this->enrollmentRequestModel->getAllRequests();
            $approvedEnrollments = array_filter($totalEnrollments, fn($r) => $r['status'] === 'approved');
            $rejectedEnrollments = array_filter($totalEnrollments, fn($r) => $r['status'] === 'rejected');

            $this->view->render('it/it_dashboard', [
                'title' => 'IT Officer Dashboard',
                'itOfficer' => $itOfficer,
                'pendingRequestsCount' => count($pendingRequests),
                'recentLogs' => $recentLogs,
                'totalCourses' => $totalCourses,
                'totalSections' => $totalSections,
                'totalDoctors' => $totalDoctors,
                'totalStudents' => $totalStudents,
                'approvedEnrollmentsCount' => count($approvedEnrollments),
                'rejectedEnrollmentsCount' => count($rejectedEnrollments),
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

        // Handle AJAX GET requests
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];
            
            if ($action === 'get_sections') {
                $courseId = (int)($_GET['course_id'] ?? 0);
                $semester = $_GET['semester'] ?? null;
                $academicYear = $_GET['year'] ?? null;
                
                if ($courseId > 0) {
                    $sections = $this->sectionModel->getSectionNumbersByCourse($courseId, $semester, $academicYear);
                    header('Content-Type: application/json');
                    echo json_encode(['sections' => $sections]);
                    exit;
                }
                
                header('Content-Type: application/json');
                echo json_encode(['sections' => []]);
                exit;
            }
            
            if ($action === 'check_database') {
                $this->checkDatabaseTables();
                exit;
            }
            
            if ($action === 'run_migration') {
                $this->runMigration();
                exit;
            }
        }
        
        // Handle AJAX POST requests for creating tables
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
            if ($_GET['action'] === 'create_tables') {
                $this->createDatabaseTables();
                exit;
            }
            if ($_GET['action'] === 'run_migration') {
                $this->runMigration();
                exit;
            }
        }

        // Handle POST requests (form submissions) - but not AJAX actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_GET['action']) || $_GET['action'] !== 'run_migration')) {
            try {
                // Check if quick mode (multiple courses for same day)
                $isQuickMode = !empty($_POST['quick_mode']);
                
                if ($isQuickMode) {
                    // Handle quick schedule creation
                    $this->handleQuickSchedule();
                    return;
                }
                
                // Check if bulk mode
                $isBulkMode = !empty($_POST['bulk_mode']);
                
                if ($isBulkMode) {
                    // Handle bulk schedule creation
                    $this->handleBulkSchedule();
                    return;
                }
                
                // Core Concept: Each schedule entry = (Semester + Course + Day + Time + Room)
                // Support multiple doctors, multiple sections, and multiple days
                $days = $_POST['days'] ?? [];
                $startTimes = $_POST['start_time'] ?? [];
                $endTimes = $_POST['end_time'] ?? [];
                $sessionType = trim($_POST['session_type'] ?? 'lecture');
                
                // If no days selected, check for single day (backward compatibility)
                if (empty($days) && !empty($_POST['day_of_week'])) {
                    $days = [$_POST['day_of_week']];
                    $startTimes[$_POST['day_of_week']] = $_POST['start_time'] ?? '';
                    $endTimes[$_POST['day_of_week']] = $_POST['end_time'] ?? '';
                }
                
                if (empty($days)) {
                    $error = 'Please select at least one day for the schedule entry.';
                } else {
                    $entriesCreated = 0;
                    $entriesFailed = 0;
                    $conflictErrors = [];
                    
                    $courseId = (int)($_POST['course_id'] ?? 0);
                    $doctorIds = $_POST['doctor_ids'] ?? [];
                    $sectionNumbers = $_POST['section_numbers'] ?? [];
                    $semester = trim($_POST['semester'] ?? '');
                    $academicYear = trim($_POST['academic_year'] ?? '');
                    $room = trim($_POST['room'] ?? '');
                    $capacity = (int)($_POST['capacity'] ?? 30);
                    
                    // Validate required fields
                    if (!$courseId) {
                        $error = 'Please select a course.';
                    } elseif (empty($doctorIds)) {
                        $error = 'Please select at least one doctor.';
                    } elseif (empty($sectionNumbers)) {
                        $error = 'Please select at least one section number.';
                    } elseif (!$semester || !$academicYear) {
                        $error = 'Please select semester and enter academic year.';
                    } elseif (!$room) {
                        $error = 'Please enter a room number.';
                    } else {
                        // Create entries for each combination: doctor × section × day
                        foreach ($doctorIds as $doctorId) {
                            $doctorId = (int)$doctorId;
                            foreach ($sectionNumbers as $sectionNumber) {
                                $sectionNumber = trim($sectionNumber);
                                
                                // Create one schedule entry per selected day
                                foreach ($days as $day) {
                                    $startTime = $startTimes[$day] ?? '';
                                    $endTime = $endTimes[$day] ?? '';
                                    
                                    // Validate time for this day
                                    if (empty($startTime) || empty($endTime)) {
                                        $entriesFailed++;
                                        $conflictErrors[] = "{$day}: Start time and end time are required";
                                        continue;
                                    }
                                    
                                    // Builder Pattern - Build schedule entry step by step
                                    $builder = new SectionBuilder();
                                    $builder->setCourse($courseId)
                                            ->setDoctor($doctorId)
                                            ->setSectionNumber($sectionNumber)
                                            ->setSemester($semester)
                                            ->setAcademicYear($academicYear)
                                            ->setRoom($room ?: null)
                                            ->setCapacity($capacity)
                                            ->setTimeSlot($day, $startTime, $endTime);
                                    
                                    // Add session type if supported
                                    if (method_exists($builder, 'setSessionType')) {
                                        $builder->setSessionType($sessionType);
                                    }
                                    
                                    $entryData = $builder->build();
                                    
                                    // Conflict Detection: Check in order of priority
                                    // Rule: Conflict exists if (Same Semester + Same Day + Same Room + Overlapping Time)
                                    $db = DatabaseConnection::getInstance()->getConnection();
                                    $dayError = null;
                                    
                                    // 1. Check room conflict first (most critical - same room can't be double-booked)
                                    if (!empty($entryData['room'])) {
                                        $this->conflictDetector->setStrategy(new RoomConflictStrategy($db));
                                        if ($this->conflictDetector->detectConflict($entryData)) {
                                            $dayError = "Room conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                                        }
                                    }
                                    
                                    // 2. Check doctor availability (doctor can't teach two courses at same time)
                                    if (!$dayError) {
                                        $this->conflictDetector->setStrategy(new DoctorAvailabilityStrategy($db));
                                        if ($this->conflictDetector->detectConflict($entryData)) {
                                            $dayError = "Doctor conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                                        }
                                    }
                                    
                                    // 3. Check for exact duplicate entry
                                    // Core Concept: Multiple sessions for the same course ARE ALLOWED on the same day
                                    // as long as they have different:
                                    // - Section numbers (e.g., "001" vs "002"), OR
                                    // - Session types (e.g., "Lecture" vs "Lab"), OR
                                    // - Time slots (non-overlapping times)
                                    // Only block if ALL fields match exactly (true duplicate)
                                    if (!$dayError) {
                                        // Build section number with session type for comparison
                                        $fullSectionNumber = $sectionNumber;
                                        if ($sessionType && strpos($sectionNumber, $sessionType) === false) {
                                            $fullSectionNumber = $sectionNumber . '-' . ucfirst($sessionType);
                                        }
                                        
                                        $checkDuplicate = $db->prepare("
                                            SELECT COUNT(*) as count FROM sections
                                            WHERE course_id = :course_id
                                            AND doctor_id = :doctor_id
                                            AND semester = :semester
                                            AND academic_year = :academic_year
                                            AND day_of_week = :day_of_week
                                            AND section_number = :section_number
                                            AND start_time = :start_time
                                            AND end_time = :end_time
                                            AND room = :room
                                        ");
                                        $checkDuplicate->execute([
                                            'course_id' => $courseId,
                                            'doctor_id' => $doctorId,
                                            'semester' => $semester,
                                            'academic_year' => $academicYear,
                                            'day_of_week' => $day,
                                            'section_number' => $fullSectionNumber,
                                            'start_time' => $startTime,
                                            'end_time' => $endTime,
                                            'room' => $room ?: '',
                                        ]);
                                        $duplicate = $checkDuplicate->fetch(PDO::FETCH_ASSOC);
                                        if ((int)$duplicate['count'] > 0) {
                                            $dayError = "Duplicate entry: This exact schedule entry already exists for {$day}";
                                        }
                                    }
                                    
                                    if (!$dayError) {
                                        // Create schedule entry (one per day)
                                        try {
                                            $entrySuccess = $builder->create($this->sectionModel);
                                            
                                            if ($entrySuccess) {
                                                $entriesCreated++;
                                                $sectionId = $this->sectionModel->getLastInsertId();
                                                
                                                // Observer Pattern - Notify observers about schedule entry creation
                                                $doctor = $this->doctorModel->findById($doctorId);
                                                if ($doctor) {
                                                    $this->enrollmentSubject->sectionCreated([
                                                        'user_id' => $doctor['user_id'],
                                                        'section_id' => $sectionId,
                                                        'message' => "You have been assigned to section {$sectionNumber} on {$day}",
                                                        'entity_type' => 'section',
                                                        'entity_id' => $sectionId,
                                                        'details' => json_encode($entryData),
                                                    ]);
                                                }
                                                
                                                // Log the schedule entry creation
                                                $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
                                                $this->auditLogModel->create([
                                                    'user_id' => $userId,
                                                    'action' => 'schedule_entry_created',
                                                    'entity_type' => 'section',
                                                    'entity_id' => $sectionId,
                                                    'details' => json_encode([
                                                        'course_id' => $courseId,
                                                        'doctor_id' => $doctorId,
                                                        'section_number' => $sectionNumber,
                                                        'day' => $day,
                                                        'time' => "{$startTime}-{$endTime}",
                                                        'room' => $room,
                                                        'session_type' => $sessionType,
                                                    ])
                                                ]);
                                            } else {
                                                $entriesFailed++;
                                                $conflictErrors[] = "Doctor {$doctorId}, Section {$sectionNumber}, {$day}: Failed to create schedule entry (database error)";
                                            }
                                        } catch (\Exception $e) {
                                            $entriesFailed++;
                                            $conflictErrors[] = "Doctor {$doctorId}, Section {$sectionNumber}, {$day}: " . $e->getMessage();
                                            error_log("Schedule creation error: " . $e->getMessage());
                                        }
                                    } else {
                                        $entriesFailed++;
                                        $conflictErrors[] = "Doctor {$doctorId}, Section {$sectionNumber}, {$day}: {$dayError}";
                                    }
                                }
                            }
                        }
                        
                        if ($entriesCreated > 0) {
                            $success = "Created {$entriesCreated} schedule entry/entries successfully";
                            if ($entriesFailed > 0) {
                                $success .= " ({$entriesFailed} failed)";
                                $error = implode('; ', array_slice($conflictErrors, 0, 5));
                                if (count($conflictErrors) > 5) {
                                    $error .= ' (and ' . (count($conflictErrors) - 5) . ' more errors)';
                                }
                            }
                        } else {
                            $error = $error ?? 'Failed to create schedule entries. ' . implode('; ', array_slice($conflictErrors, 0, 5));
                            if (count($conflictErrors) > 5) {
                                $error .= ' (and ' . (count($conflictErrors) - 5) . ' more errors)';
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = 'Error: ' . $e->getMessage();
                error_log("Schedule creation error: " . $e->getMessage());
                error_log("Schedule creation error trace: " . $e->getTraceAsString());
            }
            
            // Check if AJAX request - multiple ways to detect
            $isAjax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            );
            
            // Log for debugging
            error_log("Schedule POST - AJAX: " . ($isAjax ? 'YES' : 'NO'));
            error_log("Schedule POST - Success: " . ($success ?? 'null'));
            error_log("Schedule POST - Error: " . ($error ?? 'null'));
            error_log("Schedule POST - Entries created: " . ($entriesCreated ?? 0));
            error_log("Schedule POST - POST data keys: " . implode(', ', array_keys($_POST)));
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'error' => $error,
                    'entries_created' => $entriesCreated ?? 0
                ]);
                exit;
            }
            
            // Redirect to avoid resubmission and show messages
            $queryParams = [];
            if ($success) {
                $queryParams['success'] = urlencode($success);
            }
            if ($error) {
                $queryParams['error'] = urlencode($error);
            }
            
            $config = require dirname(__DIR__) . '/config/config.php';
            $base = rtrim($config['base_url'] ?? '', '/');
            $redirectUrl = $base . '/it/schedule';
            if (!empty($queryParams)) {
                $redirectUrl .= '?' . http_build_query($queryParams);
            }
            
            header("Location: {$redirectUrl}");
            exit;
        }

        // Get current semester/year (default to current)
        $currentSemester = $_GET['semester'] ?? date('n') <= 6 ? 'Spring' : 'Fall';
        $currentYear = $_GET['year'] ?? date('Y');

        // Get schedule entries from database for current semester/year
        $sections = $this->sectionModel->getBySemester($currentSemester, $currentYear);
        
        // Get weekly timetable view (organized by day)
        $weeklyTimetable = $this->sectionModel->getWeeklyTimetable($currentSemester, $currentYear);
        
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
        
        // Get all sections for history (all semesters)
        $allSections = $this->sectionModel->getAll();
        
        // Group history by semester/year
        $historyBySemester = [];
        foreach ($allSections as $section) {
            $key = $section['semester'] . ' ' . $section['academic_year'];
            if (!isset($historyBySemester[$key])) {
                $historyBySemester[$key] = [];
            }
            $historyBySemester[$key][] = $section;
        }
        
        // Sort history by year and semester (newest first)
        uksort($historyBySemester, function($a, $b) {
            // Extract year and semester
            preg_match('/(\w+)\s+(\d+)/', $a, $matchA);
            preg_match('/(\w+)\s+(\d+)/', $b, $matchB);
            $yearA = (int)($matchA[2] ?? 0);
            $yearB = (int)($matchB[2] ?? 0);
            if ($yearA !== $yearB) {
                return $yearB - $yearA; // Descending year
            }
            // Same year, sort by semester
            $semOrder = ['Fall' => 3, 'Summer' => 2, 'Spring' => 1];
            $semA = $semOrder[$matchA[1] ?? ''] ?? 0;
            $semB = $semOrder[$matchB[1] ?? ''] ?? 0;
            return $semB - $semA;
        });

        $this->view->render('it/it_schedule', [
            'title' => 'Manage Semester Schedule',
            'sections' => $decoratedSections,
            'weeklyTimetable' => $weeklyTimetable,
            'courses' => $courses,
            'doctors' => $doctors,
            'currentSemester' => $currentSemester,
            'currentYear' => $currentYear,
            'error' => $error,
            'success' => $success,
            'historyBySemester' => $historyBySemester,
            'showSidebar' => true,
        ]);
    }
    
    /**
     * Handle quick schedule creation - multiple courses for same day(s) in a table format
     */
    private function handleQuickSchedule(): void
    {
        $error = null;
        $success = null;
        
        try {
            $days = $_POST['quick_days'] ?? [];
            $semester = trim($_POST['quick_semester'] ?? '');
            $academicYear = trim($_POST['quick_academic_year'] ?? '');
            
            $courses = $_POST['quick_course'] ?? [];
            $doctors = $_POST['quick_doctor'] ?? [];
            $sections = $_POST['quick_section'] ?? [];
            $sessionTypes = $_POST['quick_session_type'] ?? [];
            $rooms = $_POST['quick_room'] ?? [];
            $startTimes = $_POST['quick_start_time'] ?? [];
            $endTimes = $_POST['quick_end_time'] ?? [];
            $capacities = $_POST['quick_capacity'] ?? [];
            
            if (empty($days)) {
                $error = 'Please select at least one day.';
            } elseif (empty($semester) || empty($academicYear)) {
                $error = 'Please specify semester and academic year.';
            } elseif (empty($courses)) {
                $error = 'Please add at least one course to schedule.';
            } else {
                $entriesCreated = 0;
                $entriesFailed = 0;
                $conflictErrors = [];
                
                $db = DatabaseConnection::getInstance()->getConnection();
                
                // Process each course row
                foreach ($courses as $rowIndex => $courseId) {
                    $courseId = (int)$courseId;
                    $doctorId = (int)($doctors[$rowIndex] ?? 0);
                    $sectionNumber = trim($sections[$rowIndex] ?? '');
                    $sessionType = trim($sessionTypes[$rowIndex] ?? 'lecture');
                    $room = trim($rooms[$rowIndex] ?? '');
                    $startTime = trim($startTimes[$rowIndex] ?? '');
                    $endTime = trim($endTimes[$rowIndex] ?? '');
                    $capacity = (int)($capacities[$rowIndex] ?? 30);
                    
                    if (!$courseId || !$doctorId || !$sectionNumber || !$room || !$startTime || !$endTime) {
                        $entriesFailed++;
                        $conflictErrors[] = "Row " . ($rowIndex + 1) . ": Missing required fields";
                        continue;
                    }
                    
                    // Create entry for each selected day
                    foreach ($days as $day) {
                        // Builder Pattern - Build schedule entry
                        $builder = new SectionBuilder();
                        $builder->setCourse($courseId)
                                ->setDoctor($doctorId)
                                ->setSectionNumber($sectionNumber)
                                ->setSemester($semester)
                                ->setAcademicYear($academicYear)
                                ->setRoom($room)
                                ->setCapacity($capacity)
                                ->setTimeSlot($day, $startTime, $endTime);
                        
                        if (method_exists($builder, 'setSessionType')) {
                            $builder->setSessionType($sessionType);
                        }
                        
                        $entryData = $builder->build();
                        
                        // Conflict Detection
                        $dayError = null;
                        
                        // 1. Check room conflict
                        if (!empty($entryData['room'])) {
                            $this->conflictDetector->setStrategy(new RoomConflictStrategy($db));
                            if ($this->conflictDetector->detectConflict($entryData)) {
                                $dayError = "Room conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                            }
                        }
                        
                        // 2. Check doctor availability
                        if (!$dayError) {
                            $this->conflictDetector->setStrategy(new DoctorAvailabilityStrategy($db));
                            if ($this->conflictDetector->detectConflict($entryData)) {
                                $dayError = "Doctor conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                            }
                        }
                        
                        // 3. Check for exact duplicate
                        if (!$dayError) {
                            $fullSectionNumber = $sectionNumber;
                            if ($sessionType && strpos($sectionNumber, $sessionType) === false) {
                                $fullSectionNumber = $sectionNumber . '-' . ucfirst($sessionType);
                            }
                            
                            $checkDuplicate = $db->prepare("
                                SELECT COUNT(*) as count FROM sections
                                WHERE course_id = :course_id
                                AND doctor_id = :doctor_id
                                AND semester = :semester
                                AND academic_year = :academic_year
                                AND day_of_week = :day_of_week
                                AND section_number = :section_number
                                AND start_time = :start_time
                                AND end_time = :end_time
                                AND room = :room
                            ");
                            $checkDuplicate->execute([
                                'course_id' => $courseId,
                                'doctor_id' => $doctorId,
                                'semester' => $semester,
                                'academic_year' => $academicYear,
                                'day_of_week' => $day,
                                'section_number' => $fullSectionNumber,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'room' => $room,
                            ]);
                            $duplicate = $checkDuplicate->fetch(\PDO::FETCH_ASSOC);
                            if ((int)$duplicate['count'] > 0) {
                                $dayError = "Duplicate entry: This exact schedule entry already exists for {$day}";
                            }
                        }
                        
                        if (!$dayError) {
                            // Create schedule entry
                            $entrySuccess = $builder->create($this->sectionModel);
                            
                            if ($entrySuccess) {
                                $entriesCreated++;
                                $sectionId = $this->sectionModel->getLastInsertId();
                                
                                // Observer Pattern - Notify doctor
                                $doctor = $this->doctorModel->findById($doctorId);
                                if ($doctor) {
                                    $this->enrollmentSubject->sectionCreated([
                                        'user_id' => $doctor['user_id'],
                                        'section_id' => $sectionId,
                                        'message' => "You have been assigned to section {$sectionNumber} on {$day}",
                                        'entity_type' => 'section',
                                        'entity_id' => $sectionId,
                                        'details' => json_encode($entryData),
                                    ]);
                                }
                                
                                // Audit log
                                $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
                                $this->auditLogModel->create([
                                    'user_id' => $userId,
                                    'action' => 'schedule_entry_created',
                                    'entity_type' => 'section',
                                    'entity_id' => $sectionId,
                                    'details' => json_encode([
                                        'course_id' => $courseId,
                                        'doctor_id' => $doctorId,
                                        'section_number' => $sectionNumber,
                                        'day' => $day,
                                        'time' => "{$startTime}-{$endTime}",
                                        'room' => $room,
                                        'session_type' => $sessionType,
                                    ])
                                ]);
                            } else {
                                $entriesFailed++;
                                $conflictErrors[] = "Row " . ($rowIndex + 1) . " on {$day}: Failed to create schedule entry";
                            }
                        } else {
                            $entriesFailed++;
                            $conflictErrors[] = "Row " . ($rowIndex + 1) . " on {$day}: {$dayError}";
                        }
                    }
                }
                
                // Build success/error message
                if ($entriesCreated > 0) {
                    $success = "Successfully created {$entriesCreated} schedule entry(ies).";
                    if ($entriesFailed > 0) {
                        $success .= " {$entriesFailed} entry(ies) failed due to conflicts or errors.";
                        $error = implode('; ', array_slice($conflictErrors, 0, 5));
                        if (count($conflictErrors) > 5) {
                            $error .= ' (and ' . (count($conflictErrors) - 5) . ' more errors)';
                        }
                    }
                } else {
                    $error = "Failed to create any schedule entries. " . implode('; ', array_slice($conflictErrors, 0, 5));
                    if (count($conflictErrors) > 5) {
                        $error .= ' (and ' . (count($conflictErrors) - 5) . ' more errors)';
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Quick schedule creation error: " . $e->getMessage());
            $error = "An error occurred while creating schedule entries: " . $e->getMessage();
        }
        
        // Check if AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'error' => $error
            ]);
            exit;
        }
        
        // Redirect back with message
        $queryParams = [];
        if ($error) $queryParams['error'] = urlencode($error);
        if ($success) $queryParams['success'] = urlencode($success);
        
        $redirectUrl = $this->url('it/schedule');
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        
        header("Location: {$redirectUrl}");
        exit;
    }
    
    /**
     * Handle bulk schedule creation - multiple courses on same day(s)
     */
    private function handleBulkSchedule(): void
    {
        $error = null;
        $success = null;
        
        try {
            $courseIds = $_POST['bulk_courses'] ?? [];
            $days = $_POST['bulk_days'] ?? [];
            $semester = trim($_POST['bulk_semester'] ?? '');
            $academicYear = trim($_POST['bulk_academic_year'] ?? '');
            $doctors = $_POST['bulk_doctor'] ?? [];
            $sections = $_POST['bulk_section'] ?? [];
            $sessionTypes = $_POST['bulk_session_type'] ?? [];
            $rooms = $_POST['bulk_room'] ?? [];
            $capacities = $_POST['bulk_capacity'] ?? [];
            $startTimes = $_POST['bulk_start_time'] ?? [];
            $endTimes = $_POST['bulk_end_time'] ?? [];
            
            if (empty($courseIds)) {
                $error = 'Please select at least one course.';
            } elseif (empty($days)) {
                $error = 'Please select at least one day.';
            } elseif (empty($semester) || empty($academicYear)) {
                $error = 'Please specify semester and academic year.';
            } else {
                $entriesCreated = 0;
                $entriesFailed = 0;
                $conflictErrors = [];
                
                $db = DatabaseConnection::getInstance()->getConnection();
                
                // Process each selected course
                foreach ($courseIds as $courseId) {
                    $courseId = (int)$courseId;
                    $doctorId = (int)($doctors[$courseId] ?? 0);
                    $sectionNumber = trim($sections[$courseId] ?? '');
                    $sessionType = trim($sessionTypes[$courseId] ?? 'lecture');
                    $room = trim($rooms[$courseId] ?? '');
                    $capacity = (int)($capacities[$courseId] ?? 30);
                    
                    if (!$doctorId || !$sectionNumber || !$room) {
                        $entriesFailed++;
                        $conflictErrors[] = "Course ID {$courseId}: Missing required fields (doctor, section, or room)";
                        continue;
                    }
                    
                    // Process each selected day for this course
                    foreach ($days as $day) {
                        $startTime = trim($startTimes[$courseId][$day] ?? '');
                        $endTime = trim($endTimes[$courseId][$day] ?? '');
                        
                        if (empty($startTime) || empty($endTime)) {
                            $entriesFailed++;
                            $conflictErrors[] = "Course ID {$courseId} on {$day}: Start time and end time are required";
                            continue;
                        }
                        
                        // Build section number with session type
                        $fullSectionNumber = $sectionNumber;
                        if ($sessionType && strpos($sectionNumber, $sessionType) === false) {
                            $fullSectionNumber = $sectionNumber . '-' . ucfirst($sessionType);
                        }
                        
                        // Builder Pattern - Build schedule entry
                        $builder = new SectionBuilder();
                        $builder->setCourse($courseId)
                                ->setDoctor($doctorId)
                                ->setSectionNumber($fullSectionNumber)
                                ->setSemester($semester)
                                ->setAcademicYear($academicYear)
                                ->setRoom($room)
                                ->setCapacity($capacity)
                                ->setTimeSlot($day, $startTime, $endTime)
                                ->setSessionType($sessionType);
                        
                        $entryData = $builder->build();
                        
                        // Conflict Detection
                        $dayError = null;
                        
                        // 1. Check room conflict
                        if (!empty($entryData['room'])) {
                            $this->conflictDetector->setStrategy(new RoomConflictStrategy($db));
                            if ($this->conflictDetector->detectConflict($entryData)) {
                                $dayError = "Room conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                            }
                        }
                        
                        // 2. Check doctor availability
                        if (!$dayError) {
                            $this->conflictDetector->setStrategy(new DoctorAvailabilityStrategy($db));
                            if ($this->conflictDetector->detectConflict($entryData)) {
                                $dayError = "Doctor conflict on {$day}: " . $this->conflictDetector->getErrorMessage();
                            }
                        }
                        
                        // 3. Check for exact duplicate
                        if (!$dayError) {
                            $checkDuplicate = $db->prepare("
                                SELECT COUNT(*) as count FROM sections
                                WHERE course_id = :course_id
                                AND semester = :semester
                                AND academic_year = :academic_year
                                AND day_of_week = :day_of_week
                                AND section_number = :section_number
                                AND start_time = :start_time
                                AND end_time = :end_time
                                AND room = :room
                            ");
                            $checkDuplicate->execute([
                                'course_id' => $courseId,
                                'semester' => $semester,
                                'academic_year' => $academicYear,
                                'day_of_week' => $day,
                                'section_number' => $fullSectionNumber,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'room' => $room,
                            ]);
                            $duplicate = $checkDuplicate->fetch(PDO::FETCH_ASSOC);
                            if ((int)$duplicate['count'] > 0) {
                                $dayError = "Duplicate entry: This exact schedule entry already exists for {$day}";
                            }
                        }
                        
                        if (!$dayError) {
                            // Create schedule entry
                            $entrySuccess = $builder->create($this->sectionModel);
                            
                            if ($entrySuccess) {
                                $entriesCreated++;
                                $sectionId = $this->sectionModel->getLastInsertId();
                                
                                // Observer Pattern - Notify doctor
                                $doctor = $this->doctorModel->findById($doctorId);
                                if ($doctor) {
                                    $this->enrollmentSubject->sectionCreated([
                                        'user_id' => $doctor['user_id'],
                                        'section_id' => $sectionId,
                                        'message' => "You have been assigned to section {$fullSectionNumber} on {$day}",
                                        'entity_type' => 'section',
                                        'entity_id' => $sectionId,
                                        'details' => json_encode($entryData),
                                    ]);
                                }
                                
                                // Audit log
                                $this->auditLogModel->log([
                                    'user_id' => $_SESSION['user_id'],
                                    'action' => 'create_section',
                                    'entity_type' => 'section',
                                    'entity_id' => $sectionId,
                                    'details' => json_encode($entryData),
                                ]);
                            } else {
                                $entriesFailed++;
                                $conflictErrors[] = "Course ID {$courseId} on {$day}: Failed to create schedule entry";
                            }
                        } else {
                            $entriesFailed++;
                            $conflictErrors[] = "Course ID {$courseId} on {$day}: {$dayError}";
                        }
                    }
                }
                
                // Build success/error message
                if ($entriesCreated > 0) {
                    $success = "Successfully created {$entriesCreated} schedule entry(ies).";
                    if ($entriesFailed > 0) {
                        $success .= " {$entriesFailed} entry(ies) failed due to conflicts or errors.";
                    }
                } else {
                    $error = "Failed to create any schedule entries. " . implode(' ', array_slice($conflictErrors, 0, 5));
                    if (count($conflictErrors) > 5) {
                        $error .= " (and " . (count($conflictErrors) - 5) . " more errors)";
                    }
                }
                
                if (!empty($conflictErrors) && $entriesCreated > 0) {
                    // Log detailed errors
                    error_log("Bulk schedule creation errors: " . implode(' | ', $conflictErrors));
                }
            }
        } catch (\Exception $e) {
            error_log("Bulk schedule creation error: " . $e->getMessage());
            $error = "An error occurred while creating schedule entries: " . $e->getMessage();
        }
        
        // Redirect back with message
        $queryParams = [];
        if ($error) $queryParams['error'] = urlencode($error);
        if ($success) $queryParams['success'] = urlencode($success);
        
        $redirectUrl = $this->url('it/schedule');
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        
        header("Location: {$redirectUrl}");
        exit;
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
            $this->redirectTo('it/enrollments');
            return;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $userId = $_SESSION['user']['id'];
        $itOfficer = $this->itOfficerModel->findByUserId($userId);

        if (!$itOfficer || !$requestId) {
            $this->redirectTo('it/enrollments?error=invalid_request');
            return;
        }

        $request = $this->enrollmentRequestModel->findById($requestId);
        if (!$request) {
            $this->redirectTo('it/enrollments?error=request_not_found');
            return;
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

            $this->redirectTo('it/enrollments?success=approved');
        } else {
            $this->redirectTo('it/enrollments?error=approval_failed');
        }
    }

    public function approveAllEnrollments(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectTo('it/enrollments');
            return;
        }

        $userId = $_SESSION['user']['id'];
        $itOfficer = $this->itOfficerModel->findByUserId($userId);

        if (!$itOfficer) {
            $this->redirectTo('it/enrollments?error=unauthorized');
            return;
        }

        // Get all pending requests
        $pendingRequests = $this->enrollmentRequestModel->getPendingRequests();
        $approved = 0;
        $failed = 0;

        foreach ($pendingRequests as $request) {
            $success = $this->enrollmentRequestModel->approveRequest($request['request_id'], $itOfficer['it_id']);
            if ($success) {
                $approved++;
                
                // Observer Pattern - Notify observers about enrollment approval
                $student = $this->studentModel->findByStudentId($request['student_id']);
                if ($student && isset($student['user_id'])) {
                    $this->enrollmentSubject->enrollmentApproved([
                        'user_id' => $student['user_id'],
                        'request_id' => $request['request_id'],
                        'message' => "Your enrollment request for {$request['course_code']} - Section {$request['section_number']} has been approved.",
                        'entity_type' => 'enrollment_request',
                        'entity_id' => $request['request_id'],
                        'details' => json_encode($request),
                    ]);
                }
            } else {
                $failed++;
            }
        }

        if ($approved > 0) {
            $this->redirectTo("it/enrollments?success=approved_all&count={$approved}&failed={$failed}");
        } else {
            $this->redirectTo('it/enrollments?error=approval_failed');
        }
    }

    public function rejectEnrollment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectTo('it/enrollments');
            return;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $userId = $_SESSION['user']['id'];
        $itOfficer = $this->itOfficerModel->findByUserId($userId);

        if (!$itOfficer || !$requestId) {
            $this->redirectTo('it/enrollments?error=invalid_request');
            return;
        }

        $request = $this->enrollmentRequestModel->findById($requestId);
        if (!$request) {
            $this->redirectTo('it/enrollments?error=request_not_found');
            return;
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

            $this->redirectTo('it/enrollments?success=rejected');
        } else {
            $this->redirectTo('it/enrollments?error=rejection_failed');
        }
    }

    public function course(): void
    {
        $message = null;
        $messageType = 'info';
        
        // Handle POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'create-course') {
                $courseCode = trim($_POST['course_code'] ?? '');
                $courseName = trim($_POST['course_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $creditHours = (int)($_POST['credit_hours'] ?? 3);
                $department = trim($_POST['department'] ?? '');
                
                if (empty($courseCode) || empty($courseName)) {
                    $message = 'Course code and name are required';
                    $messageType = 'error';
                } else {
                    // Check if course code already exists
                    $existing = $this->courseModel->findByCode($courseCode);
                    if ($existing) {
                        $message = 'Course code already exists';
                        $messageType = 'error';
                    } else {
                        $success = $this->courseModel->create([
                            'course_code' => $courseCode,
                            'name' => $courseName,
                            'description' => $description,
                            'credit_hours' => $creditHours,
                            'department' => $department,
                        ]);
                        
                        try {
                            $success = $this->courseModel->create([
                                'course_code' => $courseCode,
                                'name' => $courseName,
                                'description' => $description,
                                'credit_hours' => $creditHours,
                                'department' => $department,
                            ]);
                            
                            if ($success) {
                                // Get the created course ID
                                $createdCourse = $this->courseModel->findByCode($courseCode);
                                $courseId = $createdCourse ? $createdCourse['course_id'] : null;
                                
                                // Observer Pattern - Log course creation
                                if ($courseId) {
                                    $this->auditLogModel->create([
                                        'user_id' => $_SESSION['user']['id'],
                                        'action' => 'course_created',
                                        'entity_type' => 'course',
                                        'entity_id' => $courseId,
                                        'details' => json_encode([
                                            'course_code' => $courseCode,
                                            'course_name' => $courseName,
                                            'department' => $department,
                                        ])
                                    ]);
                                }
                                
                                $message = 'Course created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Error creating course';
                                $messageType = 'error';
                            }
                        } catch (\Exception $e) {
                            $message = 'Error creating course: ' . $e->getMessage();
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'assign-doctor' || $action === 'assign-doctors') {
                $courseId = (int)($_POST['course_id'] ?? 0);
                $doctorIds = is_array($_POST['doctor_id'] ?? []) ? $_POST['doctor_id'] : [$_POST['doctor_id'] ?? 0];
                
                if ($courseId && !empty($doctorIds)) {
                    $assigned = 0;
                    $skipped = 0;
                    $errors = [];
                    
                    foreach ($doctorIds as $doctorId) {
                        $doctorId = (int)$doctorId;
                        if ($doctorId <= 0) continue;
                        
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
                            $skipped++;
                        } else {
                            $success = $this->courseModel->assignDoctor($courseId, $doctorId);
                            if ($success) {
                                $assigned++;
                                
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
                            } else {
                                $errors[] = "Error assigning doctor ID $doctorId";
                            }
                        }
                    }
                    
                    if (count($doctorIds) === 1) {
                        if ($assigned > 0) {
                            $message = 'Doctor assigned successfully';
                            $messageType = 'success';
                        } elseif ($skipped > 0) {
                            $message = 'Doctor is already assigned to this course';
                            $messageType = 'warning';
                        } else {
                            $message = !empty($errors) ? implode(', ', $errors) : 'Error assigning doctor';
                            $messageType = 'error';
                        }
                    } else {
                        $message = "$assigned doctor(s) assigned successfully";
                        if ($skipped > 0) {
                            $message .= ", $skipped already assigned";
                        }
                        if (!empty($errors)) {
                            $message .= ". Errors: " . implode(', ', $errors);
                        }
                        $messageType = !empty($errors) ? 'warning' : 'success';
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
        
        // Enhance courses with assigned doctors, enrolled students, and pending requests
        // Decorator Pattern - Format courses for display
        foreach ($courses as &$course) {
            $course['assigned_doctors'] = $this->courseModel->getAssignedDoctors($course['course_id']);
            $course['enrolled_students'] = $this->courseModel->getEnrolledStudents($course['course_id']);
            
            // Get pending enrollment requests for this course
            $allRequests = $this->enrollmentRequestModel->getPendingRequests();
            $course['pending_requests'] = [];
            foreach ($allRequests as $request) {
                // Check if request is for a section of this course
                $section = $this->sectionModel->findById($request['section_id'] ?? 0);
                if ($section && $section['course_id'] == $course['course_id']) {
                    $course['pending_requests'][] = $request;
                }
            }
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
            $message = null;
            $messageType = 'info';
            
            // Handle POST actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                
                if ($action === 'clear-logs') {
                    $success = $this->auditLogModel->clearAll();
                    if ($success) {
                        $message = 'Logs cleared successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Error clearing logs';
                        $messageType = 'error';
                    }
                } elseif ($action === 'export-logs') {
                    // Get filters from POST
                    $filters = [
                        'action' => $_POST['action_filter'] ?? '',
                        'entity_type' => $_POST['entity_type'] ?? '',
                        'dateRange' => $_POST['dateRange'] ?? 'month',
                        'search' => $_POST['search'] ?? ''
                    ];
                    
                    $logs = $this->auditLogModel->getWithFilters($filters, 1000);
                    
                    // Export as CSV
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d_His') . '.csv"');
                    
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['ID', 'Timestamp', 'User', 'Role', 'Action', 'Entity Type', 'Entity ID', 'Details', 'IP Address']);
                    
                    foreach ($logs as $log) {
                        fputcsv($output, [
                            $log['log_id'] ?? '',
                            $log['created_at'] ?? '',
                            trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')),
                            $log['user_role'] ?? '',
                            $log['action'] ?? '',
                            $log['entity_type'] ?? '',
                            $log['entity_id'] ?? '',
                            $log['details'] ?? '',
                            $log['ip_address'] ?? ''
                        ]);
                    }
                    
                    fclose($output);
                    exit;
                }
            }
            
            // Get filters from GET
            $filters = [
                'action' => trim($_GET['action'] ?? ''),
                'entity_type' => trim($_GET['entity_type'] ?? ''),
                'dateRange' => trim($_GET['dateRange'] ?? 'month'),
                'search' => trim($_GET['search'] ?? '')
            ];
            
            // Get logs with filters from database
            $logs = $this->auditLogModel->getWithFilters($filters, 500);
            
            // Get statistics from database
            $stats = $this->auditLogModel->getStats($filters['dateRange']);
            
            // Get unique entity types for filter
            $db = DatabaseConnection::getInstance()->getConnection();
            $entityTypesStmt = $db->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type");
            $entityTypes = $entityTypesStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $this->view->render('it/it_logs', [
                'title' => 'System Logs',
                'logs' => $logs,
                'stats' => $stats,
                'filters' => $filters,
                'entityTypes' => $entityTypes,
                'message' => $message ?? $_GET['message'] ?? null,
                'messageType' => $messageType ?? $_GET['type'] ?? 'info',
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Logs error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to load audit logs: ' . $e->getMessage()]);
        }
    }

    public function sendNotification(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            $itOfficer = $this->itOfficerModel->findByUserId($userId);
            
            if (!$itOfficer) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            $message = null;
            $messageType = 'info';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $targetUserIds = $_POST['user_ids'] ?? [];
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['message'] ?? '');
                $type = trim($_POST['type'] ?? 'info');
                $sendToAll = isset($_POST['send_to_all']) && $_POST['send_to_all'] === '1';

                if (empty($title) || empty($content)) {
                    $message = 'Title and message are required';
                    $messageType = 'error';
                } else {
                    $sent = 0;
                    $failed = 0;

                    if ($sendToAll) {
                        // Get all users
                        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                        $stmt = $db->query("SELECT id FROM users WHERE role IN ('student', 'doctor', 'advisor', 'admin')");
                        $allUsers = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                        
                        foreach ($allUsers as $targetUserId) {
                            if ($this->notificationModel->create([
                                'user_id' => $targetUserId,
                                'title' => $title,
                                'message' => $content,
                                'type' => $type,
                                'related_id' => $itOfficer['it_id'],
                                'related_type' => 'it_officer'
                            ])) {
                                $sent++;
                            } else {
                                $failed++;
                            }
                        }
                    } else {
                        // Send to selected users
                        if (empty($targetUserIds)) {
                            $message = 'Please select at least one user or choose "Send to All"';
                            $messageType = 'error';
                        } else {
                            foreach ($targetUserIds as $targetUserId) {
                                $targetUserId = (int)$targetUserId;
                                if ($targetUserId <= 0) continue;
                                
                                if ($this->notificationModel->create([
                                    'user_id' => $targetUserId,
                                    'title' => $title,
                                    'message' => $content,
                                    'type' => $type,
                                    'related_id' => $itOfficer['it_id'],
                                    'related_type' => 'it_officer'
                                ])) {
                                    $sent++;
                                } else {
                                    $failed++;
                                }
                            }
                        }
                    }

                    if ($sent > 0) {
                        // Observer Pattern - Log notification sending
                        $this->auditLogModel->create([
                            'user_id' => $userId,
                            'action' => 'notifications_sent',
                            'entity_type' => 'notification',
                            'details' => json_encode([
                                'sent_count' => $sent,
                                'failed_count' => $failed,
                                'send_to_all' => $sendToAll,
                                'title' => $title,
                            ])
                        ]);
                        
                        $message = "Notification sent to {$sent} user(s)";
                        if ($failed > 0) {
                            $message .= " ({$failed} failed)";
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send notifications';
                        $messageType = 'error';
                    }
                }
            }

            // Get all users for selection
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT u.id, u.first_name, u.last_name, u.email, u.role,
                       CASE 
                           WHEN u.role = 'student' THEN s.student_number
                           WHEN u.role = 'doctor' THEN d.doctor_id
                           WHEN u.role = 'advisor' THEN a.advisor_id
                           ELSE NULL
                       END as identifier
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN doctors d ON u.id = d.user_id
                LEFT JOIN advisors a ON u.id = a.user_id
                WHERE u.role IN ('student', 'doctor', 'advisor', 'admin')
                ORDER BY u.role, u.first_name, u.last_name
            ");
            $allUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->view->render('it/it_send_notification', [
                'title' => 'Send Notification',
                'users' => $allUsers,
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Send notification error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Check if required database tables exist
     */
    private function checkDatabaseTables(): void
    {
        header('Content-Type: application/json');
        
        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            $requiredTables = ['sections', 'courses', 'doctors', 'users'];
            $existingTables = [];
            $missingTables = [];
            
            foreach ($requiredTables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }
            
            echo json_encode([
                'exists' => empty($missingTables),
                'tables' => $existingTables,
                'missing' => $missingTables
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'exists' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Create missing database tables
     */
    private function createDatabaseTables(): void
    {
        header('Content-Type: application/json');
        
        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            
            // Check if sections table exists
            $stmt = $db->query("SHOW TABLES LIKE 'sections'");
            if ($stmt->rowCount() == 0) {
                // Create sections table
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `sections` (
                        `section_id` INT(11) NOT NULL AUTO_INCREMENT,
                        `course_id` INT(11) NOT NULL,
                        `doctor_id` INT(11) NOT NULL,
                        `section_number` VARCHAR(10) NOT NULL,
                        `semester` VARCHAR(20) NOT NULL,
                        `academic_year` VARCHAR(10) NOT NULL,
                        `room` VARCHAR(50) DEFAULT NULL,
                        `time_slot` VARCHAR(100) DEFAULT NULL,
                        `day_of_week` VARCHAR(20) DEFAULT NULL,
                        `start_time` TIME DEFAULT NULL,
                        `end_time` TIME DEFAULT NULL,
                        `capacity` INT(11) NOT NULL DEFAULT 30,
                        `current_enrollment` INT(11) DEFAULT 0,
                        `status` ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`section_id`),
                        INDEX `idx_course_id` (`course_id`),
                        INDEX `idx_doctor_id` (`doctor_id`),
                        INDEX `idx_semester` (`semester`, `academic_year`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Database tables created successfully'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Run migration for sections table from SQL file
     */
    private function runMigration(): void
    {
        header('Content-Type: application/json');
        
        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            
            // Path to migration file
            $migrationFile = dirname(__DIR__, 2) . '/database/migrations/create_sections_table.sql';
            
            if (!file_exists($migrationFile)) {
                throw new \Exception("Migration file not found: {$migrationFile}");
            }
            
            // Read SQL file
            $sql = file_get_contents($migrationFile);
            
            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^\s*$/s', $stmt);
                }
            );
            
            $executed = 0;
            $errors = [];
            
            foreach ($statements as $statement) {
                try {
                    $db->exec($statement . ';');
                    $executed++;
                } catch (\PDOException $e) {
                    // If table already exists, that's okay
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate key') === false) {
                        $errors[] = $e->getMessage();
                    } else {
                        $executed++;
                    }
                }
            }
            
            // Verify table was created
            $stmt = $db->query("SHOW TABLES LIKE 'sections'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                // Check table structure
                $stmt = $db->query("DESCRIBE sections");
                $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $columnNames = array_column($columns, 'Field');
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Migration completed successfully!',
                    'executed' => $executed,
                    'table_exists' => true,
                    'columns' => $columnNames,
                    'errors' => $errors
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Migration executed but table was not created. Please check database permissions.',
                    'executed' => $executed,
                    'errors' => $errors
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

