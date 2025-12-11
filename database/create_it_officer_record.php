<?php
/**
 * Create IT officer record for users with role 'it'
 */

require_once dirname(__DIR__) . '/app/config/database.php';

$dbConfig = require dirname(__DIR__) . '/app/config/database.php';

$host = $dbConfig['host'];
$port = $dbConfig['port'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$charset = $dbConfig['charset'];

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "Connected to database: {$database}\n\n";
    
    // Find users with role 'it' who don't have an IT officer record
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.first_name, u.last_name 
        FROM users u 
        LEFT JOIN it_officers it ON u.id = it.user_id 
        WHERE u.role = 'it' AND it.user_id IS NULL
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users with role 'it' found, or all already have IT officer records.\n";
        echo "Checking existing IT officers...\n\n";
        
        $stmt = $pdo->query("
            SELECT it.it_id, u.id as user_id, u.email, u.first_name, u.last_name 
            FROM it_officers it 
            JOIN users u ON it.user_id = u.id
        ");
        $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($existing)) {
            echo "No IT officers found. You may need to:\n";
            echo "1. Create a user with role 'it' first\n";
            echo "2. Or manually insert: INSERT INTO it_officers (user_id) VALUES (YOUR_USER_ID);\n";
        } else {
            echo "Existing IT officers:\n";
            foreach ($existing as $it) {
                echo "  - ID: {$it['it_id']}, User: {$it['email']} ({$it['first_name']} {$it['last_name']})\n";
            }
        }
    } else {
        echo "Found " . count($users) . " user(s) with role 'it' that need IT officer records:\n\n";
        
        foreach ($users as $user) {
            echo "Creating IT officer record for: {$user['email']} ({$user['first_name']} {$user['last_name']})...\n";
            
            $stmt = $pdo->prepare("INSERT INTO it_officers (user_id) VALUES (:user_id)");
            $stmt->execute(['user_id' => $user['id']]);
            
            echo "✓ IT officer record created successfully!\n\n";
        }
    }
    
    echo "✓ Done!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}

