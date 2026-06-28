<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Guild Glory</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <script>
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
        const IS_LOGGED_IN = <?php echo isset($_SESSION['admin_id']) ? 'true' : 'false'; ?>;
    </script>
    <script src="js/matrix.js"></script>

    <!-- Login Screen -->
    <div id="login-screen" class="container <?php echo isset($_SESSION['admin_id']) ? 'hidden' : ''; ?>">
        <div class="glass-panel">
            <h2>Admin Terminal Access</h2>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="login-user" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="login-pass">
            </div>
            <button id="btn-login">Initialize Connection</button>
            <div id="login-error" class="terminal-error text-center" style="margin-top:1rem; display:none;"></div>
        </div>
    </div>

    <!-- Admin Dashboard -->
    <div id="admin-dashboard" class="admin-container <?php echo isset($_SESSION['admin_id']) ? '' : 'hidden'; ?>">
        <div class="flex justify-between items-center" style="margin-bottom:2rem;">
            <h2>System Core Dashboard</h2>
            <button id="btn-logout" class="btn-small btn-danger">Terminate Session</button>
        </div>

        <div class="stats-grid" id="stats-container">
            <!-- Stats inserted via JS -->
        </div>

        <div class="flex" style="gap:2rem;">
            <!-- Left Column: Keys -->
            <div class="glass-panel" style="flex:2;">
                <div class="flex justify-between items-center">
                    <h3>Access Keys</h3>
                    <button id="btn-generate-key" class="btn-small">Generate New Key</button>
                </div>

                <div style="margin:1rem 0; display:flex; gap:1rem;">
                    <select id="key-filter" style="width:auto;">
                        <option value="all">All Keys</option>
                        <option value="active">Active</option>
                        <option value="used">Used</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Key</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="keys-table-body">
                            <!-- Keys injected here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Column: Settings & Logs -->
            <div style="flex:1; display:flex; flex-direction:column; gap:2rem;">
                <!-- Settings -->
                <div class="glass-panel">
                    <h3>System Settings</h3>
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" id="set-site-name">
                    </div>
                    <div class="form-group">
                        <label>Expiry Duration (Days)</label>
                        <input type="text" id="set-expiry-duration">
                    </div>
                    <div class="form-group">
                        <label>Matrix Background</label>
                        <select id="set-matrix">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sound Effects</label>
                        <select id="set-sound">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <button id="btn-save-settings" class="btn-small">Save Settings</button>
                </div>

                <!-- Deployment Logs -->
                <div class="glass-panel" style="flex:1;">
                    <h3>Recent Deployments</h3>
                    <div style="overflow-x:auto; max-height:400px;">
                        <table style="font-size:0.9rem;">
                            <thead>
                                <tr>
                                    <th>Guild</th>
                                    <th>Server</th>
                                    <th>Plan</th>
                                    <th>Key Used</th>
                                </tr>
                            </thead>
                            <tbody id="sims-table-body">
                                <!-- Sims injected here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>