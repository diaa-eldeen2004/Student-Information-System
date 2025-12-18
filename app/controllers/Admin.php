<?php
namespace controllers;

use core\Controller;
use models\Advisor;
use models\User;
use models\Student;
use models\Doctor;
use models\Course;
use models\AuditLog;
use models\ItOfficer;
use models\AdminRole;
use models\Report;
use models\CalendarEvent;
use PDO;

class Admin extends Controller
{
    private Advisor $advisorModel;
    private User $userModel;
    private Student $studentModel;
    private Doctor $doctorModel;
    private Course $courseModel;
    private AuditLog $auditLogModel;
    private ItOfficer $itOfficerModel;
    private AdminRole $adminRoleModel;
    private Report $reportModel;
    private CalendarEvent $calendarEventModel;

    public function __construct()
    {
        parent::__construct();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $this->redirectTo('auth/login');
        }

        $this->advisorModel = new Advisor();
        $this->userModel = new User();
        $this->studentModel = new Student();
        $this->doctorModel = new Doctor();
        $this->courseModel = new Course();
        $this->auditLogModel = new AuditLog();
        $this->itOfficerModel = new ItOfficer();
        $this->adminRoleModel = new AdminRole();
        $this->reportModel = new Report();
        $this->calendarEventModel = new CalendarEvent();
    }

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
            // Get statistics
            $totalStudents = $this->getTotalStudents();
            $studentsThisMonth = $this->getStudentsThisMonth();
            $totalDoctors = $this->getTotalDoctors();
            $doctorsThisMonth = $this->getDoctorsThisMonth();
            $totalCourses = $this->getTotalCourses();
            $coursesThisSemester = $this->getCoursesThisSemester();
            $systemAlerts = $this->getSystemAlerts();
            $recentActivity = $this->getRecentActivity(10);
            $courseDistribution = $this->getCourseDistribution();
            $userDistribution = $this->getUserDistribution();

            $this->view->render('admin/admin_dashboard', [
                'title' => 'Admin Dashboard',
                'totalStudents' => $totalStudents,
                'studentsThisMonth' => $studentsThisMonth,
                'totalDoctors' => $totalDoctors,
                'doctorsThisMonth' => $doctorsThisMonth,
                'totalCourses' => $totalCourses,
                'coursesThisSemester' => $coursesThisSemester,
                'systemAlerts' => $systemAlerts,
                'recentActivity' => $recentActivity,
                'courseDistribution' => $courseDistribution,
                'userDistribution' => $userDistribution,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Dashboard error: " . $e->getMessage());
            $this->view->render('errors/500', [
                'title' => 'Error',
                'message' => 'Failed to load dashboard: ' . $e->getMessage()
            ]);
        }
    }

    private function getTotalStudents(): int
    {
        $students = $this->studentModel->getAllStudents();
        return count($students);
    }

    private function getStudentsThisMonth(): int
    {
        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM students WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) AND MONTH(created_at) = MONTH(CURRENT_DATE())");
        return (int)$stmt->fetchColumn();
    }

    private function getTotalDoctors(): int
    {
        $doctors = $this->doctorModel->getAll();
        return count($doctors);
    }

    private function getDoctorsThisMonth(): int
    {
        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
        $stmt = $db->query("SELECT COUNT(*) FROM doctors WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) AND MONTH(created_at) = MONTH(CURRENT_DATE())");
        return (int)$stmt->fetchColumn();
    }

    private function getTotalCourses(): int
    {
        $courses = $this->courseModel->getAll();
        return count($courses);
    }

    private function getCoursesThisSemester(): int
    {
        // Count courses with active sections this semester
        $currentSemester = date('n') <= 6 ? 'Spring' : 'Fall';
        $currentYear = date('Y');
        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT course_id) 
            FROM schedule 
            WHERE semester = :semester 
            AND academic_year = :year
        ");
        $stmt->execute(['semester' => $currentSemester, 'year' => $currentYear]);
        return (int)$stmt->fetchColumn();
    }

    private function getSystemAlerts(): array
    {
        // Return empty array - no real system alerts backend implemented
        return [];
    }

    private function getRecentActivity(int $limit = 10): array
    {
        // Get recent audit logs
        $logs = $this->auditLogModel->getAll($limit);
        $activities = [];

        foreach ($logs as $log) {
            $activities[] = [
                'icon' => $this->getActivityIcon($log['action']),
                'color' => $this->getActivityColor($log['action']),
                'title' => $this->formatActivityTitle($log['action']),
                'message' => $log['details'] ?? $log['action'],
                'time' => $this->formatTimeAgo($log['created_at'])
            ];
        }

        return $activities;
    }

    private function getActivityIcon(string $action): string
    {
        if (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
            return 'fa-user-plus';
        } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
            return 'fa-edit';
        } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
            return 'fa-trash';
        } elseif (strpos($action, 'course') !== false) {
            return 'fa-book';
        } elseif (strpos($action, 'doctor') !== false || strpos($action, 'faculty') !== false) {
            return 'fa-chalkboard-teacher';
        } else {
            return 'fa-info-circle';
        }
    }

    private function getActivityColor(string $action): string
    {
        if (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
            return 'var(--success-color)';
        } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
            return 'var(--error-color)';
        } elseif (strpos($action, 'course') !== false) {
            return 'var(--primary-color)';
        } elseif (strpos($action, 'doctor') !== false || strpos($action, 'faculty') !== false) {
            return 'var(--accent-color)';
        } else {
            return 'var(--primary-color)';
        }
    }

    private function formatActivityTitle(string $action): string
    {
        $title = str_replace('_', ' ', $action);
        $title = ucwords($title);
        return $title;
    }

    private function formatTimeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) return $diff . ' seconds ago';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        return date('Y-m-d', $timestamp);
    }

    private function getCourseDistribution(): array
    {
        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT department, COUNT(*) as count 
            FROM courses 
            WHERE department IS NOT NULL AND department != ''
            GROUP BY department 
            ORDER BY count DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getUserDistribution(): array
    {
        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            WHERE role IN ('student', 'doctor', 'advisor', 'it', 'admin')
            GROUP BY role
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function calendar(): void
    {
        $message = null;
        $messageType = 'info';

        // Check if calendar_events table exists
        $tableExists = $this->calendarEventModel->tableExists();

        // Get current month/year for calendar display
        $currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

        // Read filters from query params
        $search = trim($_GET['search'] ?? '');
        $eventTypeFilter = trim($_GET['eventType'] ?? '');
        $departmentFilter = trim($_GET['department'] ?? '');
        $monthFilter = trim($_GET['monthFilter'] ?? '');

        // Initialize default values
        $eventsThisMonth = 0;
        $examsScheduled = 0;
        $conflicts = 0;
        $peopleAffected = 0;
        $events = [];
        $upcomingEvents = [];
        $calendarEvents = [];
        $eventsByDay = [];
        $departments = [];
        $editEvent = null;

        if ($tableExists) {
            // Get statistics
            $eventsThisMonth = $this->calendarEventModel->getCountThisMonth($currentMonth, $currentYear);
            $examsScheduled = $this->calendarEventModel->getExamsScheduledCount();
            $conflicts = $this->calendarEventModel->getConflictsCount();
            $peopleAffected = $this->calendarEventModel->getPeopleAffectedCount();

            // Build filters for getAll
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($eventTypeFilter) $filters['eventType'] = $eventTypeFilter;
            if ($departmentFilter) $filters['department'] = $departmentFilter;
            if ($monthFilter) $filters['month'] = (int)$monthFilter;

            // Get events with filters
            $events = $this->calendarEventModel->getAll($filters);

            // Get upcoming events (next 7 days)
            $upcomingEvents = $this->calendarEventModel->getUpcomingEvents(7, 10);

            // Get events for current month for calendar grid
            $calendarEvents = $this->calendarEventModel->getEventsForMonth($currentMonth, $currentYear, 'active');

            // Group events by day for calendar display
            foreach ($calendarEvents as $event) {
                $day = (int)date('j', strtotime($event['start_date']));
                if (!isset($eventsByDay[$day])) {
                    $eventsByDay[$day] = [];
                }
                $eventsByDay[$day][] = $event;
            }

            // Get unique departments for filter
            $departments = $this->calendarEventModel->getUniqueDepartments();

            // Handle POST requests (Create, Update, Delete)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';

                if ($action === 'create' || $action === 'update') {
                    $title = trim($_POST['title'] ?? '');
                    $description = trim($_POST['description'] ?? '');
                    $eventType = trim($_POST['event_type'] ?? 'other');
                    $status = trim($_POST['status'] ?? 'active');
                    $startDate = trim($_POST['start_date'] ?? '');
                    $endDate = trim($_POST['end_date'] ?? '');
                    $department = trim($_POST['department'] ?? '');
                    $location = trim($_POST['location'] ?? '');
                    $courseId = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;

                    // Validate required fields
                    if (empty($title)) {
                        $message = 'Event title is required';
                        $messageType = 'error';
                    } elseif (empty($startDate)) {
                        $message = 'Start date is required';
                        $messageType = 'error';
                    } elseif (empty($eventType)) {
                        $message = 'Event type is required';
                        $messageType = 'error';
                    } else {
                        // Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL DATETIME format (YYYY-MM-DD HH:MM:SS)
                        $startDateFormatted = str_replace('T', ' ', $startDate);
                        if (strlen($startDateFormatted) === 16) {
                            $startDateFormatted .= ':00'; // Add seconds
                        }
                        
                        $endDateFormatted = null;
                        if (!empty($endDate)) {
                            $endDateFormatted = str_replace('T', ' ', $endDate);
                            if (strlen($endDateFormatted) === 16) {
                                $endDateFormatted .= ':00'; // Add seconds
                            }
                        }
                        
                        // Prepare data
                        $data = [
                            'title' => $title,
                            'description' => $description,
                            'event_type' => $eventType,
                            'status' => $status,
                            'start_date' => $startDateFormatted,
                            'department' => $department ?: null,
                            'location' => $location ?: null,
                            'course_id' => $courseId,
                        ];

                        if (!empty($endDateFormatted)) {
                            $data['end_date'] = $endDateFormatted;
                        }

                        if ($action === 'create') {
                            if ($this->calendarEventModel->create($data)) {
                                $message = 'Event created successfully';
                                $messageType = 'success';
                                $this->redirectTo('admin/calendar?success=1');
                            } else {
                                $message = 'Failed to create event';
                                $messageType = 'error';
                            }
                        } else {
                            $eventId = (int)($_POST['id'] ?? 0);
                            if ($eventId > 0 && $this->calendarEventModel->update($eventId, $data)) {
                                $message = 'Event updated successfully';
                                $messageType = 'success';
                                $this->redirectTo('admin/calendar?success=1');
                            } else {
                                $message = 'Failed to update event';
                                $messageType = 'error';
                            }
                        }
                    }
                } elseif ($action === 'delete') {
                    $eventId = (int)($_POST['id'] ?? $_POST['event_id'] ?? 0);
                    if ($eventId > 0 && $this->calendarEventModel->delete($eventId)) {
                        $message = 'Event deleted successfully';
                        $messageType = 'success';
                        $this->redirectTo('admin/calendar?success=1');
                    } else {
                        $message = 'Failed to delete event';
                        $messageType = 'error';
                    }
                }
            }

            // Check if editing an event
            if (isset($_GET['edit'])) {
                $editId = (int)$_GET['edit'];
                $editEvent = $this->calendarEventModel->findById($editId);
                if (!$editEvent) {
                    $message = 'Event not found';
                    $messageType = 'error';
                }
            }
        }

        // Generate calendar grid data
        $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
        $daysInMonth = date('t', $firstDay);
        $dayOfWeek = date('w', $firstDay); // 0 = Sunday, 6 = Saturday
        $monthName = date('F', $firstDay);

        // Check for success message
        if (isset($_GET['success'])) {
            $message = 'Operation completed successfully';
            $messageType = 'success';
        }

        $this->view->render('admin/admin_calendar', [
            'title' => 'Calendar Management',
            'tableExists' => $tableExists,
            'eventsThisMonth' => $eventsThisMonth,
            'examsScheduled' => $examsScheduled,
            'conflicts' => $conflicts,
            'peopleAffected' => $peopleAffected,
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
            'eventsByDay' => $eventsByDay,
            'departments' => $departments,
            'currentMonth' => $currentMonth,
            'currentYear' => $currentYear,
            'monthName' => $monthName,
            'daysInMonth' => $daysInMonth,
            'dayOfWeek' => $dayOfWeek,
            'firstDay' => $firstDay,
            'search' => $search,
            'eventTypeFilter' => $eventTypeFilter,
            'departmentFilter' => $departmentFilter,
            'monthFilter' => $monthFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editEvent' => $editEvent ?? null,
            'showSidebar' => true,
        ]);
    }
    public function profile(): void
    {
        $message = null;
        $messageType = 'info';

        // Get logged-in admin ID from session
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            $this->redirectTo('auth/login');
            return;
        }

        // Get admin user data
        $admin = $this->userModel->findById($userId);
        if (!$admin) {
            $message = 'User not found';
            $messageType = 'error';
            $admin = [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $_SESSION['user']['email'] ?? 'admin@university.edu',
                'phone' => '',
                'role' => 'admin',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        // Get statistics (reuse dashboard methods)
        $totalStudents = $this->getTotalStudents();
        $studentsThisMonth = $this->getStudentsThisMonth();
        $totalDoctors = $this->getTotalDoctors();
        $doctorsThisMonth = $this->getDoctorsThisMonth();
        $totalCourses = $this->getTotalCourses();
        $coursesThisSemester = $this->getCoursesThisSemester();

        // Get total reports if table exists
        $totalReports = 0;
        try {
            if ($this->reportModel->tableExists()) {
                $totalReports = $this->reportModel->getCount([]);
            }
        } catch (\Exception $e) {
            // Ignore if reports table doesn't exist
        }

        // Handle POST requests (Update profile or password)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_profile') {
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');

                // Validate required fields
                if (empty($firstName) || empty($lastName) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Invalid email address';
                    $messageType = 'error';
                } else {
                    // Update user profile
                    $updateData = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    if ($this->userModel->updateUser($userId, $updateData)) {
                        // Update session
                        $_SESSION['user']['first_name'] = $firstName;
                        $_SESSION['user']['last_name'] = $lastName;
                        $_SESSION['user']['email'] = $email;
                        $message = 'Profile updated successfully';
                        $messageType = 'success';
                        $this->redirectTo('admin/profile?success=1');
                    } else {
                        $message = 'Failed to update profile';
                        $messageType = 'error';
                    }
                }
            } elseif ($action === 'update_password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                // Validate
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $message = 'All password fields are required';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'New passwords do not match';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 8) {
                    $message = 'Password must be at least 8 characters long';
                    $messageType = 'error';
                } elseif (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                    $message = 'Current password is incorrect';
                    $messageType = 'error';
                } else {
                    // Update password
                    $updateData = [
                        'first_name' => $admin['first_name'],
                        'last_name' => $admin['last_name'],
                        'email' => $admin['email'],
                        'phone' => $admin['phone'] ?? '',
                        'password' => $newPassword,
                    ];

                    if ($this->userModel->updateUser($userId, $updateData)) {
                        $message = 'Password updated successfully';
                        $messageType = 'success';
                        $this->redirectTo('admin/profile?success=1');
                    } else {
                        $message = 'Failed to update password';
                        $messageType = 'error';
                    }
                }
            }
        }

        // Check for success message
        if (isset($_GET['success'])) {
            $message = 'Operation completed successfully';
            $messageType = 'success';
        }

        // Get admin level from AdminRole if exists
        $adminLevel = 'admin';
        try {
            $adminRole = $this->adminRoleModel->findByUserId($userId);
            if ($adminRole && isset($adminRole['admin_level'])) {
                $adminLevel = $adminRole['admin_level'];
            }
        } catch (\Exception $e) {
            // Use default
        }

        $this->view->render('admin/admin_profile', [
            'title' => 'Admin Profile',
            'admin' => $admin,
            'adminLevel' => $adminLevel,
            'totalStudents' => $totalStudents,
            'studentsThisMonth' => $studentsThisMonth,
            'totalDoctors' => $totalDoctors,
            'doctorsThisMonth' => $doctorsThisMonth,
            'totalCourses' => $totalCourses,
            'coursesThisSemester' => $coursesThisSemester,
            'totalReports' => $totalReports,
            'message' => $message,
            'messageType' => $messageType,
            'showSidebar' => true,
        ]);
    }
    public function reports(): void
    {
        $message = null;
        $messageType = 'info';

        // Check if reports table exists
        $tableExists = $this->reportModel->tableExists();

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $title = trim($_POST['title'] ?? $_POST['report_name'] ?? '');
                $type = trim($_POST['type'] ?? $_POST['report_type'] ?? 'other');
                $period = trim($_POST['period'] ?? $_POST['report_period'] ?? 'on_demand');
                $status = trim($_POST['status'] ?? 'generating');
                $file_path = trim($_POST['file_path'] ?? '');
                $parameters = $_POST['parameters'] ?? '';

                if (empty($title)) {
                    $message = 'Report title is required';
                    $messageType = 'error';
                } else {
                    $reportData = [
                        'title' => $title,
                        'type' => $type,
                        'period' => $period,
                        'status' => $status,
                        'file_path' => $file_path ?: null,
                    ];

                    if (!empty($parameters)) {
                        try {
                            $paramsJson = json_decode($parameters, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $reportData['parameters'] = $paramsJson;
                            } else {
                                $reportData['parameters'] = $parameters;
                            }
                        } catch (\Exception $e) {
                            $reportData['parameters'] = $parameters;
                        }
                    }

                    if ($action === 'create') {
                        $success = $this->reportModel->create($reportData);
                        if ($success) {
                            $message = 'Report created successfully';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to create report';
                            $messageType = 'error';
                        }
                    } else {
                        // Update
                        $reportId = (int)($_POST['report_id'] ?? $_POST['id'] ?? 0);
                        if ($reportId > 0) {
                            $success = $this->reportModel->update($reportId, $reportData);
                            if ($success) {
                                $message = 'Report updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update report';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid report ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $reportId = (int)($_POST['report_id'] ?? $_POST['id'] ?? 0);
                if ($reportId > 0) {
                    $success = $this->reportModel->delete($reportId);
                    if ($success) {
                        $message = 'Report deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete report';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid report ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/reports?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $typeFilter = trim($_GET['type'] ?? '');
        $periodFilter = trim($_GET['period'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($typeFilter)) $filters['type'] = $typeFilter;
        if (!empty($periodFilter)) $filters['period'] = $periodFilter;
        if (!empty($statusFilter)) $filters['status'] = $statusFilter;

        // Get reports with filters
        $reports = $tableExists ? $this->reportModel->getAll($filters) : [];
        $totalReports = $tableExists ? $this->reportModel->getCount($filters) : 0;
        $reportsToday = $tableExists ? $this->reportModel->getTodayCount() : 0;
        $scheduledReports = $tableExists ? $this->reportModel->getScheduledCount() : 0;
        $totalDownloads = $tableExists ? $this->reportModel->getDownloadsCount() : 0;
        $reportsByType = $tableExists ? $this->reportModel->getReportsByType() : [
            'academic' => 0,
            'attendance' => 0,
            'financial' => 0,
            'system' => 0,
            'other' => 0
        ];
        $reportsByStatus = $tableExists ? $this->reportModel->getReportsByStatus() : [
            'completed' => 0,
            'generating' => 0,
            'scheduled' => 0,
            'failed' => 0
        ];

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get report for edit if requested
        $editReport = null;
        if (isset($_GET['edit']) && !empty($_GET['edit']) && $tableExists) {
            $editReport = $this->reportModel->findById((int)$_GET['edit']);
        }

        $this->view->render('admin/admin_reports', [
            'title' => 'Reports & Analytics',
            'reports' => $reports,
            'totalReports' => $totalReports,
            'reportsToday' => $reportsToday,
            'scheduledReports' => $scheduledReports,
            'totalDownloads' => $totalDownloads,
            'reportsByType' => $reportsByType,
            'reportsByStatus' => $reportsByStatus,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'periodFilter' => $periodFilter,
            'statusFilter' => $statusFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editReport' => $editReport,
            'tableExists' => $tableExists,
            'showSidebar' => true,
        ]);
    }
    public function manageStudent(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $student_number = trim($_POST['student_number'] ?? '');
                $year_enrolled = !empty($_POST['year_enrolled']) ? (int)$_POST['year_enrolled'] : null;
                $major = trim($_POST['major'] ?? '');
                $minor = trim($_POST['minor'] ?? '');
                $midterm_cardinality = trim($_POST['midterm_cardinality'] ?? ''); // Password for midterm quiz
                $final_cardinality = trim($_POST['final_cardinality'] ?? ''); // Password for final quiz
                $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : 0.00;
                $status = trim($_POST['status'] ?? 'active');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    $userData = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    $studentData = [
                        'student_number' => $student_number ?: null,
                        'year_enrolled' => $year_enrolled, // Will be converted to admission_date in model
                        'major' => $major ?: null,
                        'minor' => $minor ?: null,
                        'midterm_cardinality' => $midterm_cardinality ?: null, // Password for midterm quiz
                        'final_cardinality' => $final_cardinality ?: null, // Password for final quiz
                        'gpa' => $gpa,
                        'status' => $status,
                    ];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for student: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->studentModel->createStudentWithUser($userData, $studentData);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'Student created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create student';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $studentId = (int)($_POST['student_id'] ?? $_POST['id'] ?? 0);
                        if ($studentId > 0) {
                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            try {
                                $success = $this->studentModel->updateStudent($studentId, $userData, $studentData);
                                if ($success) {
                                    $message = 'Student updated successfully';
                                    $messageType = 'success';
                                } else {
                                    $message = 'Failed to update student';
                                    $messageType = 'error';
                                }
                            } catch (\PDOException $e) {
                                $message = 'Failed to update student: ' . $e->getMessage();
                                $messageType = 'error';
                                error_log("Student update error: " . $e->getMessage());
                            }
                        } else {
                            $message = 'Invalid student ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $studentId = (int)($_POST['student_id'] ?? $_POST['id'] ?? 0);
                if ($studentId > 0) {
                    $success = $this->studentModel->deleteStudent($studentId);
                    if ($success) {
                        $message = 'Student deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete student';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid student ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-student?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $yearFilter = trim($_GET['year'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $programFilter = trim($_GET['program'] ?? ''); // Maps to major

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($yearFilter)) $filters['year_enrolled'] = $yearFilter;
        if (!empty($statusFilter)) $filters['status'] = $statusFilter;
        if (!empty($programFilter)) $filters['major'] = $programFilter;

        // Get students with filters
        $students = $this->studentModel->getAll($filters);
        $totalStudents = $this->studentModel->getCount($filters);
        $studentsThisMonth = $this->studentModel->getThisMonthCount();
        $activeStudents = $this->studentModel->getActiveCount();
        $majors = $this->studentModel->getUniqueMajors();
        $years = $this->studentModel->getUniqueYears();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get student for edit if requested
        $editStudent = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editStudent = $this->studentModel->findById((int)$_GET['edit']);
        }

        // Check if required columns exist
        $missingColumns = [];
        try {
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'major'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = 'major';
            }
            $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'minor'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = 'minor';
            }
            $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'midterm_cardinality'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = 'midterm_cardinality';
            }
            $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'final_cardinality'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = 'final_cardinality';
            }
        } catch (\Exception $e) {
            // Error checking columns - assume they might be missing
            $missingColumns = ['major', 'minor', 'midterm_cardinality', 'final_cardinality'];
        }

        $this->view->render('admin/admin_manage_student', [
            'title' => 'Manage Students',
            'students' => $students,
            'totalStudents' => $totalStudents,
            'studentsThisMonth' => $studentsThisMonth,
            'activeStudents' => $activeStudents,
            'majors' => $majors,
            'years' => $years,
            'search' => $search,
            'yearFilter' => $yearFilter,
            'statusFilter' => $statusFilter,
            'programFilter' => $programFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editStudent' => $editStudent,
            'missingColumns' => $missingColumns,
            'showSidebar' => true,
        ]);
    }
    public function manageDoctor(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    $userData = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    $doctorData = [
                        'department' => $department ?: null,
                        'bio' => $bio ?: null,
                    ];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for doctor: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->doctorModel->createDoctorWithUser($userData, $doctorData);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'Doctor created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create doctor';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $doctorId = (int)($_POST['doctor_id'] ?? $_POST['id'] ?? 0);
                        if ($doctorId > 0) {
                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            $success = $this->doctorModel->updateDoctor($doctorId, $userData, $doctorData);
                            if ($success) {
                                $message = 'Doctor updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update doctor';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid doctor ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $doctorId = (int)($_POST['doctor_id'] ?? $_POST['id'] ?? 0);
                if ($doctorId > 0) {
                    $success = $this->doctorModel->deleteDoctor($doctorId);
                    if ($success) {
                        $message = 'Doctor deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete doctor';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid doctor ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-doctor?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $departmentFilter = trim($_GET['department'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($departmentFilter)) $filters['department'] = $departmentFilter;

        // Get doctors with filters
        $doctors = $this->doctorModel->getAll($filters);
        $totalDoctors = $this->doctorModel->getCount($filters);
        $doctorsThisMonth = $this->doctorModel->getThisMonthCount();
        $activeDoctors = $this->doctorModel->getActiveCount();
        $departments = $this->doctorModel->getUniqueDepartments();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get doctor for edit if requested
        $editDoctor = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editDoctor = $this->doctorModel->findById((int)$_GET['edit']);
        }

        // Check if required columns exist
        $missingColumns = [];
        try {
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->query("SHOW COLUMNS FROM doctors LIKE 'bio'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = 'bio';
            }
        } catch (\Exception $e) {
            // Error checking columns - assume they might be missing
            $missingColumns = ['bio'];
        }

        $this->view->render('admin/admin_manage_doctor', [
            'title' => 'Manage Doctors',
            'doctors' => $doctors,
            'totalDoctors' => $totalDoctors,
            'doctorsThisMonth' => $doctorsThisMonth,
            'activeDoctors' => $activeDoctors,
            'departments' => $departments,
            'search' => $search,
            'departmentFilter' => $departmentFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editDoctor' => $editDoctor,
            'missingColumns' => $missingColumns,
            'showSidebar' => true,
        ]);
    }

    public function manageCourse(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $course_code = trim($_POST['course_code'] ?? '');
                $name = trim($_POST['course_name'] ?? $_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $credit_hours = (int)($_POST['credits'] ?? $_POST['credit_hours'] ?? 3);

                if (empty($course_code) || empty($name)) {
                    $message = 'Course code and name are required';
                    $messageType = 'error';
                } else {
                    $data = [
                        'course_code' => $course_code,
                        'name' => $name,
                        'description' => $description ?: null,
                        'department' => $department ?: null,
                        'credit_hours' => $credit_hours,
                    ];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if course code already exists
                        error_log("Checking course code existence for course: " . $course_code);
                        $existingCourse = $this->courseModel->findByCode($course_code);
                        if ($existingCourse) {
                            $message = 'Course code already exists: ' . htmlspecialchars($course_code);
                            $messageType = 'error';
                            error_log("Course code check FAILED - Found existing course: ID={$existingCourse['course_id']}, Code='{$existingCourse['course_code']}', Searching for: '{$course_code}'");
                        } else {
                            error_log("Course code check PASSED - No existing course found for: '{$course_code}'");
                            $success = $this->courseModel->create($data);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'Course created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create course';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $courseId = (int)($_POST['course_id'] ?? $_POST['id'] ?? 0);
                        if ($courseId > 0) {
                            $data['course_id'] = $courseId;
                            $success = $this->courseModel->update($data);
                            if ($success) {
                                $message = 'Course updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update course';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid course ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $courseId = (int)($_POST['course_id'] ?? $_POST['id'] ?? 0);
                if ($courseId > 0) {
                    $success = $this->courseModel->delete($courseId);
                    if ($success) {
                        $message = 'Course deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete course';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid course ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-course?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $departmentFilter = trim($_GET['department'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($departmentFilter)) $filters['department'] = $departmentFilter;

        // Get courses with filters
        $courses = $this->courseModel->getCoursesWithDoctorInfo($filters);
        $totalCourses = $this->courseModel->getCount($filters);
        $coursesThisSemester = $this->courseModel->getThisMonthCount();
        $activeCourses = $this->courseModel->getActiveCount();
        $departments = $this->courseModel->getUniqueDepartments();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get course for edit if requested
        $editCourse = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editCourse = $this->courseModel->findById((int)$_GET['edit']);
        }

        $this->view->render('admin/admin_manage_course', [
            'title' => 'Manage Courses',
            'courses' => $courses,
            'totalCourses' => $totalCourses,
            'coursesThisSemester' => $coursesThisSemester,
            'activeCourses' => $activeCourses,
            'departments' => $departments,
            'search' => $search,
            'departmentFilter' => $departmentFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editCourse' => $editCourse,
            'showSidebar' => true,
        ]);
    }

    public function manageAdvisor(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    $userData = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    $advisorData = [
                        'department' => $department ?: null,
                    ];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for advisor: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->advisorModel->createAdvisor($userData, $advisorData);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'Advisor created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create advisor';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $advisorId = (int)($_POST['advisor_id'] ?? 0);
                        if ($advisorId > 0) {
                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            $success = $this->advisorModel->updateAdvisor($advisorId, $userData, $advisorData);
                            if ($success) {
                                $message = 'Advisor updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update advisor';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid advisor ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $advisorId = (int)($_POST['advisor_id'] ?? 0);
                if ($advisorId > 0) {
                    $success = $this->advisorModel->deleteAdvisor($advisorId);
                    if ($success) {
                        $message = 'Advisor deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete advisor';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid advisor ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-advisor?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');
        $departmentFilter = trim($_GET['department'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        if (!empty($departmentFilter)) $filters['department'] = $departmentFilter;

        // Get advisors with filters
        $advisors = $this->advisorModel->getAll($filters);
        $totalAdvisors = $this->advisorModel->getCount($filters);
        $advisorsThisMonth = $this->advisorModel->getThisMonthCount();
        $departments = $this->advisorModel->getUniqueDepartments();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get advisor for edit if requested
        $editAdvisor = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editAdvisor = $this->advisorModel->findByAdvisorId((int)$_GET['edit']);
        }

        $this->view->render('admin/admin_manage_advisor', [
            'title' => 'Manage Advisors',
            'advisors' => $advisors,
            'totalAdvisors' => $totalAdvisors,
            'advisorsThisMonth' => $advisorsThisMonth,
            'departments' => $departments,
            'search' => $search,
            'departmentFilter' => $departmentFilter,
            'message' => $message,
            'messageType' => $messageType,
            'editAdvisor' => $editAdvisor,
            'showSidebar' => true,
        ]);
    }

    public function manageIt(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    $userData = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for IT officer: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->itOfficerModel->createItOfficerWithUser($userData);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'IT Officer created successfully';
                                $messageType = 'success';
                            } else {
                                // Get the actual error from the model
                                $modelError = \models\ItOfficer::getLastError();
                                
                                // Check if it's a duplicate key error (AUTO_INCREMENT issue)
                                if ($modelError && (strpos($modelError, 'Duplicate entry') !== false || strpos($modelError, '23000') !== false)) {
                                    // Try to fix AUTO_INCREMENT first
                                    $fixResult = $this->itOfficerModel->fixAutoIncrement();
                                    if ($fixResult['success']) {
                                        // CRITICAL: Ensure clean state before retry
                                        $dbSingleton->ensureCleanState();
                                        
                                        // Verify AUTO_INCREMENT is actually fixed before retrying
                                        $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
                                        $verifyStmt = $db->query("
                                            SELECT AUTO_INCREMENT 
                                            FROM INFORMATION_SCHEMA.TABLES 
                                            WHERE TABLE_SCHEMA = DATABASE() 
                                            AND TABLE_NAME = 'it_officers'
                                        ");
                                        $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                                        $actualAutoIncrement = (int)($verifyResult['AUTO_INCREMENT'] ?? 0);
                                        
                                        // Get max it_id
                                        $maxStmt = $db->query("SELECT COALESCE(MAX(it_id), 0) as max_id FROM it_officers");
                                        $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
                                        $maxId = (int)($maxResult['max_id'] ?? 0);
                                        
                                        // If AUTO_INCREMENT is still not fixed, try manual fix
                                        if ($actualAutoIncrement <= 0 || ($maxId > 0 && $actualAutoIncrement <= $maxId)) {
                                            // Manual fix: set to max + 1
                                            $newValue = max($maxId + 1, 1);
                                            $db->exec("ALTER TABLE it_officers AUTO_INCREMENT = {$newValue}");
                                            error_log("Manual AUTO_INCREMENT fix: Set to {$newValue} (max_id was {$maxId})");
                                        }
                                        
                                        // Check if user was already created in the first attempt
                                        $existingUser = $this->userModel->findByEmail($email);
                                        
                                        if ($existingUser) {
                                            // User exists, check if IT officer record exists
                                            $existingIt = $this->itOfficerModel->findByUserId($existingUser['id']);
                                            
                                            if ($existingIt) {
                                                // Both user and IT officer exist - creation actually succeeded!
                                                $dbSingleton->ensureCleanState();
                                                $message = 'IT Officer created successfully (AUTO_INCREMENT was fixed automatically)';
                                                $messageType = 'success';
                                            } else {
                                                // User exists but IT officer doesn't - create IT officer record only
                                                // Use direct INSERT to avoid transaction issues
                                                try {
                                                    $dbSingleton->ensureCleanState();
                                                    $insertStmt = $db->prepare("INSERT INTO it_officers (user_id) VALUES (:user_id)");
                                                    $insertSuccess = $insertStmt->execute(['user_id' => $existingUser['id']]);
                                                    
                                                    if ($insertSuccess) {
                                                        $dbSingleton->ensureCleanState();
                                                        $message = 'IT Officer created successfully (AUTO_INCREMENT was fixed automatically)';
                                                        $messageType = 'success';
                                                    } else {
                                                        $message = 'AUTO_INCREMENT was fixed, but failed to create IT officer record. Please try again.';
                                                        $messageType = 'error';
                                                    }
                                                } catch (\PDOException $e) {
                                                    $message = 'AUTO_INCREMENT was fixed, but failed to create IT officer: ' . htmlspecialchars($e->getMessage());
                                                    $messageType = 'error';
                                                    error_log("IT Officer insert error: " . $e->getMessage());
                                                }
                                            }
                                        } else {
                                            // User doesn't exist, retry full creation
                                            $dbSingleton->ensureCleanState();
                                            $retrySuccess = $this->itOfficerModel->createItOfficerWithUser($userData);
                                            if ($retrySuccess) {
                                                // CRITICAL: Ensure clean state after successful creation
                                                $dbSingleton->ensureCleanState();
                                                $message = 'IT Officer created successfully (AUTO_INCREMENT was fixed automatically)';
                                                $messageType = 'success';
                                            } else {
                                                $retryError = \models\ItOfficer::getLastError();
                                                $message = 'AUTO_INCREMENT was fixed, but creation still failed: ' . htmlspecialchars($retryError ?? 'Unknown error');
                                                $messageType = 'error';
                                                error_log("Retry creation failed after AUTO_INCREMENT fix. Error: " . ($retryError ?? 'Unknown'));
                                            }
                                        }
                                    } else {
                                        $message = 'Failed to create IT officer due to AUTO_INCREMENT issue: ' . htmlspecialchars($fixResult['message'] ?? 'Unknown error');
                                        $messageType = 'error';
                                    }
                                } else {
                                    if ($modelError) {
                                        $message = 'Failed to create IT officer: ' . htmlspecialchars($modelError);
                                    } else {
                                        $message = 'Failed to create IT officer. Please check server logs for details.';
                                    }
                                    $messageType = 'error';
                                }
                                
                                error_log("Admin controller - IT Officer creation returned false. User data: " . json_encode([
                                    'first_name' => $userData['first_name'] ?? 'missing',
                                    'last_name' => $userData['last_name'] ?? 'missing',
                                    'email' => $email ?? 'missing',
                                    'has_password' => !empty($userData['password'] ?? ''),
                                ]));
                            }
                        }
                    } else {
                        // Update
                        $itId = (int)($_POST['it_id'] ?? $_POST['id'] ?? 0);
                        if ($itId > 0) {
                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            $success = $this->itOfficerModel->updateItOfficer($itId, $userData);
                            if ($success) {
                                $message = 'IT Officer updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update IT Officer';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid IT Officer ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $itId = (int)($_POST['it_id'] ?? $_POST['id'] ?? 0);
                if ($itId > 0) {
                    $success = $this->itOfficerModel->deleteItOfficer($itId);
                    if ($success) {
                        $message = 'IT Officer deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete IT Officer';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid IT Officer ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-it?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;

        // Get IT officers with filters
        $itOfficers = $this->itOfficerModel->getAll($filters);
        $totalIT = $this->itOfficerModel->getCount($filters);
        $itThisMonth = $this->itOfficerModel->getThisMonthCount();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get IT officer for edit if requested
        $editIT = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editIT = $this->itOfficerModel->findByItId((int)$_GET['edit']);
        }

        // Check for AUTO_INCREMENT issues
        $needsAutoIncrementFix = false;
        try {
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            
            // Get max it_id first
            $stmt = $db->query("SELECT COALESCE(MAX(it_id), 0) as max_id FROM it_officers");
            $maxResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $maxId = (int)($maxResult['max_id'] ?? 0);
            
            // Try multiple methods to get AUTO_INCREMENT value (same as fix method)
            $autoIncrement = 0;
            
            // Method 1: INFORMATION_SCHEMA (most reliable)
            try {
                $stmt = $db->query("
                    SELECT AUTO_INCREMENT 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'it_officers'
                ");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $autoIncrement = (int)($result['AUTO_INCREMENT'] ?? 0);
            } catch (\Exception $e) {
                // Try next method
            }
            
            // Method 2: SHOW TABLE STATUS WHERE Name
            if ($autoIncrement == 0) {
                try {
                    $stmt = $db->query("SHOW TABLE STATUS WHERE Name = 'it_officers'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $autoIncrement = (int)($result['Auto_increment'] ?? $result['AUTO_INCREMENT'] ?? 0);
                } catch (\Exception $e) {
                    // Try next method
                }
            }
            
            // Method 3: SHOW TABLE STATUS LIKE
            if ($autoIncrement == 0) {
                try {
                    $stmt = $db->query("SHOW TABLE STATUS LIKE 'it_officers'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $autoIncrement = (int)($result['Auto_increment'] ?? $result['AUTO_INCREMENT'] ?? 0);
                } catch (\Exception $e) {
                    // Last resort
                }
            }
            
            // If AUTO_INCREMENT is 0 or NULL, and we have records, it needs fixing
            // If AUTO_INCREMENT is less than or equal to max_id, it needs fixing
            // Exception: If table is empty (maxId == 0), AUTO_INCREMENT of 0 or 1 is acceptable
            if ($maxId > 0 && ($autoIncrement <= 0 || $autoIncrement <= $maxId)) {
                $needsAutoIncrementFix = true;
            } elseif ($maxId == 0 && $autoIncrement == 0) {
                // Empty table with AUTO_INCREMENT = 0 might be okay, but let's fix it to 1 to be safe
                $needsAutoIncrementFix = true;
            }
            
            // Debug logging
            error_log("AUTO_INCREMENT check: autoIncrement={$autoIncrement}, maxId={$maxId}, needsFix=" . ($needsAutoIncrementFix ? 'true' : 'false'));
        } catch (\Exception $e) {
            // If we can't check, assume it's okay
            error_log("Could not check AUTO_INCREMENT: " . $e->getMessage());
        }

        $this->view->render('admin/admin_manage_it', [
            'title' => 'Manage IT Officers',
            'itOfficers' => $itOfficers,
            'totalIT' => $totalIT,
            'itThisMonth' => $itThisMonth,
            'search' => $search,
            'message' => $message,
            'messageType' => $messageType,
            'editIT' => $editIT,
            'needsAutoIncrementFix' => $needsAutoIncrementFix,
            'showSidebar' => true,
        ]);
    }

    public function manageAdmin(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    $userData = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                    ];

                    $adminData = [];

                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for admin: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->adminRoleModel->createAdminWithUser($userData, $adminData);
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'Admin created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create admin';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $adminId = (int)($_POST['admin_id'] ?? $_POST['id'] ?? 0);
                        if ($adminId > 0) {
                            // Prevent editing yourself's email (optional security measure)
                            $currentAdmin = $this->adminRoleModel->findByAdminId($adminId);
                            if ($currentAdmin && isset($_SESSION['user']['admin_id']) && $_SESSION['user']['admin_id'] == $adminId) {
                                // Allow self-update but warn if changing email
                                // For now, we'll allow it
                            }

                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            $success = $this->adminRoleModel->updateAdmin($adminId, $userData, $adminData);
                            if ($success) {
                                $message = 'Admin updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update admin';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid admin ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $adminId = (int)($_POST['admin_id'] ?? $_POST['id'] ?? 0);
                if ($adminId > 0) {
                    // Prevent deleting yourself
                    if (isset($_SESSION['user']['admin_id']) && $_SESSION['user']['admin_id'] == $adminId) {
                        $message = 'Cannot delete your own account';
                        $messageType = 'error';
                    } else {
                        $success = $this->adminRoleModel->deleteAdmin($adminId);
                        if ($success) {
                            $message = 'Admin deleted successfully';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to delete admin';
                            $messageType = 'error';
                        }
                    }
                } else {
                    $message = 'Invalid admin ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-admin?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;

        // Get admins with filters
        $admins = $this->adminRoleModel->getAll($filters);
        $totalAdmins = $this->adminRoleModel->getCount($filters);
        $adminsThisMonth = $this->adminRoleModel->getThisMonthCount();

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get admin for edit if requested
        $editAdmin = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editAdmin = $this->adminRoleModel->findByAdminId((int)$_GET['edit']);
        }

        $this->view->render('admin/admin_manage_admin', [
            'title' => 'Manage Admins',
            'admins' => $admins,
            'totalAdmins' => $totalAdmins,
            'adminsThisMonth' => $adminsThisMonth,
            'search' => $search,
            'message' => $message,
            'messageType' => $messageType,
            'editAdmin' => $editAdmin,
            'showSidebar' => true,
        ]);
    }

    public function manageUser(): void
    {
        $message = null;
        $messageType = 'info';

        // Handle POST requests (Create, Update, Delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create' || $action === 'update') {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = 'First name, last name, and email are required';
                    $messageType = 'error';
                } else {
                    if ($action === 'create') {
                        // CRITICAL: Use singleton's ensureCleanState method to ensure clean connection state
                        // This properly handles transaction state without interfering with model operations
                        $dbSingleton = \patterns\Singleton\DatabaseConnection::getInstance();
                        $dbSingleton->ensureCleanState();
                        
                        // Check if email already exists (case-insensitive)
                        error_log("Checking email existence for user: " . $email);
                        $existingUser = $this->userModel->findByEmail($email);
                        if ($existingUser) {
                            $message = 'Email already exists: ' . htmlspecialchars($email) . ' (Found in database with ID: ' . ($existingUser['id'] ?? 'N/A') . ')';
                            $messageType = 'error';
                            error_log("Email check FAILED - Found existing user: ID={$existingUser['id']}, Email='{$existingUser['email']}', Role={$existingUser['role']}, Searching for: '{$email}'");
                        } else {
                            error_log("Email check PASSED - No existing user found for: '{$email}'");
                            // Generate password if not provided
                            if (empty($password)) {
                                $password = bin2hex(random_bytes(8)); // Generate random password
                            }
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                            $success = $this->userModel->createUser([
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'email' => $email,
                                'phone' => $phone,
                                'password' => $hashedPassword,
                                'role' => 'user', // Default role for general users
                            ]);
                            
                            if ($success) {
                                // CRITICAL: Ensure clean state after successful creation
                                $dbSingleton->ensureCleanState();
                                $message = 'User created successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to create user';
                                $messageType = 'error';
                            }
                        }
                    } else {
                        // Update
                        $userId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
                        if ($userId > 0) {
                            $userData = [
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'email' => $email,
                                'phone' => $phone,
                            ];
                            
                            if (!empty($password)) {
                                $userData['password'] = $password; // Will be hashed in model
                            }
                            
                            $success = $this->userModel->updateUser($userId, $userData);
                            if ($success) {
                                $message = 'User updated successfully';
                                $messageType = 'success';
                            } else {
                                $message = 'Failed to update user';
                                $messageType = 'error';
                            }
                        } else {
                            $message = 'Invalid user ID';
                            $messageType = 'error';
                        }
                    }
                }
            } elseif ($action === 'delete') {
                $userId = (int)($_POST['user_id'] ?? $_POST['id'] ?? 0);
                if ($userId > 0) {
                    $success = $this->userModel->deleteUser($userId);
                    if ($success) {
                        $message = 'User deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete user';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Invalid user ID';
                    $messageType = 'error';
                }
            }

            // Redirect to avoid resubmission
            if ($message) {
                $config = require dirname(__DIR__) . '/config/config.php';
                $base = rtrim($config['base_url'] ?? '', '/');
                $redirectUrl = $base . '/admin/manage-user?message=' . urlencode($message) . '&type=' . urlencode($messageType);
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Get filter parameters
        $search = trim($_GET['search'] ?? '');

        // Build filters array
        $filters = [];
        if (!empty($search)) $filters['search'] = $search;
        $filters['role'] = 'user'; // Only show default users

        // Get users with filters
        $users = $this->userModel->getAll($filters);
        $totalUsers = $this->userModel->getCount($filters);
        $usersThisMonth = $this->userModel->getThisMonthCount($filters);

        // Get message from URL if redirected
        $message = $message ?? $_GET['message'] ?? null;
        $messageType = $messageType ?? $_GET['type'] ?? 'info';

        // Get user for edit if requested
        $editUser = null;
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editUser = $this->userModel->findById((int)$_GET['edit']);
        }

        $this->view->render('admin/admin_manage_user', [
            'title' => 'Manage Users',
            'users' => $users,
            'totalUsers' => $totalUsers,
            'usersThisMonth' => $usersThisMonth,
            'search' => $search,
            'message' => $message,
            'messageType' => $messageType,
            'editUser' => $editUser,
            'showSidebar' => true,
        ]);
    }

    // API endpoints for AJAX requests
    public function getStudentDetails(): void
    {
        header('Content-Type: application/json');
        $studentId = (int)($_GET['id'] ?? 0);
        if ($studentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
            return;
        }
        $student = $this->studentModel->findById($studentId);
        if ($student) {
            echo json_encode(['success' => true, 'data' => $student]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
    }

    public function getDoctorDetails(): void
    {
        header('Content-Type: application/json');
        $doctorId = (int)($_GET['id'] ?? 0);
        if ($doctorId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
            return;
        }
        $doctor = $this->doctorModel->findById($doctorId);
        if ($doctor) {
            echo json_encode(['success' => true, 'data' => $doctor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        }
    }

    public function getAdvisorDetails(): void
    {
        header('Content-Type: application/json');
        $advisorId = (int)($_GET['id'] ?? 0);
        if ($advisorId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid advisor ID']);
            return;
        }
        $advisor = $this->advisorModel->findByAdvisorId($advisorId);
        if ($advisor) {
            echo json_encode(['success' => true, 'data' => $advisor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Advisor not found']);
        }
    }

    public function getItOfficerDetails(): void
    {
        header('Content-Type: application/json');
        $itId = (int)($_GET['id'] ?? 0);
        if ($itId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid IT officer ID']);
            return;
        }
        $itOfficer = $this->itOfficerModel->findByItId($itId);
        if ($itOfficer) {
            echo json_encode(['success' => true, 'data' => $itOfficer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'IT officer not found']);
        }
    }

    public function getAdminDetails(): void
    {
        header('Content-Type: application/json');
        $adminId = (int)($_GET['id'] ?? 0);
        if ($adminId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
            return;
        }
        $admin = $this->adminRoleModel->findByAdminId($adminId);
        if ($admin) {
            echo json_encode(['success' => true, 'data' => $admin]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
    }

    public function getUserDetails(): void
    {
        header('Content-Type: application/json');
        $userId = (int)($_GET['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            return;
        }
        $user = $this->userModel->findById($userId);
        if ($user) {
            echo json_encode(['success' => true, 'data' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    }

    public function getCourseDetails(): void
    {
        header('Content-Type: application/json');
        $courseId = (int)($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
            return;
        }
        $course = $this->courseModel->findById($courseId);
        if ($course) {
            echo json_encode(['success' => true, 'data' => $course]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
        }
    }

    public function fixItAutoIncrement(): void
    {
        header('Content-Type: application/json');
        
        try {
            $result = $this->itOfficerModel->fixAutoIncrement();
            echo json_encode($result);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage()
            ]);
        }
    }
}

