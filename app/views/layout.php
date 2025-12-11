<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) : 'App' ?></title>
    <?php if (!empty($baseUrl)): ?>
        <base href="<?= htmlspecialchars(rtrim($baseUrl, '/')) ?>/">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= isset($asset) ? $asset('assets/css/style.css') : '/assets/css/style.css' ?>">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($title ?? 'App') ?></h1>
        <nav>
            <a href="/">Home</a>
        </nav>
    </header>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer>
        <small>&copy; <?= date('Y') ?> My MVC App</small>
    </footer>
    <script src="<?= isset($asset) ? $asset('assets/js/app.js') : '/assets/js/app.js' ?>"></script>
</body>
</html>

