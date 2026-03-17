<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// AJAX: Log DDoS mitigation result
if (isset($_GET['action']) && $_GET['action'] === 'log') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed']);
        exit;
    }
    header('Content-Type: application/json');
    $attack_type  = $_POST['attack_type']  ?? 'SYN Flood';
    $intensity    = $_POST['intensity']    ?? 'Medium';
    $mitigated    = (int)($_POST['mitigated'] ?? 0);
    $time_taken   = (float)($_POST['time_taken'] ?? 0);

    // Create table if not exists (graceful)
    $conn->query("CREATE TABLE IF NOT EXISTS ddos_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        attack_type VARCHAR(50),
        intensity VARCHAR(20),
        mitigated TINYINT(1) DEFAULT 0,
        time_taken FLOAT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $conn->prepare("INSERT INTO ddos_logs (user_id, attack_type, intensity, mitigated, time_taken) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issid", $user_id, $attack_type, $intensity, $mitigated, $time_taken);
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
    <title>CyberShield | DDoS Mitigation Elite Lab</title>
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
                        "danger": "#ff4b2b",
                        "warn": "#f59e0b",
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
        :root { --primary-glow: rgba(160, 240, 0, 0.4); }

        body {
            background-color: #060802;
            background-image:
                radial-gradient(circle at 50% 50%, rgba(160, 240, 0, 0.04) 0%, transparent 55%),
                linear-gradient(rgba(18, 20, 10, 0.8) 1px, transparent 1px),
                linear-gradient(90deg, rgba(18, 20, 10, 0.8) 1px, transparent 1px);
            background-size: 100% 100%, 25px 25px, 25px 25px;
        }

        .glass-panel {
            background: rgba(18, 22, 12, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(160, 240, 0, 0.15);
            box-shadow: 0 8px 32px rgba(0,0,0,0.8);
        }

        .glass-panel.danger-panel {
            border-color: rgba(255, 75, 43, 0.3);
            box-shadow: 0 0 30px rgba(255, 75, 43, 0.08);
        }

        .glow-text { text-shadow: 0 0 8px var(--primary-glow); }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #a0f00044; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a0f00088; }

        @keyframes scanline {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }
        .scanline {
            position: fixed; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to bottom, transparent, rgba(160, 240, 0, 0.05), transparent);
            animation: scanline 10s linear infinite;
            pointer-events: none; z-index: 50;
        }

        @keyframes pulse-danger {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 75, 43, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(255, 75, 43, 0); }
        }
        .pulse-danger { animation: pulse-danger 1.2s infinite; }

        @keyframes flood-in {
            from { opacity: 0; transform: translateX(-8px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .flood-line { animation: flood-in 0.15s ease-out forwards; }

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
        .cyber-button:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(160, 240, 0, 0.4); filter: brightness(1.1); }

        .input-field {
            background: rgba(10, 12, 2, 0.8);
            border: 1px solid #23281b;
            color: #fff;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .input-field:focus { border-color: #a0f000; box-shadow: 0 0 0 2px rgba(160, 240, 0, 0.1); outline: none; }

        /* Traffic graph bars */
        .bar-container { display: flex; align-items: flex-end; gap: 3px; height: 80px; }
        .traffic-bar {
            flex: 1;
            min-height: 2px;
            border-radius: 2px 2px 0 0;
            transition: height 0.3s ease, background 0.3s ease;
        }

        /* Toggle switch */
        .toggle-switch {
            position: relative; display: inline-block; width: 40px; height: 22px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #23281b; border-radius: 22px; transition: 0.3s;
            border: 1px solid #334155;
        }
        .toggle-slider:before {
            content: ""; position: absolute; height: 14px; width: 14px;
            left: 3px; bottom: 3px; background: #64748b;
            border-radius: 50%; transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: rgba(160,240,0,0.2); border-color: #a0f000; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); background: #a0f000; }

        .tag { font-size: 0.68rem; font-weight: 900; padding: 2px 6px; border-radius: 2px; text-transform: uppercase; }
        .tag-req { color: #5bc0de; background: rgba(91,192,222,0.1); }
        .tag-block { color: #ff4b2b; background: rgba(255,75,43,0.15); }
        .tag-pass { color: #a0f000; background: rgba(160,240,0,0.1); }
        .tag-warn { color: #f59e0b; background: rgba(245,158,11,0.1); }
        .tag-info { color: #a0f000; background: rgba(160,240,0,0.08); }

        .intensity-btn {
            padding: 6px 14px; border: 1px solid #23281b; border-radius: 4px;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
            cursor: pointer; transition: all 0.2s; color: #64748b; background: rgba(10,12,2,0.6);
        }
        .intensity-btn.active-low    { border-color: #22c55e; color: #22c55e; background: rgba(34,197,94,0.1); }
        .intensity-btn.active-medium { border-color: #f59e0b; color: #f59e0b; background: rgba(245,158,11,0.1); }
        .intensity-btn.active-high   { border-color: #f97316; color: #f97316; background: rgba(249,115,22,0.1); }
        .intensity-btn.active-critical{ border-color: #ff4b2b; color: #ff4b2b; background: rgba(255,75,43,0.15); box-shadow: 0 0 12px rgba(255,75,43,0.2); }

        .mitigation-card {
            padding: 12px 14px; border-radius: 6px;
            border: 1px solid #23281b; background: rgba(10,12,2,0.5);
            transition: all 0.25s;
        }
        .mitigation-card.active { border-color: rgba(160,240,0,0.3); background: rgba(160,240,0,0.05); }
        .mitigation-card.active .card-icon { color: #a0f000; }
    </style>
</head>

<body class="text-slate-300 font-sans min-h-screen overflow-x-hidden selection:bg-primary selection:text-black">
    <div class="scanline"></div>

    <!-- Top Navigation -->
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
                <span class="material-symbols-outlined text-sm">security</span>
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-widest truncate">DDoS Mitigation Elite Lab</span>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-4 text-[10px] md:text-xs font-mono">
            <button onclick="window.location.href='../dashboard/dashboard.php'" class="px-3 py-2 bg-surface border border-primary/30 rounded text-primary hover:bg-primary/10 transition-all flex items-center gap-1 md:gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> <span class="hidden sm:inline">Back</span>
            </button>
            <div class="px-2 md:px-3 py-1 bg-surface border border-primary/30 rounded text-primary shrink-0">DD-02</div>
        </div>
    </div>

    <main class="max-w-[1600px] mx-auto p-4 md:p-6 space-y-6">

        <!-- Header -->
        <div class="glass-panel rounded-lg p-6 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-[120px] text-danger">thunderstorm</span>
            </div>
            <div class="flex items-start gap-4">
                <div class="shrink-0 w-12 h-12 rounded-lg bg-danger/10 border border-danger/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-danger text-2xl">thunderstorm</span>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-1">DDoS Mitigation Elite</h2>
                    <p class="text-sm text-slate-400 max-w-2xl leading-relaxed">
                        Simulate high-volume Distributed Denial-of-Service attacks and deploy real-time mitigation controls to neutralize the threat.
                        <span class="text-primary/60 italic">For educational purposes only.</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Column 1: Attack Config -->
            <div class="lg:col-span-3 space-y-4">
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-5 border-l-4 border-danger pl-3">
                        <span class="material-symbols-outlined text-sm text-danger">bolt</span>
                        Attack Vector
                    </h3>

                    <div class="space-y-5">
                        <!-- Attack Type -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Attack Type:</label>
                            <div class="relative">
                                <select id="attack_type" class="input-field w-full rounded p-2.5 appearance-none pr-10">
                                    <option value="SYN Flood">SYN Flood</option>
                                    <option value="UDP Flood">UDP Flood</option>
                                    <option value="HTTP Flood">HTTP Flood</option>
                                    <option value="Slowloris">Slowloris</option>
                                    <option value="DNS Amplification">DNS Amplification</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">expand_more</span>
                            </div>
                        </div>

                        <!-- Intensity -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Intensity Level:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button class="intensity-btn active-low" data-level="Low" onclick="setIntensity('Low')">Low</button>
                                <button class="intensity-btn" data-level="Medium" onclick="setIntensity('Medium')">Medium</button>
                                <button class="intensity-btn" data-level="High" onclick="setIntensity('High')">High</button>
                                <button class="intensity-btn" data-level="Critical" onclick="setIntensity('Critical')">Critical</button>
                            </div>
                        </div>

                        <!-- Source IPs -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Botnet Nodes:</label>
                            <div id="botnet_count" class="text-2xl font-black text-white font-mono">128 <span class="text-sm text-slate-500">nodes</span></div>
                        </div>

                        <!-- Req/s -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Request Rate:</label>
                            <div id="req_rate_display" class="text-2xl font-black text-danger font-mono">4,200 <span class="text-sm text-slate-500">req/s</span></div>
                        </div>

                        <button onclick="startAttack()" id="start_btn" class="cyber-button w-full py-4 mt-2 flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #ff4b2b 0%, #c0392b 100%); color: white; clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%);">
                            <span class="material-symbols-outlined text-sm">bolt</span> Launch Attack
                        </button>
                        <button onclick="stopAttack()" id="stop_btn" class="hidden cyber-button w-full py-4 flex items-center justify-center gap-2" style="background: #1e293b; color: #94a3b8; clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%); border: 1px solid #334155;">
                            Abort Simulation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column 2: Console + Graph -->
            <div class="lg:col-span-6 space-y-4">

                <!-- Live Traffic Graph -->
                <div class="glass-panel rounded-lg p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest">
                            <span class="material-symbols-outlined text-sm text-primary">show_chart</span>
                            Live Traffic Volume
                        </h3>
                        <div class="flex items-center gap-3 text-[10px] font-mono">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-danger inline-block"></span> Incoming</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-primary inline-block"></span> Allowed</span>
                        </div>
                    </div>
                    <div id="graph_container" class="bar-container px-1">
                        <!-- bars injected by JS -->
                    </div>
                    <div class="flex justify-between text-[9px] font-mono text-slate-600 mt-1">
                        <span>-30s</span><span>-20s</span><span>-10s</span><span>Now</span>
                    </div>
                </div>

                <!-- Attack Console -->
                <div class="glass-panel rounded-lg overflow-hidden flex flex-col" style="height: 380px;">
                    <div class="bg-neutral-dark/80 px-4 py-3 border-b border-primary/20 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-danger">terminal</span>
                            <span class="text-[10px] font-black uppercase text-white tracking-widest">Traffic Console</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div id="status_dot" class="w-2 h-2 rounded-full bg-slate-700"></div>
                            <span id="status_label" class="text-[10px] font-mono text-slate-500 uppercase">Idle</span>
                        </div>
                    </div>
                    <div id="console" class="flex-1 p-4 font-mono text-xs overflow-y-auto custom-scrollbar space-y-0.5 bg-black/40">
                        <div class="text-slate-600 italic">// Awaiting attack vector...</div>
                    </div>
                </div>

                <!-- Metrics Row -->
                <div class="glass-panel rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-black/40 p-3 rounded border border-danger/10">
                            <div class="text-[9px] font-bold text-slate-500 uppercase mb-1">Total Requests</div>
                            <div id="m_total" class="text-xl font-bold text-white font-mono">0</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-danger/10">
                            <div class="text-[9px] font-bold text-slate-500 uppercase mb-1">Blocked</div>
                            <div id="m_blocked" class="text-xl font-bold text-danger font-mono">0</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="text-[9px] font-bold text-slate-500 uppercase mb-1">Passed</div>
                            <div id="m_passed" class="text-xl font-bold text-primary font-mono">0</div>
                        </div>
                        <div class="bg-black/40 p-3 rounded border border-primary/10">
                            <div class="text-[9px] font-bold text-slate-500 uppercase mb-1">Block Rate</div>
                            <div id="m_blockrate" class="text-xl font-bold text-white font-mono">0%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Mitigation -->
            <div class="lg:col-span-3 space-y-4">
                <div class="glass-panel rounded-lg p-5">
                    <h3 class="flex items-center gap-2 text-xs font-black uppercase text-white tracking-widest mb-5 border-l-4 border-primary pl-3">
                        <span class="material-symbols-outlined text-sm text-primary">shield</span>
                        Mitigation Controls
                    </h3>

                    <div class="space-y-3" id="mitigation_controls">
                        <!-- Rate Limiting -->
                        <div class="mitigation-card" id="card_rate">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-lg text-slate-500 card-icon transition-colors">speed</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">Rate Limiting</div>
                                        <div class="text-[10px] text-slate-500">Cap req/s per IP</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle_rate" onchange="toggleMitigation('rate')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div id="eff_rate" class="text-[10px] text-slate-600 mt-2 font-mono hidden">— Drops ~35% flood traffic</div>
                        </div>

                        <!-- CAPTCHA -->
                        <div class="mitigation-card" id="card_captcha">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-lg text-slate-500 card-icon transition-colors">smart_toy</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">CAPTCHA Challenge</div>
                                        <div class="text-[10px] text-slate-500">Bot detection layer</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle_captcha" onchange="toggleMitigation('captcha')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div id="eff_captcha" class="text-[10px] text-slate-600 mt-2 font-mono hidden">— Drops ~40% bot traffic</div>
                        </div>

                        <!-- Geo-Block -->
                        <div class="mitigation-card" id="card_geo">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-lg text-slate-500 card-icon transition-colors">public_off</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">Geo-Blocking</div>
                                        <div class="text-[10px] text-slate-500">Block high-risk regions</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle_geo" onchange="toggleMitigation('geo')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div id="eff_geo" class="text-[10px] text-slate-600 mt-2 font-mono hidden">— Drops ~25% by region</div>
                        </div>

                        <!-- IP Blacklist -->
                        <div class="mitigation-card" id="card_blacklist">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-lg text-slate-500 card-icon transition-colors">block</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">IP Blacklisting</div>
                                        <div class="text-[10px] text-slate-500">Known botnet IPs</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle_blacklist" onchange="toggleMitigation('blacklist')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div id="eff_blacklist" class="text-[10px] text-slate-600 mt-2 font-mono hidden">— Drops ~20% botnet nodes</div>
                        </div>

                        <!-- WAF -->
                        <div class="mitigation-card" id="card_waf">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-lg text-slate-500 card-icon transition-colors">firewall</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">WAF Rules</div>
                                        <div class="text-[10px] text-slate-500">Layer 7 inspection</div>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggle_waf" onchange="toggleMitigation('waf')">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            <div id="eff_waf" class="text-[10px] text-slate-600 mt-2 font-mono hidden">— Drops ~30% L7 attacks</div>
                        </div>
                    </div>

                    <!-- Mitigation Strength -->
                    <div class="mt-5 pt-4 border-t border-primary/10">
                        <div class="flex justify-between text-[10px] font-bold uppercase mb-2">
                            <span class="text-slate-500">Mitigation Strength</span>
                            <span id="mit_percent" class="text-primary">0%</span>
                        </div>
                        <div class="w-full h-2 bg-black/40 rounded overflow-hidden">
                            <div id="mit_bar" class="h-full rounded transition-all duration-500" style="width:0%; background: #a0f000; box-shadow: 0 0 8px #a0f000;"></div>
                        </div>
                        <div id="mit_status" class="text-[10px] text-slate-600 mt-2 italic">Enable controls above to begin mitigation.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Button -->
        <div id="return_btn" class="hidden flex justify-center mt-4">
            <button onclick="returnToDashboard()" class="cyber-button px-10 py-5 text-lg flex items-center gap-3">
                <span class="material-symbols-outlined">verified</span> Finalize & Return to Dashboard
            </button>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="success_modal" class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-6 hidden backdrop-blur-xl">
        <div class="glass-panel max-w-lg w-full p-8 text-center space-y-6">
            <div class="w-20 h-20 bg-primary/20 rounded-full border border-primary flex items-center justify-center mx-auto">
                <span class="material-symbols-outlined text-[40px] text-primary glow-text">verified_user</span>
            </div>
            <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter">Attack Neutralized</h3>
            <p class="text-sm text-slate-400 leading-relaxed">Your mitigation strategy successfully defended the system. The DDoS vector has been neutralized and logged for analysis.</p>

            <div class="grid grid-cols-3 gap-3 text-left">
                <div class="p-3 bg-primary/10 border border-primary/20 rounded">
                    <div class="text-[9px] text-primary uppercase font-bold mb-1">Block Rate</div>
                    <div id="modal_blockrate" class="text-lg font-black text-white font-mono">0%</div>
                </div>
                <div class="p-3 bg-primary/10 border border-primary/20 rounded">
                    <div class="text-[9px] text-primary uppercase font-bold mb-1">Mitigations</div>
                    <div id="modal_mitigations" class="text-lg font-black text-white font-mono">0</div>
                </div>
                <div class="p-3 bg-primary/10 border border-primary/20 rounded">
                    <div class="text-[9px] text-primary uppercase font-bold mb-1">Time</div>
                    <div id="modal_time" class="text-lg font-black text-white font-mono">0s</div>
                </div>
            </div>

            <button onclick="acknowledgeSuccess()" class="cyber-button w-full py-4">
                Acknowledge & Debrief
            </button>
        </div>
    </div>

    <script>
        // ────── State ──────
        const intensityConfig = {
            Low:      { nodes: 64,   rate: '1,200',  rawRate: 1200,  reqPerTick: 4,   baseBlock: 5 },
            Medium:   { nodes: 128,  rate: '4,200',  rawRate: 4200,  reqPerTick: 12,  baseBlock: 10 },
            High:     { nodes: 512,  rate: '18,500', rawRate: 18500, reqPerTick: 40,  baseBlock: 20 },
            Critical: { nodes: 2048, rate: '95,000', rawRate: 95000, reqPerTick: 120, baseBlock: 40 },
        };

        const mitigationPower = { rate: 35, captcha: 40, geo: 25, blacklist: 20, waf: 30 };
        const fakeIPs = ['192.168.','10.0.','172.16.','45.33.','185.220.','104.21.','103.76.'];
        const methods = ['GET','POST','SYN','UDP','HEAD'];
        const paths   = ['/','/?q=','///','/.env','wp-login.php','/api/v1'];

        let currentIntensity = 'Low';
        let isRunning = false;
        let mitigations = { rate: false, captcha: false, geo: false, blacklist: false, waf: false };
        let totalReqs = 0, blockedReqs = 0, passedReqs = 0;
        let simInterval = null, graphInterval = null;
        let graphBars = [];
        let startTime = null;
        const GRAPH_BARS = 30;

        // ────── Init Graph ──────
        function initGraph() {
            const gc = document.getElementById('graph_container');
            gc.innerHTML = '';
            graphBars = [];
            for (let i = 0; i < GRAPH_BARS; i++) {
                const bar = document.createElement('div');
                bar.className = 'traffic-bar';
                bar.style.height = '2px';
                bar.style.background = '#1e293b';
                gc.appendChild(bar);
                graphBars.push(bar);
            }
        }

        function pushGraphBar(blockPct) {
            const cfg = intensityConfig[currentIntensity];
            const fullH = 76;
            const incomingH = Math.min(fullH, Math.floor(Math.random() * 20 + cfg.reqPerTick * 0.8));
            const allowedH  = Math.floor(incomingH * (1 - blockPct / 100));

            graphBars.shift();
            const bar = document.createElement('div');
            bar.className = 'traffic-bar';

            const blockColor = blockPct > 70 ? '#a0f000' : blockPct > 40 ? '#f59e0b' : '#ff4b2b';
            bar.style.height = incomingH + 'px';
            bar.style.background = `linear-gradient(to top, ${blockColor} ${blockPct}%, #ff4b2b ${blockPct}%)`;
            document.getElementById('graph_container').appendChild(bar);
            graphBars.push(bar);
        }

        initGraph();

        // ────── Intensity ──────
        function setIntensity(level) {
            currentIntensity = level;
            const cfg = intensityConfig[level];
            document.getElementById('botnet_count').innerHTML = cfg.nodes.toLocaleString() + ' <span class="text-sm text-slate-500">nodes</span>';
            document.getElementById('req_rate_display').innerHTML = cfg.rate + ' <span class="text-sm text-slate-500">req/s</span>';
            document.querySelectorAll('.intensity-btn').forEach(b => {
                b.className = 'intensity-btn';
                if (b.dataset.level === level) b.classList.add('active-' + level.toLowerCase());
            });
        }
        setIntensity('Low');

        // ────── Mitigation ──────
        function getMitigationPct() {
            let total = 0;
            for (const [key, on] of Object.entries(mitigations)) {
                if (on) total += mitigationPower[key];
            }
            return Math.min(total, 99);
        }

        function toggleMitigation(key) {
            mitigations[key] = document.getElementById('toggle_' + key).checked;
            const card = document.getElementById('card_' + key);
            const eff  = document.getElementById('eff_' + key);
            card.classList.toggle('active', mitigations[key]);
            if (mitigations[key]) eff.classList.remove('hidden'); else eff.classList.add('hidden');
            updateMitigationBar();

            if (isRunning) {
                const pct = getMitigationPct();
                logConsole(mitigations[key]
                    ? `[MITIGATE] ${key.toUpperCase()} activated — blocking ~${mitigationPower[key]}% of traffic.`
                    : `[REMOVE] ${key.toUpperCase()} deactivated.`,
                    mitigations[key] ? 'pass' : 'warn'
                );
            }
        }

        function updateMitigationBar() {
            const pct = getMitigationPct();
            const bar = document.getElementById('mit_bar');
            const lbl = document.getElementById('mit_percent');
            const sta = document.getElementById('mit_status');

            bar.style.width = pct + '%';
            lbl.textContent = pct + '%';

            if (pct === 0) { bar.style.background = '#ff4b2b'; bar.style.boxShadow = 'none'; sta.textContent = 'No mitigation active. System exposed.'; sta.className = 'text-[10px] text-danger mt-2 italic'; }
            else if (pct < 50) { bar.style.background = '#f59e0b'; bar.style.boxShadow = '0 0 8px #f59e0b'; sta.textContent = 'Partial mitigation. Increase controls.'; sta.className = 'text-[10px] text-warn mt-2 italic'; }
            else if (pct < 85) { bar.style.background = '#a0f000'; bar.style.boxShadow = '0 0 8px #a0f000'; sta.textContent = 'Good coverage. Monitor for leakage.'; sta.className = 'text-[10px] text-primary mt-2 italic'; }
            else { bar.style.background = '#a0f000'; bar.style.boxShadow = '0 0 16px #a0f000'; sta.textContent = '✓ Maximum mitigation engaged.'; sta.className = 'text-[10px] text-primary mt-2 italic font-bold'; }
        }

        // ────── Console ──────
        function logConsole(msg, type = 'req') {
            const con = document.getElementById('console');
            const line = document.createElement('div');
            line.className = 'flood-line flex items-center gap-2 py-px';

            const tag = document.createElement('span');
            tag.className = `tag tag-${type}`;
            const labels = { req: 'REQ', block: 'BLOCK', pass: 'PASS', warn: 'WARN', info: 'INFO' };
            tag.innerText = labels[type] || type.toUpperCase();

            const text = document.createElement('span');
            text.className = 'text-slate-400';
            text.innerHTML = msg;

            line.appendChild(tag);
            line.appendChild(text);
            con.appendChild(line);
            // keep last 120 lines
            while (con.children.length > 120) con.removeChild(con.firstChild);
            con.scrollTop = con.scrollHeight;
        }

        function randomIP() {
            const pfx = fakeIPs[Math.floor(Math.random() * fakeIPs.length)];
            return pfx + Math.floor(Math.random()*254+1) + '.' + Math.floor(Math.random()*254+1);
        }

        function simulateTick() {
            const cfg = intensityConfig[currentIntensity];
            const mitigPct = getMitigationPct();
            const ticks = cfg.reqPerTick;

            for (let i = 0; i < ticks; i++) {
                const ip     = randomIP();
                const method = methods[Math.floor(Math.random() * methods.length)];
                const path   = paths[Math.floor(Math.random() * paths.length)];
                const roll   = Math.random() * 100;
                totalReqs++;

                if (roll < mitigPct) {
                    blockedReqs++;
                    if (Math.random() < 0.15) logConsole(`${ip} ${method} ${path} <span class="text-danger">→ BLOCKED</span>`, 'block');
                } else {
                    passedReqs++;
                    if (Math.random() < 0.08) logConsole(`${ip} ${method} ${path} <span class="text-primary">→ 200 OK</span>`, 'pass');
                }
            }

            document.getElementById('m_total').textContent   = totalReqs.toLocaleString();
            document.getElementById('m_blocked').textContent = blockedReqs.toLocaleString();
            document.getElementById('m_passed').textContent  = passedReqs.toLocaleString();
            const br = totalReqs > 0 ? Math.round((blockedReqs / totalReqs) * 100) : 0;
            document.getElementById('m_blockrate').textContent = br + '%';

            // check win condition: >= 90% block rate after at least 500 requests
            if (totalReqs >= 200 && br >= 90) {
                triggerSuccess(br);
            }
        }

        // ────── Start/Stop ──────
        function startAttack() {
            if (isRunning) return;
            isRunning = true;
            startTime = Date.now();
            totalReqs = 0; blockedReqs = 0; passedReqs = 0;

            document.getElementById('start_btn').classList.add('hidden');
            document.getElementById('stop_btn').classList.remove('hidden');
            document.getElementById('console').innerHTML = '';
            document.getElementById('return_btn').classList.add('hidden');

            const dot   = document.getElementById('status_dot');
            const label = document.getElementById('status_label');
            dot.className = 'w-2 h-2 rounded-full bg-danger pulse-danger';
            label.textContent = 'UNDER ATTACK';
            label.className = 'text-[10px] font-mono text-danger uppercase font-bold';

            initGraph();
            const cfg = intensityConfig[currentIntensity];
            logConsole(`Attack initiated: <strong>${document.getElementById('attack_type').value}</strong> | Intensity: <strong class="text-danger">${currentIntensity}</strong>`, 'info');
            logConsole(`Botnet nodes: ${cfg.nodes} | Rate: ${cfg.rate} req/s`, 'info');
            logConsole('─'.repeat(50), 'info');

            simInterval   = setInterval(simulateTick, 100);
            graphInterval = setInterval(() => pushGraphBar(getMitigationPct()), 300);
        }

        function stopAttack(success = false) {
            isRunning = false;
            clearInterval(simInterval);
            clearInterval(graphInterval);
            document.getElementById('start_btn').classList.remove('hidden');
            document.getElementById('stop_btn').classList.add('hidden');

            const dot   = document.getElementById('status_dot');
            const label = document.getElementById('status_label');
            dot.className = 'w-2 h-2 rounded-full bg-slate-600';
            label.textContent = success ? 'MITIGATED' : 'STOPPED';
            label.className = 'text-[10px] font-mono ' + (success ? 'text-primary' : 'text-slate-500') + ' uppercase';

            if (!success) logConsole('Simulation aborted.', 'warn');
        }

        let successTriggered = false;
        function triggerSuccess(blockRate) {
            if (successTriggered) return;
            successTriggered = true;

            const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);
            const numMit  = Object.values(mitigations).filter(Boolean).length;

            document.getElementById('modal_blockrate').textContent    = blockRate + '%';
            document.getElementById('modal_mitigations').textContent  = numMit;
            document.getElementById('modal_time').textContent         = elapsed + 's';

            stopAttack(true);
            setTimeout(() => document.getElementById('success_modal').classList.remove('hidden'), 600);

            // Log to DB
            const fd = new FormData();
            fd.append('attack_type', document.getElementById('attack_type').value);
            fd.append('intensity', currentIntensity);
            fd.append('mitigated', 1);
            fd.append('time_taken', elapsed);
            fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            fetch('?action=log', { method: 'POST', body: fd });
        }

        function acknowledgeSuccess() {
            document.getElementById('success_modal').classList.add('hidden');
            document.getElementById('return_btn').classList.remove('hidden');
        }

        function returnToDashboard() {
            window.location.href = '../dashboard/dashboard.php?lab_completed=ddos';
        }
    </script>
</body>
</html>
