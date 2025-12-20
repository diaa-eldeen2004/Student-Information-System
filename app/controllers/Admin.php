<?php
namespace controllers;

use core\Controller;
use models\User;
use models\Student;
use models\Doctor;
use models\Course;
use models\AuditLog;
use models\ItOfficer;
use models\AdminRole;
use models\Report;
use models\Notification;
use PDO;

class Admin extends Controller
{
    private User $userModel;
    private Student $studentModel;
    private Doctor $doctorModel;
    private Course $courseModel;
    private AuditLog $auditLogModel;
    private ItOfficer $itOfficerModel;
    private AdminRole $adminRoleModel;
    private Report $reportModel;
    private Notification $notificationModel;

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

        $this->userModel = new User();
        $this->studentModel = new Student();
        $this->doctorModel = new Doctor();
        $this->courseModel = new Course();
        $this->auditLogModel = new AuditLog();
        $this->itOfficerModel = new ItOfficer();
        $this->adminRoleModel = new AdminRole();
        $this->reportModel = new Report();
        $this->notificationModel = new Notification();
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
            WHERE role IN ('student', 'doctor', 'it', 'admin')
            GROUP BY role
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        error_log("=== REPORT POST REQUEST START ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("Table exists: " . ($tableExists ? 'YES' : 'NO'));
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableExists) {
            $action = $_POST['action'] ?? '';
            error_log("Action: " . $action);

            if ($action === 'create' || $action === 'update') {
                $title = trim($_POST['title'] ?? $_POST['report_name'] ?? '');
                $type = trim($_POST['type'] ?? $_POST['report_type'] ?? 'other');
                $period = trim($_POST['period'] ?? $_POST['report_period'] ?? 'on_demand');
                $status = trim($_POST['status'] ?? 'generating');
                $file_path = trim($_POST['file_path'] ?? '');
                $parameters = $_POST['parameters'] ?? '';

                error_log("Extracted values - Title: '{$title}', Type: '{$type}', Period: '{$period}', Status: '{$status}'");

                if (empty($title)) {
                    $message = 'Report title is required';
                    $messageType = 'error';
                    error_log("ERROR: Title is empty");
                } else {
                    $reportData = [
                        'title' => $title,
                        'type' => $type,
                        'period' => $period,
                        'status' => $status,
                        'file_path' => $file_path ?: null,
                    ];

                    // Handle file upload if file_data column exists
                    $hasFileDataColumn = $this->reportModel->columnExists('file_data');
                    error_log("File_data column exists: " . ($hasFileDataColumn ? 'YES' : 'NO'));
                    
                    if ($hasFileDataColumn) {
                        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['report_file'];
                            $maxFileSize = 5 * 1024 * 1024; // 5MB limit to avoid MySQL issues
                            
                            error_log("File upload detected: " . $file['name'] . " (" . $file['size'] . " bytes)");
                            
                            // Check file size
                            if ($file['size'] > $maxFileSize) {
                                $message = 'File is too large. Maximum file size is 5MB. Your file is ' . round($file['size'] / 1024 / 1024, 2) . 'MB.';
                                $messageType = 'error';
                                error_log("File too large: " . $file['size'] . " bytes (max: " . $maxFileSize . ")");
                            } else {
                                try {
                                    $fileContents = file_get_contents($file['tmp_name']);
                                    if ($fileContents === false) {
                                        throw new \Exception("Failed to read file contents");
                                    }
                                    $reportData['file_data'] = $fileContents;
                                    $reportData['file_name'] = $file['name'];
                                    $reportData['file_type'] = $file['type'];
                                    $reportData['file_size'] = $file['size'];
                                    error_log("File loaded successfully: " . strlen($fileContents) . " bytes");
                                } catch (\Exception $e) {
                                    $message = 'Failed to read uploaded file: ' . $e->getMessage();
                                    $messageType = 'error';
                                    error_log("File read error: " . $e->getMessage());
                                }
                            }
                        } elseif (isset($_FILES['report_file'])) {
                            $uploadError = $_FILES['report_file']['error'];
                            $errorMessages = [
                                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
                            ];
                            $errorMsg = $errorMessages[$uploadError] ?? 'Unknown upload error (' . $uploadError . ')';
                            error_log("File upload error: " . $errorMsg);
                            // Don't set message here - file upload is optional
                        }
                    }

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
                            error_log("JSON decode error: " . $e->getMessage());
                        }
                    }

                    error_log("Final report data (without file_data): " . print_r(array_merge($reportData, ['file_data' => isset($reportData['file_data']) ? '[BINARY DATA ' . strlen($reportData['file_data']) . ' bytes]' : 'NULL']), true));

                    if ($action === 'create') {
                        error_log("Calling reportModel->create()...");
                        try {
                            $success = $this->reportModel->create($reportData);
                            error_log("create() returned: " . ($success ? 'TRUE' : 'FALSE'));
                            
                            if ($success) {
                                $message = 'Report created successfully';
                                $messageType = 'success';
                                error_log("SUCCESS: Report created");
                            } else {
                                // Get the actual error from the model
                                $errorMsg = $this->reportModel->lastError ?? 'Unknown error occurred';
                                $message = 'Failed to create report: ' . htmlspecialchars($errorMsg);
                                $messageType = 'error';
                                error_log("ERROR: Report creation returned FALSE - Error: {$errorMsg}");
                                error_log("ERROR: Report creation returned FALSE - Data: " . print_r(array_merge($reportData, ['file_data' => isset($reportData['file_data']) ? '[BINARY DATA]' : 'NULL']), true));
                            }
                        } catch (\Exception $e) {
                            error_log("EXCEPTION in create(): " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                            $message = 'Failed to create report: ' . htmlspecialchars($e->getMessage());
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
                                $errorMsg = $this->reportModel->lastError ?? 'Unknown error occurred';
                                $message = 'Failed to update report: ' . htmlspecialchars($errorMsg);
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
                error_log("Redirecting with message: {$message} (type: {$messageType})");
                $this->redirectTo('admin/reports?message=' . urlencode($message) . '&type=' . urlencode($messageType));
            } else {
                error_log("No message set, not redirecting");
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("POST request but table doesn't exist or method mismatch");
                if (!$tableExists) {
                    error_log("ERROR: Reports table does not exist!");
                }
            }
        }
        error_log("=== REPORT POST REQUEST END ===");

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

        // Check if file_data column exists (for migration button)
        $fileDataColumnExists = $tableExists && $this->reportModel->columnExists('file_data');

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
            'fileDataColumnExists' => $fileDataColumnExists,
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

        // Check if sections table exists first
        $sectionsTableExists = $this->checkTableExists('sections');
        
        // Get courses with filters - handle gracefully if sections table doesn't exist
        try {
            $courses = $this->courseModel->getCoursesWithDoctorInfo($filters);
        } catch (\PDOException $e) {
            // If sections table doesn't exist, get courses without doctor info
            error_log("Error getting courses with doctor info: " . $e->getMessage());
            $courses = $this->courseModel->getAll();
            // Add empty doctor and student count fields
            foreach ($courses as &$course) {
                $course['doctors'] = '';
                $course['student_count'] = 0;
            }
        }
        
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

        // Check if sections table exists
        $sectionsTableExists = $this->checkTableExists('sections');

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
            'sectionsTableExists' => $sectionsTableExists,
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

    /**
     * Check if a table exists in the database
     */
    private function checkTableExists(string $tableName): bool
    {
        try {
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Run a specific migration file
     */
    public function runTableMigration(): void
    {
        // Start output buffering to catch any unwanted output
        ob_start();
        
        // Security: Only allow admins
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            ob_end_clean();
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Clear any output buffer and set headers
        ob_end_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $migrationFile = $_POST['file'] ?? $_GET['file'] ?? '';
        if (empty($migrationFile)) {
            echo json_encode(['success' => false, 'message' => 'No migration file specified']);
            exit;
        }

        $dbConfig = require dirname(__DIR__) . '/config/database.php';
        
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $charset = $dbConfig['charset'];
        
        $messages = [];
        $success = false;
        
        try {
            // Connect to the database
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            // Read migration file
            $migrationPath = dirname(__DIR__, 2) . '/database/migrations/' . basename($migrationFile);
            
            if (!file_exists($migrationPath)) {
                throw new \Exception("Migration file not found: {$migrationFile}");
            }
            
            $messages[] = "Reading migration file: {$migrationFile}";
            $sql = file_get_contents($migrationPath);
            
            // Remove comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            
            // Split SQL into individual statements
            $statements = [];
            $currentStatement = '';
            $lines = explode("\n", $sql);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0) {
                    continue;
                }
                
                $currentStatement .= $line . " ";
                
                if (substr(rtrim($line), -1) === ';') {
                    $stmt = trim($currentStatement);
                    if (!empty($stmt)) {
                        $statements[] = rtrim($stmt, ';');
                    }
                    $currentStatement = '';
                }
            }
            
            if (!empty(trim($currentStatement))) {
                $statements[] = trim($currentStatement);
            }
            
            $statements = array_filter($statements, function($stmt) {
                return !empty(trim($stmt));
            });
            
            $messages[] = "Found " . count($statements) . " SQL statement(s)";
            
            // Execute each statement
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }
                
                $statement = rtrim($statement, ';');
                $pdo->exec($statement);
            }
            
            $success = true;
            $messages[] = "Migration completed successfully!";
            
        } catch (\PDOException $e) {
            $messages[] = "Migration failed: " . $e->getMessage();
            error_log("Migration error: " . $e->getMessage());
        } catch (\Exception $e) {
            $messages[] = "Error: " . $e->getMessage();
            error_log("Migration error: " . $e->getMessage());
        }
        
        // Ensure no output before JSON
        if (ob_get_level()) {
            ob_clean();
        }
        
        echo json_encode([
            'success' => $success,
            'messages' => $messages
        ]);
        exit;
    }

    public function notifications(): void
    {
        try {
            $userId = $_SESSION['user']['id'];
            
            if (!$userId) {
                $this->view->render('errors/403', ['title' => 'Access Denied']);
                return;
            }

            // Handle mark as read action
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId) {
                    $this->notificationModel->markAsRead($notificationId, $userId);
                }
                $this->redirectTo('admin/notifications');
                return;
            }

            // Get notifications for the admin
            $notifications = $this->notificationModel->getByUserId($userId, 50);
            $unreadNotifications = $this->notificationModel->getUnreadByUserId($userId);
            $unreadCount = count($unreadNotifications);
            $unreadNotificationsCount = $unreadCount;

            $this->view->render('admin/admin_notifications', [
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
            
            if (!$userId) {
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
                    
                    foreach ($userIds as $targetUserId) {
                        $targetUserId = (int)$targetUserId;
                        if ($targetUserId > 0) {
                            if ($this->notificationModel->create([
                                'user_id' => $targetUserId,
                                'title' => $title,
                                'message' => $content,
                                'type' => $type,
                                'related_id' => $userId,
                                'related_type' => 'admin'
                            ])) {
                                $successCount++;
                            } else {
                                $errorCount++;
                            }
                        }
                    }
                    
                    if ($successCount > 0) {
                        $message = "Notification sent successfully to {$successCount} user(s)";
                        if ($errorCount > 0) {
                            $message .= ". {$errorCount} failed.";
                        }
                        $messageType = 'success';
                    } else {
                        $message = 'Error sending notifications';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please select at least one user and fill all required fields';
                    $messageType = 'error';
                }
            }

            // Get all users (students, doctors, IT officers, admins) for admin to send notifications to
            $allUsers = [];
            
            // Get all students
            $students = $this->studentModel->getAll();
            foreach ($students as $student) {
                if (isset($student['user_id']) && $student['user_id']) {
                    $user = $this->userModel->findById($student['user_id']);
                    if ($user && !isset($allUsers[$user['id']])) {
                        $allUsers[$user['id']] = [
                            'user_id' => $user['id'],
                            'first_name' => $user['first_name'] ?? '',
                            'last_name' => $user['last_name'] ?? '',
                            'email' => $user['email'] ?? '',
                            'role' => 'student',
                            'student_number' => $student['student_number'] ?? ''
                        ];
                    }
                }
            }
            
            // Get all doctors
            $doctors = $this->doctorModel->getAll();
            foreach ($doctors as $doctor) {
                if (isset($doctor['user_id']) && $doctor['user_id']) {
                    $user = $this->userModel->findById($doctor['user_id']);
                    if ($user && !isset($allUsers[$user['id']])) {
                        $allUsers[$user['id']] = [
                            'user_id' => $user['id'],
                            'first_name' => $user['first_name'] ?? '',
                            'last_name' => $user['last_name'] ?? '',
                            'email' => $user['email'] ?? '',
                            'role' => 'doctor',
                            'doctor_id' => $doctor['doctor_id'] ?? ''
                        ];
                    }
                }
            }
            
            // Get all IT officers
            $itOfficers = $this->itOfficerModel->getAll();
            foreach ($itOfficers as $itOfficer) {
                if (isset($itOfficer['user_id']) && $itOfficer['user_id']) {
                    $user = $this->userModel->findById($itOfficer['user_id']);
                    if ($user && !isset($allUsers[$user['id']])) {
                        $allUsers[$user['id']] = [
                            'user_id' => $user['id'],
                            'first_name' => $user['first_name'] ?? '',
                            'last_name' => $user['last_name'] ?? '',
                            'email' => $user['email'] ?? '',
                            'role' => 'it',
                            'it_officer_id' => $itOfficer['it_officer_id'] ?? ''
                        ];
                    }
                }
            }
            
            // Get all admins (excluding current admin) - query directly
            $db = \patterns\Singleton\DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($admins as $admin) {
                if ($admin['id'] != $userId && !isset($allUsers[$admin['id']])) {
                    $allUsers[$admin['id']] = [
                        'user_id' => $admin['id'],
                        'first_name' => $admin['first_name'] ?? '',
                        'last_name' => $admin['last_name'] ?? '',
                        'email' => $admin['email'] ?? '',
                        'role' => 'admin'
                    ];
                }
            }

            $this->view->render('admin/admin_send_notification', [
                'title' => 'Send Notification',
                'users' => array_values($allUsers),
                'message' => $message,
                'messageType' => $messageType,
                'showSidebar' => true,
            ]);
        } catch (\Exception $e) {
            error_log("Send notification error: " . $e->getMessage());
            $this->view->render('errors/500', ['title' => 'Error', 'message' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }
}

