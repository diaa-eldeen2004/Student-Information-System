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
        max-width: 500px;
        text-align: center;
        position: relative;
    }
    .auth-header { margin-bottom: 2rem; }
    .auth-logo { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }
    .auth-title { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
    .auth-subtitle { color: var(--text-secondary); margin-bottom: 2rem; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
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
    .btn-signup {
        width: 100%; padding: 1rem; background-color: var(--primary-color);
        color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
        cursor: pointer; transition: all 0.3s ease; margin-bottom: 1rem;
    }
    .btn-signup:hover { background-color: #1d4ed8; transform: translateY(-2px); }
    .auth-links { margin-top: 1.5rem; }
    .auth-links a { color: var(--primary-color); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .auth-links a:hover { color: var(--accent-color); }
    .back-home { position: absolute; top: 1.5rem; left: 1.5rem; color: var(--text-secondary); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .back-home:hover { color: var(--accent-color); }
    .password-strength { margin-top: 0.5rem; font-size: 0.85rem; }
    .strength-bar { height: 4px; background-color: var(--border-color); border-radius: 2px; margin-top: 0.25rem; overflow: hidden; }
    .strength-fill { height: 100%; transition: width 0.3s ease, background-color 0.3s ease; }
    .strength-weak { background-color: var(--error-color); }
    .strength-medium { background-color: var(--warning-color); }
    .strength-strong { background-color: var(--success-color); }
    .terms-checkbox { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 1.5rem; text-align: left; }
    .terms-checkbox input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); margin-top: 0.2rem; }
    .terms-checkbox label { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.4; }
    .terms-checkbox a { color: var(--primary-color); text-decoration: none; }
    .terms-checkbox a:hover { text-decoration: underline; }
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
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join our university community</p>
        </div>

        <form method="post" action="<?= isset($url) ? htmlspecialchars($url('auth/sign')) : '/auth/sign' ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="firstName">First Name</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="firstName" name="firstName" class="form-input" placeholder="First name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="lastName">Last Name</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Last name" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                </div>
                <div class="password-strength">
                    <div id="strengthText">Password strength</div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirmPassword">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
                </div>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn-signup">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="<?= isset($url) ? htmlspecialchars($url('auth/login')) : '/auth/login' ?>">Sign in here</a></p>
        </div>
    </div>
</div>
</div>