<?php
/**
 * Web interface for IT Officers migration
 * Access this page to run the migration
 * URL: http://localhost/Student-Information-System/public/migrate_it_officers.php
 */

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/config/database.php';

$appConfig = require dirname(__DIR__) . '/app/config/config.php';
$baseUrl = rtrim($appConfig['base_url'] ?? 'http://localhost/Student-Information-System/public', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Officers Migration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 2.5rem;
        }
        
        h1 {
            color: #1a202c;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        h1 i {
            color: #667eea;
        }
        
        .subtitle {
            color: #718096;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .info-box {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .info-box h3 {
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .info-box p {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .info-box ul {
            margin-top: 0.75rem;
            padding-left: 1.5rem;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .info-box li {
            margin-bottom: 0.5rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            display: none;
        }
        
        .result.success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            display: block;
        }
        
        .result.error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            display: block;
        }
        
        .result.info {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            color: #1e40af;
            display: block;
        }
        
        .result h4 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
        }
        
        .loading.active {
            display: block;
        }
        
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .back-link {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-database"></i>
            IT Officers Migration
        </h1>
        <p class="subtitle">Create the IT Officers table and migrate existing users</p>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> What this migration does:</h3>
            <ul>
                <li>Creates the <code>it_officers</code> table if it doesn't exist</li>
                <li>Creates IT officer records for users with role 'it' who don't have a record</li>
                <li>Ensures database structure is ready for IT officer functionality</li>
            </ul>
        </div>
        
        <button id="runMigration" class="btn">
            <i class="fas fa-play"></i>
            Run Migration
        </button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; color: #4a5568;">Running migration...</p>
        </div>
        
        <div class="result" id="result"></div>
        
        <div class="back-link">
            <a href="<?= htmlspecialchars($baseUrl) ?>">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>
    
    <script>
        document.getElementById('runMigration').addEventListener('click', function() {
            const btn = this;
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            // Disable button and show loading
            btn.disabled = true;
            loading.classList.add('active');
            result.style.display = 'none';
            result.className = 'result';
            
            // Run migration - use relative path from public folder
            fetch('../database/migrate_it_officers.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                loading.classList.remove('active');
                btn.disabled = false;
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = `
                        <h4><i class="fas fa-check-circle"></i> Success!</h4>
                        <p>${data.message}</p>
                        ${data.records_created > 0 ? `<p><strong>${data.records_created}</strong> IT officer record(s) were created.</p>` : ''}
                    `;
                } else {
                    result.className = 'result error';
                    result.innerHTML = `
                        <h4><i class="fas fa-exclamation-circle"></i> Error</h4>
                        <p>${data.message}</p>
                        ${data.error ? `<p><small>${data.error}</small></p>` : ''}
                    `;
                }
                result.style.display = 'block';
            })
            .catch(error => {
                loading.classList.remove('active');
                btn.disabled = false;
                result.className = 'result error';
                result.innerHTML = `
                    <h4><i class="fas fa-exclamation-circle"></i> Error</h4>
                    <p>Failed to run migration: ${error.message}</p>
                    <p><small>Make sure the database/migrate_it_officers.php file exists and is accessible.</small></p>
                `;
                result.style.display = 'block';
            });
        });
    </script>
</body>
</html>
