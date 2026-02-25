<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ..auth/login.php");
    exit();
}

$userName = $_SESSION['user_name'];
?>
<!DOCTYPE html>

<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield Command Center</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-light": "#f7f8f5",
                        "background-dark": "#0a0a0a",
                        "neutral-dark": "#1c230f",
                        "surface": "#161810",
                        "border-dim": "#343a27",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .bg-glow-primary {
            box-shadow: 0 0 15px rgba(160, 240, 0, 0.15);
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #161810;
        }

        ::-webkit-scrollbar-thumb {
            background: #343a27;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0f000;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 antialiased h-screen overflow-hidden">
    <div class="flex h-full w-full">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0 border-r border-border-dim bg-surface flex flex-col justify-between p-4">
            <div class="flex flex-col gap-8">
                <!-- Brand -->
                <div class="flex items-center gap-3 px-2">
                    <div class="size-10 bg-primary rounded-lg flex items-center justify-center text-background-dark">
                        <span class="material-symbols-outlined font-bold">shield</span>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-white text-base font-bold leading-none tracking-tight">CyberShield</h1>
                        <p class="text-primary/60 text-[10px] uppercase tracking-widest font-bold mt-1">Command Center</p>
                    </div>
                </div>
                <!-- Nav Links -->
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-background-dark font-semibold transition-all" href="#">
                        <span class="material-symbols-outlined fill-1">dashboard</span>
                        <span class="text-sm">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-white/5 hover:text-white transition-all" href="#">
                        <span class="material-symbols-outlined">biotech</span>
                        <span class="text-sm">SOC Lab</span>
                    </a>

                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-white/5 hover:text-white transition-all" href="#">
                        <span class="material-symbols-outlined">person</span>
                        <span class="text-sm">Profile</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:bg-white/5 hover:text-white transition-all" href="#">
                        <span class="material-symbols-outlined">settings</span>
                        <span class="text-sm">Settings</span>
                    </a>
                </nav>
            </div>
            <!-- Sidebar Footer -->
            <div class="space-y-4">
                <div class="bg-border-dim/30 p-4 rounded-xl border border-border-dim">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Next Rank Progress</span>
                        <span class="text-[10px] font-bold text-primary">0%</span>
                    </div>
                    <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                        <div class="bg-primary h-full rounded-full" style="width: 0%"></div>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2">Earn 550 more XP for <span class="text-white">Senior Analyst</span></p>
                </div>
                <button class="w-full flex items-center justify-center gap-2 py-2.5 bg-primary/10 border border-primary/20 text-primary rounded-lg text-xs font-bold hover:bg-primary/20 transition-all">
                    <div class="size-2 bg-primary rounded-full animate-pulse"></div>
                    SYSTEM ONLINE
                </button>
            </div>
        </aside>
        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="h-16 border-b border-border-dim bg-surface/50 backdrop-blur-md flex items-center justify-between px-8">
                <div class="flex items-center gap-8">
                    <div class="relative group">
                        <span class="material-symbols-outlined text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 text-lg">search</span>
                        <input class="bg-background-dark border-border-dim rounded-lg pl-10 pr-4 py-1.5 text-sm w-80 focus:ring-1 focus:ring-primary focus:border-primary transition-all text-white placeholder:text-slate-600" placeholder="Search systems, labs, or alerts..." type="text" />
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3 px-4 py-1.5 bg-neutral-dark border border-border-dim rounded-lg">
                        <span class="text-xs font-bold text-slate-400">XP: <span class="text-primary ml-1">0</span></span>
                        <div class="w-px h-3 bg-border-dim"></div>
                        <span class="text-xs font-bold text-slate-400">RANK: <span class="text-white ml-1">Novice Analyst</span></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs font-bold text-white leading-none">
                                    <?php echo htmlspecialchars($userName); ?>
                                </p>
                                <p class="text-[10px] text-primary font-medium mt-1">Lvl 12 Student</p>
                            </div>
                            <div class="size-9 rounded-full bg-gradient-to-tr from-primary to-lime-600 border-2 border-surface flex items-center justify-center text-background-dark font-bold text-sm">
                                <?php
                                $nameParts = explode(" ", trim($userName));

                                if (count($nameParts) > 1) {
                                    // If name has space → first letter of first two words
                                    echo strtoupper($nameParts[0][0] . $nameParts[1][0]);
                                } else {
                                    // If single word → only first letter
                                    echo strtoupper($userName[0]);
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Logout Button -->
                        <button onclick="confirmLogout()" class="size-9 rounded-full bg-surface border border-border-dim text-slate-400 hover:text-red-500 hover:border-red-500/50 hover:bg-red-500/10 transition-all flex items-center justify-center group" title="Logout">
                            <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform">logout</span>
                        </button>
                    </div>
                </div>
            </header>
            <!-- Scrollable Dashboard Content -->
            <div class="flex-1 overflow-y-auto p-8 flex gap-8">
                <!-- Left Section: Pathway Grid & Summary -->
                <div class="flex-1 flex flex-col gap-8">
                    <!-- Welcome & Stats -->
                    <section class="flex flex-col gap-6">
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-tight">Command Center Dashboard</h2>
                            <p class="text-slate-400 text-sm mt-1">Operational status: <span class="text-primary">Nominal</span>. Review your active training pathways below.</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-surface p-5 rounded-xl border border-border-dim flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Completed Labs</span>
                                    <span class="material-symbols-outlined text-primary">check_circle</span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-white">0</span>
                                    <span class="text-sm text-slate-500">/ 45 modules</span>
                                </div>
                                <div class="w-full bg-background-dark h-1 rounded-full mt-2">
                                    <div class="bg-primary h-full" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="bg-surface p-5 rounded-xl border border-border-dim flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">System Readiness</span>
                                    <span class="material-symbols-outlined text-primary">bolt</span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-white">0%</span>
                                    <span class="text-xs text-primary font-bold">+0% vs last week</span>
                                </div>
                                <div class="w-full bg-background-dark h-1 rounded-full mt-2">
                                    <div class="bg-primary h-full" style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="bg-surface p-5 rounded-xl border border-border-dim flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Active Alerts</span>
                                    <span class="material-symbols-outlined text-amber-500">warning</span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-black text-white">00</span>
                                    <span class="text-sm text-slate-500">critical simulations</span>
                                </div>
                                <div class="w-full bg-background-dark h-1 rounded-full mt-2">
                                    <div class="bg-amber-500 h-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- Pathways Section -->
                    <section>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-white">Simulation Pathways</h3>
                            <button class="text-primary text-xs font-bold hover:underline">VIEW ALL PATHWAYS</button>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Pathway Card: Phishing -->
                            <div class="bg-surface p-6 rounded-xl border border-border-dim group hover:border-primary/50 transition-all cursor-pointer">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="p-3 bg-primary/10 rounded-lg text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                        <span class="material-symbols-outlined">mail</span>
                                    </div>
                                    <span class="px-2 py-1 bg-neutral-dark text-[10px] font-bold text-primary rounded border border-primary/20 uppercase">Easy</span>
                                </div>
                                <h4 class="text-lg font-bold text-white mb-1">Social Engineering &amp; Phishing</h4>
                                <p class="text-xs text-slate-400 mb-6">Learn to identify and mitigate advanced email-based threats.</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="text-white">0%</span>
                                    </div>
                                    <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-primary h-full rounded-full" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center pt-2">
                                        <span class="text-[10px] text-slate-500">Level 2 / 5</span>
                                        <span class="text-[10px] text-primary font-bold">NEXT: HEADER ANALYSIS</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Pathway Card: Brute Force -->
                            <div class="bg-surface p-6 rounded-xl border border-border-dim group hover:border-primary/50 transition-all cursor-pointer">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="p-3 bg-primary/10 rounded-lg text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                        <span class="material-symbols-outlined">lock_open</span>
                                    </div>
                                    <span class="px-2 py-1 bg-neutral-dark text-[10px] font-bold text-amber-500 rounded border border-amber-500/20 uppercase">Medium</span>
                                </div>
                                <h4 class="text-lg font-bold text-white mb-1">Brute Force &amp; Credential Stuffing</h4>
                                <p class="text-xs text-slate-400 mb-6">Defending authentication gateways from automated attacks.</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="text-white">0%</span>
                                    </div>
                                    <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-primary h-full rounded-full" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center pt-2">
                                        <span class="text-[10px] text-slate-500">Level 1 / 8</span>
                                        <span class="text-[10px] text-primary font-bold">NEXT: RATE LIMITING</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Pathway Card: DDoS -->
                            <div class="bg-surface p-6 rounded-xl border border-border-dim group hover:border-primary/50 transition-all cursor-pointer opacity-75">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="p-3 bg-primary/10 rounded-lg text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                        <span class="material-symbols-outlined">lan</span>
                                    </div>
                                    <span class="px-2 py-1 bg-neutral-dark text-[10px] font-bold text-red-500 rounded border border-red-500/20 uppercase">Hard</span>
                                </div>
                                <h4 class="text-lg font-bold text-white mb-1">DDoS Mitigation Strategies</h4>
                                <p class="text-xs text-slate-400 mb-6">Analyzing traffic patterns and implementing firewall rules.</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="text-white">0%</span>
                                    </div>
                                    <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-primary h-full rounded-full" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center pt-2">
                                        <span class="text-[10px] text-slate-500">Not Started</span>
                                        <span class="text-[10px] text-slate-500 font-bold">LOCKED</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Pathway Card: Malware -->
                            <div class="bg-surface p-6 rounded-xl border border-border-dim group hover:border-primary/50 transition-all cursor-pointer">
                                <div class="flex justify-between items-start mb-6">
                                    <div class="p-3 bg-primary/10 rounded-lg text-primary group-hover:bg-primary group-hover:text-background-dark transition-all">
                                        <span class="material-symbols-outlined">bug_report</span>
                                    </div>
                                    <span class="px-2 py-1 bg-neutral-dark text-[10px] font-bold text-red-500 rounded border border-red-500/20 uppercase">Hard</span>
                                </div>
                                <h4 class="text-lg font-bold text-white mb-1">Malware Analysis &amp; Sandbox</h4>
                                <p class="text-xs text-slate-400 mb-6">Dissecting malicious binaries in a safe, controlled environment.</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="text-white">0%</span>
                                    </div>
                                    <div class="w-full bg-background-dark h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-primary h-full rounded-full" style="width: 0%"></div>
                                    </div>
                                    <div class="flex justify-between items-center pt-2">
                                        <span class="text-[10px] text-slate-500">Level 3 / 4</span>
                                        <span class="text-[10px] text-primary font-bold">FINAL: REVERSE ENGINEERING</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <!-- Right Section: Activity Feed -->
                <aside class="w-80 flex flex-col gap-6 max-h-full">
                    <div class="bg-surface rounded-xl border border-border-dim flex flex-col overflow-hidden h-[500px]">
                        <div class="p-4 border-b border-border-dim flex items-center justify-between bg-neutral-dark/30">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">history</span>
                                RECENT ACTIVITY
                            </h3>
                            <button class="material-symbols-outlined text-slate-500 hover:text-white text-lg">refresh</button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-4 space-y-4">
                            <!-- Activity Item -->
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 size-8 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">play_arrow</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-xs text-white leading-tight">Started <span class="text-primary">'Email Header Analysis'</span> Lab</p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">14:22:05 — SESSION_INIT</p>
                                </div>
                            </div>
                            <!-- Activity Item -->
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 size-8 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">check</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-xs text-white leading-tight">Brute Force Simulation: <span class="text-primary">Successful Detection</span></p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">11:05:30 — EVENT_SUCCESS</p>
                                </div>
                            </div>
                            <!-- Activity Item (Alert) -->
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 size-8 bg-red-500/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-red-500 text-sm">priority_high</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-xs text-white leading-tight">Alert: <span class="text-red-500">Unauthorized Access</span> Detected (Simulation)</p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">09:12:15 — THREAT_DETECT</p>
                                </div>
                            </div>
                            <!-- Activity Item -->
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 size-8 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">military_tech</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-xs text-white leading-tight">Achievement Unlocked: <span class="text-primary">First Firewall Rule</span></p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">Yesterday — RANK_UP</p>
                                </div>
                            </div>
                            <!-- Activity Item -->
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 size-8 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">login</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-xs text-white leading-tight">Logged in from <span class="text-primary">192.168.1.45</span></p>
                                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">Yesterday — AUTH_LOG</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-t border-border-dim bg-neutral-dark/30 text-center">
                            <button class="text-[10px] font-bold text-slate-500 hover:text-primary transition-colors">VIEW FULL SYSTEM LOGS</button>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "../auth/logout.php";
            }
        }
    </script>
</body>

</html>