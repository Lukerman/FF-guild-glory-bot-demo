<?php
require_once 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only verify CSRF on state-changing actions
    if ($action !== 'validate_key' && $action !== 'login' && $action !== 'log_smartlink') {
        verify_csrf();
    }

    // Rate Limiting (Simple session-based)
    $rate_limit_key = 'rate_limit_' . $action;
    $time_now = time();
    if (isset($_SESSION[$rate_limit_key]) && $time_now - $_SESSION[$rate_limit_key] < 1) { // 1 request per second per action
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait.']);
        exit;
    }
    $_SESSION[$rate_limit_key] = $time_now;

    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Rate Limiting specific for login
        $login_attempts_key = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
        $attempts = $_SESSION[$login_attempts_key] ?? 0;

        if ($attempts >= 5) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Too many failed login attempts. Please try again later.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            unset($_SESSION[$login_attempts_key]); // Reset attempts on successful login
            echo json_encode(['success' => true]);
        } else {
            $_SESSION[$login_attempts_key] = $attempts + 1;
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
        exit;
    }

    if ($action === 'validate_key') {
        $key = $_POST['access_key'] ?? '';

        // Simulating some processing time for realism
        sleep(1);

        $stmt = $db->prepare("SELECT * FROM access_keys WHERE access_key = ?");
        $stmt->execute([$key]);
        $keyData = $stmt->fetch();

        if (!$keyData) {
            echo json_encode(['success' => false, 'error' => 'Invalid Access Key']);
            exit;
        }

        if ($keyData['is_used'] == 1) {
            echo json_encode(['success' => false, 'error' => 'Key has already been used']);
            exit;
        }

        if ($keyData['is_active'] == 0) {
            echo json_encode(['success' => false, 'error' => 'Key is disabled']);
            exit;
        }

        if ($keyData['expires_at'] && strtotime($keyData['expires_at']) < time()) {
            echo json_encode(['success' => false, 'error' => 'Key has expired']);
            exit;
        }

        // Store validated key in session for simulation step
        $_SESSION['validated_key'] = $key;
        echo json_encode(['success' => true, 'message' => 'Access Granted']);
        exit;
    }

    if ($action === 'simulate') {
        $guild_id = $_POST['guild_id'] ?? '';
        $server = $_POST['server'] ?? '';
        $plan = $_POST['plan'] ?? '';
        $key = $_SESSION['validated_key'] ?? '';

        if (!$key || !$guild_id || !$server || !$plan) {
            echo json_encode(['success' => false, 'error' => 'Missing simulation parameters']);
            exit;
        }

        // Limit inputs to reasonable lengths
        $guild_id = substr($guild_id, 0, 100);
        $server = substr($server, 0, 50);
        $plan = substr($plan, 0, 50);

        // Verify key again just in case
        $stmt = $db->prepare("SELECT * FROM access_keys WHERE access_key = ? AND is_used = 0 AND is_active = 1");
        $stmt->execute([$key]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired key during simulation']);
            exit;
        }

        // Mark key as used
        $stmt = $db->prepare("UPDATE access_keys SET is_used = 1 WHERE access_key = ?");
        $stmt->execute([$key]);

        // Log simulation
        $stmt = $db->prepare("INSERT INTO simulations (guild_id, server, plan, access_key) VALUES (?, ?, ?, ?)");
        $stmt->execute([$guild_id, $server, $plan, $key]);

        unset($_SESSION['validated_key']);
        echo json_encode(['success' => true]);
        exit;
    }

    // --- ADMIN ACTIONS (Require Authentication) ---
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'generate_key') {
        // Generate key GK-XXXX-XXXX-XXXX
        $parts = [];
        for ($i=0; $i<3; $i++) {
            $parts[] = strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4));
        }
        $newKey = "GK-" . implode("-", $parts);

        // Get expiry duration from settings
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'expiry_duration_days'");
        $stmt->execute();
        $days = (int)$stmt->fetchColumn() ?: 30;

        $expiresAt = date('Y-m-d H:i:s', strtotime("+$days days"));

        $stmt = $db->prepare("INSERT INTO access_keys (access_key, expires_at) VALUES (?, ?)");
        if ($stmt->execute([$newKey, $expiresAt])) {
            echo json_encode(['success' => true, 'key' => $newKey]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to generate key']);
        }
        exit;
    }

    if ($action === 'toggle_key') {
        $id = $_POST['id'] ?? 0;
        $active = $_POST['active'] ?? 1;
        $stmt = $db->prepare("UPDATE access_keys SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_key') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM access_keys WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_settings') {
        $site_name = $_POST['site_name'] ?? 'Guild Glory';
        $expiry_duration = $_POST['expiry_duration_days'] ?? '30';
        $matrix = $_POST['matrix_background'] ?? '0';
        $sound = $_POST['sound_effects'] ?? '0';

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_name'");
        $stmt->execute([$site_name]);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'expiry_duration_days'");
        $stmt->execute([$expiry_duration]);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'matrix_background'");
        $stmt->execute([$matrix]);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'sound_effects'");
        $stmt->execute([$sound]);

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_smartlink') {
        $url = $_POST['smartlink_url'] ?? '';
        $enabled = $_POST['smartlink_enabled'] ?? '0';
        $triggers = $_POST['smartlink_triggers'] ?? '{"validate":false,"next":false,"start":false,"copy":false}';

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'smartlink_url'");
        $stmt->execute([$url]);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'smartlink_enabled'");
        $stmt->execute([$enabled]);

        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'smartlink_triggers'");
        $stmt->execute([$triggers]);

        echo json_encode(['success' => true]);
        exit;
    }
}

// Public endpoint to log smartlink clicks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'log_smartlink') {
    // We don't require CSRF for logging the smartlink clicks so that it reliably fires
    $source = $_POST['source'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO smartlink_logs (ip_address, trigger_source) VALUES (?, ?)");
    $stmt->execute([$ip, $source]);
    echo json_encode(['success' => true]);
    exit;
}

// GET Requests for data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_settings') {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(['success' => true, 'data' => $settings]);
        exit;
    }


    // --- ADMIN GET ACTIONS ---
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if ($action === 'get_stats') {
        $stats = [];
        $stats['total_keys'] = $db->query("SELECT COUNT(*) FROM access_keys")->fetchColumn();
        $stats['active_keys'] = $db->query("SELECT COUNT(*) FROM access_keys WHERE is_active = 1 AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP")->fetchColumn();
        $stats['expired_keys'] = $db->query("SELECT COUNT(*) FROM access_keys WHERE expires_at <= CURRENT_TIMESTAMP")->fetchColumn();
        $stats['used_keys'] = $db->query("SELECT COUNT(*) FROM access_keys WHERE is_used = 1")->fetchColumn();
        $stats['total_simulations'] = $db->query("SELECT COUNT(*) FROM simulations")->fetchColumn();

        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    }

    if ($action === 'get_keys') {
        $filter = $_GET['filter'] ?? 'all';
        $query = "SELECT * FROM access_keys";

        if ($filter === 'active') {
            $query .= " WHERE is_active = 1 AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP";
        } elseif ($filter === 'used') {
            $query .= " WHERE is_used = 1";
        } elseif ($filter === 'expired') {
            $query .= " WHERE expires_at <= CURRENT_TIMESTAMP";
        }

        $query .= " ORDER BY created_at DESC";
        $stmt = $db->query($query);
        $keys = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $keys]);
        exit;
    }

    if ($action === 'get_simulations') {
        $stmt = $db->query("SELECT * FROM simulations ORDER BY created_at DESC LIMIT 50");
        $sims = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $sims]);
        exit;
    }

    if ($action === 'get_smartlink_logs') {
        $stmt = $db->query("SELECT * FROM smartlink_logs ORDER BY created_at DESC LIMIT 50");
        $logs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $logs]);
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
