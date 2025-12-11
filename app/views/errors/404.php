<div style="min-height: 60vh; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 2rem;">
    <h1 style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;">404</h1>
    <h2 style="font-size: 2rem; color: var(--text-primary); margin-bottom: 1rem;">Page Not Found</h2>
    <p style="color: var(--text-secondary); margin-bottom: 2rem; max-width: 600px;">
        <?= htmlspecialchars($message ?? 'The page you requested could not be found.') ?>
    </p>
    <a href="<?= isset($url) ? htmlspecialchars($url('')) : '/' ?>" class="btn btn-primary" style="text-decoration: none;">
        <i class="fas fa-home"></i> Go to Home
    </a>
</div>

