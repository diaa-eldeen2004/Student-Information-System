<section>
    <div style="text-align: center; padding: 3rem;">
        <h2 style="font-size: 3rem; color: var(--text-color); margin-bottom: 1rem;">400 - Bad Request</h2>
        <p style="font-size: 1.2rem; color: var(--text-muted); margin-bottom: 2rem;">
            <?= htmlspecialchars($message ?? 'The request you made is invalid or malformed. Please check your input and try again.') ?>
        </p>
        <a href="<?= htmlspecialchars($url('')) ?>" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; background: var(--primary-color); color: white;">
            <i class="fas fa-home"></i> Go to Home
        </a>
    </div>
</section>

