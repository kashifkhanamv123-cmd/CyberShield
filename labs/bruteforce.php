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
        // For simulation purposes, if not in DB, assume it's "admin" with "admin@123" if the user hasn't setup targets
        if ($target === 'admin' && $password === 'admin@123') {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
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
    $target = $_POST['target'] ?? '';
    $type = $_POST['type'] ?? 'Dictionary';
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
    <title>CyberShield | Brute Force Simulation Lab</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet" />
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
                        "sans": ["Inter", "sans-serif"],
                        "mono": ["JetBrains Mono", "monospace"]
                    }
                },
            },
        }
    </script>
    <style>
        :root {
            --primary-glow: rgba(160, 240, 0, 0.4);
        }

        body {
            background-color: #060802;
            background-image:
                radial-gradient(circle at 50% 50%, rgba(160, 240, 0, 0.05) 0%, transparent 50%),
                linear-gradient(rgba(18, 20, 10, 0.8) 1px, transparent 1px),
                linear-gradient(90deg, rgba(18, 20, 10, 0.8) 1px, transparent 1px);
            background-size: 100% 100%, 25px 25px, 25px 25px;
        }

        .glass-panel {
            background: rgba(18, 22, 12, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(160, 240, 0, 0.15);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        }

        .glow-text {
            text-shadow: 0 0 8px var(--primary-glow);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #a0f00044;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a0f00088;
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
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to bottom, transparent, rgba(160, 240, 0, 0.05), transparent);
            animation: scanline 10s linear infinite;
            pointer-events: none;
            z-index: 50;
        }

        .cyber-button {
            position: relative;
            background: linear-gradient(135deg, #a0f000 0%, #7dbb00 100%);
            color: #0a0c02;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%);
        }

        .cyber-button:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(160, 240, 0, 0.4);
            filter: brightness(1.1);
        }

        .input-field {
            background: rgba(10, 12, 2, 0.8);
            border: 1px solid #23281b;
            color: #fff;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #a0f000;
            box-shadow: 0 0 0 2px rgba(160, 240, 0, 0.1);
            outline: none;
        }

        .tag {
            font-size: 0.7rem;
            font-weight: 900;
            padding: 2px 6px;
            border-radius: 2px;
            text-transform: uppercase;
        }

        .tag-info {
            color: #a0f000;
            background: rgba(160, 240, 0, 0.1);
        }

        .tag-try {
            color: #5bc0de;
            background: rgba(91, 192, 222, 0.1);
        }

        .tag-fail {
            color: #ff4b2b;
            background: rgba(255, 75, 43, 0.1);
        }

        .tag-success {
            color: #a0f000;
            background: rgba(160, 240, 0, 0.2);
        }

        .progress-segment {
            height: 100%;
            background: #a0f000;
            box-shadow: 0 0 10px #a0f000;
            transition: width 0.3s ease-out;
        }
    </style>
</head>

<body class="text-slate-300 font-sans min-h-screen overflow-x-hidden selection:bg-primary selection:text-black">
    <div class="scanline"></div>

    <div class="px-4 md:px-6 py-4 border-b border-primary/20 bg-black/60 backdrop-blur-md flex flex-wrap gap-4 items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-4 md:gap-6">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary rounded flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black font-bold">shield</span>
                </div>
                <span class="text-xl font-black text-white tracking-tighter uppercase italic hidden sm:block">Cyber<span class="text-primary">Shield</span></span>
            </div>
            <div class="h-6 w-px bg-primary/20 hidden sm:block"></div>
            <div class="flex items-center gap-2 text-primary/80">
                <span class="material-symbols-outlined text-sm">grid_view</span>
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-widest truncate">Brute Force Lab</span>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-4 text-[10px] md:text-xs font-mono">
            <button onclick="window.location.href='../dashboard/dashboard.php'" class="px-3 py-2 bg-surface border border-primary/30 rounded text-primary hover:bg-primary/10 transition-all flex items-center gap-1 md:gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> <span class="hidden sm:inline">Back</span>
            </button>
            <div class="px-2 md:px-3 py-1 bg-surface border border-primary/30 rounded text-primary shrink-0">BF-01</div>
        </div>
    </div>

    <main class="max-w-[1600px] mx-auto p-6 space-y-6">
        <!-- Dashboard Header -->
        <div class="glass-panel rounded-lg p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-[120px] text-primary">security</span>
            </div>
            <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-2">Brute Force Attack Simulation</h2>
            <p class="text-sm text-slate-400 max-w-2xl leading-relaxed">
                Learn how brute force attacks work by simulating password cracking techniques in a controlled environment.
                <span class="text-primary/60 italic">For educational purposes only.</span>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Column 1: Attack Configuration -->
            <div class="lg:col-span-3 space-y-6">
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-6 border-l-4 border-primary pl-3">
                        <span class="material-symbols-outlined text-sm text-primary">chevron_right</span>
                        Attack Configuration
                    </h3>

                    <div class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Target Username:</label>
                            <div class="relative">
                                <select id="target_username" class="input-field w-full rounded p-2.5 appearance-none pr-10">
                                    <option value="admin">admin</option>
                                    <option value="root">root</option>
                                    <option value="user1">user1</option>
                                    <option value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"><?php echo htmlspecialchars($_SESSION['user_name']); ?></option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">expand_more</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Attack Type:</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 group cursor-pointer">
                                    <div class="relative flex items-center">
                                        <input type="radio" name="attack_type" value="Dictionary" checked onchange="loadPreset(this.value)" class="peer sr-only">
                                        <div class="w-4 h-4 border border-primary/40 rounded-full peer-checked:bg-primary transition-all"></div>
                                        <div class="absolute inset-0 m-auto w-1.5 h-1.5 bg-black rounded-full scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 group-hover:text-primary transition-colors">Dictionary Attack</span>
                                </label>
                                <label class="flex items-center gap-3 group cursor-pointer">
                                    <div class="relative flex items-center">
                                        <input type="radio" name="attack_type" value="Custom" onchange="loadPreset(this.value)" class="peer sr-only">
                                        <div class="w-4 h-4 border border-primary/40 rounded-full peer-checked:bg-primary transition-all"></div>
                                        <div class="absolute inset-0 m-auto w-1.5 h-1.5 bg-black rounded-full scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 group-hover:text-primary transition-colors">Custom Payload</span>
                                </label>
                                <label class="flex items-center gap-3 group cursor-pointer">
                                    <div class="relative flex items-center">
                                        <input type="radio" name="attack_type" value="Stuffing" onchange="loadPreset(this.value)" class="peer sr-only">
                                        <div class="w-4 h-4 border border-primary/40 rounded-full peer-checked:bg-primary transition-all"></div>
                                        <div class="absolute inset-0 m-auto w-1.5 h-1.5 bg-black rounded-full scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                    <span class="text-xs text-slate-300 group-hover:text-primary transition-colors">Credential Stuffing</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Custom Payloads:</label>
                            <textarea id="custom_payloads" rows="5" class="input-field w-full rounded p-3 font-mono text-xs custom-scrollbar resize-none" placeholder="123456&#10;password&#10;admin123"></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Wordlist Upload:</label>
                            <div class="flex">
                                <input type="file" id="wordlist_upload" class="hidden" accept=".txt">
                                <label for="wordlist_upload" class="flex-1 bg-surface border border-primary/20 rounded-l p-2 px-3 text-[10px] cursor-pointer hover:bg-primary/5 transition-colors border-r-0 truncate">
                                    <span id="file_name">No file chosen</span>
                                </label>
                                <button onclick="document.getElementById('wordlist_upload').click()" class="bg-primary/10 border border-primary/20 rounded-r px-4 text-[10px] font-bold text-primary hover:bg-primary/20 transition-colors">Choose File</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Max Attempts: <span id="max_attempts_display" class="text-primary italic">100</span></label>
                                <input type="range" id="max_attempts" min="10" max="500" value="100" step="10" class="w-full h-1 bg-surface rounded-lg appearance-none cursor-pointer accent-primary">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Speed: <span id="speed_display" class="text-primary italic">Normal</span></label>
                                <select id="attack_speed" class="input-field w-full rounded p-2 text-xs">
                                    <option value="slow">Slow (500ms)</option>
                                    <option value="normal" selected>Normal (100ms)</option>
                                    <option value="fast">Fast (20ms)</option>
                                    <option value="instant">Instant (5ms)</option>
                                </select>
                            </div>
                        </div>

                        <button onclick="startAttack()" id="start_btn" class="cyber-button w-full py-4 mt-4 flex items-center justify-center gap-2 group">
                            Start Simulation
                        </button>
                        <button onclick="stopAttack()" id="stop_btn" class="hidden cyber-button w-full py-4 mt-4 bg-red-600 border-none text-white flex items-center justify-center gap-2 group">
                            Stop Simulation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column 2: Attack Console & Metrics -->
            <div class="lg:col-span-6 space-y-6">
                <!-- Attack Console -->
                <div class="glass-panel rounded-lg overflow-hidden flex flex-col h-[500px]">
                    <div class="bg-neutral-dark/80 px-4 py-3 border-b border-primary/20 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-primary">eco</span>
                            <span class="text-[10px] font-black uppercase text-white tracking-widest">Attack Console</span>
                        </div>
                        <div class="flex gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-700"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-700"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-primary/40"></div>
                        </div>
                    </div>
                    <div id="console" class="flex-1 p-6 font-mono text-xs overflow-y-auto custom-scrollbar space-y-1 bg-black/40">
                        <div class="text-slate-500 italic mb-2">// System ready. Awaiting initialization...</div>
                    </div>
                </div>

                <!-- Attack Metrics -->
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-4">
                        <span class="material-symbols-outlined text-sm text-primary">timer</span>
                        Attack Metrics
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="flex items-center gap-2 text-[9px] font-bold text-slate-500 uppercase mb-1">
                                <span class="material-symbols-outlined text-xs">arrow_drop_up</span> Attempts
                            </div>
                            <div id="stat_attempts" class="text-xl font-bold text-white font-mono">0</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="flex items-center gap-2 text-[9px] font-bold text-slate-500 uppercase mb-1">
                                <span class="material-symbols-outlined text-xs">arrow_right</span> Payload Size
                            </div>
                            <div id="stat_payload" class="text-xl font-bold text-white font-mono">0</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="flex items-center gap-2 text-[9px] font-bold text-slate-500 uppercase mb-1">
                                <span class="material-symbols-outlined text-xs">arrow_right</span> Time Elapsed
                            </div>
                            <div id="stat_time" class="text-xl font-bold text-white font-mono">0.0s</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="flex items-center gap-2 text-[9px] font-bold text-slate-500 uppercase mb-1">
                                <span class="material-symbols-outlined text-xs">arrow_right</span> Speed
                            </div>
                            <div id="stat_speed" class="text-xl font-bold text-white font-mono">0 <span class="text-[10px]">att/s</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Results & Defense -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Results -->
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-6">
                        <span class="material-symbols-outlined text-sm text-primary">shield</span>
                        Results
                    </h3>
                    <div id="results_container" class="space-y-4 opacity-30">
                        <div class="flex justify-between items-center py-2 border-b border-primary/5">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Target:</span>
                            <span id="res_target" class="text-xs font-bold text-white">---</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-primary/5">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Password:</span>
                            <span id="res_password" class="text-xs font-bold text-primary italic">---</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-primary/5">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Attempts:</span>
                            <span id="res_attempts" class="text-xs font-bold text-white font-mono">---</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-primary/5">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Time Taken:</span>
                            <span id="res_time" class="text-xs font-bold text-white font-mono">---</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-[10px] text-slate-500 uppercase font-bold">Difficulty:</span>
                            <span id="res_difficulty" class="text-xs font-bold text-green-500 uppercase tracking-tighter">---</span>
                        </div>
                    </div>
                </div>

                <!-- Defense Strategies -->
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-4">
                        <span class="material-symbols-outlined text-sm text-primary">security</span>
                        Defense Strategies
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Why it was cracked:</p>
                            <div id="crack_reason" class="text-[11px] text-slate-500 italic">No attack data yet.</div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 mb-3">Prevention Tips:</p>
                            <ul class="space-y-2">
                                <li class="flex items-center gap-2 group">
                                    <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                    <span class="text-[10px] text-slate-400 group-hover:text-slate-200 transition-colors">Use Strong Passwords</span>
                                </li>
                                <li class="flex items-center gap-2 group">
                                    <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                    <span class="text-[10px] text-slate-400 group-hover:text-slate-200 transition-colors">Enable Account Lockout</span>
                                </li>
                                <li class="flex items-center gap-2 group">
                                    <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                    <span class="text-[10px] text-slate-400 group-hover:text-slate-200 transition-colors">Implement Rate Limiting</span>
                                </li>
                                <li class="flex items-center gap-2 group">
                                    <span class="material-symbols-outlined text-sm text-primary">check_circle</span>
                                    <span class="text-[10px] text-slate-400 group-hover:text-slate-200 transition-colors">Use Multi-Factor Authentication</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="glass-panel rounded-lg p-2 flex items-center gap-4">
            <div class="w-full h-2 bg-black/40 rounded overflow-hidden">
                <div id="master_progress" class="progress-segment w-0"></div>
            </div>
            <div class="text-[10px] font-mono text-primary font-bold min-w-[30px]" id="progress_percent">0%</div>
        </div>

        <div id="dashboard_return_btn" class="hidden flex justify-center mt-6">
            <button onclick="window.location.href='../dashboard/dashboard.php?lab_completed=bruteforce'" class="cyber-button px-10 py-5 text-lg">
                Finalize Results & Return to Dashboard
            </button>
        </div>
    </main>

    <div id="success_modal" class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-6 hidden backdrop-blur-xl">
        <div class="glass-panel max-w-lg w-full p-8 text-center space-y-6 animate-in zoom-in duration-300">
            <div class="w-20 h-20 bg-primary/20 rounded-full border border-primary flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-[40px] text-primary glow-text">key</span>
            </div>
            <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter">System Compromised</h3>
            <p class="text-sm text-slate-400">The attack was successful. The target user's credentials have been recovered and logged into the module registry.</p>

            <div class="p-4 bg-primary/10 border border-primary/20 rounded font-mono">
                <div class="text-[10px] text-primary uppercase font-bold mb-1">Recovered Password:</div>
                <div id="modal_password" class="text-xl text-white font-black tracking-widest">********</div>
            </div>

            <button onclick="acknowledgeSuccess()" class="cyber-button w-full py-4">
                Acknowledge Directive
            </button>
        </div>
    </div>

    <script>
        const presets = {
            Dictionary: ["123456", "password", "12345678", "qwerty", "12345", "111111", "admin123", "letmein", "welcome", "security", "1234567", "123123", "P@ssword", "StrongPassword!", "C!berShield2026", "R00tSecure!", "admin@123"],
            Custom: ["admin", "root", "toor", "user", "guest", "test", "analyst", "operator"],
            Stuffing: ["root:password", "admin:admin", "admin:password", "operator:123456", "analyst:C!berShield2026"]
        };

        const speedSettings = {
            slow: 500,
            normal: 100,
            fast: 20,
            instant: 5
        };

        let isRunning = false;
        let attempts = 0;
        let startTime;
        let interval;
        let currentWordlist = [];

        // Max attempts display update
        document.getElementById('max_attempts').oninput = function() {
            document.getElementById('max_attempts_display').innerText = this.value;
        };

        // File upload handling
        document.getElementById('wordlist_upload').onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('file_name').innerText = file.name;
                const reader = new FileReader();
                reader.onload = (event) => {
                    const content = event.target.result;
                    document.getElementById('custom_payloads').value = content;

                    // Auto-select Custom radio button
                    const customRadio = document.querySelector('input[name="attack_type"][value="Custom"]');
                    if (customRadio) {
                        customRadio.checked = true;
                    }

                    logToConsole(`[INFO] Loaded wordlist from file: ${file.name}`, 'info');
                    logToConsole(`Detected ${content.split(/\r?\n/).filter(l => l.trim()).length} potential credentials.`, 'info');
                };
                reader.readAsText(file);
            }
        };

        function loadPreset(type) {
            if (presets[type]) {
                document.getElementById('custom_payloads').value = presets[type].join('\n');
                logToConsole(`[INFO] Loaded built-in payloads for ${type} attack.`);
            }
        }

        // Initialize with default
        window.onload = () => loadPreset('Dictionary');

        function logToConsole(message, type = 'info') {
            const console = document.getElementById('console');
            const line = document.createElement('div');
            line.className = 'flex items-center gap-2 py-0.5 animate-in slide-in-from-left duration-200';

            const tag = document.createElement('span');
            tag.className = `tag tag-${type.toLowerCase()}`;
            tag.innerText = `[${type.toUpperCase()}]`;

            const text = document.createElement('span');
            text.className = 'text-slate-300';
            text.innerHTML = message;

            line.appendChild(tag);
            line.appendChild(text);
            console.appendChild(line);
            console.scrollTop = console.scrollHeight;
        }

        function updateStats() {
            const elapsed = (Date.now() - startTime) / 1000;
            document.getElementById('stat_attempts').innerText = attempts;
            document.getElementById('stat_time').innerText = elapsed.toFixed(1) + 's';
            document.getElementById('stat_speed').innerText = Math.round(attempts / (elapsed || 0.1));

            const progress = (attempts / currentWordlist.length) * 100;
            document.getElementById('master_progress').style.width = Math.min(progress, 100) + '%';
            document.getElementById('progress_percent').innerText = Math.min(Math.round(progress), 100) + '%';
        }

        async function startAttack() {
            const target = document.getElementById('target_username').value;
            const attackType = document.querySelector('input[name="attack_type"]:checked').value;
            const maxAttempts = parseInt(document.getElementById('max_attempts').value);
            const speed = document.getElementById('attack_speed').value;
            const delay = speedSettings[speed];

            let payloadsText = document.getElementById('custom_payloads').value.trim();
            if (!payloadsText) {
                alert("Please provide payloads or select an attack type.");
                return;
            }

            currentWordlist = payloadsText.split(/\r?\n/).map(p => p.trim()).filter(p => p !== '');

            isRunning = true;
            attempts = 0;
            startTime = Date.now();

            document.getElementById('start_btn').classList.add('hidden');
            document.getElementById('stop_btn').classList.remove('hidden');
            document.getElementById('console').innerHTML = '';
            document.getElementById('results_container').classList.add('opacity-30');
            document.getElementById('dashboard_return_btn').classList.add('hidden');

            logToConsole(`Initializing ${attackType} Attack on target: <strong>${target}</strong>`);
            logToConsole(`Payload Size: ${currentWordlist.length} | Speed: ${speed}`, 'info');

            for (let pwd of currentWordlist) {
                if (!isRunning) break;

                attempts++;
                updateStats();

                if (attempts > maxAttempts) {
                    logToConsole(`FAILED: Reached Maximum Attempts Limit (${maxAttempts})`, 'fail');
                    logToConsole(`Defensive measure detected: Connection Reset`, 'info');
                    stopAttack();
                    return;
                }

                logToConsole(`Testing credential: <span class="text-primary/70">${pwd}</span>`, 'try');

                await new Promise(r => setTimeout(r, delay));

                const result = await checkPassword(target, pwd);

                if (result.success) {
                    logToConsole(`SUCCESS: Authentication Confirmed for password: <strong>${pwd}</strong>`, 'success');
                    const timeTaken = (Date.now() - startTime) / 1000;
                    showResults(target, pwd, attempts, timeTaken);
                    logFinalResult(target, attackType, attempts, 1, timeTaken);
                    stopAttack(true);
                    return;
                } else {
                    logToConsole(`Incorrect password for target ${target}`, 'fail');
                }
            }

            if (isRunning) {
                logToConsole(`Attack exhausted. No valid credentials found in payload.`, 'info');
                stopAttack();
            }
        }

        async function checkPassword(target, password) {
            try {
                const formData = new FormData();
                formData.append('target', target);
                formData.append('password', password);
                const response = await fetch('?action=verify', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (e) {
                return {
                    success: false
                };
            }
        }

        function stopAttack(success = false) {
            isRunning = false;
            document.getElementById('start_btn').classList.remove('hidden');
            document.getElementById('stop_btn').classList.add('hidden');

            if (success) {
                setTimeout(() => {
                    document.getElementById('success_modal').classList.remove('hidden');
                }, 800);
            }
        }

        function acknowledgeSuccess() {
            document.getElementById('success_modal').classList.add('hidden');
            document.getElementById('dashboard_return_btn').classList.remove('hidden');
            // Ensure results are visible
            document.getElementById('results_container').classList.remove('opacity-30');
        }

        function showResults(target, password, att, time) {
            document.getElementById('results_container').classList.remove('opacity-30');
            document.getElementById('res_target').innerText = target;
            document.getElementById('res_password').innerText = password;
            document.getElementById('res_attempts').innerText = att;
            document.getElementById('res_time').innerText = time.toFixed(2) + 's';
            const diff = att < 10 ? 'VERY EASY' : (att < 50 ? 'EASY' : 'MODERATE');
            document.getElementById('res_difficulty').innerText = diff;
            document.getElementById('res_difficulty').className = `text-xs font-bold uppercase tracking-tighter ${att < 10 ? 'text-green-500' : 'text-yellow-500'}`;
            document.getElementById('modal_password').innerText = password;

            // Update crack reason
            const reasons = [
                "- Weak password policy detected",
                "- Common wordlist match",
                "- Missing account lockout policy",
                "- No multi-factor authentication"
            ];
            document.getElementById('crack_reason').innerHTML = reasons.slice(0, 2).join('<br>');
        }

        async function logFinalResult(target, type, attempts, success, time) {
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
    </script>
</body>

</html>