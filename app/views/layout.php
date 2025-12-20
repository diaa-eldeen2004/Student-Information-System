<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) : 'App' ?></title>
    
    <!-- CSS Files -->
    <?php
    // Use asset helper if available, otherwise build path from baseUrl
    if (isset($asset) && is_callable($asset)) {
        $cssPath = $asset('assets/css/style.css');
    } else {
        // Fallback: build path from baseUrl
        $basePath = isset($baseUrl) && !empty($baseUrl) ? parse_url($baseUrl, PHP_URL_PATH) : '';
        $basePath = rtrim($basePath ?: '', '/');
        $cssPath = $basePath . '/assets/css/style.css';
    }
    // Ensure path starts with / for absolute path
    if (!empty($cssPath) && $cssPath[0] !== '/') {
        $cssPath = '/' . $cssPath;
    }
    // Remove double slashes
    $cssPath = preg_replace('#/+#', '/', $cssPath);
    // Debug: Output the path (temporary - remove after fixing)
    echo "<!-- CSS Path: " . htmlspecialchars($cssPath) . " -->";
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>?v=<?= time() ?>" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    // Check if we're on an IT, Doctor, Admin, or Student page (sidebar should be shown)
    // Check authentication before showing sidebar
    $isAuthenticated = isset($_SESSION['user']) && isset($_SESSION['user']['role']);
    $userRole = $isAuthenticated ? $_SESSION['user']['role'] : null;
    
    $currentPage = $_SERVER['REQUEST_URI'] ?? '';
    $currentPath = parse_url($currentPage, PHP_URL_PATH) ?? '';
    $isDoctorPage = strpos($currentPath, '/doctor/') !== false;
    $isItPage = strpos($currentPath, '/it/') !== false;
    $isAdminPage = strpos($currentPath, '/admin/') !== false;
    $isStudentPage = strpos($currentPath, '/student/') !== false;
    
    // Only show sidebar if authenticated and role matches the page
    $showSidebar = isset($showSidebar) ? $showSidebar : (
        $isAuthenticated && (
            ($isItPage && $userRole === 'it') ||
            ($isDoctorPage && $userRole === 'doctor') ||
            ($isAdminPage && $userRole === 'admin') ||
            ($isStudentPage && $userRole === 'student')
        )
    );
    
    // Get pending enrollment requests count for IT pages
    $pendingEnrollmentRequestsCount = 0;
    if ($isItPage) {
        // Try to get from view data first (passed from controller)
        // Check for both possible variable names
        if (isset($pendingEnrollmentRequestsCount) && $pendingEnrollmentRequestsCount > 0) {
            // Already set from controller
        } elseif (isset($pendingRequestsCount) && $pendingRequestsCount > 0) {
            $pendingEnrollmentRequestsCount = $pendingRequestsCount;
        } else {
            // Fetch directly if not passed
            try {
                if (class_exists('patterns\Factory\ModelFactory')) {
                    $enrollmentRequestModel = \patterns\Factory\ModelFactory::create('EnrollmentRequest');
                    $pendingRequests = $enrollmentRequestModel->getPendingRequests();
                    $pendingEnrollmentRequestsCount = count($pendingRequests);
                }
            } catch (\Exception $e) {
                // Silently fail if model not available
                $pendingEnrollmentRequestsCount = 0;
            }
        }
    }
    
    // Get unread notifications count for all user types
    $unreadNotificationsCount = 0;
    if ($showSidebar && isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        // Try to get from view data first (passed from controller)
        if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0) {
            // Already set from controller
        } else {
            // Fetch directly if not passed
            try {
                if (class_exists('patterns\Factory\ModelFactory')) {
                    $notificationModel = \patterns\Factory\ModelFactory::create('Notification');
                    $unreadNotifications = $notificationModel->getUnreadByUserId($userId);
                    $unreadNotificationsCount = count($unreadNotifications);
                }
            } catch (\Exception $e) {
                // Silently fail if model not available
                $unreadNotificationsCount = 0;
            }
        }
    }
    ?>
    
    <?php if ($showSidebar): ?>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> <?= $isAdminPage ? 'Admin' : ($isDoctorPage ? 'Doctor' : ($isStudentPage ? 'Student' : 'IT')) ?> Portal</h2>
        </div>
        <nav class="sidebar-nav">
            <?php if ($isAdminPage): ?>
                <a href="<?= htmlspecialchars($url('admin/dashboard')) ?>" class="nav-item <?= (strpos($currentPath, '/admin/dashboard') !== false || ($currentPath === '/admin' || $currentPath === '/admin/')) ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-student')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-student') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate"></i> Manage Students
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-doctor')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-doctor') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chalkboard-teacher"></i> Manage Doctors
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-course')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-course') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Manage Courses
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-it')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-it') !== false ? 'active' : '' ?>">
                    <i class="fas fa-laptop-code"></i> Manage IT
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-admin')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-admin') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Manage Admins
                </a>
                <a href="<?= htmlspecialchars($url('admin/manage-user')) ?>" class="nav-item <?= strpos($currentPath, '/admin/manage-user') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="<?= htmlspecialchars($url('admin/reports')) ?>" class="nav-item <?= strpos($currentPath, '/admin/reports') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="<?= htmlspecialchars($url('admin/notifications')) ?>" class="nav-item <?= (strpos($currentPath, '/admin/notifications') !== false || strpos($currentPath, '/admin/send-notification') !== false) ? 'active' : '' ?>" style="position: relative;">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0): ?>
                        <span class="notification-badge" style="
                            position: absolute;
                            top: 50%;
                            right: 1rem;
                            transform: translateY(-50%);
                            background-color: #ef4444;
                            color: white;
                            border-radius: 50%;
                            min-width: 20px;
                            height: 20px;
                            padding: 0 6px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 11px;
                            font-weight: bold;
                        "><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= htmlspecialchars($url('admin/profile')) ?>" class="nav-item <?= strpos($currentPath, '/admin/profile') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php elseif ($isDoctorPage): ?>
                <a href="<?= htmlspecialchars($url('doctor/dashboard')) ?>" class="nav-item <?= (strpos($currentPath, '/doctor/dashboard') !== false || ($currentPath === '/doctor' || $currentPath === '/doctor/')) ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/course') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Courses
                </a>
                <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/assignments') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Assignments/Quizzes
                </a>
                <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/attendance') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a href="<?= htmlspecialchars($url('doctor/notifications')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/notifications') !== false ? 'active' : '' ?>" style="position: relative;">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="notification-badge" style="
                            position: absolute;
                            top: 50%;
                            right: 1rem;
                            transform: translateY(-50%);
                            background-color: #ef4444;
                            color: white;
                            border-radius: 50%;
                            min-width: 20px;
                            height: 20px;
                            padding: 0 6px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 11px;
                            font-weight: bold;
                            line-height: 1;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                            z-index: 10;
                        "><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                    <?php endif; ?>
                </a>
            <?php elseif ($isStudentPage): ?>
                <a href="<?= htmlspecialchars($url('student/dashboard')) ?>" class="nav-item <?= (strpos($currentPath, '/student/dashboard') !== false || ($currentPath === '/student' || $currentPath === '/student/')) ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= htmlspecialchars($url('student/course')) ?>" class="nav-item <?= strpos($currentPath, '/student/course') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Courses
                </a>
                <a href="<?= htmlspecialchars($url('student/schedule')) ?>" class="nav-item <?= strpos($currentPath, '/student/schedule') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Schedule
                </a>
                <a href="<?= htmlspecialchars($url('student/assignments')) ?>" class="nav-item <?= strpos($currentPath, '/student/assignments') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Assignments
                </a>
                <a href="<?= htmlspecialchars($url('student/calendar')) ?>" class="nav-item <?= strpos($currentPath, '/student/calendar') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
                <a href="<?= htmlspecialchars($url('student/notifications')) ?>" class="nav-item <?= strpos($currentPath, '/student/notifications') !== false ? 'active' : '' ?>" style="position: relative;">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="notification-badge" style="
                            position: absolute;
                            top: 50%;
                            right: 1rem;
                            transform: translateY(-50%);
                            background-color: #ef4444;
                            color: white;
                            border-radius: 50%;
                            min-width: 20px;
                            height: 20px;
                            padding: 0 6px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 11px;
                            font-weight: bold;
                            line-height: 1;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                            z-index: 10;
                        "><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= htmlspecialchars($url('student/profile')) ?>" class="nav-item <?= strpos($currentPath, '/student/profile') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url('it/dashboard')) ?>" class="nav-item <?= (strpos($currentPath, '/it/dashboard') !== false || ($currentPath === '/it' || $currentPath === '/it/')) ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= htmlspecialchars($url('it/schedule')) ?>" class="nav-item <?= strpos($currentPath, '/it/schedule') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Schedule
                </a>
                <a href="<?= htmlspecialchars($url('it/course')) ?>" class="nav-item <?= strpos($currentPath, '/it/course') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Courses
                </a>
                <a href="<?= htmlspecialchars($url('it/enrollments')) ?>" class="nav-item <?= strpos($currentPath, '/it/enrollments') !== false ? 'active' : '' ?>" style="position: relative;">
                    <i class="fas fa-user-check"></i> Enrollments
                    <?php if ($pendingEnrollmentRequestsCount > 0): ?>
                        <span class="notification-badge" style="
                            position: absolute;
                            top: 50%;
                            right: 1rem;
                            transform: translateY(-50%);
                            background-color: #ef4444;
                            color: white;
                            border-radius: 50%;
                            min-width: 20px;
                            height: 20px;
                            padding: 0 6px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 11px;
                            font-weight: bold;
                            line-height: 1;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                            z-index: 10;
                        "><?= $pendingEnrollmentRequestsCount > 99 ? '99+' : $pendingEnrollmentRequestsCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="nav-item <?= strpos($currentPath, '/it/logs') !== false ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> Audit Logs
                </a>
                <a href="<?= htmlspecialchars($url('it/send-notification')) ?>" class="nav-item <?= strpos($currentPath, '/it/send-notification') !== false ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Send Notification
                </a>
            <?php endif; ?>
            <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border-color);">
            <a href="<?= htmlspecialchars($url('')) ?>" class="nav-item">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="<?= htmlspecialchars($url('logout')) ?>" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <?php endif; ?>

    <!-- Sidebar Toggle Button -->
    <?php if ($showSidebar): ?>
    <button class="sidebar-toggle" title="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <?php endif; ?>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" title="Switch to dark mode">
        <i class="fas fa-moon"></i>
    </button>


    <main class="main-content">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer - Show on all pages -->
    <footer class="footer" id="main-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>University Portal</h3>
                <p>Comprehensive campus management system designed to streamline academic operations and enhance the learning experience.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="<?= isset($url) ? htmlspecialchars($url('')) : '/' ?>">Home</a>
                <a href="<?= isset($url) ? htmlspecialchars($url('about')) : '/about' ?>">About Us</a>
                <a href="<?= isset($url) ? htmlspecialchars($url('contact')) : '/contact' ?>">Contact</a>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p><i class="fas fa-envelope"></i> info@university.edu</p>
                <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                <p><i class="fas fa-map-marker-alt"></i> 123 University Ave, Campus City</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> University Portal. All rights reserved. | Privacy Policy | Terms of Service</p>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <?php
    // Use asset helper if available, otherwise build path from baseUrl
    if (isset($asset) && is_callable($asset)) {
        $jsPath = $asset('assets/js/app.js');
    } else {
        // Fallback: build path from baseUrl
        $basePath = isset($baseUrl) && !empty($baseUrl) ? parse_url($baseUrl, PHP_URL_PATH) : '';
        $basePath = rtrim($basePath ?: '', '/');
        $jsPath = $basePath . '/assets/js/app.js';
    }
    // Ensure path starts with / for absolute path
    if (!empty($jsPath) && $jsPath[0] !== '/') {
        $jsPath = '/' . $jsPath;
    }
    // Remove double slashes
    $jsPath = preg_replace('#/+#', '/', $jsPath);
    // Debug: Output the path (temporary - remove after fixing)
    echo "<!-- JS Path: " . htmlspecialchars($jsPath) . " -->";
    ?>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="<?= htmlspecialchars($jsPath) ?>?v=<?= time() ?>" type="text/javascript"></script>
</body>
</html>

