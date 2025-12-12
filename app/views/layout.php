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
    // Check if we're on an IT or Doctor page (sidebar should be shown)
    $showSidebar = isset($showSidebar) ? $showSidebar : (strpos($_SERVER['REQUEST_URI'] ?? '', '/it/') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/doctor/') !== false);
    $currentPage = $_SERVER['REQUEST_URI'] ?? '';
    $currentPath = parse_url($currentPage, PHP_URL_PATH) ?? '';
    $isDoctorPage = strpos($currentPath, '/doctor/') !== false;
    $isItPage = strpos($currentPath, '/it/') !== false;
    ?>
    
    <?php if ($showSidebar): ?>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> <?= $isDoctorPage ? 'Doctor' : 'IT' ?> Portal</h2>
        </div>
        <nav class="sidebar-nav">
            <?php if ($isDoctorPage): ?>
                <a href="<?= htmlspecialchars($url('doctor/dashboard')) ?>" class="nav-item <?= (strpos($currentPath, '/doctor/dashboard') !== false || ($currentPath === '/doctor' || $currentPath === '/doctor/')) ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?= htmlspecialchars($url('doctor/course')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/course') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book"></i> Courses
                </a>
                <a href="<?= htmlspecialchars($url('doctor/assignments')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/assignments') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Assignments
                </a>
                <a href="<?= htmlspecialchars($url('doctor/attendance')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/attendance') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a href="<?= htmlspecialchars($url('doctor/calendar')) ?>" class="nav-item <?= strpos($currentPath, '/doctor/calendar') !== false ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i> Calendar
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
                <a href="<?= htmlspecialchars($url('it/enrollments')) ?>" class="nav-item <?= strpos($currentPath, '/it/enrollments') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-check"></i> Enrollments
                </a>
                <a href="<?= htmlspecialchars($url('it/logs')) ?>" class="nav-item <?= strpos($currentPath, '/it/logs') !== false ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i> Audit Logs
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

    <?php if (!$showSidebar): ?>
    <header>
        <h1><?= htmlspecialchars($title ?? 'App') ?></h1>
        <nav>
            <a href="/">Home</a>
        </nav>
    </header>
    <?php endif; ?>

    <main class="main-content">
        <?= $content ?? '' ?>
    </main>

    <?php if (!$showSidebar): ?>
    <footer class="footer">
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
    <?php endif; ?>

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

