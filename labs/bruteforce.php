<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// AJAX Endpoint for password verification
if (isset($_GET['action']) && $_GET['action'] === 'verify') {
    header('Content-Type: application/json');
    $target = $_POST['target'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if targeting the current logged-in user
    if ($target === $_SESSION['user_name']) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if (password_verify($password, $user_data['password'])) {
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }

    // Default check against lab_targets
    $stmt = $conn->prepare("SELECT password FROM lab_targets WHERE username = ?");
    $stmt->bind_param("s", $target);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $data = $result->fetch_assoc();
    if ($password === $data['password']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// AJAX Endpoint for logging results
if (isset($_GET['action']) && $_GET['action'] === 'log') {
    header('Content-Type: application/json');
    $target = mysqli_real_escape_string($conn, $_POST['target'] ?? '');
    $type = mysqli_real_escape_string($conn, $_POST['type'] ?? 'Dictionary');
    $attempts = (int)($_POST['attempts'] ?? 0);
    $success = (int)($_POST['success'] ?? 0);
    $time = (float)($_POST['time'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO bruteforce_logs (user_id, target_username, attack_type, attempts, success, time_taken) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssid", $user_id, $target, $type, $attempts, $success, $time);
    $stmt->execute();

    echo json_encode(['status' => 'logged']);
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Real-time Brute Force Lab</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-dark": "#0a0c02",
                        "surface": "#12140a",
                        "neutral-dark": "#16190e",
                        "border-dim": "#23281b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        .terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .glass-panel {
            background: rgba(18, 20, 10, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }

        .glow-text {
            text-shadow: 0 0 10px rgba(160, 240, 0, 0.5);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #23281b;
            border-radius: 10px;
        }

        @keyframes scanline {
            0% {
                transform: translateY(-100%);
            }

            100% {
                transform: translateY(100%);
            }
        }

        .scanline {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(160, 240, 0, 0.1);
            animation: scanline 8s linear infinite;
            pointer-events: none;
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen terminal-grid selection:bg-primary selection:text-background-dark overflow-hidden">
    <div class="flex h-screen">
        <!-- Main Workspace -->
        <div class="flex-1 flex flex-col relative overflow-hidden">
            <div class="scanline"></div>

            <!-- Header -->
            <header class="shrink-0 px-8 py-6 border-b border-border-dim bg-background-dark/80 backdrop-blur-md flex items-center justify-between z-10">
                <div class="flex items-center gap-4">
                    <a href="../dashboard/dashboard.php" class="p-2 rounded-lg bg-surface border border-border-dim text-slate-400 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <div>
                        <h1 class="text-2xl font-black text-white italic uppercase tracking-tighter">Real-time <span class="text-primary glow-text">Brute Force</span></h1>
                        <p class="text-[10px] text-slate-500 font-mono uppercase tracking-[0.2em]">Live Simulation Environment</p>
                    </div>
                </div>
                <div id="status-indicator" class="flex items-center gap-3 px-4 py-2 rounded-full bg-slate-800/50 border border-slate-700 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <span class="size-2 bg-slate-500 rounded-full"></span> System Idle
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto custom-scrollbar">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

                    <!-- Left: Control Panel -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="glass-panel rounded-2xl p-6 border-primary/10">
                            <h3 class="text-white font-bold mb-6 flex items-center gap-2 text-xs uppercase tracking-widest">
                                <span class="material-symbols-outlined text-primary text-sm">settings_suggest</span>
                                Configuration
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Target Username</label>
                                    <select id="target_username" class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary appearance-none transition-all">
                                        <option value="admin">admin</option>
                                        <option value="root">root</option>
                                        <option value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"><?php echo htmlspecialchars($_SESSION['user_name']); ?> (You)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Payload Wordlist</label>
                                    <select id="payload_preset" onchange="loadPreset()"
                                        class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary appearance-none transition-all mb-4">
                                        <option value="custom">-- Custom Payload --</option>
                                        <option value="common">Common Passwords (Fast)</option>
                                        <option value="aggressive">Aggressive Dictionary</option>
                                        <option value="system">System Default</option>
                                    </select>
                                    <textarea id="wordlist" rows="8" placeholder="Enter passwords line by line..."
                                        class="w-full bg-background-dark/50 border border-border-dim rounded-xl px-4 py-3 text-xs text-slate-400 font-mono custom-scrollbar resize-none focus:outline-none focus:border-primary transition-all"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Speed (ms)</label>
                                        <input type="number" id="delay" value="50" min="1" max="1000"
                                            class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-primary">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Auto-Lock</label>
                                        <input type="number" id="lockout" value="20" min="5" max="100"
                                            class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-primary">
                                    </div>
                                </div>

                                <button onclick="startAttack()" id="start-btn"
                                    class="w-full py-4 bg-primary text-background-dark font-black rounded-xl text-xs uppercase tracking-[0.2em] shadow-[0_0_20px_rgba(160,240,0,0.2)] hover:brightness-110 transition-all flex items-center justify-center gap-2 group">
                                    Initialize Intrusion <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">bolt</span>
                                </button>

                                <button onclick="stopAttack()" id="stop-btn" style="display:none;"
                                    class="w-full py-4 bg-red-600 text-white font-black rounded-xl text-xs uppercase tracking-[0.2em] hover:brightness-110 transition-all flex items-center justify-center gap-2">
                                    Abort Attack <span class="material-symbols-outlined text-sm">stop_circle</span>
                                </button>
                            </div>
                        </div>

                        <!-- Educational Alert (Phishing style) -->
                        <div id="edu-card" class="glass-panel rounded-2xl p-6 border-amber-500/20 bg-amber-500/5 hidden">
                            <div class="flex items-center gap-3 text-amber-500 mb-2">
                                <span class="material-symbols-outlined">lightbulb</span>
                                <h4 class="font-black text-[10px] uppercase tracking-widest">Analyst Briefing</h4>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-relaxed mb-4">
                                Brute force attacks rely on automation to guess passwords. Organizations defend against this using <strong class="text-slate-200">Rate Limiting</strong> and <strong class="text-slate-200">Account Lockout Policies</strong>.
                            </p>
                            <a href="../dashboard/dashboard.php?lab_completed=bruteforce" class="text-[10px] font-bold text-primary hover:underline flex items-center gap-1">
                                Complete Lab & Return <span class="material-symbols-outlined text-xs">arrow_forward</span>
                            </a>
                        </div>
                    </div>

                    <!-- Right: Real-time Console -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="glass-panel rounded-2xl flex flex-col h-[550px] overflow-hidden border-primary/20 bg-[#050702]/80">
                            <div class="bg-neutral-dark/80 px-6 py-3 border-b border-border-dim flex justify-between items-center text-[10px] font-mono">
                                <div class="flex gap-1.5">
                                    <div class="size-2 rounded-full bg-red-500/30"></div>
                                    <div class="size-2 rounded-full bg-yellow-500/30"></div>
                                    <div class="size-2 rounded-full bg-green-500/30"></div>
                                </div>
                                <div class="text-primary/70 uppercase tracking-[0.2em]">Intrusion Module: Live Feed</div>
                            </div>

                            <div id="terminal" class="flex-1 p-8 font-mono text-[11px] overflow-y-auto custom-scrollbar space-y-1">
                                <div class="text-slate-600 italic mb-4 uppercase tracking-widest text-[9px]">-- System Standby. Awaiting user input --</div>
                            </div>

                            <div class="px-6 py-3 bg-neutral-dark/40 border-t border-border-dim grid grid-cols-3 text-[9px] font-mono text-slate-500">
                                <div id="stat-attempts">ATTEMPTS: 0</div>
                                <div id="stat-speed" class="text-center">SPEED: 0 A/S</div>
                                <div id="stat-time" class="text-right">TIME: 0.00s</div>
                            </div>
                        </div>

                        <!-- Live Stats Card -->
                        <div id="result-card" class="glass-panel rounded-2xl p-8 border-primary/30 bg-primary/5 hidden animate-in fade-in zoom-in duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter">Attacker <span class="text-primary">Success</span></h4>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Vulnerability Correlation Complete</p>
                                </div>
                                <div class="size-14 rounded-full bg-primary/20 border border-primary/30 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined text-3xl">key</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <p class="text-slate-400 text-sm leading-relaxed">
                                        The password was cracked after <strong class="text-white" id="res-attempts">0</strong> attempts in <strong class="text-primary" id="res-time">0.00s</strong>.
                                    </p>
                                    <div class="p-4 bg-primary/10 border border-primary/30 rounded-xl mb-4">
                                        <p class="text-[11px] text-primary font-bold uppercase mb-1 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-xs">info</span> Educational Insight
                                        </p>
                                        <p class="text-[10px] text-slate-400 leading-relaxed italic">
                                            This simulation demonstrates how attackers use automated "wordlists" to try thousands of combinations. If a password matches one in common breach data, it can be hijacked in seconds.
                                        </p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-background-dark border border-border-dim flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Recovered Key</span>
                                        <span class="text-primary font-mono font-bold" id="res-pass">********</span>
                                    </div>
                                </div>
                                <div class="bg-surface/50 rounded-xl p-6 border border-border-dim">
                                    <h5 class="text-white text-xs font-bold uppercase mb-4 tracking-wider">Mitigation Summary</h5>
                                    <ul class="text-[10px] text-slate-500 space-y-3 font-medium">
                                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-xs">check</span>Enforce Multi-Factor Authentication</li>
                                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-xs">check</span>Implement Adaptive Rate Limiting</li>
                                        <li class="flex items-start gap-2"><span class="material-symbols-outlined text-primary text-xs">check</span>Minimum 12 Characters with Entropy</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const presets = {
            common: ["123456", "password", "12345678", "qwerty", "12345", "111111", "admin123", "letmein", "welcome", "security"],
            aggressive: ["qaz123", "wsx123", "edc123", "rfv123", "admin", "admin@123", "root", "toor", "P@ssword", "StrongPassword!"],
            system: ["test", "analyst", "C!berShield2026", "R00tSecure!", "root123", "guest", "operator"]
        };

        function loadPreset() {
            const val = document.getElementById('payload_preset').value;
            if (presets[val]) {
                document.getElementById('wordlist').value = presets[val].join('\n');
            }
        }

        let isRunning = false;
        let attempts = 0;
        let startTime;

        async function startAttack() {
            const target = document.getElementById('target_username').value.trim();
            const wordlist = document.getElementById('wordlist').value.split('\n').filter(p => p.trim() !== '');
            const delay = parseInt(document.getElementById('delay').value) || 50;
            const lockout = parseInt(document.getElementById('lockout').value) || 20;

            if (!target || wordlist.length === 0) {
                alert("Please provide a target and wordlist.");
                return;
            }

            isRunning = true;
            attempts = 0;
            startTime = Date.now();

            // UI States
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('stop-btn').style.display = 'flex';
            document.getElementById('terminal').innerHTML = `<div class="text-blue-400 mb-2">[INFO] Initializing Real-time Intrusion on ${target}...</div>`;
            document.getElementById('status-indicator').innerHTML = `<span class="size-2 bg-primary rounded-full animate-pulse"></span> Attack Active`;
            document.getElementById('status-indicator').classList.add('text-primary');
            document.getElementById('result-card').classList.add('hidden');
            document.getElementById('edu-card').classList.add('hidden');

            for (let pwd of wordlist) {
                if (!isRunning) break;

                attempts++;
                const isLockout = attempts > lockout;

                updateStats();

                const line = document.createElement('div');
                line.className = 'flex items-center gap-2 py-0.5';

                if (isLockout) {
                    line.innerHTML = `<span class="text-red-500 font-bold">[BLOCKED]</span> <span class="text-slate-600">Attempt for '${pwd}' rejected by server.</span>`;
                    document.getElementById('terminal').appendChild(line);
                    logFinal(target, 'Dictionary', attempts, 0, (Date.now() - startTime) / 1000);

                    const critical = document.createElement('div');
                    critical.className = 'text-red-500 font-black bg-red-500/10 p-2 rounded mt-2 uppercase tracking-tighter italic';
                    critical.innerText = '[CRITICAL] RATE LIMIT REACHED: ACCOUNT LOCKED BY SYSTEM';
                    document.getElementById('terminal').appendChild(critical);

                    stopAttack();
                    return;
                }

                line.innerHTML = `<span class="text-slate-500">[TRY]</span> <span class="text-slate-400">Testing ${pwd}...</span>`;
                document.getElementById('terminal').appendChild(line);
                document.getElementById('terminal').scrollTop = document.getElementById('terminal').scrollHeight;

                await new Promise(r => setTimeout(r, delay));

                const success = await verifyPassword(target, pwd);
                if (success) {
                    line.innerHTML = `<span class="text-primary font-bold">[SUCCESS]</span> <span class="text-white">Validation confirmed for '${pwd}'</span>`;

                    const timeTaken = (Date.now() - startTime) / 1000;
                    logFinal(target, 'Dictionary', attempts, 1, timeTaken);

                    showSuccess(pwd, attempts, timeTaken);
                    stopAttack();
                    return;
                } else {
                    line.querySelector('span:first-child').innerText = '[FAIL]';
                    line.querySelector('span:nth-child(2)').innerText = `Unauthorized: ${pwd}`;
                }
            }

            if (isRunning) {
                const info = document.createElement('div');
                info.className = 'text-yellow-500 mt-4 italic';
                info.innerText = '-- Simulation sequence exhausted. No key found. --';
                document.getElementById('terminal').appendChild(info);
                stopAttack();
            }
        }

        function stopAttack() {
            isRunning = false;
            document.getElementById('start-btn').style.display = 'flex';
            document.getElementById('stop-btn').style.display = 'none';
            document.getElementById('status-indicator').innerHTML = `<span class="size-2 bg-slate-500 rounded-full"></span> System Idle`;
            document.getElementById('status-indicator').classList.remove('text-primary');
        }

        async function verifyPassword(target, password) {
            try {
                const formData = new FormData();
                formData.append('target', target);
                formData.append('password', password);

                const res = await fetch('?action=verify', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                return data.success;
            } catch (e) {
                console.error(e);
                return false;
            }
        }

        async function logFinal(target, type, attempts, success, time) {
            const formData = new FormData();
            formData.append('target', target);
            formData.append('type', type);
            formData.append('attempts', attempts);
            formData.append('success', success);
            formData.append('time', time);

            fetch('?action=log', {
                method: 'POST',
                body: formData
            });
        }

        function updateStats() {
            const time = (Date.now() - startTime) / 1000;
            document.getElementById('stat-attempts').innerText = `ATTEMPTS: ${attempts}`;
            document.getElementById('stat-time').innerText = `TIME: ${time.toFixed(2)}s`;
            document.getElementById('stat-speed').innerText = `SPEED: ${Math.round(attempts / (time || 1))} A/S`;
        }

        function showSuccess(password, att, time) {
            document.getElementById('res-pass').innerText = password;
            document.getElementById('res-attempts').innerText = att;
            document.getElementById('res-time').innerText = time.toFixed(2) + 's';
            document.getElementById('result-card').classList.remove('hidden');
            document.getElementById('edu-card').classList.remove('hidden');
        }

        // Initialize with system default on load
        window.onload = loadPreset;
    </script>
</body>

</html>