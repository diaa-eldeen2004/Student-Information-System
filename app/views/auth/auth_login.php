<style>
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        padding: 2rem;
    }
    .auth-card {
        background-color: var(--surface-color);
        border-radius: 16px;
        padding: 3rem;
        box-shadow: 0 20px 40px var(--shadow-color);
        width: 100%;
        max-width: 400px;
        text-align: center;
        position: relative;
    }
    .auth-header { margin-bottom: 2rem; }
    .auth-logo { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }
    .auth-title { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
    .auth-subtitle { color: var(--text-secondary); margin-bottom: 2rem; }
    .form-group { margin-bottom: 1.5rem; text-align: left; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); }
    .form-input {
        width: 100%; padding: 1rem; border: 2px solid var(--border-color);
        border-radius: 8px; background-color: var(--background-color);
        color: var(--text-primary); font-size: 1rem;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .form-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    .input-group { position: relative; }
    .input-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    .input-group .form-input { padding-left: 3rem; }
    .btn-login {
        width: 100%; padding: 1rem; background-color: var(--primary-color);
        color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
        cursor: pointer; transition: all 0.3s ease; margin-bottom: 1rem;
    }
    .btn-login:hover { background-color: #1d4ed8; transform: translateY(-2px); }
    .auth-links { margin-top: 1.5rem; }
    .auth-links a { color: var(--primary-color); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .auth-links a:hover { color: var(--accent-color); }
    .remember-forgot { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); }
    .forgot-password { color: var(--primary-color); text-decoration: none; font-size: 0.9rem; }
    .forgot-password:hover { text-decoration: underline; }
    .back-home { position: absolute; top: 1.5rem; left: 1.5rem; color: var(--text-secondary); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .back-home:hover { color: var(--accent-color); }
</style>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<button class="theme-toggle" title="Switch to dark mode">
    <i class="fas fa-moon"></i>
</button>

<div class="auth-container">
    <div class="auth-card">
        <a href="<?= htmlspecialchars($url('')) ?>" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your account</p>
        </div>

        <form method="post" action="<?= isset($url) ? htmlspecialchars($url('auth/login')) : '/auth/login' ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
            </div>

            <div class="remember-forgot">
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="<?= isset($url) ? htmlspecialchars($url('auth/forgot-password')) : '/auth/forgot-password' ?>" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="auth-links">
            <p>Don't have an account? <a href="<?= isset($url) ? htmlspecialchars($url('auth/sign')) : '/auth/sign' ?>">Sign up here</a></p>
        </div>
    </div>
</div>

