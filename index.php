<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guild Glory</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <script>
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
    </script>
    <script src="js/matrix.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <audio id="terminal-sound" src="sound.ogg" preload="auto"></audio>

    <div class="container">
        <!-- Step 1: Access Terminal -->
        <div id="step-1" class="step active glass-panel">
            <h2>Access Terminal</h2>
            <div id="access-terminal" class="terminal blinking-cursor">
                <div class="terminal-line">Waiting for input...</div>
            </div>
            <div class="form-group">
                <label for="access-key">Enter Access Key</label>
                <input type="text" id="access-key" placeholder="GK-XXXX-XXXX-XXXX" autocomplete="off">
            </div>
            <button id="btn-validate">Authenticate</button>
        </div>

        <!-- Step 2: Guild Information -->
        <div id="step-2" class="step glass-panel">
            <h2>Guild Configuration</h2>
            <div class="form-group">
                <label for="guild-id">Guild ID</label>
                <input type="text" id="guild-id" placeholder="Enter Guild ID">
            </div>
            <div class="form-group">
                <label for="server-select">Server Region</label>
                <select id="server-select">
                    <option value="Global">Global</option>
                    <option value="India">India</option>
                    <option value="Singapore">Singapore</option>
                    <option value="Brazil">Brazil</option>
                    <option value="Europe">Europe</option>
                    <option value="North America">North America</option>
                    <option value="South America">South America</option>
                    <option value="Middle East">Middle East</option>
                    <option value="Thailand">Thailand</option>
                    <option value="Vietnam">Vietnam</option>
                    <option value="Other available regions">Other available regions</option>
                </select>
            </div>
            <button id="btn-next-plan">Next Step</button>
        </div>

        <!-- Step 3: Plan Selection -->
        <div id="step-3" class="step glass-panel">
            <h2>Select Deployment Plan</h2>
            <div class="plans-grid">
                <div class="plan-card" data-plan="1"><h3>1 Bot</h3></div>
                <div class="plan-card" data-plan="2"><h3>2 Bots</h3></div>
                <div class="plan-card" data-plan="4"><h3>4 Bots</h3></div>
                <div class="plan-card" data-plan="8"><h3>8 Bots</h3></div>
                <div class="plan-card" data-plan="16"><h3>16 Bots</h3></div>
                <div class="plan-card" data-plan="32"><h3>32 Bots</h3></div>
            </div>
            <button id="btn-start-sim" disabled>Start Deployment</button>
        </div>

        <!-- Step 4: Deployment -->
        <div id="step-4" class="step glass-panel">
            <div class="flex justify-between items-center mb-2">
                <h2>Deployment Sequence</h2>
                <button id="btn-toggle-sound" class="btn-small">Mute Sound</button>
            </div>
            <div id="sim-terminal" class="terminal blinking-cursor"></div>
            <div class="progress-container">
                <div id="sim-progress" class="progress-bar"></div>
                <div id="sim-progress-text" class="progress-text">0%</div>
            </div>
        </div>

        <!-- Step 5: Final Success Screen -->
        <div id="step-5" class="step glass-panel text-center">
            <h2 class="terminal-success">✅ Process Completed Successfully</h2>
            <div class="terminal" style="text-align: left; margin: 2rem 0;">
                <div class="terminal-line">Guild ID: <span id="out-guild" class="terminal-info"></span></div>
                <div class="terminal-line">Server: <span id="out-server" class="terminal-info"></span></div>
                <div class="terminal-line">Plan: <span id="out-plan" class="terminal-info"></span></div>
                <br>
                <div class="terminal-line">Your request has been submitted successfully.</div>
                <br>
                <div class="terminal-line">The Glory Bot will attempt to join the clan within 2–3 hours. Due to high demand and a large number of pending requests, processing may take longer than expected.</div>
                <br>
                <div class="terminal-line">If the bot does not join the clan, we apologize for the inconvenience. The service may be experiencing heavy usage and the bot could be temporarily busy handling other requests.</div>
                <br>
                <div class="terminal-line terminal-success">Thank you for your patience.</div>
            </div>
            <button onclick="location.reload()">Restart Terminal</button>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>