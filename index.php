<?php
require_once __DIR__ . '/config/session.php';
?>
<!DOCTYPE html>

<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Master Attack &amp; Defense</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23a0f000'><path d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.47 4.34-3.1 8.25-7 9.53V12H5V6.3l7-3.11v8.8z'/></svg>">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-light": "#f7f8f5",
                        "background-dark": "#0a0a0a",
                        "surface-dark": "#161810",
                        "border-dark": "#343a27",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "mono": ["ui-monospace", "SFMono-Regular", "Menlo", "Monaco", "Consolas", "Liberation Mono", "Courier New", "monospace"]
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
        .grid-pattern {
            background-image: radial-gradient(circle, #343a27 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .scanline {
            width: 100%;
            height: 2px;
            background: rgba(160, 240, 0, 0.1);
            position: absolute;
            z-index: 10;
            top: 0;
            pointer-events: none;
        }

        .glow-shield {
            box-shadow: 0 0 50px -10px rgba(160, 240, 0, 0.3);
        }

        .terminal-window {
            background: linear-gradient(135deg, #161810 0%, #0a0a0a 100%);
        }

        .terminal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            backdrop-filter: blur(8px);
        }

        .terminal-content {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            color: #a0f000;
            line-height: 1.5;
            overflow-y: auto;
            max-height: 100%;
        }

        .repo-lock {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .cursor {
            display: inline-block;
            width: 8px;
            height: 15px;
            background: #a0f000;
            animation: blink 1s step-end infinite;
            vertical-align: middle;
            margin-left: 5px;
        }

        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 selection:bg-primary selection:text-background-dark overflow-x-hidden">
    <!-- Sticky Navigation -->
    <nav class="sticky top-0 z-50 w-full border-b border-border-dark bg-background-dark/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="size-8 bg-primary rounded flex items-center justify-center">
                    <span class="material-symbols-outlined text-background-dark font-bold">shield</span>
                </div>
                <a href="index.php" class="text-xl font-black tracking-tighter text-white uppercase italic">
                    CyberShield
                </a>
            </div>
            <div class="hidden md:flex items-center gap-10">
                <a class="text-sm font-medium hover:text-primary transition-colors" href="index.php#modules">
                    Modules
                </a>
                <a class="text-sm font-medium hover:text-primary transition-colors" href="index.php#soc">
                    SOC Experience
                </a>
                <a class="text-sm font-medium hover:text-primary transition-colors" href="about.php">
                    About
                </a>
            </div>
            <div class="flex items-center gap-4">
                <a href="auth/login.php" class="text-sm font-bold text-white hover:text-primary px-4 py-2 transition-colors">
                    Login
                </a>
                <a href="auth/register.php" class="bg-primary text-background-dark text-sm font-black px-6 py-2 rounded uppercase tracking-wider hover:brightness-110 transition-all">
                    Get Started
                </a>
            </div>
        </div>
    </nav>
    <main class="relative grid-pattern min-h-screen">
        <!-- Hero Section -->
        <section class="relative max-w-7xl mx-auto px-6 pt-20 pb-32 flex flex-col lg:flex-row items-center gap-16">
            <div class="flex-1 space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-primary/30 bg-primary/10 text-primary text-xs font-bold uppercase tracking-widest">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                    </span>
                    System Online: V.2.0.4
                </div>
                <h2 class="text-5xl md:text-7xl font-black text-white leading-[1.1] tracking-tight">
                    Master the Art of <span class="text-primary italic">Attack</span> and <span class="border-b-4 border-primary/40">Defense</span>
                </h2>
                <p class="text-lg md:text-xl text-slate-400 max-w-xl font-light leading-relaxed">
                    Practical security training and real-time SOC monitoring for aspiring analysts. Develop technical skills through guided scenarios and live defensive operations.
                </p>
                <div class="flex flex-wrap gap-4 pt-4">
                    <a href="auth/register.php" class="bg-primary text-background-dark font-black px-8 py-4 rounded-lg uppercase tracking-widest text-lg shadow-[0_0_30px_-5px_rgba(160,240,0,0.5)] hover:scale-105 transition-transform inline-block">
                        Access Lab
                    </a>
                </div>
            </div>
            <div class="flex-1 relative w-full aspect-square max-w-[500px]">
                <!-- 3D Shield Graphic Representation -->
                <div class="absolute inset-0 bg-primary/20 rounded-full blur-[120px] opacity-30"></div>
                <div class="relative w-full h-full flex items-center justify-center">
                    <div class="relative z-10 w-64 h-80 bg-surface-dark border-4 border-primary rounded-xl overflow-hidden shadow-[0_0_60px_-10px_rgba(160,240,0,0.4)] flex flex-col">
                        <div class="h-8 border-b border-border-dark flex items-center px-3 gap-1">
                            <div class="size-2 rounded-full bg-red-500"></div>
                            <div class="size-2 rounded-full bg-yellow-500"></div>
                            <div class="size-2 rounded-full bg-green-500"></div>
                        </div>
                        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                            <span class="material-symbols-outlined text-8xl text-primary mb-4">verified_user</span>
                            <div class="space-y-2 w-full">
                                <div class="h-1 bg-primary/20 w-full rounded-full overflow-hidden">
                                    <div class="h-full bg-primary w-[75%]"></div>
                                </div>
                                <p class="text-[10px] font-mono text-primary/70 tracking-tighter">ENCRYPTING_LAYER_04...</p>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-10 -right-10 p-4 border border-border-dark bg-background-dark/80 rounded font-mono text-xs text-primary/80 backdrop-blur">
                        <p class="text-white mb-1">Incoming Packets:</p>
                        <p>192.168.1.4 &gt; 80</p>
                        <p>192.168.1.12 &gt; 443</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Core Modules Section -->
        <section id="modules" class="max-w-7xl mx-auto px-6 py-24 border-t border-border-dark">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-4">
                <div class="space-y-2">
                    <h3 class="text-4xl font-black text-white uppercase italic">Core Modules</h3>
                </div>
                <p class="text-slate-400 font-mono text-xs">Total Progress: 12% Completed</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Module 1 -->
                <a href="auth/register.php" class="group relative bg-surface-dark border border-border-dark rounded-xl p-6 hover:border-primary/50 transition-all cursor-pointer block">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary/10 group-hover:bg-primary transition-colors"></div>
                    <span class="material-symbols-outlined text-primary text-4xl mb-6">mail</span>
                    <h4 class="text-xl font-bold text-white mb-2">Phishing</h4>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">Deploy advanced social engineering campaigns and analyze psychological triggers.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-500 uppercase">Difficulty: Medium</span>
                        <span class="material-symbols-outlined text-primary text-xl translate-x-4 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all">arrow_forward</span>
                    </div>
                </a>
                <!-- Module 2 -->
                <a href="auth/register.php" class="group relative bg-surface-dark border border-border-dark rounded-xl p-6 hover:border-primary/50 transition-all cursor-pointer block">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary/10 group-hover:bg-primary transition-colors"></div>
                    <span class="material-symbols-outlined text-primary text-4xl mb-6">password</span>
                    <h4 class="text-xl font-bold text-white mb-2">Brute Force</h4>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">Master credential exhaustion techniques and password cracking workflows.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-500 uppercase">Difficulty: Hard</span>
                        <span class="material-symbols-outlined text-primary text-xl translate-x-4 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all">arrow_forward</span>
                    </div>
                </a>
                <!-- Module 3 -->
                <a href="auth/register.php" class="group relative bg-surface-dark border border-border-dark rounded-xl p-6 hover:border-primary/50 transition-all cursor-pointer block">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary/10 group-hover:bg-primary transition-colors"></div>
                    <span class="material-symbols-outlined text-primary text-4xl mb-6">waves</span>
                    <h4 class="text-xl font-bold text-white mb-2">DDoS</h4>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">Test network resilience and simulate high-volume resource exhaustion attacks.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-500 uppercase">Difficulty: Easy</span>
                        <span class="material-symbols-outlined text-primary text-xl translate-x-4 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all">arrow_forward</span>
                    </div>
                </a>
                <!-- Module 4 -->
                <a href="auth/register.php" class="group relative bg-surface-dark border border-border-dark rounded-xl p-6 hover:border-primary/50 transition-all cursor-pointer block">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary/10 group-hover:bg-primary transition-colors"></div>
                    <span class="material-symbols-outlined text-primary text-4xl mb-6">bug_report</span>
                    <h4 class="text-xl font-bold text-white mb-2">Malware</h4>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">Perform static and dynamic analysis in controlled sandbox environments.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono text-slate-500 uppercase">Difficulty: Extreme</span>
                        <span class="material-symbols-outlined text-primary text-xl translate-x-4 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all">arrow_forward</span>
                    </div>
                </a>
        </section>
        <!-- The SOC Experience Section -->
        <section id="soc" class="bg-surface-dark py-32 border-y border-border-dark relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10">
                <div class="lg:col-span-5 space-y-6">
                    <h3 class="text-4xl md:text-5xl font-black text-white uppercase italic leading-tight">The SOC <br /> Experience</h3>
                    <p class="text-slate-400 leading-relaxed">
                        Step into the shoes of a Security Operations Center analyst. Monitor real-time log streams, manage high-priority alerts, and neutralize threats before they compromise the infrastructure.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-white font-medium">
                            <span class="material-symbols-outlined text-primary">done_all</span> Real-time SIEM Integration
                        </li>
                        <li class="flex items-center gap-3 text-white font-medium">
                            <span class="material-symbols-outlined text-primary">done_all</span> Incident Response Playbooks
                        </li>
                        <li class="flex items-center gap-3 text-white font-medium">
                            <span class="material-symbols-outlined text-primary">done_all</span> Advanced Threat Hunting
                        </li>
                    </ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard/dashboard.php" class="bg-primary/10 border border-primary text-primary font-black px-6 py-3 rounded uppercase tracking-wider hover:bg-primary hover:text-background-dark transition-all inline-block">
                            Launch Dashboard
                        </a>
                    <?php else: ?>
                        <a href="auth/register.php" class="bg-primary/10 border border-primary text-primary font-black px-6 py-3 rounded uppercase tracking-wider hover:bg-primary hover:text-background-dark transition-all inline-block">
                            Launch Dashboard
                        </a>
                    <?php endif; ?>
                </div>
                <div class="lg:col-span-7">
                    <!-- Mock Dashboard UI -->
                    <div class="bg-background-dark border border-border-dark rounded-xl overflow-hidden shadow-2xl">
                        <div class="bg-surface-dark p-3 border-b border-border-dark flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-2 rounded-full bg-red-500 animate-pulse"></div>
                                <span class="text-[10px] font-mono text-white tracking-widest uppercase">Live Threat Feed</span>
                            </div>
                            <div class="flex gap-1">
                                <div class="w-8 h-1 bg-border-dark"></div>
                                <div class="w-8 h-1 bg-primary"></div>
                            </div>
                        </div>
                        <div class="p-0 grid grid-cols-3 min-h-[400px]">
                            <div class="col-span-2 border-r border-border-dark p-4 flex flex-col gap-4">
                                <div class="h-32 rounded bg-surface-dark/50 p-3 flex flex-col justify-end relative overflow-hidden">
                                    <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: linear-gradient(0deg, #a0f000 0%, transparent 100%); background-size: 100% 10px;"></div>
                                    <div class="text-[10px] font-mono text-primary flex justify-between">
                                        <span>NETWORK_LATENCY</span>
                                        <span>42ms</span>
                                    </div>
                                    <div class="h-12 w-full flex items-end gap-1">
                                        <div class="flex-1 bg-primary/40 h-[40%]"></div>
                                        <div class="flex-1 bg-primary/40 h-[60%]"></div>
                                        <div class="flex-1 bg-primary/40 h-[30%]"></div>
                                        <div class="flex-1 bg-primary/40 h-[90%]"></div>
                                        <div class="flex-1 bg-primary/40 h-[70%]"></div>
                                        <div class="flex-1 bg-primary/40 h-[50%]"></div>
                                    </div>
                                </div>
                                <div class="flex-1 font-mono text-[10px] space-y-1 text-slate-500">
                                    <p><span class="text-primary">[14:22:01]</span> ALERT: Potential SQLi detected on IP 10.0.0.52</p>
                                    <p><span class="text-primary">[14:22:05]</span> LOG: User 'admin' logged in from unauthorized location.</p>
                                    <p><span class="text-primary">[14:22:12]</span> CRITICAL: Unauthorized file access in /root/secrets</p>
                                    <p><span class="text-primary">[14:22:18]</span> STATUS: Firewall rules updated by SYSTEM</p>
                                    <p><span class="text-primary">[14:22:25]</span> ALERT: Outbound connection to known C2 server</p>
                                </div>
                            </div>
                            <div class="p-4 bg-surface-dark/30">
                                <p class="text-[10px] font-mono text-white uppercase mb-4 border-b border-border-dark pb-2">Active Alerts</p>
                                <div class="space-y-3">
                                    <div class="p-2 border border-red-900/50 bg-red-900/10 rounded">
                                        <p class="text-[9px] text-red-500 font-bold uppercase">Critical</p>
                                        <p class="text-[10px] text-white">Data Exfiltration</p>
                                    </div>
                                    <div class="p-2 border border-yellow-900/50 bg-yellow-900/10 rounded">
                                        <p class="text-[9px] text-yellow-500 font-bold uppercase">Warning</p>
                                        <p class="text-[10px] text-white">RDP Brute Force</p>
                                    </div>
                                    <div class="p-2 border border-blue-900/50 bg-blue-900/10 rounded">
                                        <p class="text-[9px] text-blue-500 font-bold uppercase">Info</p>
                                        <p class="text-[10px] text-white">New User Created</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decorative Background Element -->
            <div class="absolute right-0 top-0 w-1/3 h-full bg-primary/5 blur-[150px] pointer-events-none"></div>
        </section>
        <!-- CTA Section -->
        <section class="max-w-4xl mx-auto px-6 py-32 text-center">
            <h3 class="text-4xl md:text-5xl font-black text-white uppercase mb-6 tracking-tighter">Ready to <span class="text-primary italic underline decoration-4 underline-offset-8">get started?</span></h3>
            <p class="text-slate-400 text-lg mb-12 max-w-2xl mx-auto">
                Join a community of security professionals and students building their expertise on a modern, hands-on training platform.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4"><?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard/dashboard.php" class="w-full sm:w-auto bg-primary text-background-dark font-black px-10 py-4 rounded-lg uppercase tracking-widest text-xl shadow-[0_0_30px_-5px_rgba(160,240,0,0.4)] hover:scale-105 transition-all inline-block text-center">
                        START YOUR JOURNEY
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="w-full sm:w-auto bg-primary text-background-dark font-black px-10 py-4 rounded-lg uppercase tracking-widest text-xl shadow-[0_0_30px_-5px_rgba(160,240,0,0.4)] hover:scale-105 transition-all inline-block text-center">
                        START YOUR JOURNEY
                    </a>
                <?php endif; ?>
    </main>
    <!-- Footer -->
    <footer class="bg-background-dark border-t border-border-dark py-12">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-3 grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all">
                <div class="size-6 bg-primary rounded flex items-center justify-center">
                    <span class="material-symbols-outlined text-background-dark text-sm font-bold">shield</span>
                </div>
                <h1 class="text-lg font-black tracking-tighter text-white uppercase italic">CyberShield</h1>
            </div>
            <div class="flex gap-8 text-slate-500 text-xs font-mono uppercase tracking-widest">
                <a class="hover:text-primary" href="about.php">About</a>
                <a class="hover:text-primary" href="javascript:void(0)" onclick="return false;">Status</a>
                <a class="hover:text-primary" href="javascript:void(0)" onclick="return false;">API</a>
                <a class="hover:text-primary" href="javascript:void(0)" onclick="return false;">Privacy</a>
                <a class="hover:text-primary" href="javascript:void(0)" onclick="return false;">Terms</a>
            </div>
            <div class="flex gap-4">
                <a class="size-10 rounded border border-border-dark flex items-center justify-center text-slate-400 hover:border-primary hover:text-primary transition-all" href="javascript:void(0)" onclick="toggleTerminal(true)">
                    <span class="material-symbols-outlined">terminal</span>
                </a>
                <a class="size-10 rounded border border-border-dark flex items-center justify-center text-slate-400 hover:border-primary hover:text-primary transition-all" href="javascript:void(0)" onclick="toggleRepo(true)">
                    <span class="material-symbols-outlined">code</span>
                </a>
                <button onclick="navigator.clipboard.writeText(window.location.href); alert('CyberShield project link copied to clipboard!');" class="size-10 rounded border border-border-dark flex items-center justify-center text-slate-400 hover:border-primary hover:text-primary transition-all" title="Share Project">
                    <span class="material-symbols-outlined">share</span>
                </button>
            </div>
        </div>
        <div class="mt-8 text-center text-slate-600 text-[10px] font-mono">
            © 2024 CYBERSHIELD OPERATIONS INC. ALL RIGHTS RESERVED. // AUTHORIZED ACCESS ONLY.
        </div>
    </footer>
    <!-- Scroll Buttons -->
    <div class="fixed bottom-8 right-8 flex flex-col gap-3 z-[100]">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="size-12 rounded-full bg-surface-dark border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-background-dark transition-all shadow-glow group">
            <span class="material-symbols-outlined group-hover:animate-bounce">arrow_upward</span>
        </button>
        <button onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" class="size-12 rounded-full bg-surface-dark border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-background-dark transition-all shadow-glow group">
            <span class="material-symbols-outlined group-hover:animate-bounce">arrow_downward</span>
        </button>
    </div>
    <!-- Mock Terminal Overlay -->
    <div id="terminalOverlay" class="terminal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 200;">
        <div class="max-w-4xl mx-auto h-full p-8 flex flex-col">
            <div class="flex items-center justify-between mb-4 border-b border-primary/20 pb-2">
                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined text-primary text-sm">terminal</span>
                    <h4 class="text-[10px] font-mono text-white/70 uppercase tracking-[0.2em]">Deployment :: Node_Alpha_01 [SECURED]</h4>
                </div>
                <button onclick="toggleTerminal(false)" class="text-slate-500 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="terminalLog" class="terminal-content flex-1 p-4 bg-black/40 rounded-lg text-sm font-mono text-slate-300 overflow-y-auto">
                <p>Establishing secure connection to CYBERSHIELD_CENTRAL...</p>
                <p>Connection established. Encryption layer active.</p>
                <p>Welcome, Operator. Monitoring system nodes...</p>
                <p class="mt-4"><span class="text-white">root@cybershield:~$</span> <span id="typedValue"></span><span class="cursor"></span></p>
            </div>
        </div>
    </div>

    <!-- Mock Repository Overlay -->
    <div id="repoOverlay" class="terminal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 250;">
        <div class="max-w-4xl mx-auto h-full p-8 flex flex-col">
            <div class="flex items-center justify-between mb-8 border-b border-primary/20 pb-4">
                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined text-primary text-sm">source</span>
                    <h4 class="text-[10px] font-mono text-white/70 uppercase tracking-[0.2em]">Core :: Simulation_Kernels [V.5.4]</h4>
                </div>
                <button onclick="toggleRepo(false)" class="text-slate-500 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="flex-1 flex flex-col items-center justify-center space-y-12">
                <div class="size-24 rounded-full border-4 border-red-500/50 flex items-center justify-center animate-pulse shadow-[0_0_50px_rgba(239,68,68,0.2)]">
                    <span class="material-symbols-outlined text-6xl text-red-500 repo-lock">lock</span>
                </div>
                <div class="text-center space-y-4">
                    <h5 class="text-2xl font-black text-white uppercase tracking-tighter">Repository Access Restricted</h5>
                    <p class="text-slate-400 max-w-md mx-auto">This repository contains proprietary simulation kernels. Specialized clearance and hardware-keys are required for retrieval.</p>
                </div>
                <div class="w-full max-w-sm space-y-4">
                    <div class="h-12 bg-white/5 border border-white/10 rounded flex items-center px-4 gap-3 relative">
                        <span class="material-symbols-outlined text-slate-500 text-sm">key</span>
                        <input type="password" id="repoSeedInput" placeholder="ENTER_CLEARANCE_SEED" class="bg-transparent border-none outline-none text-primary font-mono text-sm w-full" oninput="showRepoError()">
                        <span class="text-[10px] font-mono text-slate-500">AUTH_REQ</span>
                        <!-- Error Message -->
                        <div id="repoError" class="absolute -top-10 left-0 w-full text-center hidden">
                            <span class="text-[10px] bg-red-500/20 border border-red-500/50 text-red-500 px-3 py-1 rounded font-mono animate-pulse">! ACCESS_DENIED: INVALID_SEED_FORMAT</span>
                        </div>
                    </div>
                    <a href="auth/register.php" class="block w-full bg-primary/20 border border-primary/50 text-primary py-3 rounded font-black text-center uppercase tracking-widest hover:bg-primary hover:text-black transition-all">
                        Request Source Access
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let terminalInterval;
        let logCount = 0;
        const logEntries = [
            "SCANNING_NETWORK: 192.168.1.0/24 - COMPLETED",
            "ALERT: Unauthorized packet attempt on PORT 8080 - BLOCKED",
            "SYNCING: Lab database with node_east_04",
            "LOG: User 'dev_analyst' initialized Phishing simulation",
            "CORE: CPU Load 42% | MEMORY 2.4GB Available",
            "STATUS: Firewall rules updated successfully",
            "SECURITY: SQL Injection signature detected and neutralized",
            "NODE: 10.0.0.52 is online and responding",
            "TRAFFIC: 1.2 GB/s inbound through Edge_Gateway_Alpha"
        ];

        function toggleTerminal(show) {
            const overlay = document.getElementById('terminalOverlay');
            const log = document.getElementById('terminalLog');
            overlay.style.display = show ? 'block' : 'none';

            if (show) {
                document.body.style.overflow = 'hidden';
                logCount = 0;
                log.innerHTML = `
                    <p>Establishing secure connection to CYBERSHIELD_CENTRAL...</p>
                    <p>Connection established. Encryption layer active.</p>
                    <p>Welcome, Operator. Monitoring system nodes...</p>
                    <p class="mt-4"><span class="text-white">root@cybershield:~$</span> <span id="typedValue"></span><span class="cursor"></span></p>
                `;
                
                if (!terminalInterval) {
                    terminalInterval = setInterval(() => {
                        if (logCount < 9) {
                            const entry = document.createElement('p');
                            const timestamp = new Date().toLocaleTimeString();
                            entry.innerHTML = `<span class="opacity-40">[${timestamp}]</span> ${logEntries[Math.floor(Math.random() * logEntries.length)]}`;
                            log.appendChild(entry);
                            log.scrollTop = log.scrollHeight;
                            logCount++;
                        } else {
                            clearInterval(terminalInterval);
                            terminalInterval = null;
                            const loginPrompt = document.createElement('div');
                            loginPrompt.className = "mt-8 p-4 border border-primary/30 bg-primary/5 rounded space-y-4";
                            loginPrompt.innerHTML = `
                                <p class="text-white font-bold blink">! AUTHENTICATION REQUIRED</p>
                                <p class="text-xs">Security protocol 41-B has been triggered. Continuing the simulation requires valid operator credentials.</p>
                                <a href="auth/login.php" class="inline-block bg-primary text-black px-4 py-2 text-xs font-bold uppercase rounded hover:brightness-110">Login to Access Lab</a>
                            `;
                            log.appendChild(loginPrompt);
                            log.scrollTop = log.scrollHeight;
                        }
                    }, 1500);
                }
            } else {
                document.body.style.overflow = 'auto';
                clearInterval(terminalInterval);
                terminalInterval = null;
            }
        }

        function toggleRepo(show) {
            const overlay = document.getElementById('repoOverlay');
            overlay.style.display = show ? 'block' : 'none';
            document.body.style.overflow = show ? 'hidden' : 'auto';
            if (!show) {
                document.getElementById('repoError').classList.add('hidden');
                document.getElementById('repoSeedInput').value = '';
            }
        }

        function showRepoError() {
            const error = document.getElementById('repoError');
            const input = document.getElementById('repoSeedInput');
            
            if (input.value.length > 0) {
                error.classList.remove('hidden');
                // Shake the lock
                const lock = document.querySelector('.repo-lock');
                lock.classList.add('animate-shake');
                setTimeout(() => lock.classList.remove('animate-shake'), 500);
            } else {
                error.classList.add('hidden');
            }
        }
    </script>
</body>

</html>