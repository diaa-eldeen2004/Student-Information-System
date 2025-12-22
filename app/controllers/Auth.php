<?php
namespace controllers;

use core\Controller;
use models\User;

class Auth extends Controller
{
    private User $users;
    private array $config;
    private string $basePath;

    public function __construct()
    {
        parent::__construct();
        $this->users = new User();
        $this->config = require dirname(__DIR__) . '/config/config.php';
        $this->basePath = rtrim(parse_url($this->config['base_url'] ?? '', PHP_URL_PATH) ?? '', '/');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function path(string $path): string
    {
        $clean = ltrim($path, '/');
        if ($this->basePath === '' || $this->basePath === '/') {
            return '/' . $clean;
        }
        return $this->basePath . '/' . $clean;
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $this->users->findByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user',
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['last_name'] ?? '',
                ];

                $redirects = [
                    'doctor' => 'doctor/dashboard',
                    'admin' => 'admin/dashboard',
                    'student' => 'student/dashboard',
                    'it' => 'it/dashboard',
                    'user' => '',
                ];

                $role = $user['role'] ?? 'user';
                $target = $redirects[$role] ?? '';
                $destination = $target === '' ? ($this->basePath ?: '/') : $this->path($target);
                header("Location: {$destination}");
                exit;
            }

            $this->view->render('auth/auth_login', [
                'error' => 'Invalid email or password',
                'email' => $email,
                'title' => 'Login',
            ]);
            return;
        }

        $this->view->render('auth/auth_login', ['title' => 'Login']);
    }

    public function sign(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $first = trim($_POST['firstName'] ?? '');
            $last = trim($_POST['lastName'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirmPassword'] ?? '';

            // Basic validation
            if ($first === '' || $last === '' || $email === '' || $password === '' || $confirm === '') {
                $this->view->render('auth/auth_sign', [
                    'error' => 'All required fields must be filled.',
                    'title' => 'Sign Up',
                    'email' => $email,
                ]);
                return;
            }

            if ($password !== $confirm) {
                $this->view->render('auth/auth_sign', [
                    'error' => 'Passwords do not match',
                    'title' => 'Sign Up',
                    'email' => $email,
                ]);
                return;
            }

            if (strlen($password) < 8) {
                $this->view->render('auth/auth_sign', [
                    'error' => 'Password must be at least 8 characters',
                    'title' => 'Sign Up',
                    'email' => $email,
                ]);
                return;
            }

            $existing = $this->users->findByEmail($email);
            if ($existing) {
                $this->view->render('auth/auth_sign', [
                    'error' => 'Email already registered',
                    'title' => 'Sign Up',
                    'email' => $email,
                ]);
                return;
            }

            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $success = $this->users->createUser([
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $hashedPassword,
                    'role' => 'user', // default role for public signup
                ]);

                if (!$success) {
                    throw new \RuntimeException('Failed to create user account');
                }

                // Get the created user to set session with ID
                $newUser = $this->users->findByEmail($email);
                if (!$newUser) {
                    throw new \RuntimeException('User created but could not be retrieved');
                }

                $_SESSION['user'] = [
                    'id' => $newUser['id'],
                    'email' => $newUser['email'],
                    'role' => $newUser['role'],
                    'first_name' => $newUser['first_name'],
                    'last_name' => $newUser['last_name'],
                ];

                $home = $this->basePath ?: '/';
                // Suppress header warnings during tests (expected when testing redirects)
                if (defined('TESTING')) {
                    @header('Location: ' . $home);
                } else {
                    header('Location: ' . $home);
                }
                exit;
            } catch (\Exception $e) {
                // Suppress header-related errors during tests (they're expected when testing redirects)
                $isHeaderError = strpos($e->getMessage(), 'Cannot modify header information') !== false ||
                                strpos($e->getMessage(), 'headers already sent') !== false;
                // Only log if not in test environment AND it's not a header error
                // In test environment, never log header errors
                if (defined('TESTING')) {
                    // In test mode, only log if it's NOT a header error
                    if (!$isHeaderError) {
                        error_log("Signup error: " . $e->getMessage());
                    }
                } else {
                    // Not in test mode, log all errors
                    error_log("Signup error: " . $e->getMessage());
                }
                $this->view->render('auth/auth_sign', [
                    'error' => 'An error occurred during registration. Please try again.',
                    'title' => 'Sign Up',
                    'email' => $email,
                ]);
                return;
            }
        }

        $this->view->render('auth/auth_sign', ['title' => 'Sign Up']);
    }

    public function forgotPassword(): void
    {
        // Placeholder; integrate with mail/OTP later
        $this->view->render('auth/auth_forgot_password', ['title' => 'Forgot Password']);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        // Suppress header warnings during tests (expected when testing redirects)
        if (defined('TESTING')) {
            @header('Location: ' . $this->path('auth/login'));
        } else {
            header('Location: ' . $this->path('auth/login'));
        }
        exit;
    }
}

