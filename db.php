<?php
session_start();

$db_file = __DIR__ . '/db/guild_glory.sqlite';
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
        ");

        // Insert default admin: admin / admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', :hash)");
        $stmt->execute([':hash' => $hash]);

        // Insert default settings
        $settings = [
            ['site_name', 'Guild Glory Simulator'],
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
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}
?>