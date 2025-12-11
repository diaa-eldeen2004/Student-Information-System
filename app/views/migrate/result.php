<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Migration') ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-left: 3px solid #2563eb;
            background: #f8fafc;
        }
        .message.success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .message.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Migration</h1>
        
        <?php foreach ($messages ?? [] as $message): ?>
            <div class="message <?= $success ? 'success' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endforeach; ?>
        
        <?php if ($success ?? false): ?>
            <p style="color: #10b981; font-weight: bold; margin-top: 20px;">
                ✓ Database setup complete! You can now use the application.
            </p>
        <?php endif; ?>
        
        <a href="/" class="back-link">← Back to Home</a>
    </div>
</body>
</html>

