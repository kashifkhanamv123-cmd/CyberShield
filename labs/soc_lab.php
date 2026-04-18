<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Fetch initial alerts
$alerts_res = $conn->query("SELECT * FROM soc_alerts WHERE status = 'active' ORDER BY severity DESC, created_at DESC");
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | SOC Command Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#a0f000",
                        danger: "#ff3e3e",
                        warning: "#ffb400",
                        info: "#00e0ff",
                        "neutral-dark": "#0d0f0a",
                        surface: "#161810",
                        "border-dim": "#2a2e21",
                        "bg-dark": "#080906"
                    },
                    fontFamily: {
                        mono: ['JetBrains Mono', 'monospace'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #050604; color: #cbd5e1; overflow: hidden; }
        .glass-panel { background: rgba(22, 24, 16, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(160, 240, 0, 0.1); }
        .alert-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 4px solid transparent; }
        .alert-card:hover { transform: translateX(8px); background: rgba(160, 240, 0, 0.05); }
        .severity-critical { border-left-color: #ff3e3e; }
        .severity-high { border-left-color: #ffb400; }
        .severity-medium { border-left-color: #00e0ff; }
        .severity-low { border-left-color: #a0f000; }
        
        .map-ping { fill: currentColor; filter: drop-shadow(0 0 8px currentColor); animation: pulse 2s infinite; cursor: pointer; }
        @keyframes pulse { 0% { r: 3; opacity: 1; } 100% { r: 12; opacity: 0; } }
        
        .scanline {
            width: 100%; height: 2px; background: rgba(160, 240, 0, 0.05);
            position: absolute; top: 0; z-index: 50; pointer-events: none;
            animation: scan 4s linear infinite;
        }
        @keyframes scan { from { top: 0; } to { top: 100%; } }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2a2e21; border-radius: 10px; }
    </style>
</head>
<body class="h-screen flex flex-col font-sans selection:bg-primary selection:text-neutral-dark">
    <div class="scanline"></div>

    <!-- Top Navigation -->
    <header class="h-16 flex items-center justify-between px-8 border-b border-border-dim bg-neutral-dark/80 backdrop-blur-md z-50">
        <div class="flex items-center gap-4">
            <a href="../dashboard/dashboard.php" class="size-8 bg-primary/10 border border-primary/20 rounded-lg flex items-center justify-center text-primary hover:bg-primary hover:text-neutral-dark transition-all">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
            <div class="h-8 w-px bg-border-dim mx-2"></div>
            <div class="flex flex-col">
                <h1 class="text-sm font-black uppercase tracking-widest text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-lg">shield_with_house</span>
                    SOC Command Center
                </h1>
                <p class="text-[9px] font-mono text-primary/50 uppercase tracking-[0.3em]">Authorized Access: Analyst_<?php echo substr($userName, 0, 8); ?></p>
            </div>
        </div>

        <div class="flex items-center gap-6 font-mono text-[10px]">
            <div class="flex items-center gap-2">
                <span class="text-primary italic">SYSTEM_TIME:</span>
                <span id="system-time" class="text-white">--:--:-- ZULU</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-primary italic">SEC_LEVEL:</span>
                <span class="text-danger flex items-center gap-1">
                    <span class="size-1.5 rounded-full bg-danger animate-pulse"></span>
                    ELEVATED
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        <!-- Sidebar: Alert Queue -->
        <aside class="w-96 flex flex-col border-r border-border-dim bg-neutral-dark/40 backdrop-blur-xl">
            <div class="p-6 border-b border-border-dim flex items-center justify-between shrink-0">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Threat Queue</h3>
                <span id="alert-count" class="px-2 py-0.5 bg-danger/10 text-danger text-[9px] font-black rounded border border-danger/20">0 ACTIVE</span>
            </div>
            
            <div id="alert-queue" class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-2">
                <!-- Alerts will be injected here -->
            </div>
        </aside>

        <!-- Main Workspace: Map & Details -->
        <section class="flex-1 flex flex-col relative bg-bg-dark">
            <!-- Map Overlay -->
            <div class="flex-1 relative overflow-hidden flex items-center justify-center p-12">
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at center, #a0f000 0.5px, transparent 0.5px); background-size: 20px 20px;"></div>
                
                <svg id="threat-map" viewBox="0 0 1000 500" class="w-full h-full max-h-[600px] text-slate-800 transition-opacity duration-1000">
                    <!-- World Outlines -->
                    <path class="fill-none stroke-slate-800 stroke-[0.5]" d="M150,150 Q200,100 250,150 T350,150 T450,200 T550,150 T650,200 T750,150 T850,200" />
                    <path class="fill-none stroke-slate-800 stroke-[0.5]" d="M100,250 Q150,200 200,250 T300,250 T400,300 T500,250 T600,300 T700,250 T800,300" />
                    <g id="map-nodes"></g>
                </svg>

                <!-- Investigation Modal -->
                <div id="investigation-modal" class="hidden absolute inset-0 bg-black/90 backdrop-blur-md z-[60] p-12 flex items-center justify-center overflow-y-auto">
                    <div class="glass-panel w-full max-w-4xl rounded-3xl border-primary/30 p-10 flex flex-col gap-8 shadow-3xl">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="size-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-glow">
                                    <span class="material-symbols-outlined text-3xl">search_insights</span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Evidence <span class="text-primary italic">Investigation</span></h3>
                                    <p class="text-[9px] font-mono text-slate-500 uppercase tracking-widest">Identify the attack vector from forensics data</p>
                                </div>
                            </div>
                            <button onclick="closeInvestigation()" class="text-slate-500 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-3xl">close</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div class="bg-black/50 border border-white/5 rounded-2xl p-6 font-mono text-[11px] leading-relaxed relative overflow-hidden group">
                                    <div class="absolute top-0 right-0 p-2 text-[8px] text-primary/30 font-bold">RAW_FORENSICS_01</div>
                                    <pre id="log-display" class="text-primary/70 whitespace-pre-wrap">-- LOADING_DATA_STREAM --</pre>
                                </div>
                                <div id="feedback-msg" class="hidden text-xs font-bold text-center p-3 rounded-lg"></div>
                            </div>

                            <div class="flex flex-col gap-4">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Classify Attack Vector:</p>
                                <div class="grid grid-cols-1 gap-3">
                                    <?php 
                                    $vectors = ['SQL Injection', 'Brute Force', 'DDoS Attempt', 'Unauthorized Access', 'Malware Beacon'];
                                    foreach ($vectors as $v): ?>
                                        <button onclick="verifyIdentification('<?php echo $v; ?>')" 
                                                class="flex items-center justify-between px-6 py-4 bg-white/5 border border-white/10 rounded-xl text-left text-sm font-bold text-slate-300 hover:bg-primary/10 hover:border-primary/40 hover:text-primary transition-all group">
                                            <span><?php echo $v; ?></span>
                                            <span class="material-symbols-outlined text-lg opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HUD Overlays -->
                <div class="absolute top-8 left-8 p-4 glass-panel rounded-xl border-primary/20 space-y-4 w-64 shadow-2xl">
                    <p class="text-[9px] font-black text-primary uppercase tracking-widest border-b border-primary/10 pb-2">Global Heatmap</p>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-[8px] font-mono">
                            <span class="text-slate-500">North America</span>
                            <span class="text-warning">Stable</span>
                        </div>
                        <div class="flex justify-between items-center text-[8px] font-mono">
                            <span class="text-slate-500">Europe</span>
                            <span class="text-danger flex items-center gap-1">Critical <span class="size-1 rounded-full bg-danger animate-ping"></span></span>
                        </div>
                        <div class="flex justify-between items-center text-[8px] font-mono">
                            <span class="text-slate-500">Asia Pacific</span>
                            <span class="text-primary">Nominal</span>
                        </div>
                    </div>
                </div>

                <!-- Incident Terminal (Hidden until alert selected) -->
                <div id="incident-terminal" class="absolute bottom-8 left-8 right-8 glass-panel rounded-2xl border-primary/30 p-6 flex items-start gap-8 transform translate-y-full opacity-0 transition-all duration-500 shadow-[0_-20px_50px_-10px_rgba(0,0,0,0.5)]">
                    <div class="size-16 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shrink-0 shadow-glow">
                        <span id="terminal-icon" class="material-symbols-outlined text-4xl">warning</span>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 id="terminal-title" class="text-xl font-black text-white uppercase italic tracking-tighter">--</h2>
                            <span id="terminal-severity" class="px-3 py-1 bg-white/5 rounded-full text-[10px] font-black uppercase tracking-widest">--</span>
                        </div>
                        <p id="terminal-desc" class="text-sm text-slate-400 font-mono italic leading-relaxed">System awaiting operator directive...</p>
                        <div class="flex items-center gap-4 text-[10px] font-mono text-primary/60">
                            <span>S_IP: <span id="terminal-ip" class="text-white">0.0.0.0</span></span>
                            <span>TS: <span id="terminal-time" class="text-white">--</span></span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 w-48">
                        <button onclick="startInvestigation()" class="w-full py-3 bg-primary text-neutral-dark font-black rounded-lg uppercase tracking-widest text-[10px] hover:brightness-110 shadow-glow transition-all">Start Investigation</button>
                        <button onclick="handleAction('dismiss')" class="w-full py-3 bg-white/5 text-slate-400 font-bold rounded-lg uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all">Dismiss Alert</button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Status -->
    <footer class="h-10 bg-neutral-dark border-t border-border-dim flex items-center justify-between px-8 z-50">
        <div class="flex items-center gap-8 text-[9px] font-mono text-slate-500 uppercase tracking-widest">
            <div class="flex items-center gap-2">
                <span class="text-primary font-black">//_NODE:</span>
                <span>csh_core_prod</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-primary font-black">//_STATUS:</span>
                <span id="connection-status">UPLINK_ESTABLISHED</span>
            </div>
        </div>
        <div class="text-[9px] font-mono text-slate-500 uppercase tracking-widest font-black italic">
            Shield Command Interface // Build 5.1.0-EXP
        </div>
    </footer>

    <script>
        let selectedAlert = null;

        function updateTime() {
            const now = new Date();
            document.getElementById('system-time').innerText = 
                now.getUTCHours().toString().padStart(2, '0') + ':' + 
                now.getUTCMinutes().toString().padStart(2, '0') + ':' + 
                now.getUTCSeconds().toString().padStart(2, '0') + ' ZULU';
        }
        setInterval(updateTime, 1000);
        updateTime();

        async function fetchAlerts() {
            const res = await fetch('manage_soc.php?action=get_alerts');
            const data = await res.json();
            if (data.success) renderAlerts(data.alerts);
        }

        function renderAlerts(alerts) {
            const queue = document.getElementById('alert-queue');
            const nodesGrp = document.getElementById('map-nodes');
            const count = document.getElementById('alert-count');
            
            queue.innerHTML = '';
            nodesGrp.innerHTML = '';
            count.innerText = `${alerts.length} ACTIVE`;

            alerts.forEach((alert, index) => {
                const card = document.createElement('div');
                card.className = `alert-card glass-panel p-4 rounded-xl cursor-pointer severity-${alert.severity.toLowerCase()}`;
                card.onclick = () => selectAlert(alert);
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] font-mono text-slate-500 uppercase">Alert: ${alert.id}</span>
                        <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded ${getSeverityClass(alert.severity)}">${alert.severity}</span>
                    </div>
                    <h4 class="text-xs font-black text-white uppercase italic truncate">${alert.type}</h4>
                    <p class="text-[10px] text-slate-500 font-mono mt-1">${alert.source_ip}</p>
                `;
                queue.appendChild(card);

                const x = 200 + (index * 120 % 600);
                const y = 150 + (index * 80 % 250);
                const node = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                node.setAttribute("cx", x);
                node.setAttribute("cy", y);
                node.setAttribute("r", 6);
                node.setAttribute("class", `map-ping ${getSeverityColor(alert.severity)}`);
                node.onclick = () => selectAlert(alert);
                nodesGrp.appendChild(node);
            });
        }

        function getSeverityClass(sev) {
            const map = { 'Critical': 'bg-danger/20 text-danger border border-danger/30', 'High': 'bg-warning/20 text-warning border border-warning/30', 'Medium': 'bg-info/20 text-info border border-info/30', 'Low': 'bg-primary/20 text-primary border border-primary/30' };
            return map[sev] || '';
        }

        function getSeverityColor(sev) {
            const map = { 'Critical': 'text-danger', 'High': 'text-warning', 'Medium': 'text-info', 'Low': 'text-primary' };
            return map[sev] || 'text-primary';
        }

        function selectAlert(alert) {
            selectedAlert = alert;
            const terminal = document.getElementById('incident-terminal');
            terminal.classList.remove('translate-y-full', 'opacity-0');
            
            document.getElementById('terminal-title').innerText = alert.type;
            document.getElementById('terminal-desc').innerText = alert.description;
            document.getElementById('terminal-ip').innerText = alert.source_ip;
            document.getElementById('terminal-severity').innerText = alert.severity;
            document.getElementById('terminal-severity').className = `px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest ${getSeverityClass(alert.severity)}`;
            document.getElementById('terminal-time').innerText = new Date(alert.created_at).toLocaleTimeString();
            
            const iconMap = { 'SQL Injection': 'database', 'Brute Force': 'lock_open', 'DDoS Attempt': 'waves', 'Unauthorized Access': 'no_accounts', 'Malware Beacon': 'bug_report' };
            document.getElementById('terminal-icon').innerText = iconMap[alert.type] || 'warning';
        }

        function startInvestigation() {
            if (!selectedAlert) return;
            document.getElementById('log-display').innerText = selectedAlert.log_evidence || '-- NO LOG DATA --';
            document.getElementById('investigation-modal').classList.remove('hidden');
            document.getElementById('feedback-msg').classList.add('hidden');
        }

        function closeInvestigation() {
            document.getElementById('investigation-modal').classList.add('hidden');
        }

        async function verifyIdentification(answer) {
            if (!selectedAlert) return;
            
            const msg = document.getElementById('feedback-msg');
            msg.classList.remove('hidden', 'bg-red-500/20', 'text-red-500', 'bg-primary/20', 'text-primary');
            msg.innerText = 'VERIFYING FORENSICS...';
            
            const res = await fetch(`manage_soc.php?action=verify_investigation&id=${selectedAlert.id}&answer=${encodeURIComponent(answer)}`);
            const data = await res.json();
            
            if (data.success) {
                msg.classList.add('bg-primary/20', 'text-primary');
                msg.innerText = data.message;
                setTimeout(() => {
                    closeInvestigation();
                    document.getElementById('incident-terminal').classList.add('translate-y-full', 'opacity-0');
                    selectedAlert = null;
                    fetchAlerts();
                }, 1500);
            } else {
                msg.classList.add('bg-red-500/20', 'text-red-500');
                msg.innerText = data.message + ' REVISIT LOGS.';
            }
        }

        async function handleAction(action) {
            if (!selectedAlert) return;
            const res = await fetch(`manage_soc.php?action=${action}&id=${selectedAlert.id}`);
            const data = await res.json();
            if (data.success) {
                document.getElementById('incident-terminal').classList.add('translate-y-full', 'opacity-0');
                selectedAlert = null;
                fetchAlerts();
            }
        }

        fetchAlerts();
        setInterval(fetchAlerts, 5000);
    </script>
</body>
</html>
