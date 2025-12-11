<?php
$role = $effectiveRole ?? null;
$destination = $dashboardDestination ?? '';
$label = $dashboardLabel ?? 'Dashboard';
?>

<!-- Theme Toggle Button -->
<button class="theme-toggle" title="Switch to dark mode">
    <i class="fas fa-moon"></i>
</button>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="navbar-brand">
        <i class="fas fa-graduation-cap"></i>
        University Portal
    </div>
    <ul class="navbar-nav">
        <li><a href="<?= htmlspecialchars($url('')) ?>">Home</a></li>
        <li><a href="<?= htmlspecialchars($url('about')) ?>">About Us</a></li>
        <li><a href="<?= htmlspecialchars($url('contact')) ?>">Contact</a></li>
        <li><a href="<?= htmlspecialchars($url('help_center')) ?>">Help</a></li>
        <?php if ($role): ?>
            <li>
                <a href="<?= htmlspecialchars($destination) ?>" class="btn btn-primary dashboard-btn" data-destination="<?= htmlspecialchars($destination) ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            </li>
            <li>
                <a href="<?= htmlspecialchars($url('logout')) ?>" class="btn btn-outline">Logout</a>
            </li>
        <?php else: ?>
            <li><a href="<?= htmlspecialchars($url('auth/login')) ?>" class="btn btn-primary">Login</a></li>
            <li><a href="<?= htmlspecialchars($url('auth/sign')) ?>" class="btn btn-outline">Create Account</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="container">
        <h1>Welcome to University Portal</h1>
        <p>Your comprehensive campus management system for students, doctors, and administrators</p>
        <div class="hero-buttons">
            <?php if ($role): ?>
                <a href="<?= htmlspecialchars($destination) ?>" class="btn btn-primary dashboard-btn" data-destination="<?= htmlspecialchars($destination) ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url('auth/login')) ?>" class="btn btn-primary">Get Started</a>
                <a href="<?= htmlspecialchars($url('auth/sign')) ?>" class="btn btn-outline">Create Account</a>
            <?php endif; ?>
                <a href="<?= htmlspecialchars($url('about')) ?>" class="btn btn-outline">Learn More</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" style="padding: 4rem 2rem; background-color: var(--surface-color);">
    <div class="container">
        <h2 class="text-center mb-4">Why Choose Our Portal?</h2>
        <div class="grid grid-3">
            <div class="card text-center">
                <div class="card-icon" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Multi-Role Support</h3>
                <p>Seamlessly manage different user roles - students, doctors, and administrators - all in one unified platform.</p>
            </div>
            <div class="card text-center">
                <div class="card-icon" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Smart Calendar</h3>
                <p>Integrated calendar system for assignments, events, and important dates with automatic synchronization.</p>
            </div>
            <div class="card text-center">
                <div class="card-icon" style="font-size: 3rem; color: var(--success-color); margin-bottom: 1rem;">
                    <i class="fas fa-file-upload"></i>
                </div>
                <h3>File Management</h3>
                <p>Easy upload and management of course materials, assignments, and documents with organized sections.</p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section" style="padding: 4rem 2rem; background-color: var(--primary-color); color: white;">
    <div class="container">
        <h2 class="text-center mb-4">Portal Statistics</h2>
        <div class="grid grid-4">
            <div class="text-center">
                <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">1,250+</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">Active Students</div>
            </div>
            <div class="text-center">
                <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">85+</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">Faculty Members</div>
            </div>
            <div class="text-center">
                <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">150+</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">Courses Available</div>
            </div>
            <div class="text-center">
                <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;">98%</div>
                <div style="font-size: 1.2rem; opacity: 0.9;">User Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section" style="padding: 4rem 2rem; text-align: center;">
    <div class="container">
        <h2>Ready to Get Started?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem; color: var(--text-secondary);">
            Join thousands of students and faculty members who are already using our portal
        </p>
        <div class="cta-buttons">
            <?php if ($role): ?>
                <a href="<?= htmlspecialchars($destination) ?>" class="btn btn-primary dashboard-btn" data-destination="<?= htmlspecialchars($destination) ?>" style="margin-right: 1rem;">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url('auth/login')) ?>" class="btn btn-primary" style="margin-right: 1rem;">Login Now</a>
                <a href="<?= htmlspecialchars($url('auth/sign')) ?>" class="btn btn-outline">Create Account</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>University Portal</h3>
            <p>Comprehensive campus management system designed to streamline academic operations and enhance the learning experience.</p>
        </div>
        <div class="footer-section">
            <h3>Quick Links</h3>
            <a href="<?= htmlspecialchars($url('')) ?>">Home</a>
            <a href="<?= htmlspecialchars($url('about')) ?>">About Us</a>
            <a href="<?= htmlspecialchars($url('contact')) ?>">Contact</a>
            <a href="<?= htmlspecialchars($url('help_center')) ?>">Help Center</a>
        </div>

        <div class="footer-section">
            <h3>Contact Info</h3>
            <p><i class="fas fa-envelope"></i> info@university.edu</p>
            <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
            <p><i class="fas fa-map-marker-alt"></i> 123 University Ave, Campus City</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 University Portal. All rights reserved. | Privacy Policy | Terms of Service</p>
    </div>
</footer>

