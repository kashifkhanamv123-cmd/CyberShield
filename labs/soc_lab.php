<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Elite SOC Command</title>
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
                        "bg-dark": "#050604"
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
        body { background-color: #020301; color: #cbd5e1; overflow: hidden; }
        .glass-panel { background: rgba(10, 11, 8, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(160, 240, 0, 0.1); }
        .alert-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border-left: 2px solid transparent; }
        .alert-card:hover { background: rgba(160, 240, 0, 0.08); border-left-color: #a0f000; }
        .severity-critical { border-left-color: #ff3e3e !important; }
        
        .map-ping { fill: currentColor; filter: drop-shadow(0 0 12px currentColor); animation: pulse 2.5s infinite; cursor: pointer; }
        @keyframes pulse { 0% { r: 4; opacity: 1; } 100% { r: 18; opacity: 0; } }
        
        .radar-sweep {
            position: absolute; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, rgba(160, 240, 0, 0.1) 0deg, transparent 90deg);
            top: -50%; left: -50%; animation: rotate 4s linear infinite;
            pointer-events: none; mask-image: radial-gradient(circle, black, transparent 70%);
        }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .glitch-flash { animation: glitch 0.2s ease; }
        @keyframes glitch { 0% { opacity: 1; transform: skew(0deg); } 20% { opacity: 0.8; transform: skew(5deg); } 40% { opacity: 1; transform: skew(-5deg); } 100% { opacity: 1; transform: skew(0deg); } }
        
        .custom-scrollbar::-webkit-scrollbar { width: 3px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2a2e21; border-radius: 10px; }

        .terminal-text { text-shadow: 0 0 5px rgba(160, 240, 0, 0.3); }
        .data-grid { background-size: 30px 30px; background-image: linear-gradient(to right, rgba(160, 240, 0, 0.02) 1px, transparent 1px), linear-gradient(to bottom, rgba(160, 240, 0, 0.02) 1px, transparent 1px); }
    </style>
</head>
<body class="h-screen flex flex-col font-sans selection:bg-primary selection:text-neutral-dark data-grid">
    <!-- Top Navigation -->
    <header class="h-16 flex items-center justify-between px-8 border-b border-primary/10 bg-black/40 backdrop-blur-xl z-50">
        <div class="flex items-center gap-6">
            <a href="../dashboard/dashboard.php" class="size-9 bg-primary/5 border border-primary/20 rounded-xl flex items-center justify-center text-primary hover:bg-primary hover:text-neutral-dark transition-all group">
                <span class="material-symbols-outlined text-sm group-hover:scale-110">arrow_back</span>
            </a>
            <div class="flex flex-col">
                <h1 class="text-sm font-black text-white flex items-center gap-3 tracking-[0.2em] uppercase">
                    <span class="size-2 rounded-full bg-primary shadow-glow animate-pulse"></span>
                    Elite SOC Operations
                </h1>
                <p class="text-[8px] font-mono text-primary/40 uppercase tracking-[0.4em]">Asset: Analyst_<?php echo strtoupper(substr($userName, 0, 8)); ?></p>
            </div>
        </div>

        <div class="hidden xl:flex items-center gap-12">
            <div class="flex flex-col gap-1.5 min-w-[300px]">
                <div class="flex justify-between items-end text-[8px] font-black uppercase tracking-[0.2em] text-slate-500">
                    <span>OP: <span class="text-primary italic">SHADOW_BRIDGE</span></span>
                    <span id="phase-label" class="text-white">PHASE 0/0</span>
                </div>
                <div class="w-full h-1 bg-white/5 rounded-full overflow-hidden">
                    <div id="scenario-progress" class="h-full bg-primary shadow-glow transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-8 font-mono text-[11px]">
            <div class="flex flex-col items-end">
                <span id="system-time" class="text-white font-bold tabular-nums">--:--:-- ZULU</span>
                <span class="text-[8px] text-primary/40 uppercase tracking-widest text-right">Encrypted Feed</span>
            </div>
            <div class="h-8 w-px bg-white/10"></div>
            <div class="flex items-center gap-3 px-4 py-2 bg-danger/5 border border-danger/20 rounded-lg">
                <span class="size-2 rounded-full bg-danger animate-ping"></span>
                <span class="text-danger font-black text-[9px] tracking-widest uppercase">Level: Critical</span>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-80 flex flex-col border-r border-primary/10 bg-black/20 backdrop-blur-md">
            <div class="p-6 border-b border-primary/5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-xs text-primary">analytics</span>
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Threat Stream</h3>
                </div>
                <span id="alert-count" class="text-[9px] font-black text-primary px-2 py-0.5 bg-primary/10 rounded border border-primary/20 italic">READY</span>
            </div>
            
            <div id="alert-queue" class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-3">
                <!-- Anonymous Alerts injected here -->
            </div>
            
            <div class="p-6 border-t border-primary/5 space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-[8px] font-black text-slate-600 uppercase">
                        <span>Database Uplink</span>
                        <span class="text-primary">99.9%</span>
                    </div>
                    <div class="h-0.5 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-primary/40 w-full"></div>
                    </div>
                </div>
                <p id="analyst-brief" class="text-[10px] text-slate-500 leading-relaxed italic font-medium">System operational. Monitoring external signatures...</p>
            </div>
        </aside>

        <!-- Main Map Area -->
        <section class="flex-1 flex flex-col relative overflow-hidden">
            <div class="radar-sweep"></div>
            
            <div class="flex-1 relative p-12 flex items-center justify-center">
                <svg id="threat-map" viewBox="0 0 1000 500" class="w-full h-full max-h-[600px] text-slate-900/50 transition-all duration-1000">
                    <path class="fill-none stroke-white/5 stroke-[1.5] stroke-dasharray-[1,4]" d="M150,150 Q200,100 250,150 T350,150 T450,200 T550,150 T650,200 T750,150 T850,200" />
                    <path class="fill-none stroke-white/5 stroke-[1.5] stroke-dasharray-[1,4]" d="M100,250 Q150,200 200,250 T300,250 T400,300 T500,250 T600,300 T700,250 T800,300" />
                    <g id="map-nodes"></g>
                </svg>

                <!-- Investigation HUD -->
                <div id="investigation-modal" class="hidden absolute inset-0 bg-[#020301]/95 z-[60] flex items-center justify-center p-8 backdrop-blur-2xl">
                    <div class="glass-panel w-full max-w-6xl rounded-[3rem] p-16 flex flex-col gap-12 shadow-[0_0_100px_rgba(160,240,0,0.1)] border-primary/20 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary/40 to-transparent"></div>
                        
                        <div class="flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-8">
                                <div class="size-20 rounded-[2rem] bg-primary/5 flex items-center justify-center text-primary shadow-glow ring-1 ring-primary/20">
                                    <span class="material-symbols-outlined text-5xl">biotech</span>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-4xl font-black text-white uppercase italic tracking-tighter leading-none">Forensic <span class="text-primary italic">Analysis</span></h3>
                                    <p class="text-[11px] font-mono text-primary/40 uppercase tracking-[0.4em]">Op: Shadow Bridge // Intelligence Protocol 0x1A</p>
                                </div>
                            </div>
                            <button onclick="closeInvestigation()" class="size-12 rounded-2xl flex items-center justify-center bg-white/5 text-slate-500 hover:text-white transition-all transform hover:rotate-90">
                                <span class="material-symbols-outlined text-3xl">close</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 flex-1 overflow-hidden">
                            <!-- Forensics logs -->
                            <div class="lg:col-span-7 flex flex-col gap-6 overflow-hidden">
                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.2em]">
                                    <span class="text-primary italic">>> RAW_NETWORK_INGRESS</span>
                                    <span class="text-slate-600">Secure Stream [AES-256]</span>
                                </div>
                                <div class="flex-1 bg-black/80 border border-white/5 rounded-[2.5rem] p-10 font-mono text-[13px] leading-loose shadow-inner relative overflow-y-auto custom-scrollbar group">
                                    <div class="absolute top-4 right-6 size-2 rounded-full bg-primary/20 animate-pulse"></div>
                                    <pre id="log-display" class="text-primary/80 whitespace-pre-wrap terminal-text">CROSS-REFERENCING ASSETS...</pre>
                                </div>
                                <div id="feedback-msg" class="hidden text-[11px] font-black text-center py-5 rounded-[1.8rem] border uppercase tracking-widest italic animate-bounce"></div>
                            </div>

                            <!-- Identification Panel -->
                            <div class="lg:col-span-5 flex flex-col gap-8">
                                <div class="space-y-4">
                                    <p class="text-xs font-black text-slate-500 uppercase tracking-[0.3em] pl-2">Classify Signature:</p>
                                    <div class="grid grid-cols-1 gap-3">
                                        <?php 
                                        $vectors = [
                                            ['Protocol Probe', 'radar'],
                                            ['Payload Delivery', 'package_2'],
                                            ['Data Infiltration', 'database_upload'],
                                            ['System Takeover', 'vital_signs'],
                                            ['Exfiltration Trace', 'cloud_upload']
                                        ];
                                        foreach ($vectors as [$type, $icon]): ?>
                                            <button onclick="verifyIdentification('<?php echo $type; ?>')" 
                                                    class="flex items-center gap-6 px-8 py-6 bg-white/[0.02] border border-white/5 rounded-3xl text-left hover:bg-primary/[0.08] hover:border-primary/40 transition-all group relative overflow-hidden">
                                                <div class="absolute inset-y-0 left-0 w-1 bg-primary transform -translate-x-full group-hover:translate-x-0 transition-transform"></div>
                                                <span class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors text-2xl"><?php echo $icon; ?></span>
                                                <span class="text-[13px] font-black text-slate-400 group-hover:text-white uppercase tracking-widest"><?php echo $type; ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="p-6 rounded-3xl bg-primary/5 border border-primary/10 italic text-[10px] text-primary/60 leading-relaxed shadow-glow">
                                    Intelligence failure results in immediate mission reset. Confirm all parameters before identification.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal -->
                <div id="incident-terminal" class="absolute bottom-8 left-8 right-8 glass-panel rounded-[3rem] p-10 flex items-center gap-12 transform translate-y-full opacity-0 transition-all duration-1000 shadow-[0_-40px_100px_rgba(0,0,0,0.9)] z-40 border-primary/20">
                    <div class="size-28 rounded-[2.5rem] bg-danger/10 flex items-center justify-center text-danger shrink-0 shadow-[0_0_60px_rgba(255,62,62,0.2)] ring-1 ring-danger/30 relative group overflow-hidden">
                         <div class="absolute inset-0 bg-danger/5 animate-pulse"></div>
                         <span id="terminal-icon" class="material-symbols-outlined text-6xl relative z-10 transition-transform group-hover:scale-110">warning</span>
                    </div>
                    <div class="flex-1 space-y-5">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <h2 id="terminal-title" class="text-3xl font-black text-white uppercase italic tracking-tighter terminal-text">--</h2>
                                <p id="terminal-desc" class="text-[11px] text-slate-500 font-bold tracking-widest uppercase italic opacity-60">--</p>
                            </div>
                            <span id="terminal-severity" class="px-5 py-2 bg-white/5 border border-white/10 rounded-full text-[11px] font-black uppercase tracking-[0.3em] italic">--</span>
                        </div>
                        <div class="flex items-center gap-10 text-[10px] font-mono">
                            <div class="flex items-center gap-3">
                                <span class="text-primary font-black">// SRC:</span>
                                <span id="terminal-ip" class="text-white bg-white/5 px-3 py-1 rounded">0.0.0.0</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-primary font-black">// TS:</span>
                                <span id="terminal-time" class="text-white bg-white/5 px-3 py-1 rounded">--</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 min-w-[260px]">
                        <button onclick="startInvestigation()" class="w-full py-5 bg-primary text-neutral-dark font-black rounded-3xl uppercase tracking-[.25em] text-xs hover:brightness-110 shadow-glow transition-all active:scale-95">Verify Intel</button>
                        <button onclick="handleAction('dismiss')" class="w-full py-5 bg-white/5 text-slate-500 font-bold rounded-3xl uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all">Dismiss Log</button>
                    </div>
                </div>

                <!-- Mission Complete -->
                <div id="mission-complete" class="hidden absolute inset-0 bg-[#020301]/95 backdrop-blur-3xl z-[100] flex flex-col items-center justify-center gap-10">
                    <div class="relative">
                        <div class="size-48 rounded-full bg-primary/10 flex items-center justify-center text-primary shadow-glow ring-1 ring-primary/30">
                            <span class="material-symbols-outlined text-[120px] animate-pulse">check_circle</span>
                        </div>
                        <div class="absolute inset-0 rounded-full border-2 border-primary/20 border-t-primary animate-spin"></div>
                    </div>
                    <div class="text-center space-y-4">
                        <h2 class="text-6xl font-black text-white italic tracking-tighter uppercase">Mission <span class="text-primary">Secured</span></h2>
                        <p class="text-slate-400 font-mono text-sm max-w-xl mx-auto leading-relaxed">Operation "Shadow Bridge" neutralized. Threats identified, purged, and assets recovered from all sectors.</p>
                    </div>
                    <a href="../dashboard/dashboard.php" class="px-12 py-6 bg-primary text-neutral-dark font-black rounded-[3rem] uppercase tracking-[0.4em] text-sm hover:scale-105 transition-all shadow-glow hover:brightness-110">Exit Terminal</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="h-12 bg-black border-t border-primary/10 flex items-center justify-between px-10 z-[70]">
        <div class="flex items-center gap-12 text-[9px] font-mono text-slate-500 uppercase tracking-widest">
            <div class="flex items-center gap-2">
                <span class="text-primary font-black tracking-tighter">NODE:</span>
                <span class="text-slate-400">ELITE-SHIELD-01</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-primary font-black tracking-tighter">STATUS:</span>
                <span class="text-primary flex items-center gap-2 italic"><span class="size-1 rounded-full bg-primary shadow-glow"></span> LIVE_FEED</span>
            </div>
        </div>
        <div class="text-[10px] font-black text-slate-700 uppercase tracking-[0.4em] italic">
            CyberShield Elite Operator // Build_V7.2
        </div>
    </footer>

    <script>
        let selectedAlert = null;

        function updateTime() {
            const now = new Date();
            document.getElementById('system-time').innerText = now.getUTCHours().toString().padStart(2, '0') + ':' + now.getUTCMinutes().toString().padStart(2, '0') + ':' + now.getUTCSeconds().toString().padStart(2, '0') + ' ZULU';
        }
        setInterval(updateTime, 1000);
        updateTime();

        async function fetchAlerts() {
            const res = await fetch('manage_soc.php?action=get_alerts');
            const data = await res.json();
            if (data.success) {
                renderAlerts(data.alerts);
                updateProgress(data.progress);
            }
        }

        function renderAlerts(alerts) {
            const queue = document.getElementById('alert-queue');
            const nodesGrp = document.getElementById('map-nodes');
            const count = document.getElementById('alert-count');
            
            queue.innerHTML = '';
            nodesGrp.innerHTML = '';
            
            if (alerts.length === 0) {
                count.innerText = "NOMINAL";
                count.className = "text-[9px] font-black text-primary px-2 py-0.5 bg-primary/10 rounded border border-primary/20 italic";
                document.getElementById('analyst-brief').innerText = "All sectors clear. Monitoring background activity.";
                return;
            }

            count.innerText = "THREAT_FOUND";
            count.className = "text-[9px] font-black text-danger px-2 py-0.5 bg-danger/10 rounded border border-danger/20 italic animate-pulse";
            document.getElementById('analyst-brief').innerText = "Anomalous signature detected. Forensics recommended.";

            alerts.forEach((alert, index) => {
                const card = document.createElement('div');
                card.className = `alert-card glass-panel p-6 rounded-[2rem] cursor-pointer severity-${alert.severity.toLowerCase()} active:scale-95`;
                card.onclick = () => {
                    card.classList.add('glitch-flash');
                    selectAlert(alert);
                    setTimeout(() => card.classList.remove('glitch-flash'), 200);
                };
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-[9px] font-mono text-primary/40 uppercase font-black tracking-widest">SIG_${alert.id}</span>
                        <span class="text-[8px] font-black uppercase px-2 py-0.5 rounded-full ${getSeverityClass(alert.severity)}">${alert.severity}</span>
                    </div>
                    <h4 class="text-[11px] font-black text-white uppercase italic tracking-widest truncate">${alert.type}</h4>
                    <p class="text-[9px] text-slate-600 font-mono mt-2 tracking-tighter">${alert.source_ip}</p>
                `;
                queue.appendChild(card);

                const x = 300 + (alert.phase_order * 100);
                const y = 100 + (index * 80 % 300);
                const node = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                node.setAttribute("cx", x);
                node.setAttribute("cy", y);
                node.setAttribute("r", 9);
                node.setAttribute("class", `map-ping ${getSeverityColor(alert.severity)}`);
                node.onclick = () => selectAlert(alert);
                nodesGrp.appendChild(node);
            });
        }

        function updateProgress(progress) {
            if (!progress) return;
            document.getElementById('scenario-progress').style.width = ((progress.current-1)/progress.total)*100 + '%';
            document.getElementById('phase-label').innerText = `PHASE ${progress.current}/${progress.total}`;
            if (progress.is_complete) document.getElementById('mission-complete').classList.remove('hidden');
        }

        function getSeverityClass(sev) {
            const map={'Critical':'bg-danger/20 text-danger border border-danger/30','High':'bg-warning/20 text-warning border border-warning/30','Medium':'bg-info/20 text-info border border-info/30','Low':'bg-primary/20 text-primary border border-primary/30'};
            return map[sev] || '';
        }

        function getSeverityColor(sev) {
            const map={'Critical':'text-danger','High':'text-warning','Medium':'text-info','Low':'text-primary'};
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
            document.getElementById('terminal-severity').className = `px-5 py-2 bg-white/5 border border-white/10 rounded-full text-[11px] font-black uppercase tracking-[0.3em] italic ${getSeverityClass(alert.severity)}`;
            document.getElementById('terminal-time').innerText = new Date(alert.created_at).toLocaleTimeString();
            const iconMap={'Protocol Probe':'radar','Payload Delivery':'package_2','Data Infiltration':'database_upload','System Takeover':'vital_signs','Exfiltration Trace':'cloud_upload'};
            document.getElementById('terminal-icon').innerText = iconMap[alert.canonical_type] || 'warning';
        }

        function startInvestigation() {
            if (!selectedAlert) return;
            document.getElementById('log-display').textContent = selectedAlert.log_evidence || '-- NO LOGS --';
            document.getElementById('investigation-modal').classList.remove('hidden');
            document.getElementById('feedback-msg').classList.add('hidden');
        }

        function closeInvestigation() { document.getElementById('investigation-modal').classList.add('hidden'); }

        async function verifyIdentification(answer) {
            if (!selectedAlert) return;
            const msg = document.getElementById('feedback-msg');
            msg.classList.remove('hidden','bg-danger/10','border-danger/20','text-danger','bg-primary/10','border-primary/20','text-primary');
            msg.innerText = 'MATCHING SIGNATURES...';
            const res = await fetch(`manage_soc.php?action=verify_investigation&id=${selectedAlert.id}&answer=${encodeURIComponent(answer)}`);
            const data = await res.json();
            if (data.success) {
                msg.classList.add('bg-primary/10','border-primary/20','text-primary');
                msg.innerText = "SUCCESS: " + data.message;
                setTimeout(() => { closeInvestigation(); document.getElementById('incident-terminal').classList.add('translate-y-full','opacity-0'); selectedAlert=null; fetchAlerts(); }, 2000);
            } else {
                msg.classList.add('bg-danger/10','border-danger/20','text-danger');
                msg.innerText = "FAILURE: " + data.message;
            }
        }

        async function handleAction(action) {
            if(!selectedAlert)return;
            const res=await fetch(`manage_soc.php?action=${action}&id=${selectedAlert.id}`);
            const data=await res.json();
            if(data.success) { document.getElementById('incident-terminal').classList.add('translate-y-full','opacity-0'); selectedAlert=null; fetchAlerts(); }
        }

        fetchAlerts();
        setInterval(fetchAlerts, 10000);
    </script>
</body>
</html>
