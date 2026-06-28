document.addEventListener('DOMContentLoaded', () => {
    // Load Settings
    fetch('api.php?action=get_settings')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.title = data.data.site_name;
                toggleMatrix(data.data.matrix_background === '1');
                window.soundEnabled = data.data.sound_effects === '1';

                const btnToggleSound = document.getElementById('btn-toggle-sound');
                if (btnToggleSound) {
                    btnToggleSound.textContent = window.soundEnabled ? 'Mute Sound' : 'Enable Sound';
                }
            }
        });

    const accessKeyInput = document.getElementById('access-key');
    const btnValidate = document.getElementById('btn-validate');
    const accessTerminal = document.getElementById('access-terminal');

    const guildIdInput = document.getElementById('guild-id');
    const serverSelect = document.getElementById('server-select');
    const btnNextPlan = document.getElementById('btn-next-plan');

    const planCards = document.querySelectorAll('.plan-card');
    const btnStartSim = document.getElementById('btn-start-sim');

    const simTerminal = document.getElementById('sim-terminal');
    const simProgress = document.getElementById('sim-progress');
    const simProgressText = document.getElementById('sim-progress-text');

    let selectedPlan = null;

    function playSound() {
        if (window.soundEnabled) {
            const audio = document.getElementById('terminal-sound');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(e => console.log('Audio play failed:', e));
            }
        }
    }

    function appendTerminal(terminal, text, className = '') {
        const line = document.createElement('div');
        line.className = `terminal-line ${className}`;
        terminal.appendChild(line);
        terminal.scrollTop = terminal.scrollHeight;

        let i = 0;
        terminal.classList.remove('blinking-cursor');
        playSound();
        return new Promise(resolve => {
            function type() {
                if (i < text.length) {
                    line.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 10 + Math.random() * 30);
                } else {
                    terminal.classList.add('blinking-cursor');
                    resolve();
                }
            }
            type();
        });
    }

    function showStep(stepNum) {
        document.querySelectorAll('.step').forEach(el => el.classList.remove("active"));
        document.getElementById(`step-${stepNum}`).classList.add("active");
    }

    // Step 1: Validate Key
    btnValidate.addEventListener('click', async () => {
        const key = accessKeyInput.value.trim();
        if (!key) return;

        btnValidate.disabled = true;
        accessTerminal.innerHTML = '';

        await appendTerminal(accessTerminal, 'Checking Access Key...');

        try {
            const fd = new FormData();
            fd.append('access_key', key);

            const res = await fetch(`api.php?action=validate_key`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: fd
            });
            const data = await res.json();

            await appendTerminal(accessTerminal, 'Connecting Database...');
            await appendTerminal(accessTerminal, 'Verifying Credentials...');

            if (data.success) {
                await appendTerminal(accessTerminal, '[✓] Access Granted', 'terminal-success');
                setTimeout(() => showStep(2), 1000);
            } else {
                await appendTerminal(accessTerminal, `[✗] ${data.error}`, 'terminal-error');
                await appendTerminal(accessTerminal, '[✗] Access Denied', 'terminal-error');
                btnValidate.disabled = false;
            }
        } catch (e) {
            await appendTerminal(accessTerminal, '[✗] Connection Error', 'terminal-error');
            btnValidate.disabled = false;
        }
    });

    // Step 2: To Plan Selection
    btnNextPlan.addEventListener('click', () => {
        if (guildIdInput.value.trim() === '') {
            alert('Please enter Guild ID');
            return;
        }
        showStep(3);
    });

    // Step 3: Select Plan
    planCards.forEach(card => {
        card.addEventListener('click', () => {
            planCards.forEach(c => c.classList.remove('active'));
            card.classList.add("active");
            selectedPlan = card.dataset.plan;
            btnStartSim.disabled = false;
        });
    });

    const btnToggleSound = document.getElementById('btn-toggle-sound');

    if (btnToggleSound) {
        btnToggleSound.addEventListener('click', () => {
            window.soundEnabled = !window.soundEnabled;
            btnToggleSound.textContent = window.soundEnabled ? 'Mute Sound' : 'Enable Sound';
        });
    }

    // Step 4: Simulation
    btnStartSim.addEventListener('click', async () => {
        showStep(4);

        const messages = [
            "Establishing secure connection...",
            "Verifying Guild Information...",
            "Connecting to Region Server...",
            "Initializing Bot Network...",
            "Synchronizing Nodes...",
            "Loading Deployment Modules...",
            "Allocating Resources...",
            "Running Diagnostics...",
            "Activating Simulation...",
            "Finalizing Deployment...",
            "Operation Completed"
        ];

        const duration = 8000;
        const msgInterval = duration / messages.length;
        let progress = 0;

        const startTime = Date.now();

        const progressInterval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            progress = Math.min((elapsed / duration) * 100, 100);
            simProgress.style.width = `${progress}%`;
            simProgressText.textContent = `${Math.floor(progress)}%`;
        }, 50);

        for (const msg of messages) {
            // Typing takes time, so we don't wait for the full msgInterval
            // Instead we just wait a small amount so the whole sequence is ~8s
            appendTerminal(simTerminal, `[✓] ${msg}`, 'terminal-info');
            await new Promise(r => setTimeout(r, msgInterval));
        }

        clearInterval(progressInterval);
        simProgress.style.width = '100%';
        simProgressText.textContent = '100%';

        // Send API request to log simulation
        const fd = new FormData();
        fd.append('guild_id', guildIdInput.value);
        fd.append('server', serverSelect.value);
        fd.append('plan', `${selectedPlan} Bots`);

        await fetch('api.php?action=simulate', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: fd
        });

        setTimeout(() => {
            document.getElementById('out-guild').textContent = guildIdInput.value;
            document.getElementById('out-server').textContent = serverSelect.value;
            document.getElementById('out-plan').textContent = `${selectedPlan} Bots`;
            showStep(5);

            // Fire confetti
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#00ff00', '#008800', '#ffffff']
                });
            }
        }, 500);
    });
});
