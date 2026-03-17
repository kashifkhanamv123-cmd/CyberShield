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
$phishing_stmt = $conn->prepare("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = ?");
$phishing_stmt->bind_param("i", $user_id);
$phishing_stmt->execute();
$phishing_count = $phishing_stmt->get_result()->fetch_row()[0];
$phishing_progress = min($phishing_count * 20, 100);
$phishing_level = min(floor($phishing_count / 1) + 1, 5);

// Fetch Brute Force Progress
$conn->query("CREATE TABLE IF NOT EXISTS bruteforce_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_username VARCHAR(100),
    attack_type VARCHAR(50),
    attempts INT DEFAULT 0,
    success TINYINT(1) DEFAULT 0,
    time_taken FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$bf_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(success) as has_success FROM bruteforce_logs WHERE user_id = ?");
$bf_stmt->bind_param("i", $user_id);
$bf_stmt->execute();
$bf_data = $bf_stmt->get_result()->fetch_assoc();
$bruteforce_success  = (int)$bf_data['has_success'];
$bruteforce_count    = (int)$bf_data['total'];
$bruteforce_progress = $bruteforce_success ? 100 : min($bruteforce_count * 10, 90);

// Fetch DDoS Progress
$conn->query("CREATE TABLE IF NOT EXISTS ddos_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attack_type VARCHAR(50),
    intensity VARCHAR(20),
    mitigated TINYINT(1) DEFAULT 0,
    time_taken FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$ddos_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(mitigated) as has_success FROM ddos_logs WHERE user_id = ?");
$ddos_stmt->bind_param("i", $user_id);
$ddos_stmt->execute();
$ddos_data = $ddos_stmt->get_result()->fetch_assoc();
$ddos_success  = (int)$ddos_data['has_success'];
$ddos_count    = (int)$ddos_data['total'];
$ddos_progress = $ddos_success ? 100 : min($ddos_count * 15, 90);

// Fetch Malware Progress
$conn->query("CREATE TABLE IF NOT EXISTS malware_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sample_type VARCHAR(50),
    verdict VARCHAR(20),
    correct TINYINT(1) DEFAULT 0,
    time_taken FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$mal_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(correct) as has_success FROM malware_logs WHERE user_id = ?");
$mal_stmt->bind_param("i", $user_id);
$mal_stmt->execute();
$mal_data = $mal_stmt->get_result()->fetch_assoc();
$malware_success  = (int)$mal_data['has_success'];
$malware_count    = (int)$mal_data['total'];
$malware_progress = $malware_success ? 100 : min($malware_count * 25, 90);

// Total completed labs
$completed_labs = ($phishing_count > 0 ? 1 : 0) + ($bruteforce_success ? 1 : 0) + ($ddos_success ? 1 : 0) + ($malware_success ? 1 : 0);

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

        .lab-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-running { background: #a0f000; box-shadow: 0 0 8px #a0f000; }
        .status-stopped { background: #64748b; }
        .status-loading { background: #f59e0b; animation: pulse 1s infinite; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
    <script>
        async function manageLab(lab, action) {
            const btn = event.currentTarget;
            const originalText = btn.innerText;
            const statusEl = document.getElementById(`status-${lab}`);
            const dotEl = document.getElementById(`dot-${lab}`);
            
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span>';
            
            if (statusEl) statusEl.innerText = action === 'start' ? 'Starting...' : (action === 'stop' ? 'Stopping...' : 'Resetting...');
            if (dotEl) dotEl.className = 'lab-status-dot status-loading';

            try {
                const response = await fetch(`../labs/manage_lab.php?lab=${lab}&action=${action}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    if (action === 'start') {
                        statusEl.innerText = 'Running';
                        dotEl.className = 'lab-status-dot status-running';
                        // Provide link to user
                        const host = window.location.hostname;
                        const url = `http://${host}:${data.port}`;
                        alert(`Lab is ready! Access it at: ${url}`);
                        window.open(url, '_blank');
                    } else if (action === 'stop') {
                        statusEl.innerText = 'Stopped';
                        dotEl.className = 'lab-status-dot status-stopped';
                    } else if (action === 'reset') {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                    checkLabStatus(lab);
                }
            } catch (e) {
                console.error(e);
                alert('Connection to lab controller failed.');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }

        async function checkLabStatus(lab) {
            try {
                const response = await fetch(`../labs/manage_lab.php?lab=${lab}&action=status`);
                const data = await response.json();
                const statusEl = document.getElementById(`status-${lab}`);
                const dotEl = document.getElementById(`dot-${lab}`);
                
                if (statusEl && dotEl) {
                    if (data.status === 'running') {
                        statusEl.innerText = 'Running';
                        dotEl.className = 'lab-status-dot status-running';
                    } else {
                        statusEl.innerText = 'Stopped';
                        dotEl.className = 'lab-status-dot status-stopped';
                    }
                }
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['juiceshop', 'dvwa', 'bwapp'].forEach(checkLabStatus);
        });
    </script>
</head>

<body id="dashboard-app" class="bg-background-dark text-slate-300 font-display min-h-screen terminal-grid selection:bg-primary selection:text-background-dark overflow-hidden">

    <?php if ($lab_completed === 'bruteforce'): ?>
        <div id="completionModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary"><span class="material-symbols-outlined text-3xl">verified</span></div>
                    <div><h2 class="text-2xl font-black uppercase italic tracking-tighter">Lab <span class="text-primary glow-text">Completed</span></h2><p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Subject: Brute Force Intrusion Analysis</p></div>
                </div>
                <div class="space-y-4 relative z-10 overflow-y-auto custom-scrollbar pr-2 mb-4">
                    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">analytics</span> Debrief</h4><p class="text-[11px] text-slate-400">You successfully cracked credentials via dictionary attack. Enforce <strong class="text-slate-300">MFA + Account Lockout</strong> to prevent this.</p></div>
                    <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">security</span> Stay Safe</h4><ul class="space-y-1 text-[10px] text-slate-400"><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>Use a <strong class="text-white">Password Manager</strong> for unique keys.</li><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>Enable <strong class="text-white">2FA</strong> on all accounts.</li></ul></div>
                </div>
                <button onclick="window.history.replaceState(null,null,window.location.pathname);this.closest('#completionModal').remove();" class="w-full py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] relative z-10">Acknowledge Directive</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($lab_completed === 'ddos'): ?>
        <div id="completionModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary"><span class="material-symbols-outlined text-3xl">verified_user</span></div>
                    <div><h2 class="text-2xl font-black uppercase italic tracking-tighter">Lab <span class="text-primary glow-text">Completed</span></h2><p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Subject: DDoS Mitigation Elite</p></div>
                </div>
                <div class="space-y-4 relative z-10 overflow-y-auto custom-scrollbar pr-2 mb-4">
                    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">analytics</span> Debrief</h4><p class="text-[11px] text-slate-400">Attack neutralized using layered mitigations. <strong class="text-slate-300">Rate Limiting + WAF + Geo-Blocking</strong> form the essential defense stack.</p></div>
                    <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">security</span> Key Takeaways</h4><ul class="space-y-1 text-[10px] text-slate-400"><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>Enable rate limiting at the edge/CDN level.</li><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>Maintain IP reputation feeds for proactive blocking.</li></ul></div>
                </div>
                <button onclick="window.history.replaceState(null,null,window.location.pathname);this.closest('#completionModal').remove();" class="w-full py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] relative z-10">Acknowledge Directive</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($lab_completed === 'malware'): ?>
        <div id="completionModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary"><span class="material-symbols-outlined text-3xl">verified</span></div>
                    <div><h2 class="text-2xl font-black uppercase italic tracking-tighter">Lab <span class="text-primary glow-text">Completed</span></h2><p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Subject: Malware Analysis — Threat Classification</p></div>
                </div>
                <div class="space-y-4 relative z-10 overflow-y-auto custom-scrollbar pr-2 mb-4">
                    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">analytics</span> Debrief</h4><p class="text-[11px] text-slate-400">You extracted IOCs and classified a malware sample via static + behavioral analysis. Share IOCs via <strong class="text-slate-300">threat intelligence platforms</strong> to protect the community.</p></div>
                    <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl"><h4 class="text-xs font-bold text-primary uppercase mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">security</span> Key Takeaways</h4><ul class="space-y-1 text-[10px] text-slate-400"><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>Always analyze in an <strong class="text-white">isolated sandbox</strong>.</li><li class="flex items-center gap-2"><span class="material-symbols-outlined text-xs text-primary">check_circle</span>High entropy → likely <strong class="text-white">packed/encrypted</strong> payload.</li></ul></div>
                </div>
                <button onclick="window.history.replaceState(null,null,window.location.pathname);this.closest('#completionModal').remove();" class="w-full py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] relative z-10">Acknowledge Directive</button>
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
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="../labs/ddos.php">
                        <span class="material-symbols-outlined text-xl">security</span> DDoS Defense
                    </a>
                    <a class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white" href="../labs/malware.php">
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
                                    <p class="text-[10px] text-primary mt-1"><?php echo $completed_labs >= 4 ? 'ALL COMPLETE' : $completed_labs . ' / 4 DONE'; ?></p>
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
                                    <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">DDoS Mitigation</span>
                                    <span class="text-3xl font-black text-white"><?php echo $ddos_progress; ?>%</span>
                                    <p class="text-[10px] text-primary mt-1"><?php echo $ddos_success ? 'NEUTRALIZED' : 'IN TRAINING'; ?></p>
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
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">security</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">MODULE_02</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">DDoS Mitigation Elite</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Deploy rate limiting, WAF rules, and geo-blocking to neutralize high-volume distributed traffic attacks in real time.</p>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                            <span class="text-slate-500">Mitigation Mastery</span>
                                            <span class="text-white"><?php echo $ddos_progress; ?>%</span>
                                        </div>
                                        <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full" style="width: <?php echo $ddos_progress; ?>%"></div>
                                        </div>
                                        <div class="flex justify-between items-center pt-2">
                                            <span class="text-[10px] text-slate-500">Tier <?php echo $ddos_success ? '2' : '1'; ?> Defense</span>
                                            <span class="text-[10px] text-primary font-bold"><?php echo $ddos_success ? 'ATTACK NEUTRALIZED' : 'NEXT: DEPLOY MITIGATIONS'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="../labs/ddos.php" class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim group-hover:bg-primary group-hover:text-background-dark transition-all">Initialize Lab Instance</a>
                            </div>

                            <!-- Module Card: Malware -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">bug_report</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">MODULE_03</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">Malware Analysis Lab</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Perform static and behavioral sandbox analysis on malware samples. Extract IOCs and classify threats like a professional analyst.</p>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                            <span class="text-slate-500">Analysis Progress</span>
                                            <span class="text-white"><?php echo $malware_progress; ?>%</span>
                                        </div>
                                        <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full" style="width: <?php echo $malware_progress; ?>%"></div>
                                        </div>
                                        <div class="flex justify-between items-center pt-2">
                                            <span class="text-[10px] text-slate-500">Tier <?php echo $malware_success ? '2' : '1'; ?> Analyst</span>
                                            <span class="text-[10px] text-primary font-bold"><?php echo $malware_success ? 'SAMPLE CLASSIFIED' : 'NEXT: BEHAVIORAL SCAN'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="../labs/malware.php" class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim group-hover:bg-primary group-hover:text-background-dark transition-all">Initialize Lab Instance</a>
                            </div>

                            <!-- Module Card: Brute Force -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">lock_open</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">MODULE_04</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">Brute Force Lab</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Simulate automated credential stuffing attacks and learn how to secure authentication against high-speed brute force attempts.</p>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                            <span class="text-slate-500">Attack Success</span>
                                            <span class="text-white"><?php echo $bruteforce_progress; ?>%</span>
                                        </div>
                                        <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-primary h-full rounded-full" style="width: <?php echo $bruteforce_progress; ?>%"></div>
                                        </div>
                                        <div class="flex justify-between items-center pt-2">
                                            <span class="text-[10px] text-slate-500">Tier <?php echo $bruteforce_success ? '2' : '1'; ?> Attacker</span>
                                            <span class="text-[10px] text-primary font-bold"><?php echo $bruteforce_success ? 'TARGET CRACKED' : 'NEXT: DICTIONARY SCAN'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="../labs/bruteforce.php" class="block w-full py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] border-t border-border-dim group-hover:bg-primary group-hover:text-background-dark transition-all">Initialize Lab Instance</a>
                            </div>

                            <!-- Module Card: Juice Shop (Docker) -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">CONTAINER_NODE</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">OWASP Juice Shop</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">A modern, vulnerable web application for security testing. Practice SQLi, XSS, and broken access control in a live container.</p>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-2.5 bg-background-dark/50 rounded-lg border border-border-dim">
                                            <div class="flex items-center">
                                                <span id="dot-juiceshop" class="lab-status-dot status-stopped"></span>
                                                <span id="status-juiceshop" class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Stopped</span>
                                            </div>
                                            <div class="flex gap-1.5">
                                                <button onclick="manageLab('juiceshop', 'start')" class="size-7 flex items-center justify-center rounded bg-primary/10 border border-primary/30 text-primary hover:bg-primary hover:text-black transition-all" title="Start Lab">
                                                    <span class="material-symbols-outlined text-sm">play_arrow</span>
                                                </button>
                                                <button onclick="manageLab('juiceshop', 'stop')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-red-500/20 hover:text-red-400 hover:border-red-400/30 transition-all" title="Stop Lab">
                                                    <span class="material-symbols-outlined text-sm">stop</span>
                                                </button>
                                                <button onclick="manageLab('juiceshop', 'reset')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-white/10 hover:text-white transition-all" title="Reset Lab">
                                                    <span class="material-symbols-outlined text-sm">refresh</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-border-dim border-t border-border-dim">
                                    <a href="https://pwning.owasp-juice.shop/" target="_blank" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 hover:text-primary transition-all">Challenge Guide</a>
                                    <a href="#" onclick="manageLab('juiceshop', 'start'); return false;" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] group-hover:bg-primary group-hover:text-background-dark transition-all">Launch Console</a>
                                </div>
                            </div>

                            <!-- Module Card: DVWA (Docker) -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">database</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">CONTAINER_NODE</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">Web DVWA</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Damn Vulnerable Web Application. A PHP/MySQL web application that is damn vulnerable. Test various security levels.</p>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-2.5 bg-background-dark/50 rounded-lg border border-border-dim">
                                            <div class="flex items-center">
                                                <span id="dot-dvwa" class="lab-status-dot status-stopped"></span>
                                                <span id="status-dvwa" class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Stopped</span>
                                            </div>
                                            <div class="flex gap-1.5">
                                                <button onclick="manageLab('dvwa', 'start')" class="size-7 flex items-center justify-center rounded bg-primary/10 border border-primary/30 text-primary hover:bg-primary hover:text-black transition-all" title="Start Lab">
                                                    <span class="material-symbols-outlined text-sm">play_arrow</span>
                                                </button>
                                                <button onclick="manageLab('dvwa', 'stop')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-red-500/20 hover:text-red-400 hover:border-red-400/30 transition-all" title="Stop Lab">
                                                    <span class="material-symbols-outlined text-sm">stop</span>
                                                </button>
                                                <button onclick="manageLab('dvwa', 'reset')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-white/10 hover:text-white transition-all" title="Reset Lab">
                                                    <span class="material-symbols-outlined text-sm">refresh</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-border-dim border-t border-border-dim">
                                    <a href="https://github.com/digininja/DVWA" target="_blank" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 hover:text-primary transition-all">Documentation</a>
                                    <a href="#" onclick="manageLab('dvwa', 'start'); return false;" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] group-hover:bg-primary group-hover:text-background-dark transition-all">Launch Console</a>
                                </div>
                            </div>

                            <!-- Module Card: bWAPP (Docker) -->
                            <div class="glass-panel group rounded-2xl p-1 transition-all hover:border-primary/40">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="size-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-background-dark transition-all duration-300">
                                            <span class="material-symbols-outlined text-2xl">bug_report</span>
                                        </div>
                                        <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary uppercase">CONTAINER_NODE</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-white mb-2 group-hover:text-primary transition-colors">bWAPP Lab</h4>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">buggy Web Application. A free and open source deliberately insecure web application. Covers over 100 vulnerabilities.</p>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-2.5 bg-background-dark/50 rounded-lg border border-border-dim">
                                            <div class="flex items-center">
                                                <span id="dot-bwapp" class="lab-status-dot status-stopped"></span>
                                                <span id="status-bwapp" class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Stopped</span>
                                            </div>
                                            <div class="flex gap-1.5">
                                                <button onclick="manageLab('bwapp', 'start')" class="size-7 flex items-center justify-center rounded bg-primary/10 border border-primary/30 text-primary hover:bg-primary hover:text-black transition-all" title="Start Lab">
                                                    <span class="material-symbols-outlined text-sm">play_arrow</span>
                                                </button>
                                                <button onclick="manageLab('bwapp', 'stop')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-red-500/20 hover:text-red-400 hover:border-red-400/30 transition-all" title="Stop Lab">
                                                    <span class="material-symbols-outlined text-sm">stop</span>
                                                </button>
                                                <button onclick="manageLab('bwapp', 'reset')" class="size-7 flex items-center justify-center rounded bg-white/5 border border-white/10 text-slate-500 hover:bg-white/10 hover:text-white transition-all" title="Reset Lab">
                                                    <span class="material-symbols-outlined text-sm">refresh</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-border-dim border-t border-border-dim">
                                    <a href="http://www.itsecgames.com/" target="_blank" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 hover:text-primary transition-all">Vulnerability List</a>
                                    <a href="#" onclick="manageLab('bwapp', 'start'); return false;" class="py-4 text-center text-[9px] font-black uppercase tracking-[0.2em] group-hover:bg-primary group-hover:text-background-dark transition-all">Launch Console</a>
                                </div>
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