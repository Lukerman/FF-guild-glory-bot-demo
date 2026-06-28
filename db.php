<?php
// Enable basic error logging for the debugger
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
// For development, optionally display errors, but the debugger will read the log.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure the db directory exists
$db_dir = __DIR__ . '/db';
if (!is_dir($db_dir)) {
    mkdir($db_dir, 0755, true);
}

$db_file = $db_dir . '/guild_glory.sqlite';
$db_exists = file_exists($db_file);

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (!$db_exists) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS access_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                access_key TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                is_active INTEGER DEFAULT 1,
                is_used INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS simulations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                guild_id TEXT NOT NULL,
                server TEXT NOT NULL,
                plan TEXT NOT NULL,
                access_key TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS token_generation_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Insert default admin: admin / admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', :hash)");
        $stmt->execute([':hash' => $hash]);

        // Insert default settings
        $settings = [
            ['site_name', 'Guild Glory'],
            ['expiry_duration_days', '30'],
            ['matrix_background', '1'],
            ['sound_effects', '1']
        ];

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val)");
        foreach ($settings as $s) {
            $stmt->execute([':key' => $s[0], ':val' => $s[1]]);
        }
    }
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if ($token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}
?>