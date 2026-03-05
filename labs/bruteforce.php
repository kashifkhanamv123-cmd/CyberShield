<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$simulation_results = null;
$error_msg = "";

// Expanded default dictionary (10+ entries)
$default_passwords = [
    "123456",
    "password",
    "admin123",
    "letmein",
    "12345678",
    "qwerty",
    "111111",
    "welcome",
    "root123",
    "security",
    "cyber2026",
    "password123",
    "admin@123",
    "dragon"
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_simulation'])) {
    $target_username = mysqli_real_escape_string($conn, $_POST['target_username']);
    $attack_type = mysqli_real_escape_string($conn, $_POST['attack_type']);
    $custom_payload = trim($_POST['custom_payload'] ?? '');

    // Configurable parameters
    $delay_ms = isset($_POST['simulation_delay']) ? (int)$_POST['simulation_delay'] : 100;
    $max_attempts_before_lockout = isset($_POST['lockout_threshold']) ? (int)$_POST['lockout_threshold'] : 10;

    // 1. Fetch target password from lab_targets
    $stmt = $conn->prepare("SELECT password FROM lab_targets WHERE username = ?");
    $stmt->bind_param("s", $target_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error_msg = "Target user '$target_username' not found in simulated lab environment.";
    } else {
        $target_data = $result->fetch_assoc();
        $correct_password = $target_data['password'];

        // 2. Prepare Wordlist
        $wordlist = [];

        // Handle File Upload
        if (!empty($_FILES['wordlist_file']['tmp_name'])) {
            $file_content = file_get_contents($_FILES['wordlist_file']['tmp_name']);
            $wordlist = array_filter(array_map('trim', explode("\n", $file_content)));
        }
        // Handle Custom Payload Textarea
        elseif (!empty($custom_payload)) {
            $wordlist = array_filter(array_map('trim', explode("\n", $custom_payload)));
        }
        // Fallback to Default
        else {
            $wordlist = $default_passwords;
        }

        $attempts = 0;
        $found = false;
        $lockout_triggered = false;

        $start_time = microtime(true);
        $simulation_steps = [];
        $simulation_steps[] = "[INFO] Initializing advanced brute force simulation...";
        $simulation_steps[] = "[INFO] Target: $target_username";
        $simulation_steps[] = "[INFO] Attack Type: $attack_type";
        $simulation_steps[] = "[INFO] Parameters: Delay={$delay_ms}ms, Lockout Threshold={$max_attempts_before_lockout}";
        $simulation_steps[] = "[INFO] Wordlist size: " . count($wordlist) . " entries";

        foreach ($wordlist as $pwd) {
            $attempts++;

            // Check for simulated account lockout
            if ($attempts > $max_attempts_before_lockout && $pwd !== $correct_password) {
                $simulation_steps[] = "[CRITICAL] Security Alert: Multiple login attempts detected.";
                $simulation_steps[] = "[CRITICAL] Rate limiting triggered. Account locked.";
                $lockout_triggered = true;
                break;
            }

            $simulation_steps[] = "[TRY] Testing: " . htmlspecialchars($pwd);

            // Artificial delay for realism (converted to microseconds)
            usleep($delay_ms * 1000);

            if ($pwd === $correct_password) {
                $simulation_steps[] = "[SUCCESS] Credential cracked: " . htmlspecialchars($pwd);
                $found = true;
                break;
            } else {
                $simulation_steps[] = "[FAIL] Unauthorized: " . htmlspecialchars($pwd);
            }
        }

        $end_time = microtime(true);
        $time_taken = round($end_time - $start_time, 4);
        $speed = $attempts > 0 ? round($attempts / ($time_taken ?: 0.0001), 2) : 0;

        // 3. Log to database
        $log_stmt = $conn->prepare("INSERT INTO bruteforce_logs (user_id, target_username, attack_type, attempts, success, time_taken) VALUES (?, ?, ?, ?, ?, ?)");
        $success_val = $found ? 1 : 0;
        $log_stmt->bind_param("isssid", $user_id, $target_username, $attack_type, $attempts, $success_val, $time_taken);
        $log_stmt->execute();

        $simulation_results = [
            'steps' => $simulation_steps,
            'attempts' => $attempts,
            'found' => $found,
            'password' => $found ? $correct_password : null,
            'time_taken' => $time_taken,
            'speed' => $speed,
            'lockout' => $lockout_triggered,
            'payload_size' => count($wordlist)
        ];
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Professional Brute Force Lab</title>
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
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen terminal-grid selection:bg-primary selection:text-background-dark">
    <div class="max-w-7xl mx-auto p-8">
        <!-- Header -->
        <header class="mb-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <a href="../dashboard/dashboard.php" class="p-2 rounded-lg bg-surface border border-border-dim text-slate-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-white italic uppercase tracking-tighter">Brute Force <span class="text-primary glow-text">Command Center</span></h1>
                    <p class="text-slate-500 text-xs font-mono uppercase tracking-[0.2em]">Operational Unit: CYBER-LAB-ALPHA</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="bg-surface/50 p-3 rounded-xl border border-border-dim flex items-center gap-4">
                    <div class="text-right border-r border-border-dim pr-4">
                        <p class="text-[10px] text-slate-500 font-bold uppercase">Targets In Range</p>
                        <p class="text-[10px] text-primary font-mono tracking-wider">admin, root, analyst</p>
                    </div>
                    <div class="size-2 bg-primary rounded-full animate-pulse shadow-[0_0_10px_#a0f000]"></div>
                </div>
            </div>
        </header>

        <?php if ($error_msg): ?>
            <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-3 animate-pulse">
                <span class="material-symbols-outlined">report</span>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Advanced Config Side -->
            <div class="lg:col-span-4 space-y-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Section 1: Target & Method -->
                    <div class="glass-panel rounded-2xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2 text-sm uppercase tracking-widest">
                            <span class="material-symbols-outlined text-primary text-sm">target</span>
                            1. Target Matrix
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Victim Username</label>
                                <input type="text" name="target_username" required placeholder="admin"
                                    class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Attack Methodology</label>
                                <select name="attack_type" class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary appearance-none transition-all">
                                    <option value="Dictionary Attack">Dictionary Attack</option>
                                    <option value="Credential Stuffing">Credential Stuffing</option>
                                    <option value="Password Spraying">Password Spraying</option>
                                    <option value="Brute Force (Custom)">Brute Force (Custom)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Parameters -->
                    <div class="glass-panel rounded-2xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2 text-sm uppercase tracking-widest">
                            <span class="material-symbols-outlined text-primary text-sm">tune</span>
                            2. Parametric Control
                        </h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Delay (ms)</label>
                                <input type="number" name="simulation_delay" value="100" min="0" max="2000"
                                    class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Lockout Limit</label>
                                <input type="number" name="lockout_threshold" value="10" min="1" max="100"
                                    class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Wordlist -->
                    <div class="glass-panel rounded-2xl p-6">
                        <h3 class="text-white font-bold mb-6 flex items-center gap-2 text-sm uppercase tracking-widest">
                            <span class="material-symbols-outlined text-primary text-sm">database</span>
                            3. Wordlist Selection
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Manual Payload Injection</label>
                                <textarea name="custom_payload" rows="4" placeholder="Enter passwords line by line..."
                                    class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary font-mono custom-scrollbar resize-none transition-all"></textarea>
                            </div>

                            <div class="relative">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Upload Wordlist (.txt)</label>
                                <div class="relative group">
                                    <input type="file" name="wordlist_file" accept=".txt"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div class="w-full bg-background-dark border border-dashed border-border-dim group-hover:border-primary rounded-xl p-4 text-center transition-all">
                                        <span class="material-symbols-outlined text-primary text-xl mb-1">upload_file</span>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase">Click to select file</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="start_simulation"
                            class="w-full mt-8 py-5 bg-primary text-background-dark font-black rounded-xl text-xs uppercase tracking-[0.2em] shadow-[0_0_20px_rgba(160,240,0,0.2)] hover:shadow-[0_0_30px_rgba(160,240,0,0.4)] hover:brightness-110 transition-all flex items-center justify-center gap-3">
                            Launch Attack Sequence <span class="material-symbols-outlined text-sm font-black">bolt</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Enhanced Terminal Side -->
            <div class="lg:col-span-8 space-y-6">
                <div class="glass-panel rounded-2xl flex flex-col h-[600px] overflow-hidden border-primary/20 shadow-2xl">
                    <div class="bg-neutral-dark/80 px-6 py-4 border-b border-border-dim flex justify-between items-center relative overflow-hidden">
                        <div class="flex gap-2 relative z-10">
                            <div class="size-3 rounded-full bg-red-500/60 shadow-[0_0_10px_red]"></div>
                            <div class="size-3 rounded-full bg-yellow-500/60 shadow-[0_0_10px_yellow]"></div>
                            <div class="size-3 rounded-full bg-green-500/60 shadow-[0_0_10px_green]"></div>
                        </div>
                        <div class="flex items-center gap-4 relative z-10">
                            <span class="text-[10px] font-mono text-primary font-black uppercase tracking-[0.3em] glow-text">Intrusion Console v4.2.1</span>
                            <span class="px-2 py-0.5 rounded bg-primary/10 text-[9px] font-mono text-primary border border-primary/20">RCV_READY</span>
                        </div>
                        <!-- Decorative Header Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/5 to-transparent"></div>
                    </div>

                    <div id="console-output" class="flex-1 p-8 font-mono text-[11px] overflow-y-auto custom-scrollbar space-y-1 bg-[#050702]/90 leading-relaxed">
                        <?php if ($simulation_results): ?>
                            <?php foreach ($simulation_results['steps'] as $step):
                                $colorClass = "text-slate-400";
                                if (strpos($step, '[INFO]') !== false) $colorClass = "text-cyan-400";
                                if (strpos($step, '[TRY]') !== false) $colorClass = "text-slate-500";
                                if (strpos($step, '[FAIL]') !== false) $colorClass = "text-slate-600";
                                if (strpos($step, '[SUCCESS]') !== false) $colorClass = "text-primary font-black bg-primary/5 p-1 rounded inline-block w-full border border-primary/20";
                                if (strpos($step, '[CRITICAL]') !== false) $colorClass = "text-red-500 font-bold bg-red-500/10 p-1 rounded inline-block w-full border border-red-500/20";
                            ?>
                                <p class="<?php echo $colorClass; ?>"><?php echo htmlspecialchars($step); ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="h-full flex flex-col items-center justify-center space-y-6 opacity-40">
                                <div class="relative">
                                    <span class="material-symbols-outlined text-8xl text-primary animate-pulse">lock_open</span>
                                    <div class="absolute inset-x-0 bottom-0 h-1 bg-primary/20 blur-sm"></div>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs uppercase font-black tracking-[0.5em] text-primary mb-2">Awaiting Target Vector</p>
                                    <p class="text-[10px] text-slate-600 font-mono tracking-widest">Connect to internal node for simulation initialization</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Live Stream Status Bar -->
                    <div class="bg-neutral-dark border-t border-border-dim px-6 py-2 flex items-center justify-between text-[9px] font-mono font-bold text-slate-500">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1"><span class="size-1.5 bg-primary rounded-full animate-ping"></span> ONLINE</span>
                            <span>NODE: 127.0.0.1</span>
                            <span>CPU: <?php echo rand(22, 45); ?>%</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span>SESSION: <?php echo strtoupper(substr(session_id(), 0, 8)); ?></span>
                            <span class="text-primary italic">SEC_READY_ALPHA</span>
                        </div>
                    </div>
                </div>

                <!-- Expanded Analytics -->
                <?php if ($simulation_results): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in fade-in slide-in-from-bottom-4 duration-700">
                        <div class="glass-panel rounded-2xl p-5 border-l-4 border-l-primary/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Attacks</span>
                                <span class="material-symbols-outlined text-primary text-sm">repeat</span>
                            </div>
                            <p class="text-2xl font-black text-white"><?php echo $simulation_results['attempts']; ?></p>
                            <p class="text-[9px] text-slate-600 font-bold uppercase mt-1">Total Payload Entries</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 border-l-4 border-l-cyan-500/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Runtime</span>
                                <span class="material-symbols-outlined text-cyan-400 text-sm">timer</span>
                            </div>
                            <p class="text-2xl font-black text-white"><?php echo $simulation_results['time_taken']; ?><span class="text-xs text-slate-500 ml-1">sec</span></p>
                            <p class="text-[9px] text-slate-600 font-bold uppercase mt-1">Execution Duration</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 border-l-4 border-l-yellow-500/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Velocity</span>
                                <span class="material-symbols-outlined text-yellow-400 text-sm">speed</span>
                            </div>
                            <p class="text-2xl font-black text-white"><?php echo $simulation_results['speed']; ?></p>
                            <p class="text-[9px] text-slate-600 font-bold uppercase mt-1">Attempts Per Second</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-5 border-l-4 <?php echo $simulation_results['found'] ? 'border-l-primary/40' : 'border-l-red-500/40'; ?>">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest">Integrity</span>
                                <span class="material-symbols-outlined <?php echo $simulation_results['found'] ? 'text-primary' : 'text-red-400'; ?> text-sm">
                                    <?php echo $simulation_results['found'] ? 'key' : 'lock'; ?>
                                </span>
                            </div>
                            <p class="text-2xl font-black text-white"><?php echo $simulation_results['found'] ? 'SUCCESS' : 'FAILURE'; ?></p>
                            <p class="text-[9px] text-slate-600 font-bold uppercase mt-1">Status Verification</p>
                        </div>
                    </div>

                    <!-- Advanced Learning Module -->
                    <div class="glass-panel rounded-3xl p-10 border-primary/10 relative overflow-hidden animate-in fade-in transition-all duration-1000">
                        <div class="relative z-10 grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-6">
                                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-xl bg-primary/10 border border-primary/20">
                                    <span class="material-symbols-outlined text-primary">analytics</span>
                                    <h4 class="text-white font-black text-xs uppercase tracking-[0.2em]">Operational Debrief</h4>
                                </div>
                                <h5 class="text-2xl font-black text-white italic uppercase tracking-tight">Vulnerability <span class="text-primary">Correlation</span></h5>
                                <p class="text-sm text-slate-400 leading-relaxed font-medium">
                                    <?php if ($simulation_results['found']): ?>
                                        Access was granted because the target's credential entropy was lower than the wordlist's coverage. Systems using common dictionary terms are vulnerable to <strong class="text-primary">Dictionary Attacks</strong>.
                                    <?php elseif ($simulation_results['lockout']): ?>
                                        The intrusion was successfully mitigated by an <strong class="text-red-400">Adaptive Response Lockout</strong>. Modern WAFs and Auth systems use rate limiting to thwart high-velocity brute force attempts.
                                    <?php else: ?>
                                        Simulation concluded. The target credential remains secure outside the provided wordlist scope. This emphasizes the need for <strong class="text-cyan-400">OSINT-driven wordlist generation</strong> in real engagements.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="bg-background-dark/50 p-6 rounded-2xl border border-border-dim space-y-4">
                                <h5 class="text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] mb-4">Hardening Checklist</h5>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-4 group">
                                        <div class="size-8 rounded-lg bg-surface border border-border-dim flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                            <span class="material-symbols-outlined text-sm">security</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-200">Enforce Multi-Factor (MFA)</p>
                                            <p class="text-[9px] text-slate-500 font-mono italic">Prevents 99% of brute force success</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 group">
                                        <div class="size-8 rounded-lg bg-surface border border-border-dim flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                            <span class="material-symbols-outlined text-sm">block</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-200">Adaptive Rate Limiting</p>
                                            <p class="text-[9px] text-slate-500 font-mono italic">Progressive delays for failed logins</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 group">
                                        <div class="size-8 rounded-lg bg-surface border border-border-dim flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                            <span class="material-symbols-outlined text-sm">password</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-slate-200">High-Entropy Passwords</p>
                                            <p class="text-[9px] text-slate-500 font-mono italic">Increase computational cracking cost</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Background Glow Decoration -->
                        <div class="absolute -right-20 -top-20 size-80 bg-primary/5 rounded-full blur-[100px]"></div>
                        <div class="absolute -left-20 -bottom-20 size-80 bg-cyan-500/5 rounded-full blur-[100px]"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll console to bottom
        const consoleOutput = document.getElementById('console-output');
        if (consoleOutput) {
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
    </script>
</body>

</html>