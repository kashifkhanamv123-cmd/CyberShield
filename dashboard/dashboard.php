<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Fetch Phishing Progress
$phishing_res = $conn->query("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = $user_id");
$phishing_count = $phishing_res->fetch_row()[0];
$phishing_progress = min($phishing_count * 20, 100);
$phishing_level = min(floor($phishing_count / 1) + 1, 5);

// Fetch Brute Force Progress
$bruteforce_res = $conn->query("SELECT COUNT(*) as total, MAX(success) as has_success FROM bruteforce_logs WHERE user_id = $user_id");
$bruteforce_data = $bruteforce_res->fetch_assoc();
$bruteforce_count = (int)$bruteforce_data['total'];
$bruteforce_success = (int)$bruteforce_data['has_success'];
$bruteforce_progress = $bruteforce_success ? 100 : min($bruteforce_count * 10, 90);

// Calculate completed labs
$completed_labs = ($phishing_count > 0 ? 1 : 0) + ($bruteforce_success > 0 ? 1 : 0);

$lab_completed = $_GET['lab_completed'] ?? '';
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Security Analyst Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
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
        #dashboard-app.terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        #dashboard-app .glass-panel {
            background: rgba(18, 20, 10, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }

        #dashboard-app .nav-item.active {
            background: rgba(160, 240, 0, 0.1);
            color: #a0f000;
            border-right: 2px solid #a0f000;
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

        #dashboard-app .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #23281b;
            border-radius: 10px;
        }
    </style>
</head>

<body id="dashboard-app" class="bg-background-dark text-slate-300 font-display min-h-screen terminal-grid selection:bg-primary selection:text-background-dark overflow-hidden">

    <?php if ($lab_completed === 'bruteforce'): ?>
        <!-- Brute Force Completion Modal -->
        <div id="completionModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary shadow-[0_0_15px_rgba(160,240,0,0.3)]">
                        <span class="material-symbols-outlined text-3xl">verified</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter">
                            Lab <span class="text-primary glow-text">Completed</span>
                        </h2>
                        <p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Subject: Brute Force Intrusion Analysis</p>
                    </div>
                </div>

                <div class="space-y-6 text-sm text-slate-300 leading-relaxed relative z-10 overflow-y-auto custom-scrollbar pr-2 mb-2">
                    <div class="p-5 bg-primary/5 border border-primary/20 rounded-xl">
                        <h4 class="text-xs font-bold text-primary uppercase mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">analytics</span> Performance Metrics
                        </h4>
                        <p class="text-[11px] text-slate-400">
                            You successfully simulated an automated password recovery attack. Your telemetry indicates a high-velocity crack using dictionary matching.
                        </p>
                    </div>

                    <div class="p-5 bg-white/5 border border-white/10 rounded-xl space-y-4">
                        <h4 class="text-xs font-bold text-white uppercase flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-primary">school</span> Analyst Debrief
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <p class="text-[11px] font-bold text-primary italic uppercase mb-1">Vulnerability Correlation</p>
                                <p class="text-[11px] text-slate-500">
                                    Weak passwords represent the largest attack surface in modern infrastructure. By testing common permutations, attackers bypass core security layers without triggering complex alerts.
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-red-400 italic uppercase mb-1">Defensive Hardening</p>
                                <p class="text-[11px] text-slate-500">
                                    Enforcing <strong class="text-slate-300">Multi-Factor Authentication (MFA)</strong> and <strong class="text-slate-300">Account Lockout Policies</strong> are critical. Without these, even high-entropy passwords can eventually be cracked via distributed brute force.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- NEW: How to Stay Safe Section -->
                    <div class="p-5 bg-primary/10 border border-primary/20 rounded-xl">
                        <h4 class="text-xs font-bold text-primary uppercase mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">security</span> How to Stay Safe
                        </h4>
                        <ul class="space-y-2 text-[10px] text-slate-400">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-xs text-primary">check_circle</span>
                                <span>Use a <strong class="text-white">Password Manager</strong> to generate and store unique, random keys.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-xs text-primary">check_circle</span>
                                <span>Enable <strong class="text-white">Two-Factor Authentication (2FA)</strong> on every sensitive account.</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-xs text-primary">check_circle</span>
                                <span>Avoid using <strong class="text-white">personal information</strong> (names, dates) in your password patterns.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <button onclick="window.history.replaceState(null, null, window.location.pathname); this.closest('#completionModal').remove();"
                    class="w-full mt-8 py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] shadow-lg shadow-primary/10 relative z-10">
                    Acknowledge Directive
                </button>

                <div class="absolute -right-20 -top-20 size-60 bg-primary/5 rounded-full blur-[80px]"></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="w-64 border-r border-border-dim bg-neutral-dark/50 backdrop-blur-xl flex flex-col z-20 shrink-0">
            <div class="p-6">
                <div class="flex items-center gap-3 text-primary mb-8 px-2 transition-transform hover:scale-105 cursor-pointer">
                    <span class="material-symbols-outlined text-3xl">shield_person</span>
                    <h1 class="text-white text-xl font-black italic tracking-tighter uppercase">Cyber<span class="text-primary tracking-normal">Shield</span></h1>
                </div>

                <nav class="space-y-1">
                    <a class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all" href="#">
                        <span class="material-symbols-outlined text-xl">dashboard</span> Dashboard
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="../modules/phishing/index.php">
                        <span class="material-symbols-outlined text-xl">alternate_email</span> Phishing Lab
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="#">
                        <span class="material-symbols-outlined text-xl">security</span> DDoS Defense
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="#">
                        <span class="material-symbols-outlined text-xl">bug_report</span> Malware Analysis
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="../labs/bruteforce.php">
                        <span class="material-symbols-outlined text-xl">lock_open</span> Brute Force Lab
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="#">
                        <span class="material-symbols-outlined text-xl">data_exploration</span> SOC Dashboard
                    </a>
                </nav>
            </div>

            <div class="mt-auto p-6 space-y-4">
                <div class="p-4 rounded-xl bg-primary/5 border border-primary/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[#a0f000]">System Integrity</span>
                        <div class="size-2 bg-primary rounded-full animate-pulse"></div>
                    </div>
                </div>
                <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-400 hover:bg-red-400/10 transition-all" href="../auth/logout.php">
                    <span class="material-symbols-outlined text-xl">logout</span> Terminate Session
                </a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 flex flex-col overflow-hidden relative">
            <!-- Top Navigation Bar -->
            <header class="sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-[10px] font-mono text-primary uppercase tracking-widest">Node: csh_analyst_01</span>
                    </div>
                    <h2 class="text-xl font-black text-white italic uppercase italic">Analyst <span class="text-primary glow-text">Overview</span></h2>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm font-black text-white leading-tight">
                                    <?php echo htmlspecialchars($userName); ?>
                                </p>
                            </div>
                            <div class="size-9 rounded-full bg-gradient-to-tr from-primary to-lime-600 border-2 border-surface flex items-center justify-center text-background-dark font-bold text-sm">
                                <?php
                                $initials = '';
                                $parts = explode(' ', $userName);
                                foreach ($parts as $p) $initials .= strtoupper($p[0]);
                                echo substr($initials, 0, 2);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 pb-12">
                <div class="max-w-7xl mx-auto space-y-8">
                    <!-- Welcome Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Progress Card -->
                        <div class="lg:col-span-2 glass-panel rounded-2xl p-8 relative overflow-hidden group border-primary/20">
                            <div class="flex items-center gap-6 relative z-10">
                                <div class="size-16 rounded-2xl bg-primary/20 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                                    <span class="material-symbols-outlined text-4xl">check_circle</span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-white mb-1 uppercase tracking-tight">Active Training <span class="text-primary">Progress</span></h3>
                                    <p class="text-slate-400 text-sm">Real-time simulation metrics and certification pathway status.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 relative z-10">
                                <div class="p-4 rounded-xl bg-surface border border-border-dim">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Completed Labs</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-black text-white"><?php echo $completed_labs; ?></span>
                                        <span class="text-sm text-slate-500">/ 4 labs</span>
                                    </div>
                                    <div class="w-full bg-background-dark h-1 rounded-full mt-2">
                                        <div class="bg-primary h-full" style="width: <?php echo ($completed_labs / 4) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="p-4 rounded-xl bg-surface border border-border-dim">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Phishing Level</span>
                                    <span class="text-3xl font-black text-white"><?php echo $phishing_level; ?></span>
                                    <p class="text-[10px] text-primary mt-1">Tier 1 Expert</p>
                                </div>
                                <div class="p-4 rounded-xl bg-surface border border-border-dim">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Threats Neutralized</span>
                                    <span class="text-3xl font-black text-white">4</span>
                                    <p class="text-[10px] text-primary mt-1">+1 today</p>
                                </div>
                                <div class="p-4 rounded-xl bg-surface border border-border-dim">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Brute Force Progress</span>
                                    <span class="text-3xl font-black text-white"><?php echo $bruteforce_progress; ?>%</span>
                                    <p class="text-[10px] text-primary mt-1"><?php echo $bruteforce_success ? 'CERTIFIED' : 'IN TRAINING'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Side Action Card -->
                        <div class="glass-panel rounded-2xl p-8 flex flex-col justify-center border-white/5">
                            <div class="text-primary mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined">verified_user</span>
                                <span class="text-xs font-bold uppercase tracking-widest">Quick Launch</span>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-4">Resume Phishing Training</h4>
                            <p class="text-slate-400 text-sm mb-6 leading-relaxed">Continue your simulation and master email header analysis to prevent data breaches.</p>
                            <a href="../modules/phishing/index.php" class="w-full py-3 bg-primary text-background-dark font-black rounded-xl text-center uppercase tracking-widest hover:brightness-110 transition-all flex items-center justify-center gap-2">
                                Enter Lab <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        </div>
                    </div>

                    <!-- Learning Pathways -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black uppercase text-slate-500 tracking-[0.2em]">Available Simulation <span class="text-white">Nodes</span></h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Module Card: Phishing -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">alternate_email</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary">MODULE_01</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">Advanced Phishing Lab</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Master the art of social engineering through simulated campaigns and data exfiltration analysis.</p>

                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                                <span class="text-slate-500">Progress</span>
                                                <span class="text-white"><?php echo $phishing_progress; ?>%</span>
                                            </div>
                                            <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-primary h-full rounded-full" style="width: <?php echo $phishing_progress; ?>%"></div>
                                            </div>
                                            <div class="flex justify-between items-center pt-2">
                                                <span class="text-[10px] text-slate-500">Level <?php echo $phishing_level; ?> / 5</span>
                                                <span class="text-[10px] text-primary font-bold">
                                                    <?php echo $phishing_progress >= 100 ? 'PATHWAY MASTERED' : 'NEXT: HEADER ANALYSIS'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="../modules/phishing/index.php" class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim group-hover:bg-primary group-hover:text-background-dark transition-all">
                                    Initialize Lab Instance
                                </a>
                            </div>

                            <!-- Module Card: DDoS -->
                            <div class="glass-panel group rounded-2xl p-1 opacity-60">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-slate-800 flex items-center justify-center text-slate-500">
                                            <span class="material-symbols-outlined text-2xl">security</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold text-slate-500 uppercase">Module_02</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2">DDoS Mitigation Elite</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Configure scrubbing centers and WAF rules to defend against high-volume traffic attacks.</p>

                                    <div class="p-4 rounded-xl bg-background-dark border border-border-dim text-center">
                                        <span class="material-symbols-outlined text-slate-600 mb-1">lock</span>
                                        <p class="text-[10px] font-bold text-slate-500 uppercase">Complete Module 01 to Unlock</p>
                                    </div>
                                </div>
                                <div class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim text-slate-600">
                                    Instance Unavailable
                                </div>
                            </div>

                            <!-- Module Card: Brute Force -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">lock_open</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">Module_04</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">Brute Force Intrusion</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Simulate high-velocity dictionary attacks to exploit weak credentials and test account lockout policies.</p>

                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                                <span class="text-slate-500">Analyst Mastery</span>
                                                <span class="text-white"><?php echo $bruteforce_progress; ?>%</span>
                                            </div>
                                            <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-primary h-full rounded-full" style="width: <?php echo $bruteforce_progress; ?>%"></div>
                                            </div>
                                            <div class="flex justify-between items-center pt-2">
                                                <span class="text-[10px] text-slate-500">Tier <?php echo $bruteforce_success ? '2' : '1'; ?> Tactical</span>
                                                <span class="text-[10px] text-primary font-bold">
                                                    <?php echo $bruteforce_success ? 'SYSTEM COMPROMISED' : 'NEXT: WORDLIST FUZZING'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="../labs/bruteforce.php" class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim group-hover:bg-primary group-hover:text-background-dark transition-all">
                                    Initialize Lab Instance
                                </a>
                            </div>
                        </div>
                    </div>
            </section>

            <!-- Active Terminal Status Bar -->
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center justify-between px-6 z-30 relative">
                <div class="flex items-center gap-4 text-[10px] font-mono">
                    <div class="flex items-center gap-1.5">
                        <span class="text-primary uppercase">Console:</span>
                        <span class="text-slate-500">Connected to local_node</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-primary uppercase">Status:</span>
                        <span class="text-slate-500">Operation Ready</span>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-mono">
                    <span class="text-slate-500 uppercase">Uptime: <?php echo floor(time() / 3600) % 24; ?>h 42m</span>
                    <span class="text-primary italic">CyberShield Control v4.2 BETA</span>
                </div>
            </footer>
        </main>
    </div>
</body>

</html>