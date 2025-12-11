<?php
<div class="logout-container">
    <div class="logout-card">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h1 class="logout-title">Logging Out</h1>
        <p class="logout-message">You are being logged out and will be redirected shortly.</p>
        <div class="countdown" id="countdown">Redirecting in 3 seconds...</div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 100%;"></div>
        </div>
        <div class="logout-actions">
            <button class="btn btn-primary" onclick="window.location.href='/auth/login'">Go to Login</button>
            <button class="btn btn-outline" onclick="window.location.href='/'">Back to Home</button>
        </div>
    </div>
</div>
<script>
    let countdown = 3;
    const counter = document.getElementById('countdown');
    const fill = document.getElementById('progressFill');
    const timer = setInterval(() => {
        countdown--;
        counter.textContent = `Redirecting in ${countdown} seconds...`;
        fill.style.width = `${countdown / 3 * 100}%`;
        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = '/auth/login';
        }
    }, 1000);
</script>
?>

