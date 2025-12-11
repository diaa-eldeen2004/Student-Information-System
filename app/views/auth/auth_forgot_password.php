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
        max-width: 550px;
        text-align: center;
        position: relative;
    }
    .auth-header { margin-bottom: 2rem; }
    .auth-logo { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }
    .auth-title { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
    .auth-subtitle { color: var(--text-secondary); margin-bottom: 1.5rem; }
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
    .btn-reset {
        width: 100%; padding: 1rem; background-color: var(--primary-color);
        color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
        cursor: pointer; transition: all 0.3s ease; margin-top: 0.5rem;
    }
    .btn-reset:hover { background-color: #1d4ed8; transform: translateY(-2px); }
    .back-home { position: absolute; top: 1.5rem; left: 1.5rem; color: var(--text-secondary); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
    .back-home:hover { color: var(--accent-color); }
    .back-link { display: inline-block; margin-top: 1rem; color: var(--primary-color); text-decoration: none; }
    .back-link:hover { color: var(--accent-color); }
    .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; }
    .step { text-align: center; flex: 1; }
    .step-circle {
        width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--border-color);
        display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem auto; font-weight: 600;
    }
    .step.active .step-circle { border-color: var(--primary-color); color: var(--primary-color); }
    .step-label { font-size: 0.9rem; color: var(--text-secondary); }
    .info-box {
        background: var(--background-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    .info-box i { color: var(--primary-color); margin-top: 0.15rem; }
</style>

<button class="theme-toggle" title="Switch to dark mode">
    <i class="fas fa-moon"></i>
</button>

<div class="auth-container">
    <div class="auth-card">
        <a href="<?= htmlspecialchars($url('')) ?>" class="back-home"><i class="fas fa-arrow-left"></i>Back</a>

        <div class="step-indicator">
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-label">Email</div>
            </div>
            <div class="step">
                <div class="step-circle">2</div>
                <div class="step-label">Verify</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Reset</div>
            </div>
        </div>

        <div class="auth-header">
            <div class="auth-logo"><i class="fas fa-key"></i></div>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-subtitle">Enter your email to receive a reset code</p>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <span>We'll send a 6-digit code to your email (expires in 30 minutes)</span>
        </div>

        <form method="post" action="<?= isset($url) ? htmlspecialchars($url('auth/forgot-password')) : '/auth/forgot-password' ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>
            </div>
            <button type="submit" class="btn-reset">
                <i class="fas fa-paper-plane"></i>
                <span>Send Reset Code</span>
            </button>
            <a href="<?= isset($url) ? htmlspecialchars($url('auth/login')) : '/auth/login' ?>" class="back-link"><i class="fas fa-arrow-left"></i>Back to Login</a>
        </form>
    </div>
</div>

