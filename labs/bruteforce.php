<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";
$simulation_results = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_simulation'])) {
    $target_username = mysqli_real_escape_string($conn, $_POST['target_username']);
    $attack_type = mysqli_real_escape_string($conn, $_POST['attack_type']);

    // Internal password list for simulation
    $passwords = ["123456", "password", "admin123", "letmein", "admin@123"];
    $correct_password = "admin@123"; // Simulating that this is the target password

    $attempts = 0;
    $found = false;
    $start_time = microtime(true);

    $simulation_steps = [];
    foreach ($passwords as $pwd) {
        $attempts++;
        $simulation_steps[] = "Attempting password: $pwd...";
        if ($pwd === $correct_password) {
            $found = true;
            break;
        }
    }

    $end_time = microtime(true);
    $time_taken = round($end_time - $start_time, 4);

    // Log the simulation to the database
    $stmt = $conn->prepare("INSERT INTO bruteforce_logs (user_id, target_username, attack_type, attempts, success, time_taken) VALUES (?, ?, ?, ?, ?, ?)");
    $success_val = $found ? 1 : 0;
    $stmt->bind_param("isssid", $user_id, $target_username, $attack_type, $attempts, $success_val, $time_taken);

    if ($stmt->execute()) {
        $simulation_results = [
            'steps' => $simulation_steps,
            'attempts' => $attempts,
            'found' => $found,
            'password' => $found ? $correct_password : null,
            'time_taken' => $time_taken
        ];
    } else {
        $error_msg = "Error logging simulation: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Brute Force Simulation Lab</title>
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
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen terminal-grid selection:bg-primary selection:text-background-dark">
    <div class="max-w-4xl mx-auto p-8">
        <!-- Header -->
        <header class="mb-12 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../dashboard/dashboard.php" class="p-2 rounded-lg bg-surface border border-border-dim text-slate-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-3xl font-black text-white italic uppercase tracking-tighter">Brute Force <span class="text-primary glow-text">Lab</span></h1>
                    <p class="text-slate-500 text-xs font-mono uppercase tracking-[0.2em]">Simulation Node: BF-LAB-01</p>
                </div>
            </div>
            <div class="px-4 py-2 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase tracking-widest animate-pulse">
                System Active
            </div>
        </header>

        <!-- Educational Disclaimer -->
        <div class="glass-panel rounded-2xl p-6 border-amber-500/20 bg-amber-500/5 mb-8">
            <div class="flex items-center gap-3 text-amber-400 mb-2">
                <span class="material-symbols-outlined">warning</span>
                <h3 class="font-bold uppercase text-xs tracking-widest">Educational Disclaimer</h3>
            </div>
            <p class="text-sm text-slate-400 leading-relaxed">
                This lab is for cybersecurity education and training purposes only. The simulation performs a safe, local-only attack on a virtual target and does not target real systems or networks. Unauthorized use of such techniques on real systems is illegal and unethical.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Configuration Panel -->
            <div class="md:col-span-1 space-y-6">
                <div class="glass-panel rounded-2xl p-6">
                    <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm">settings</span>
                        Simulation Params
                    </h3>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Target Username</label>
                            <input type="text" name="target_username" required placeholder="e.g. admin"
                                class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Attack Type</label>
                            <select name="attack_type" class="w-full bg-background-dark border border-border-dim rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary appearance-none transition-all">
                                <option value="Dictionary Attack">Dictionary Attack</option>
                                <option value="Credential Stuffing">Credential Stuffing</option>
                            </select>
                        </div>
                        <button type="submit" name="start_simulation"
                            class="w-full py-4 bg-primary text-background-dark font-black rounded-xl text-xs uppercase tracking-[0.2em] hover:brightness-110 transition-all flex items-center justify-center gap-2 mt-4">
                            Start Simulation <span class="material-symbols-outlined text-sm">play_arrow</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Terminal Output -->
            <div class="md:col-span-2">
                <div class="glass-panel rounded-2xl h-full flex flex-col overflow-hidden">
                    <div class="bg-neutral-dark/80 px-6 py-3 border-b border-border-dim flex justify-between items-center">
                        <div class="flex gap-1.5">
                            <div class="size-2.5 rounded-full bg-red-500/20"></div>
                            <div class="size-2.5 rounded-full bg-yellow-500/20"></div>
                            <div class="size-2.5 rounded-full bg-green-500/20"></div>
                        </div>
                        <span class="text-[10px] font-mono text-slate-500 uppercase tracking-widest">Attack Log Output</span>
                    </div>
                    <div class="flex-1 p-6 font-mono text-sm overflow-y-auto max-h-[400px] custom-scrollbar space-y-2">
                        <?php if ($simulation_results): ?>
                            <?php foreach ($simulation_results['steps'] as $step): ?>
                                <p class="text-slate-400"><?php echo htmlspecialchars($step); ?></p>
                            <?php endforeach; ?>

                            <?php if ($simulation_results['found']): ?>
                                <div class="mt-4 p-4 rounded-xl bg-primary/10 border border-primary/20">
                                    <p class="text-primary font-bold">Successfully Cracked!</p>
                                    <p class="text-white text-lg mt-1">Found Password: <span class="bg-primary/20 px-2 italic text-primary"><?php echo htmlspecialchars($simulation_results['password']); ?></span></p>
                                    <div class="mt-4 flex gap-6">
                                        <div>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Attempts</p>
                                            <p class="text-white font-black"><?php echo $simulation_results['attempts']; ?></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase mb-0.5">Time Taken</p>
                                            <p class="text-white font-black"><?php echo $simulation_results['time_taken']; ?>s</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-red-400 mt-4">Simulation sequence completed. No valid credential found in current wordlist.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="h-full flex flex-col items-center justify-center text-slate-600 space-y-4 py-20">
                                <span class="material-symbols-outlined text-6xl opacity-20">terminal</span>
                                <p class="text-xs uppercase tracking-[0.2em] font-bold">Awaiting Simulation Start...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>