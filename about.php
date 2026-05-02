<?php
require_once __DIR__ . '/config/session.php';
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>About CyberShield | Our Mission & Core Features</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />
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
                        "surface-dark": "#161810",
                        "border-dark": "#343a27",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "mono": ["ui-monospace", "SFMono-Regular", "Menlo", "Monaco", "Consolas", "Liberation Mono", "Courier New", "monospace"]
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

        .glass-card {
            background: rgba(22, 24, 16, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }

        .glow-text {
            text-shadow: 0 0 20px rgba(160, 240, 0, 0.5);
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
            50% {
                opacity: 0;
            }
        }

        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>

<body class="bg-background-dark font-display text-slate-100 selection:bg-primary selection:text-background-dark overflow-x-clip">
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
                <a class="text-sm font-medium text-primary transition-colors" href="about.php">
                    About
                </a>
            </div>
            <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard/dashboard.php" class="bg-primary text-background-dark text-sm font-black px-6 py-2 rounded uppercase tracking-wider hover:brightness-110 transition-all">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="text-sm font-bold text-white hover:text-primary px-4 py-2 transition-colors">
                        Login
                    </a>
                    <a href="auth/register.php" class="bg-primary text-background-dark text-sm font-black px-6 py-2 rounded uppercase tracking-wider hover:brightness-110 transition-all">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="relative grid-pattern min-h-screen">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-6 pt-24 pb-16 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-primary/30 bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-[0.2em] mb-8">
                Inside the Shield
            </div>
            <h1 class="text-5xl md:text-8xl font-black text-white leading-none tracking-tighter mb-6 uppercase italic">
                Our <span class="text-primary italic underline decoration-8 underline-offset-[12px]">Approach</span>
            </h1>
            <p class="text-xl text-slate-400 max-w-3xl mx-auto font-light leading-relaxed">
                CyberShield provides a structured, hands-on environment for mastering modern security operations, moving beyond theory into practical, scenario-based learning.
            </p>
        </section>

        <!-- Content Grid -->
        <section class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <div class="glass-card p-8 rounded-2xl">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">psychology</span>
                        Scenario-Based Learning
                    </h3>
                    <p class="text-slate-400 leading-relaxed">
                        Our modules—including Phishing simulations and Credential Exhaustion labs—are built on real-world attack vectors. Students analyze the mechanics of threats to better understand defensive countermeasures.
                    </p>
                </div>
                <div class="glass-card p-8 rounded-2xl">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">monitoring</span>
                        Operational Readiness
                    </h3>
                    <p class="text-slate-400 leading-relaxed">
                        Experience a realistic Security Operations Center (SOC) workflow. Monitor traffic, triage alerts, and use incident response playbooks that mirror professional SOC environments.
                    </p>
                </div>
                <div class="glass-card p-8 rounded-2xl">
                    <h3 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">security</span>
                        Industry Alignment
                    </h3>
                    <p class="text-slate-400 leading-relaxed">
                        Aligned with frameworks like MITRE ATT&CK®, CyberShield focuses on the tactics, techniques, and procedures (TTPs) used by modern adversaries, ensuring your skills are relevant in the field.
                    </p>
                </div>
            </div>
            <div class="relative">
                <div class="absolute inset-0 bg-primary/20 blur-[80px] -z-10 rounded-full"></div>
                <img src="assets/images/cyber_ops_center_1777253856025.png" alt="Cyber Operations Center" class="rounded-2xl border border-primary/30 shadow-2xl glow-shield hover:scale-[1.02] transition-transform duration-700">
                <div class="absolute -bottom-6 -right-6 glass-card p-4 rounded border border-primary/50 font-mono text-[10px] text-primary">
                    <p>// STATUS: OPERATIONAL</p>
                    <p>// LOCATION: GLOBAL_COMMAND</p>
                </div>
            </div>
        </section>

        <!-- Stats/Features Section -->
        <section class="bg-surface-dark border-y border-border-dark py-24 my-16">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div class="text-center space-y-4">
                        <div class="text-6xl font-black text-primary glow-text">15+</div>
                        <p class="text-white font-bold uppercase tracking-widest text-sm">Active Lab Nodes</p>
                        <p class="text-slate-500 text-xs px-8">Dedicated simulation environments for diverse security scenarios.</p>
                    </div>
                    <div class="text-center space-y-4 border-x border-border-dark">
                        <div class="text-6xl font-black text-primary glow-text">Real-Time</div>
                        <p class="text-white font-bold uppercase tracking-widest text-sm">SIEM Simulation</p>
                        <p class="text-slate-500 text-xs px-8">Experience live log ingestion and automated correlation of security events.</p>
                    </div>
                    <div class="text-center space-y-4">
                        <div class="text-6xl font-black text-primary glow-text">MITRE</div>
                        <p class="text-white font-bold uppercase tracking-widest text-sm">Framework Mapping</p>
                        <p class="text-slate-500 text-xs px-8">Content structured around industry-recognized attack techniques.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="max-w-4xl mx-auto px-6 py-32 text-center">
            <h3 class="text-4xl font-black text-white uppercase mb-6 tracking-tighter">Ready to <span class="text-primary italic underline decoration-4 underline-offset-8">Begin?</span></h3>
            <p class="text-slate-400 text-lg mb-12 max-w-2xl mx-auto">
                Join our community of security researchers and analysts. Start building your technical foundation in a safe, controlled environment.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard/dashboard.php" class="bg-primary text-background-dark font-black px-10 py-4 rounded-lg uppercase tracking-widest text-xl shadow-[0_0_30px_-5px_rgba(160,240,0,0.4)] hover:scale-105 transition-all inline-block hover:brightness-110">
                        Enter Lab Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="bg-primary text-background-dark font-black px-10 py-4 rounded-lg uppercase tracking-widest text-xl shadow-[0_0_30px_-5px_rgba(160,240,0,0.4)] hover:scale-105 transition-all inline-block hover:brightness-110">
                        Start Training
                    </a>
                <?php endif; ?>
            </div>
        </section>
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
                <a class="text-primary" href="about.php">About</a>
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
                    <span class="material-symbols-outlined text-primary">terminal</span>
                    <h4 class="text-xs font-bold text-white uppercase tracking-widest">Secure_Node_01 // Root Access</h4>
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
                    <span class="material-symbols-outlined text-primary">source</span>
                    <h4 class="text-sm font-bold text-white uppercase tracking-widest">CYBERSHIELD_SRC // Repository_v24.0</h4>
                </div>
                <button onclick="toggleRepo(false)" class="text-slate-500 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div id="repoLoading" class="flex-1 flex flex-col items-center justify-center space-y-8">
                <div class="w-64 space-y-4">
                    <div class="flex justify-between font-mono text-[10px] text-primary uppercase tracking-widest">
                        <span>Initializing Clone Protocol</span>
                        <span id="clonePercent">0%</span>
                    </div>
                    <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                        <div id="cloneBar" class="h-full bg-primary w-0 transition-all duration-300"></div>
                    </div>
                </div>
                <div id="fileDiscovery" class="w-full max-w-md font-mono text-[9px] text-slate-500 space-y-1 h-48 overflow-hidden opacity-50">
                    <!-- Files discovered will appear here -->
                </div>
            </div>

            <div id="repoRestricted" class="flex-1 flex flex-col items-center justify-center space-y-12" style="display: none;">
                <div class="size-24 rounded-full border-4 border-red-500/50 flex items-center justify-center animate-pulse shadow-[0_0_50px_rgba(239,68,68,0.2)]">
                    <span class="material-symbols-outlined text-6xl text-red-500 repo-lock">lock</span>
                </div>
                <div class="text-center space-y-4">
                    <h5 class="text-2xl font-black text-white uppercase tracking-tighter">Repository Access Restricted</h5>
                    <p class="text-slate-400 max-w-md mx-auto">This repository contains proprietary simulation kernels. Specialized clearance and hardware-keys are required for retrieval.</p>
                </div>
                <div class="w-full max-w-sm space-y-4">
                    <div class="h-12 bg-white/5 border border-white/10 rounded flex items-center px-4 gap-3">
                        <span class="material-symbols-outlined text-slate-500 text-sm">key</span>
                        <input type="password" id="repoSeedInput" placeholder="ENTER_CLEARANCE_SEED" class="bg-transparent border-none outline-none text-primary font-mono text-sm w-full transition-colors">
                        <span class="text-[10px] font-mono text-slate-500">AUTH_REQ</span>
                    </div>
                    <div id="repoErrorMessage" class="text-[9px] text-red-500 font-black uppercase tracking-widest text-center opacity-0 transition-opacity duration-300">
                        ! UNAUTHORIZED_INPUT_DETECTED
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="alert('Access request submitted. Awaiting administrator approval.')" class="block w-full bg-primary/20 border border-primary/50 text-primary py-3 rounded font-black text-center uppercase tracking-widest hover:bg-primary hover:text-black transition-all">
                            Submit Clearance Request
                        </button>
                    <?php else: ?>
                        <a href="auth/register.php" class="block w-full bg-primary/20 border border-primary/50 text-primary py-3 rounded font-black text-center uppercase tracking-widest hover:bg-primary hover:text-black transition-all">
                            Register for Source Access
                        </a>
                    <?php endif; ?>
                    <button onclick="toggleReportModal(true)" class="block w-full py-2 text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] hover:text-primary transition-colors">
                        Report Access Issue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- System Report Modal -->
    <div id="reportModal" class="terminal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 300; backdrop-filter: blur(10px);">
        <div class="max-w-lg mx-auto mt-20 p-8 glass-panel border-primary/30 rounded-3xl relative">
            <button onclick="toggleReportModal(false)" class="absolute top-6 right-6 text-slate-500 hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="mb-8">
                <h4 class="text-xl font-black text-white uppercase italic tracking-tighter">Report <span class="text-primary italic">System Issue</span></h4>
                <p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Direct line to CYBERSHIELD_ADMIN</p>
            </div>
            <form id="reportForm" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-primary uppercase tracking-widest">Issue Subject</label>
                    <input type="text" name="subject" required placeholder="e.g., REPOSITORY_ACCESS_DENIED" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-primary uppercase tracking-widest">Description</label>
                    <textarea name="description" required rows="4" placeholder="Describe the anomaly detected..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none transition-all resize-none"></textarea>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-primary uppercase tracking-widest">Severity</label>
                    <select name="priority" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none transition-all">
                        <option value="low">Low - Minor Anomaly</option>
                        <option value="medium" selected>Medium - Functional Issue</option>
                        <option value="high">High - Security Warning</option>
                        <option value="critical">Critical - System Breach</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-4 bg-primary text-neutral-dark font-black rounded-2xl uppercase tracking-[0.2em] text-xs hover:brightness-110 transition-all shadow-glow">
                    Transmit Report
                </button>
            </form>
            <div id="reportStatus" class="mt-4 text-[10px] font-mono text-center hidden uppercase tracking-widest"></div>
        </div>
    </div>

    <script>
        let terminalInterval;
        let repoInterval;
        let logCount = 0;
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        const logEntries = [
            "SCANNING_NETWORK: 192.168.1.0/24 - COMPLETED",
            "ALERT: Unauthorized packet attempt on PORT 8080 - BLOCKED",
            "SYNCING: Lab database with node_east_04",
            "LOG: User 'dev_analyst' initialized Phishing simulation",
            "CORE: CPU Load 42% | MEMORY 2.4GB Available",
            "STATUS: Firewall rules updated successfully",
            "SECURITY: SQL Injection signature detected and neutralized",
            "NODE: 10.0.0.52 is online and responding",
            "TRAFFIC: 1.2 GB/s inbound through Edge_Gateway_Alpha",
            "UPLINK: Establishing handshake with satellite_link_beta",
            "ENCRYPTION: Rotating RSA keys for session_id_882",
            "AUDIT: Log integrity check passed - NO TAMPERING DETECTED",
            "KERNEL: Hotfix 4.2.1 applied to isolation_kernel",
            "WATCHDOG: Memory leak prevented in sandbox_v3",
            "IDS: Pattern match found - 'CVE-2024-X' attempt blocked",
            "SYSTEM: Garbage collection cycle completed in 12ms",
            "NETWORK: Latency spike detected in EU_CENTRAL cluster",
            "SECURITY: Heuristic analysis flagged suspicious PID 9021",
            "LOG: Automated backup stored at /mnt/secure_bkp",
            "UPLINK: Bandwidth optimized for low-latency ops"
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
                        if (logCount < 20) {
                            const entry = document.createElement('p');
                            const timestamp = new Date().toLocaleTimeString();
                            entry.innerHTML = `<span class="opacity-40">[${timestamp}]</span> ${logEntries[Math.floor(Math.random() * logEntries.length)]}`;
                            log.appendChild(entry);
                            log.scrollTop = log.scrollHeight;
                            logCount++;
                        } else {
                            clearInterval(terminalInterval);
                            terminalInterval = null;
                            const finalPrompt = document.createElement('div');
                            finalPrompt.className = "mt-8 p-4 border border-primary/30 bg-primary/5 rounded space-y-4";
                            
                            if (isLoggedIn) {
                                finalPrompt.innerHTML = `
                                    <p class="text-primary font-bold animate-pulse">✓ HANDSHAKE SUCCESSFUL</p>
                                    <p class="text-xs">Uplink verified. All nodes responding. Terminal session will remain active in the background.</p>
                                    <a href="dashboard/dashboard.php" class="inline-block bg-primary text-black px-4 py-2 text-xs font-bold uppercase rounded hover:brightness-110">Open Lab Dashboard</a>
                                `;
                            } else {
                                finalPrompt.innerHTML = `
                                    <p class="text-white font-bold blink">! AUTHENTICATION REQUIRED</p>
                                    <p class="text-xs">Security protocol 41-B has been triggered. Continuing the simulation requires valid operator credentials.</p>
                                    <a href="auth/login.php" class="inline-block bg-primary text-black px-4 py-2 text-xs font-bold uppercase rounded hover:brightness-110">Login to Access Lab</a>
                                `;
                            }
                            log.appendChild(finalPrompt);
                            log.scrollTop = log.scrollHeight;
                        }
                    }, 800);
                }
            } else {
                document.body.style.overflow = 'auto';
                clearInterval(terminalInterval);
                terminalInterval = null;
            }
        }

        function toggleRepo(show) {
            const overlay = document.getElementById('repoOverlay');
            const loading = document.getElementById('repoLoading');
            const restricted = document.getElementById('repoRestricted');
            const bar = document.getElementById('cloneBar');
            const percent = document.getElementById('clonePercent');
            const discovery = document.getElementById('fileDiscovery');

            overlay.style.display = show ? 'block' : 'none';
            document.body.style.overflow = show ? 'hidden' : 'auto';

            if (show) {
                loading.style.display = 'flex';
                restricted.style.display = 'none';
                bar.style.width = '0%';
                percent.innerText = '0%';
                discovery.innerHTML = '';
                
                let p = 0;
                repoInterval = setInterval(() => {
                    p += Math.floor(Math.random() * 15) + 5;
                    if (p >= 100) {
                        p = 100;
                        clearInterval(repoInterval);
                        setTimeout(() => {
                            loading.style.display = 'none';
                            restricted.style.display = 'flex';
                        }, 1000);
                    }
                    bar.style.width = p + '%';
                    percent.innerText = p + '%';
                    
                    const mockFiles = ['kernel_v2.bin', 'exploit_db.sql', 'node_config.json', 'handshake.sh', 'encrypted_payload.aes', 'simulation_kernel.sys'];
                    const file = document.createElement('p');
                    file.innerText = `> DISCOVERED: ${mockFiles[Math.floor(Math.random() * mockFiles.length)]} [${(Math.random() * 500).toFixed(2)} KB]`;
                    discovery.prepend(file);
                }, 400);
            } else {
                clearInterval(repoInterval);
            }
        }

        function toggleReportModal(show) {
            const modal = document.getElementById('reportModal');
            modal.style.display = show ? 'block' : 'none';
            if (!show) {
                document.getElementById('reportForm').reset();
                document.getElementById('reportStatus').classList.add('hidden');
            }
        }

        document.getElementById('reportForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const status = document.getElementById('reportStatus');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerText = 'TRANSMITTING...';
            status.classList.remove('hidden', 'text-red-500', 'text-primary');
            status.innerText = '// ESTABLISHING_SECURE_UPLINK...';
            status.classList.remove('hidden');

            const formData = new FormData(form);
            fetch('includes/submit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    status.innerText = '✓ REPORT_TRANSMITTED_SUCCESSFULLY';
                    status.classList.add('text-primary');
                    setTimeout(() => toggleReportModal(false), 2000);
                } else {
                    status.innerText = '! ERROR: ' + data.message.toUpperCase();
                    status.classList.add('text-red-500');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'TRANSMIT REPORT';
                }
            })
            .catch(() => {
                status.innerText = '! UPLINK_FAILURE_DETECTED';
                status.classList.add('text-red-500');
                submitBtn.disabled = false;
                submitBtn.innerText = 'TRANSMIT REPORT';
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const seedInput = document.getElementById('repoSeedInput');
            const errorMsg = document.getElementById('repoErrorMessage');
            
            if (seedInput) {
                seedInput.addEventListener('input', () => {
                    if (seedInput.value.length > 0) {
                        errorMsg.classList.remove('opacity-0');
                        seedInput.classList.add('text-red-500');
                        seedInput.classList.remove('text-primary');
                    } else {
                        errorMsg.classList.add('opacity-0');
                        seedInput.classList.remove('text-red-500');
                        seedInput.classList.add('text-primary');
                    }
                });

                seedInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        const seed = seedInput.value.trim();
                        if (seed === 'CYBER_ELITE_2024') {
                            alert('MASTER ACCESS GRANTED. Redirecting to internal core...');
                            window.location.href = 'dashboard/dashboard.php';
                        } else {
                            seedInput.value = '';
                            errorMsg.innerText = '! CLEARANCE_REFUSED_RETRY';
                            seedInput.placeholder = 'INVALID_SEED_RETRY';
                            seedInput.classList.add('animate-shake');
                            setTimeout(() => seedInput.classList.remove('animate-shake'), 500);
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>