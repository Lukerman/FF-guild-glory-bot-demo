<?php
require_once 'db.php';

$message = '';
$generatedKey = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    verify_csrf();

    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Check limit (max 2 per day)
    $stmt = $db->prepare("SELECT COUNT(*) FROM token_generation_log WHERE ip_address = ? AND date(created_at) = date('now')");
    $stmt->execute([$ip_address]);
    $count = $stmt->fetchColumn();

    if ($count >= 2) {
        $message = "You have reached your daily limit of 2 access tokens.";
    } else {
        // Generate key
        $parts = [];
        for ($i=0; $i<3; $i++) {
            $parts[] = strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4));
        }
        $newKey = "GK-" . implode("-", $parts);

        // Get expiry duration
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'expiry_duration_days'");
        $stmt->execute();
        $days = (int)$stmt->fetchColumn() ?: 30;

        $expiresAt = date('Y-m-d H:i:s', strtotime("+$days days"));

        $stmt = $db->prepare("INSERT INTO access_keys (access_key, expires_at) VALUES (?, ?)");
        if ($stmt->execute([$newKey, $expiresAt])) {
            // Log IP
            $stmt = $db->prepare("INSERT INTO token_generation_log (ip_address) VALUES (?)");
            $stmt->execute([$ip_address]);

            $generatedKey = $newKey;
            $message = "Access token generated successfully!";
        } else {
            $message = "Failed to generate token. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Access Token - Guild Glory</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <script>
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
    </script>
    <script src="js/matrix.js"></script>

    <div class="container">
        <div class="glass-panel text-center">
            <h2>Public Access Token Generator</h2>
            <p style="margin-bottom: 20px; color: var(--text-dim);">Generate a token to access the deployment system. Limit: 2 per day.</p>

            <?php if ($message): ?>
                <div class="terminal <?php echo $generatedKey ? 'terminal-success' : 'terminal-error'; ?>" style="margin-bottom: 20px;">
                    <?php if ($generatedKey): ?>
                        <div class="terminal-line">Congratulations! You have successfully generated an access key.</div>
                        <div class="terminal-line" style="font-size: 1.5em; margin-top: 10px;" id="generated-key-text"><?php echo htmlspecialchars($generatedKey); ?></div>
                        <button class="btn-small" style="margin-top: 15px;" onclick="copyKey()">Click Here to Copy</button>
                    <?php else: ?>
                        <div class="terminal-line"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>

            <button id="btn-generate-public">Generate Token</button>

            <div style="margin-top: 20px;">
                <a href="/" style="color: var(--accent); text-decoration: none;">Return to Terminal</a>
            </div>
        </div>
    </div>

    <script>
        let smartlinkData = { enabled: false, url: '', triggers: {} };

        function triggerSmartlink(source) {
            if (smartlinkData.enabled && smartlinkData.url && smartlinkData.triggers[source]) {
                window.open(smartlinkData.url, '_blank');
                const fd = new FormData();
                fd.append('source', source);
                fetch('api.php?action=log_smartlink', {
                    method: 'POST',
                    body: fd
                });
            }
        }

        function copyKey() {
            triggerSmartlink('copy');
            const keyText = document.getElementById('generated-key-text').innerText;
            navigator.clipboard.writeText(keyText).then(() => {
                alert('Access key copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }

        // Load Matrix Setting
        fetch('api.php?action=get_settings')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toggleMatrix(data.data.matrix_background === '1');
                    if (data.data.smartlink_enabled === '1' && data.data.smartlink_url) {
                        smartlinkData.enabled = true;
                        smartlinkData.url = data.data.smartlink_url;
                        try {
                            smartlinkData.triggers = JSON.parse(data.data.smartlink_triggers);
                        } catch(e) {}
                    }
                }
            });

        document.getElementById('btn-generate-public').addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'generate';
            input.value = '1';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = CSRF_TOKEN;

            form.appendChild(input);
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        });
    </script>
</body>
</html>