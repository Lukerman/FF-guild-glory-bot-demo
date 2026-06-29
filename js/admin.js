document.addEventListener('DOMContentLoaded', () => {
    // Initial Matrix load
    fetch('api.php?action=get_settings')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                toggleMatrix(data.data.matrix_background === '1');
                if(IS_LOGGED_IN) {
                    document.getElementById('set-matrix').value = data.data.matrix_background;
                    document.getElementById('set-sound').value = data.data.sound_effects;
                    document.getElementById('set-site-name').value = data.data.site_name;
                    document.getElementById('set-expiry-duration').value = data.data.expiry_duration_days;

                    document.getElementById('set-smartlink-url').value = data.data.smartlink_url;
                    document.getElementById('set-smartlink-enabled').value = data.data.smartlink_enabled;

                    try {
                        const triggers = JSON.parse(data.data.smartlink_triggers);
                        document.getElementById('trigger-validate').checked = triggers.validate;
                        document.getElementById('trigger-next').checked = triggers.next;
                        document.getElementById('trigger-start').checked = triggers.start;
                        document.getElementById('trigger-copy').checked = triggers.copy;
                    } catch(e) {}
                }
            }
        });

    const loginScreen = document.getElementById('login-screen');
    const dashboardScreen = document.getElementById('admin-dashboard');

    // Login logic
    const btnLogin = document.getElementById('btn-login');
    if (btnLogin) {
        btnLogin.addEventListener('click', async () => {
            const user = document.getElementById('login-user').value;
            const pass = document.getElementById('login-pass').value;
            const err = document.getElementById('login-error');

            const fd = new FormData();
            fd.append('username', user);
            fd.append('password', pass);

            try {
                const res = await fetch('api.php?action=login', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    location.reload();
                } else {
                    err.textContent = data.error;
                    err.style.display = 'block';
                }
            } catch (e) {
                err.textContent = 'Connection error';
                err.style.display = 'block';
            }
        });
    }

    // Dashboard Logic
    if (IS_LOGGED_IN) {
        loadStats();
        loadKeys();
        loadSims();
        loadSmartLinkLogs();

        document.getElementById('btn-logout').addEventListener('click', async () => {
            await fetch('api.php?action=logout');
            location.reload();
        });

        document.getElementById('btn-generate-key').addEventListener('click', async () => {
            const fd = new FormData();
            const res = await fetch('api.php?action=generate_key', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
                body: fd
            });
            const data = await res.json();
            if(data.success) {
                alert(`New Key Generated:\n\n${data.key}`);
                loadKeys();
                loadStats();
            }
        });

        document.getElementById('key-filter').addEventListener('change', loadKeys);

        document.getElementById('btn-save-settings').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('site_name', document.getElementById('set-site-name').value);
            fd.append('expiry_duration_days', document.getElementById('set-expiry-duration').value);
            fd.append('matrix_background', document.getElementById('set-matrix').value);
            fd.append('sound_effects', document.getElementById('set-sound').value);

            await fetch('api.php?action=save_settings', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
                body: fd
            });

            alert('Settings saved!');
            toggleMatrix(document.getElementById('set-matrix').value === '1');
        });

        document.getElementById('btn-save-smartlink').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('smartlink_url', document.getElementById('set-smartlink-url').value);
            fd.append('smartlink_enabled', document.getElementById('set-smartlink-enabled').value);

            const triggers = {
                validate: document.getElementById('trigger-validate').checked,
                next: document.getElementById('trigger-next').checked,
                start: document.getElementById('trigger-start').checked,
                copy: document.getElementById('trigger-copy').checked
            };
            fd.append('smartlink_triggers', JSON.stringify(triggers));

            await fetch('api.php?action=save_smartlink', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
                body: fd
            });

            alert('SmartLink Settings saved!');
        });
    }

    async function loadStats() {
        const res = await fetch('api.php?action=get_stats');
        const data = await res.json();
        if(data.success) {
            const c = document.getElementById('stats-container');
            c.innerHTML = `
                <div class="stat-card"><h3>Total Keys</h3><div class="stat-value">${data.data.total_keys}</div></div>
                <div class="stat-card"><h3>Active Keys</h3><div class="stat-value" style="color:#0f0;">${data.data.active_keys}</div></div>
                <div class="stat-card"><h3>Used Keys</h3><div class="stat-value" style="color:#ff0;">${data.data.used_keys}</div></div>
                <div class="stat-card"><h3>Expired</h3><div class="stat-value" style="color:#f00;">${data.data.expired_keys}</div></div>
                <div class="stat-card"><h3>Deployments</h3><div class="stat-value" style="color:#0ff;">${data.data.total_simulations}</div></div>
            `;
        }
    }

    async function loadKeys() {
        const filter = document.getElementById('key-filter').value;
        const res = await fetch(`api.php?action=get_keys&filter=${filter}`);
        const data = await res.json();

        if (data.success) {
            const tbody = document.getElementById('keys-table-body');
            tbody.innerHTML = '';

            data.data.forEach(key => {
                const tr = document.createElement('tr');

                let statusBadge = '';
                if (key.is_used == 1) statusBadge = '<span class="badge badge-used">Used</span>';
                else if (new Date(key.expires_at) < new Date()) statusBadge = '<span class="badge badge-expired">Expired</span>';
                else if (key.is_active == 0) statusBadge = '<span class="badge badge-disabled">Disabled</span>';
                else statusBadge = '<span class="badge badge-active">Active</span>';

                tr.innerHTML = `
                    <td style="font-family:monospace; color:var(--accent);">${key.access_key}</td>
                    <td>${statusBadge}</td>
                    <td style="font-size:0.85rem;">${key.created_at}</td>
                    <td style="font-size:0.85rem;">${key.expires_at}</td>
                    <td>
                        <button class="btn-small btn-toggle" data-id="${key.id}" data-active="${key.is_active == 1 ? 0 : 1}">
                            ${key.is_active == 1 ? 'Disable' : 'Enable'}
                        </button>
                        <button class="btn-small btn-danger btn-delete" data-id="${key.id}">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Bind events for dynamically added buttons
            document.querySelectorAll('.btn-toggle').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const id = e.target.dataset.id;
                    const active = e.target.dataset.active;
                    const fd = new FormData();
                    fd.append('id', id);
                    fd.append('active', active);
                    await fetch('api.php?action=toggle_key', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
                        body: fd
                    });
                    loadKeys();
                    loadStats();
                });
            });

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    if(!confirm('Are you sure you want to delete this key?')) return;
                    const id = e.target.dataset.id;
                    const fd = new FormData();
                    fd.append('id', id);
                    await fetch('api.php?action=delete_key', {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': CSRF_TOKEN},
                        body: fd
                    });
                    loadKeys();
                    loadStats();
                });
            });
        }
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function loadSims() {
        const res = await fetch('api.php?action=get_simulations');
        const data = await res.json();

        if (data.success) {
            const tbody = document.getElementById('sims-table-body');
            tbody.innerHTML = '';

            data.data.forEach(sim => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHTML(sim.guild_id)}</td>
                    <td>${escapeHTML(sim.server)}</td>
                    <td>${escapeHTML(sim.plan)}</td>
                    <td style="font-family:monospace; font-size:0.8rem;">${escapeHTML(sim.access_key)}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    async function loadSmartLinkLogs() {
        const res = await fetch('api.php?action=get_smartlink_logs');
        const data = await res.json();

        if (data.success) {
            const tbody = document.getElementById('smartlink-table-body');
            tbody.innerHTML = '';

            data.data.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHTML(log.ip_address)}</td>
                    <td>${escapeHTML(log.trigger_source)}</td>
                    <td style="font-size:0.8rem;">${escapeHTML(log.created_at)}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }
});
