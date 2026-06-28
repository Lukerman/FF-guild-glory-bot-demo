<?php
/**
 * Universal Debugger for Guild Glory Simulator
 * WARNING: Do NOT deploy this file to a production environment.
 * It grants full visibility into the system, database, and logs.
 */

require_once 'db.php';

// Handle AJAX actions
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    header('Content-Type: application/json');

    if ($action === 'clear_log') {
        file_put_contents(__DIR__ . '/error.log', '');
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'drop_db') {
        if (file_exists($db_file)) {
            unlink($db_file);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'run_query') {
        $query = $_POST['query'] ?? '';
        try {
            $stmt = $db->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    exit;
}

// Fetch basic info
$php_version = phpversion();
$sqlite_version = $db->query('select sqlite_version()')->fetchColumn();
$log_contents = file_exists(__DIR__ . '/error.log') ? file_get_contents(__DIR__ . '/error.log') : 'No log file found.';
$session_data = print_r($_SESSION, true);

// Fetch tables
$tables = [];
try {
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $stmt->fetch()) {
        $tables[] = $row['name'];
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universal System Debugger</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
        }
        .header {
            background: #2d2d2d;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #007acc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { margin: 0; font-size: 1.5rem; color: #fff; }
        .danger-badge {
            background: #e51400;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #444;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #2d2d2d;
            border: 1px solid #444;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background: #1e1e1e;
            border-top: 3px solid #007acc;
            color: #fff;
            font-weight: bold;
        }
        .tab-content {
            display: none;
            background: #252526;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #444;
        }
        .tab-content.active {
            display: block;
        }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #444;
            overflow-x: auto;
            color: #dcdcaa;
        }
        .card {
            background: #2d2d2d;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        button {
            background: #007acc;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover { background: #0098ff; }
        button.danger { background: #e51400; }
        button.danger:hover { background: #ff2511; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
        }
        th { background: #333; }
        textarea {
            width: 100%;
            height: 100px;
            background: #1e1e1e;
            color: #d4d4d4;
            border: 1px solid #444;
            padding: 10px;
            font-family: monospace;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Universal System Debugger</h1>
        <span class="danger-badge">DEVELOPMENT ONLY - DO NOT DEPLOY TO PROD</span>
    </div>

    <div class="tabs">
        <div class="tab active" data-target="sysinfo">System Info</div>
        <div class="tab" data-target="logs">Error Logs</div>
        <div class="tab" data-target="session">Session Data</div>
        <div class="tab" data-target="db">Database Explorer</div>
        <div class="tab" data-target="actions">Quick Actions</div>
    </div>

    <!-- System Info Tab -->
    <div id="sysinfo" class="tab-content active">
        <div class="grid">
            <div class="card">
                <h3>Environment</h3>
                <p><strong>PHP Version:</strong> <?php echo htmlspecialchars($php_version); ?></p>
                <p><strong>SQLite Version:</strong> <?php echo htmlspecialchars($sqlite_version); ?></p>
                <p><strong>Document Root:</strong> <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?></p>
                <p><strong>Database Path:</strong> <?php echo htmlspecialchars($db_file); ?></p>
            </div>
            <div class="card">
                <h3>Request Headers</h3>
                <pre><?php print_r(getallheaders()); ?></pre>
            </div>
        </div>
    </div>

    <!-- Logs Tab -->
    <div id="logs" class="tab-content">
        <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
            <h3>PHP Error Log</h3>
            <button onclick="clearLogs()" class="danger">Clear Logs</button>
        </div>
        <pre id="log-content"><?php echo htmlspecialchars($log_contents); ?></pre>
    </div>

    <!-- Session Tab -->
    <div id="session" class="tab-content">
        <h3>Current Session State</h3>
        <pre><?php echo htmlspecialchars($session_data); ?></pre>
    </div>

    <!-- Database Tab -->
    <div id="db" class="tab-content">
        <h3>Database Explorer</h3>
        <p>Available Tables: <?php echo implode(', ', $tables); ?></p>

        <div class="card">
            <h4>SQL Query Runner</h4>
            <textarea id="sql-query" placeholder="SELECT * FROM admins;"></textarea>
            <button onclick="runQuery()">Execute Query</button>

            <div id="query-results" style="margin-top: 20px;"></div>
        </div>
    </div>

    <!-- Actions Tab -->
    <div id="actions" class="tab-content">
        <h3>Quick Fixes & Actions</h3>
        <div class="grid">
            <div class="card">
                <h4>Reset Database</h4>
                <p>Deletes the current SQLite file. It will be recreated on the next page load.</p>
                <button onclick="dropDb()" class="danger">Delete Database</button>
            </div>
        </div>
    </div>

    <script>
        // Tab switching logic
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(tab.dataset.target).classList.add('active');
            });
        });

        async function clearLogs() {
            if(!confirm("Are you sure you want to clear the error log?")) return;
            const res = await fetch('debugger.php?action=clear_log', { method: 'POST' });
            const data = await res.json();
            if(data.success) {
                document.getElementById('log-content').textContent = '';
                alert("Logs cleared.");
            }
        }

        async function dropDb() {
            if(!confirm("DANGER! This will delete the entire database. Are you absolutely sure?")) return;
            const res = await fetch('debugger.php?action=drop_db', { method: 'POST' });
            const data = await res.json();
            if(data.success) {
                alert("Database deleted. Reload the main app to recreate it.");
            }
        }

        async function runQuery() {
            const query = document.getElementById('sql-query').value;
            const resContainer = document.getElementById('query-results');
            resContainer.innerHTML = 'Executing...';

            const fd = new FormData();
            fd.append('query', query);

            try {
                const res = await fetch('debugger.php?action=run_query', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    if (data.data.length === 0) {
                        resContainer.innerHTML = '<p>Query executed successfully. 0 rows returned.</p>';
                        return;
                    }

                    let html = '<table><thead><tr>';
                    Object.keys(data.data[0]).forEach(key => {
                        html += `<th>${key}</th>`;
                    });
                    html += '</tr></thead><tbody>';

                    data.data.forEach(row => {
                        html += '<tr>';
                        Object.values(row).forEach(val => {
                            html += `<td>${val}</td>`;
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    resContainer.innerHTML = html;
                } else {
                    resContainer.innerHTML = `<p style="color:#e51400">Error: ${data.error}</p>`;
                }
            } catch (e) {
                resContainer.innerHTML = `<p style="color:#e51400">Request Error</p>`;
            }
        }
    </script>
</body>
</html>