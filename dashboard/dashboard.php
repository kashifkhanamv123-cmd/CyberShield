<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Fetch User Data for Header (Photo/Settings)
$user_stmt = $conn->prepare("SELECT name, email, profile_type, profile_image FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Fetch Phishing Progress
$phishing_stmt = $conn->prepare("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = ?");
$phishing_stmt->bind_param("i", $user_id);
$phishing_stmt->execute();
$phishing_count = $phishing_stmt->get_result()->fetch_row()[0];
$phishing_progress = min($phishing_count * 20, 100);
$phishing_level = min(floor($phishing_count / 1) + 1, 5);

// Fetch Brute Force Progress
$bruteforce_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(success) as has_success FROM bruteforce_logs WHERE user_id = ?");
$bruteforce_stmt->bind_param("i", $user_id);
$bruteforce_stmt->execute();
$bruteforce_res = $bruteforce_stmt->get_result();
$bruteforce_data = $bruteforce_res->fetch_assoc();
$bruteforce_count = (int)$bruteforce_data['total'];
$bruteforce_success = (int)$bruteforce_data['has_success'];
$bruteforce_progress = $bruteforce_success ? 100 : min($bruteforce_count * 10, 90);

// Fetch DDoS Progress
$ddos_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(mitigated) as has_success FROM ddos_logs WHERE user_id = ?");
$ddos_stmt->bind_param("i", $user_id);
$ddos_stmt->execute();
$ddos_data = $ddos_stmt->get_result()->fetch_assoc();
$ddos_success  = (int)($ddos_data['has_success'] ?? 0);
$ddos_count    = (int)($ddos_data['total'] ?? 0);
$ddos_progress = $ddos_success ? 100 : min($ddos_count * 15, 90);

// Fetch Malware Progress
$mal_stmt = $conn->prepare("SELECT COUNT(*) as total, MAX(correct) as has_success FROM malware_logs WHERE user_id = ?");
$mal_stmt->bind_param("i", $user_id);
$mal_stmt->execute();
$mal_data = $mal_stmt->get_result()->fetch_assoc();
$malware_success  = (int)($mal_data['has_success'] ?? 0);
$malware_count    = (int)($mal_data['total'] ?? 0);
$malware_progress = $malware_success ? 100 : min($malware_count * 25, 90);

// Total completed labs
$completed_labs = ($phishing_count > 0 ? 1 : 0) + ($bruteforce_success ? 1 : 0) + ($ddos_success ? 1 : 0) + ($malware_success ? 1 : 0);

// Fetch SOC Alert Count (active)
$soc_res = $conn->query("SELECT COUNT(*) FROM soc_alerts WHERE status = 'active'");
$soc_active_count = $soc_res->fetch_row()[0];
$soc_res->close();

$lab_completed = $_GET['lab_completed'] ?? '';

// Helper for letter avatar
function getLetterAvatar($name) {
    if (!$name) $name = "U";
    $initial = strtoupper(substr(trim($name), 0, 1));
    return '<div class="size-full flex items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5 text-primary font-black text-xl uppercase tracking-tighter">' . $initial . '</div>';
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Security Analyst Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23a0f000'><path d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.47 4.34-3.1 8.25-7 9.53V12H5V6.3l7-3.11v8.8z'/></svg>">
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#a0f000",
                        "neutral-dark": "#0d0f0a",
                        "surface": "#161810",
                        "border-dim": "#2a2e21",
                        "bg-dark": "#080906"
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: theme('colors.bg-dark'); font-family: 'Inter', sans-serif; color: #fff; }
        .glass-panel { background: rgba(22, 24, 16, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(160, 240, 0, 0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: theme('colors.border-dim'); border-radius: 10px; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .lab-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-running { background: #a0f000; box-shadow: 0 0 8px #a0f000; }
        .status-stopped { background: #64748b; }
        .status-loading { background: #f59e0b; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        
        /* Threat Map Styling */
        .map-ping { fill: theme('colors.primary'); filter: drop-shadow(0 0 5px theme('colors.primary')); animation: mapPing 2s infinite; }
        @keyframes mapPing { 0% { opacity: 0; r: 2; } 50% { opacity: 1; r: 6; } 100% { opacity: 0; r: 10; } }
        .threat-feed-item { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="flex h-screen overflow-hidden selection:bg-primary selection:text-neutral-dark text-slate-300">

    <!-- Completion Modals (Preserved Logic) -->
    <?php if ($lab_completed === 'bruteforce'): ?>
        <div id="completionModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary shadow-[0_0_15px_rgba(160,240,0,0.3)]">
                        <span class="material-symbols-outlined text-3xl">verified</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter">Lab <span class="text-primary">Completed</span></h2>
                        <p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Subject: Brute Force Intrusion Analysis</p>
                    </div>
                </div>
                <div class="space-y-6 text-sm text-slate-300 leading-relaxed relative z-10 overflow-y-auto custom-scrollbar pr-2 mb-2">
                    <div class="p-5 bg-primary/5 border border-primary/20 rounded-xl">
                        <h4 class="text-xs font-bold text-primary uppercase mb-3 flex items-center gap-2">Analyst Debrief</h4>
                        <p class="text-[11px] text-slate-400">Weak passwords represent the largest attack surface. Enforcing MFA and Account Lockout Policies are critical defenses.</p>
                    </div>
                </div>
                <button onclick="window.history.replaceState(null, null, window.location.pathname); this.closest('#completionModal').remove();"
                    class="w-full mt-8 py-4 bg-primary text-neutral-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] relative z-10">
                    Acknowledge Directive
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- DDoS & Malware Modals (Simplified versions for brevity, but logically identical) -->
    <?php if ($lab_completed === 'ddos' || $lab_completed === 'malware'): ?>
        <div id="completionModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="glass-panel border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center gap-4 mb-6 relative z-10 shrink-0">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary"><span class="material-symbols-outlined text-3xl">verified_user</span></div>
                    <div><h2 class="text-2xl font-black uppercase italic tracking-tighter">Lab <span class="text-primary italic">Completed</span></h2><p class="text-xs text-slate-500 font-mono tracking-widest uppercase">Target Objective: Neutralized</p></div>
                </div>
                <button onclick="window.history.replaceState(null,null,window.location.pathname);this.closest('#completionModal').remove();" class="w-full py-4 bg-primary text-neutral-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-[0.2em] relative z-10">Acknowledge Directive</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sidebar Navigation (Synced with settings.php) -->
    <aside class="w-20 md:w-64 flex flex-col border-r border-border-dim bg-neutral-dark shrink-0 transition-all duration-300 z-50">
        <div class="h-20 flex items-center px-6 border-b border-border-dim">
            <div class="size-8 bg-primary rounded flex items-center justify-center shrink-0 shadow-[0_0_15px_-5px_#a0f000]">
                <span class="material-symbols-outlined text-neutral-dark text-xl font-bold">shield</span>
            </div>
            <span class="ml-3 font-black tracking-tighter uppercase text-xl md:block hidden italic text-white">Shield</span>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <div class="flex items-center gap-4 px-4 py-3 rounded-xl bg-primary/10 text-primary border border-primary/20 transition-all shadow-[inset_0_0_20px_-10px_#a0f000] mb-2">
                <span class="material-symbols-outlined text-xl">dashboard</span>
                <span class="text-sm font-bold md:block hidden">Dashboard</span>
            </div>
            <div class="pt-2 pb-2 px-4">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest md:block hidden">Operations</p>
                <div class="h-px bg-border-dim w-full md:hidden"></div>
            </div>
            <a href="../modules/phishing/index.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">alternate_email</span>
                <span class="text-sm font-bold md:block hidden">Phishing Lab</span>
            </a>
            <a href="../labs/ddos.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">security</span>
                <span class="text-sm font-bold md:block hidden">DDoS Defense</span>
            </a>
            <a href="../labs/malware.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">bug_report</span>
                <span class="text-sm font-bold md:block hidden">Malware Analysis</span>
            </a>
            <a href="../labs/bruteforce.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">lock_open</span>
                <span class="text-sm font-bold md:block hidden">Brute Force</span>
            </a>
            <div class="pt-4 pb-2 px-4">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest md:block hidden">Configuration</p>
                <div class="h-px bg-border-dim w-full md:hidden"></div>
            </div>
            <a href="settings.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">settings</span>
                <span class="text-sm font-bold md:block hidden">Node Config</span>
            </a>
        </nav>

        <div class="p-4 border-t border-border-dim">
            <a href="../auth/logout.php" class="flex items-center gap-4 px-4 py-3 rounded-xl text-red-400 hover:bg-red-400/10 transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:scale-110">power_settings_new</span>
                <span class="text-sm font-bold md:block hidden uppercase tracking-wider">Terminate</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col relative overflow-hidden bg-bg-dark">
        <!-- Top Header -->
        <header class="h-20 flex items-center justify-between px-8 bg-neutral-dark/50 backdrop-blur-md border-b border-border-dim shrink-0 z-10">
            <div class="flex flex-col">
                <h1 class="text-lg font-black uppercase tracking-tight text-white">Analyst <span class="text-primary italic text-xl">Dashboard</span></h1>
                <p class="text-[10px] font-mono text-slate-500 uppercase tracking-widest">Node: csh_central_01 // Region: Local_Host</p>
            </div>

            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col text-right">
                    <span class="text-sm font-black text-white"><?php echo htmlspecialchars($user_data['name']); ?></span>
                    <span class="text-[9px] font-mono text-primary uppercase tracking-widest">Authorized Specialist</span>
                </div>
                <div class="size-11 rounded-xl overflow-hidden border border-border-dim shadow-xl bg-surface">
                    <?php
                    if ($user_data['profile_type'] === 'none' || !$user_data['profile_image']) {
                        echo getLetterAvatar($user_data['name']);
                    } elseif ($user_data['profile_type'] === 'preset') {
                        echo '<img src="'.$user_data['profile_image'].'" class="size-full object-cover p-1.5">';
                    } else {
                        echo '<img src="../'.$user_data['profile_image'].'" class="size-full object-cover">';
                    }
                    ?>
                </div>
                <!-- Search Component -->
                <div class="relative hidden sm:block ml-4">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">search</span>
                    <input type="text" id="labSearch" placeholder="Search modules..." 
                           class="bg-surface/50 border border-border-dim rounded-lg py-2 pl-9 pr-4 text-xs focus:border-primary/50 outline-none transition-all w-48 focus:w-64">
                </div>
            </div>
        </header>

        <!-- Content Hub -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-8 space-y-8 animate-fade-in">
            
            <!-- Hero Grid: Threat Map & Fast Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Active Threat Monitor Card -->
                <div class="lg:col-span-8 glass-panel rounded-[2.5rem] p-8 overflow-hidden relative flex flex-col min-h-[400px]">
                    <div class="flex items-center justify-between relative z-10 mb-6">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">public</span>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-white">Global Threat Monitor</h3>
                        </div>
                        <div class="px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full flex items-center gap-2">
                            <div class="size-1.5 bg-red-500 rounded-full animate-pulse"></div>
                            <span class="text-[9px] font-black text-red-500 uppercase tracking-widest">Security Level: High</span>
                        </div>
                    </div>

                    <!-- World Map SVG (Inline for animations) -->
                    <div class="flex-1 flex items-center justify-center relative opacity-40 hover:opacity-100 transition-opacity duration-700">
                        <svg viewBox="0 0 1000 500" class="w-full h-full max-h-[300px]">
                            <!-- Simplified World Outlines (Abstracted) -->
                            <path d="M150,150 Q200,100 250,150 T350,150 T450,200 T550,150 T650,200 T750,150 T850,200" fill="none" stroke="rgba(160,240,0,0.1)" stroke-width="1" />
                            <path d="M100,250 Q150,200 200,250 T300,250 T400,300 T500,250 T600,300 T700,250 T800,300" fill="none" stroke="rgba(160,240,0,0.1)" stroke-width="1" />
                            
                            <!-- Animated Attack Points -->
                            <circle class="map-ping" cx="210" cy="180" r="4" />
                            <circle class="map-ping" cx="480" cy="220" r="4" style="animation-delay: -0.5s" />
                            <circle class="map-ping" cx="720" cy="190" r="4" style="animation-delay: -1.2s" />
                            <circle class="map-ping" cx="850" cy="310" r="4" style="animation-delay: -0.8s" />
                            <circle class="map-ping" cx="340" cy="350" r="4" style="animation-delay: -1.5s" />
                        </svg>
                        
                        <!-- HUD Overlays -->
                        <div class="absolute inset-0 pointer-events-none border border-primary/5 rounded-3xl"></div>
                        <div class="absolute bottom-0 left-0 p-4 space-y-1">
                            <p class="text-[8px] font-mono text-primary uppercase opacity-50">// SCANNING_UPLINK...</p>
                            <p class="text-[8px] font-mono text-primary uppercase opacity-50">// GEO_LOCATING_BOTNET_NODES...</p>
                        </div>
                    </div>

                    <!-- Live Threat Feed Overlay -->
                    <div class="absolute right-6 bottom-6 w-64 glass-panel border border-white/5 rounded-2xl p-4 hidden md:block group hover:border-primary/30 transition-all">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 flex justify-between">
                            Intrusion Feed
                            <span class="text-primary italic animate-pulse">LIVE</span>
                        </p>
                        <div id="threat-feed" class="space-y-2 h-24 overflow-hidden font-mono text-[9px]">
                            <!-- JS will inject here -->
                        </div>
                    </div>
                </div>

                <!-- Fast Metrics Card -->
                <div class="lg:col-span-4 space-y-8">
                    <div class="glass-panel rounded-[2.5rem] p-8 space-y-6">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">analytics</span>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-white">Operation Stats</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="p-5 bg-bg-dark/50 border border-border-dim rounded-3xl flex justify-between items-center group hover:border-primary/30 transition-all">
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Training Progress</p>
                                    <p class="text-xl font-black text-white"><?php echo ($completed_labs / 4) * 100; ?>%</p>
                                </div>
                                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black text-xs">
                                    <?php echo $completed_labs; ?>/4
                                </div>
                            </div>
                            <div class="p-5 bg-bg-dark/50 border border-border-dim rounded-3xl flex justify-between items-center group hover:border-primary/30 transition-all">
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Active Sim Nodes</p>
                                    <p class="text-xl font-black text-white">03</p>
                                </div>
                                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined text-xl">router</span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="../labs/ddos.php" class="block w-full py-4 bg-primary text-neutral-dark font-black rounded-2xl text-center uppercase tracking-widest text-xs hover:brightness-110 shadow-lg shadow-primary/10 transition-all">
                            Initialize New Op
                        </a>
                    </div>

                    <!-- Daily Metric -->
                    <div class="glass-panel rounded-[2.5rem] p-8 flex items-center gap-4 border-primary/20">
                        <div class="size-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 transition-all">
                            <span class="material-symbols-outlined">verified_user</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Security Health</p>
                            <p class="text-md font-black text-white uppercase tracking-tight">Node Integrity Stable</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Nodes Grid -->
            <div class="space-y-6 pt-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase text-slate-500 tracking-[0.3em] ml-2">Available Operations <span class="text-white">Clusters</span></h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Lab Card: Phishing -->
                    <div class="glass-panel group rounded-[2rem] p-1 flex flex-col transition-all hover:border-primary/40 hover:translate-y-[-4px]">
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="material-symbols-outlined text-4xl text-primary group-hover:scale-110 transition-transform">alternate_email</span>
                                <span class="text-[9px] font-mono text-slate-600 bg-white/5 py-1 px-3 rounded-full">OP_01</span>
                            </div>
                            <h4 class="text-md font-black text-white mb-2 uppercase tracking-tight">Phishing Analysis</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed mb-6">Intercept and analyze deceptive communications in a controlled sandbox.</p>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wider">
                                    <span class="text-slate-500">Sync Depth</span>
                                    <span class="text-primary"><?php echo $phishing_progress; ?>%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full" style="width: <?php echo $phishing_progress; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <a href="../modules/phishing/index.php" class="p-4 bg-surface/50 text-[9px] font-black text-center text-slate-400 hover:text-primary hover:bg-primary/10 uppercase tracking-widest rounded-b-[1.8rem] border-t border-border-dim transition-all">
                            Initialize Node
                        </a>
                    </div>

                    <!-- Lab Card: DDoS -->
                    <div class="glass-panel group rounded-[2rem] p-1 flex flex-col transition-all hover:border-primary/40 hover:translate-y-[-4px]">
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="material-symbols-outlined text-4xl text-primary group-hover:scale-110 transition-transform">security</span>
                                <span class="text-[9px] font-mono text-slate-600 bg-white/5 py-1 px-3 rounded-full">OP_02</span>
                            </div>
                            <h4 class="text-md font-black text-white mb-2 uppercase tracking-tight">DDoS Mitigation</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed mb-6">Neutralize high-volume traffic floods using adaptive firewall rules.</p>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wider">
                                    <span class="text-slate-500">Uptime Load</span>
                                    <span class="text-primary"><?php echo $ddos_progress; ?>%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full" style="width: <?php echo $ddos_progress; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <a href="../labs/ddos.php" class="p-4 bg-surface/50 text-[9px] font-black text-center text-slate-400 hover:text-primary hover:bg-primary/10 uppercase tracking-widest rounded-b-[1.8rem] border-t border-border-dim transition-all">
                            Initialize Node
                        </a>
                    </div>

                    <!-- Lab Card: Malware -->
                    <div class="glass-panel group rounded-[2rem] p-1 flex flex-col transition-all hover:border-primary/40 hover:translate-y-[-4px]">
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="material-symbols-outlined text-4xl text-primary group-hover:scale-110 transition-transform">bug_report</span>
                                <span class="text-[9px] font-mono text-slate-600 bg-white/5 py-1 px-3 rounded-full">OP_03</span>
                            </div>
                            <h4 class="text-md font-black text-white mb-2 uppercase tracking-tight">Malware Analysis</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed mb-6">Deconstruct malicious payloads and classify behavioral anomalies.</p>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wider">
                                    <span class="text-slate-500">Decryption Index</span>
                                    <span class="text-primary"><?php echo $malware_progress; ?>%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full" style="width: <?php echo $malware_progress; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <a href="../labs/malware.php" class="p-4 bg-surface/50 text-[9px] font-black text-center text-slate-400 hover:text-primary hover:bg-primary/10 uppercase tracking-widest rounded-b-[1.8rem] border-t border-border-dim transition-all">
                            Initialize Node
                        </a>
                    </div>

                    <!-- Lab Card: Brute Force -->
                    <div class="glass-panel group rounded-[2rem] p-1 flex flex-col transition-all hover:border-primary/40 hover:translate-y-[-4px]">
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="material-symbols-outlined text-4xl text-primary group-hover:scale-110 transition-transform">lock_open</span>
                                <span class="text-[9px] font-mono text-slate-600 bg-white/5 py-1 px-3 rounded-full">OP_04</span>
                            </div>
                            <h4 class="text-md font-black text-white mb-2 uppercase tracking-tight">Brute Force Lab</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed mb-6">Master authentication hardening by simulating high-speed crack tests.</p>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-[9px] font-black uppercase tracking-wider">
                                    <span class="text-slate-500">Key Recovery</span>
                                    <span class="text-primary"><?php echo $bruteforce_progress; ?>%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full" style="width: <?php echo $bruteforce_progress; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <a href="../labs/bruteforce.php" class="p-4 bg-surface/50 text-[9px] font-black text-center text-slate-400 hover:text-primary hover:bg-primary/10 uppercase tracking-widest rounded-b-[1.8rem] border-t border-border-dim transition-all">
                            Initialize Node
                        </a>
                    </div>

                    <!-- Lab Card: SOC Command Center -->
                    <div class="glass-panel group rounded-[2rem] p-1 flex flex-col transition-all border-primary/20 hover:border-primary/60 hover:translate-y-[-4px] bg-gradient-to-br from-primary/5 to-transparent">
                        <div class="p-6 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="material-symbols-outlined text-4xl text-primary group-hover:scale-110 transition-transform">shield_with_house</span>
                                <div class="px-2 py-1 bg-danger/10 border border-danger/20 rounded-full flex items-center gap-1.5">
                                    <div class="size-1.5 bg-danger rounded-full animate-pulse"></div>
                                    <span class="text-[8px] font-black text-danger uppercase tracking-widest"><?php echo $soc_active_count; ?> Active</span>
                                </div>
                            </div>
                            <h4 class="text-md font-black text-white mb-2 uppercase tracking-tight italic">SOC Command Center</h4>
                            <p class="text-[11px] text-slate-500 leading-relaxed mb-6">Real-time incident response and multi-vector threat visualization hub.</p>
                            
                            <div class="flex items-center gap-2">
                                <span class="size-1.5 rounded-full bg-primary ring-4 ring-primary/10"></span>
                                <span class="text-[9px] font-mono text-primary uppercase tracking-widest">Systems Online</span>
                            </div>
                        </div>
                        <a href="../labs/soc_lab.php" class="p-4 bg-primary/10 text-[9px] font-black text-center text-primary group-hover:bg-primary group-hover:text-neutral-dark uppercase tracking-widest rounded-b-[1.8rem] border-t border-primary/20 transition-all">
                            Launch Interface
                        </a>
                    </div>
                </div>
            </div>

            <!-- Operational Activity & Container Status -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-4">
                
                <!-- Activity Log -->
                <div class="glass-panel rounded-[2.5rem] p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">history</span>
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-white">Recent Operations</h3>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <?php
                        // Dynamically fetch last few successes from different tables
                        $activities = [];
                        
                        // Brute Force recent
                        $res = $conn->query("SELECT 'Brute Force' as lab, created_at FROM bruteforce_logs WHERE user_id = $user_id AND success = 1 ORDER BY created_at DESC LIMIT 1");
                        if ($r = $res->fetch_assoc()) $activities[] = $r;

                        // DDoS recent
                        $res = $conn->query("SELECT 'DDoS Defense' as lab, created_at FROM ddos_logs WHERE user_id = $user_id AND mitigated = 1 ORDER BY created_at DESC LIMIT 1");
                        if ($r = $res->fetch_assoc()) $activities[] = $r;

                        // Malware recent
                        $res = $conn->query("SELECT 'Malware Analysis' as lab, created_at FROM malware_logs WHERE user_id = $user_id AND correct = 1 ORDER BY created_at DESC LIMIT 1");
                        if ($r = $res->fetch_assoc()) $activities[] = $r;

                        // Sort all by date
                        usort($activities, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

                        if (empty($activities)): ?>
                            <div class="p-10 text-center space-y-3 opacity-30">
                                <span class="material-symbols-outlined text-4xl">folder_off</span>
                                <p class="text-[10px] font-black uppercase tracking-widest">No Recent Ops Recorded</p>
                            </div>
                        <?php else:
                            foreach (array_slice($activities, 0, 3) as $act): ?>
                                <div class="flex items-center gap-4 p-4 bg-white/[0.02] border border-white/5 rounded-2xl group hover:border-primary/20 transition-all">
                                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined text-sm">terminal</span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black text-white uppercase tracking-tight"><?php echo $act['lab']; ?> Successful</p>
                                        <p class="text-[9px] font-mono text-slate-600 uppercase tracking-widest"><?php echo date('M d, Y H:i', strtotime($act['created_at'])); ?> Zulu</p>
                                    </div>
                                    <div class="px-2 py-0.5 bg-primary/10 rounded text-[8px] font-black text-primary uppercase tracking-widest">Verified</div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                </div>

                <!-- Container Nodes (Juice Shop, etc.) -->
                <div class="glass-panel rounded-[2.5rem] p-8 space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">container</span>
                        <h3 class="text-xs font-black uppercase tracking-[0.2em] text-white">Dynamic Sandbox Nodes</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <?php foreach (['juiceshop' => 'OWASP Juice', 'dvwa' => 'Web DVWA', 'bwapp' => 'bWAPP Lab'] as $id => $title): ?>
                            <div class="p-4 bg-surface border border-border-dim rounded-2xl space-y-4 group hover:border-primary/30 transition-all">
                                <div>
                                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1"><?php echo $title; ?></p>
                                    <div class="flex items-center gap-2">
                                        <span id="dot-<?php echo $id; ?>" class="lab-status-dot status-stopped"></span>
                                        <span id="status-<?php echo $id; ?>" class="text-[9px] font-mono font-black text-slate-600 uppercase tracking-widest">Offline</span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="manageLab('<?php echo $id; ?>', 'start')" class="size-8 rounded-lg bg-primary/10 hover:bg-primary hover:text-neutral-dark text-primary flex items-center justify-center transition-all">
                                        <span class="material-symbols-outlined text-sm">play_arrow</span>
                                    </button>
                                    <button onclick="manageLab('<?php echo $id; ?>', 'stop')" class="size-8 rounded-lg bg-white/5 hover:bg-red-500/20 hover:text-red-500 text-slate-500 flex items-center justify-center transition-all">
                                        <span class="material-symbols-outlined text-sm">stop</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLI Status Footer -->
        <footer class="h-10 bg-neutral-dark border-t border-border-dim flex items-center justify-between px-8 z-30 shrink-0">
            <div class="flex items-center gap-8 text-[9px] font-mono text-slate-500 uppercase tracking-widest">
                <div class="flex items-center gap-2">
                    <span class="text-primary font-black">//_NODE:</span>
                    <span id="system-time">Initializing...</span>
                </div>
                <div class="flex items-center gap-2 hidden md:flex">
                    <span class="text-primary font-black">//_STATUS:</span>
                    <span>READY_CORE_UPLINK</span>
                </div>
            </div>
            <div class="text-[9px] font-mono text-slate-500 uppercase tracking-widest font-black hidden sm:block">
                Shield Command Interface // Build 4.2.0-STABLE
            </div>
        </footer>
    </main>

    <script>
        // Lab Management Logic (Preserved)
        function manageLab(labId, action) {
            const statusText = document.getElementById(`status-${labId}`);
            const statusDot = document.getElementById(`dot-${labId}`);
            if (statusText) {
                statusText.innerText = 'WAITING...';
                statusText.className = 'text-[9px] font-mono font-black text-primary animate-pulse';
            }
            if (statusDot) statusDot.className = 'lab-status-dot status-loading';

            fetch(`../labs/manage_lab.php?lab=${labId}&action=${action}`)
                .then(res => res.json())
                .then(data => {
                    setTimeout(() => checkLabStatus(labId), 1000);
                    if (action === 'start' && data.port) showLabModal(labId, data.port);
                }).catch(() => checkLabStatus(labId));
        }

        function checkLabStatus(labId) {
            const statusText = document.getElementById(`status-${labId}`);
            const statusDot = document.getElementById(`dot-${labId}`);
            fetch(`../labs/manage_lab.php?lab=${labId}&action=status`)
                .then(res => res.json())
                .then(data => {
                    const isRunning = data.status === 'running';
                    if (statusText) {
                        statusText.innerText = isRunning ? 'ONLINE' : 'OFFLINE';
                        statusText.className = `text-[9px] font-mono font-black ${isRunning ? 'text-primary' : 'text-slate-600'} uppercase tracking-widest`;
                    }
                    if (statusDot) statusDot.className = `lab-status-dot status-${data.status}`;
                });
        }

        function showLabModal(labId, port) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-[300] flex items-center justify-center bg-black/90 backdrop-blur-md p-4';
            modal.innerHTML = `
                <div class="glass-panel border border-primary/40 rounded-[2.5rem] w-full max-w-md p-10 shadow-3xl text-center">
                    <div class="size-20 bg-primary/20 rounded-3xl flex items-center justify-center text-primary mx-auto mb-6 shadow-[0_0_30px_-5px_#a0f000]">
                        <span class="material-symbols-outlined text-4xl">rocket_launch</span>
                    </div>
                    <h3 class="text-2xl font-black uppercase text-white mb-2">Node <span class="text-primary">Deployed</span></h3>
                    <p class="text-[10px] text-slate-500 font-mono tracking-widest mb-8">TARGET_INSTANCE: ${labId.toUpperCase()}</p>
                    <a href="http://localhost:${port}" target="_blank" class="block w-full py-5 bg-primary text-neutral-dark font-black rounded-2xl hover:brightness-110 mb-4 transition-all uppercase tracking-widest text-xs">Access Instance Console</a>
                    <button onclick="this.closest('.fixed').remove()" class="w-full py-4 text-slate-500 font-bold uppercase tracking-widest text-[9px] hover:text-white transition-all">Dismiss Overlay</button>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Threat Feed Logic
        const threats = [
            "Unauthorized login attempt blocked from 182.12.8.x",
            "Brute-force pattern detected on Node_77",
            "XSS payload sanitized on /auth/relay",
            "Incoming DDoS burst mitigated - US_EAST",
            "Malware signature match found in /tmp/inbound",
            "Encrypted proxy detected from 82.xx.xx.xx",
            "SQL Injection attempt prevented on /api/stats",
            "Phishing link reported - Domain quarantined"
        ];
        function updateThreatFeed() {
            const feed = document.getElementById('threat-feed');
            const item = document.createElement('div');
            const time = new Date().toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit' });
            item.className = 'threat-feed-item text-slate-500 flex gap-2';
            item.innerHTML = `<span class="text-primary font-black">[${time}]</span> <span>${threats[Math.floor(Math.random() * threats.length)]}</span>`;
            feed.prepend(item);
            if (feed.children.length > 5) feed.lastElementChild.remove();
        }
        setInterval(updateThreatFeed, 4000);
        updateThreatFeed();

        // System Time
        function updateTime() {
            const now = new Date();
            document.getElementById('system-time').innerText = now.getUTCHours().toString().padStart(2, '0') + ':' + now.getUTCMinutes().toString().padStart(2, '0') + ':' + now.getUTCSeconds().toString().padStart(2, '0') + ' ZULU';
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Initialized Lab Status
        document.addEventListener('DOMContentLoaded', () => {
            ['juiceshop', 'dvwa', 'bwapp'].forEach(checkLabStatus);

            // Lab Search Filter
            const searchInput = document.getElementById('labSearch');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    const cards = document.querySelectorAll('.glass-panel.group.rounded-\\[2rem\\]');
                    cards.forEach(card => {
                        const title = card.querySelector('h4').innerText.toLowerCase();
                        const desc = card.querySelector('p').innerText.toLowerCase();
                        if (title.includes(term) || desc.includes(term)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
    <!-- Scroll Buttons -->
    <div class="fixed bottom-20 right-8 flex flex-col gap-3 z-[100]">
        <button onclick="document.querySelector('.overflow-y-auto').scrollTo({top: 0, behavior: 'smooth'})" class="size-10 rounded-full bg-surface border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-neutral-dark transition-all shadow-glow group">
            <span class="material-symbols-outlined text-sm group-hover:animate-bounce">arrow_upward</span>
        </button>
        <button onclick="const el = document.querySelector('.overflow-y-auto'); el.scrollTo({top: el.scrollHeight, behavior: 'smooth'})" class="size-10 rounded-full bg-surface border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-neutral-dark transition-all shadow-glow group">
            <span class="material-symbols-outlined text-sm group-hover:animate-bounce">arrow_downward</span>
        </button>
    </div>
</body>
</html>