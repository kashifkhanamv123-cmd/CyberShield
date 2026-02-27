<?php
require_once __DIR__ . "/../../config/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

include("../../config/db.php");

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($campaign_id > 0) {
    // Record click event
    $conn->query("INSERT INTO phishing_events 
        (campaign_id, event_type, target_email)
        VALUES ($campaign_id, 'click', 'employee@test.com')");

    // Record credential capture event
    $conn->query("INSERT INTO phishing_events 
        (campaign_id, event_type, target_email)
        VALUES ($campaign_id, 'credential', 'employee@test.com')");
}

// Mock captured data for simulation
$captured_ip = $_SERVER['REMOTE_ADDR'];
$captured_login = "target_employee@company.com";
$captured_pass = "P@ssw0rd123!";
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Phishing Simulation Results</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-dark": "#0a0a0a",
                        "surface-dark": "#161810",
                        "border-muted": "#343a27",
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
            background: rgba(22, 24, 16, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }

        .glow-text {
            text-shadow: 0 0 10px rgba(160, 240, 0, 0.5);
        }

        .scrolling-content {
            animation: scrollUp 20s linear infinite;
        }

        @keyframes scrollUp {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-50%);
            }
        }
    </style>
</head>

<body class="bg-background-dark text-white font-display min-h-screen terminal-grid p-8 overflow-y-auto">
    <div class="max-w-7xl mx-auto flex flex-col gap-8 pb-12">
        <!-- Header -->
        <header class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="size-2 bg-primary rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-mono text-primary uppercase tracking-[0.2em]">Live Simulation Node</span>
                </div>
                <h1 class="text-4xl font-black text-white italic uppercase tracking-tight">
                    Campaign <span class="text-primary glow-text">Analysis</span>
                </h1>
                <p class="text-slate-400 text-sm mt-1">Campaign ID: <span class="font-mono text-white">#<?php echo htmlspecialchars($campaign_id); ?></span> // Status: <span class="text-primary">Recorded</span></p>
            </div>
            <div class="flex gap-4">
                <a href="index.php?success=1" class="px-6 py-2.5 rounded-lg border border-border-muted hover:bg-white/5 transition-all text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    BACK TO LAB
                </a>
                <button onclick="window.print()" class="px-6 py-2.5 rounded-lg bg-primary text-background-dark font-black text-sm uppercase tracking-wider hover:brightness-110 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span>
                    GENERATE REPORT
                </button>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Targets Hit</span>
                    <span class="material-symbols-outlined text-primary">groups</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-white glow-text">1</span>
                    <span class="text-xs text-slate-500">/ 1 recipients</span>
                </div>
                <div class="w-full bg-white/5 h-1 rounded-full mt-4 overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-2xl border-primary/30">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-primary uppercase tracking-widest">Click Rate</span>
                    <span class="material-symbols-outlined text-primary">ads_click</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-primary glow-text">100%</span>
                    <span class="text-xs text-slate-500">Industry Avg: 12%</span>
                </div>
                <div class="w-full bg-white/5 h-1 rounded-full mt-4 overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Time to Action</span>
                    <span class="material-symbols-outlined text-amber-500">schedule</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-white glow-text">4s</span>
                    <span class="text-xs text-slate-500">Critical Speed</span>
                </div>
                <div class="w-full bg-white/5 h-1 rounded-full mt-4 overflow-hidden">
                    <div class="bg-amber-500 h-full rounded-full" style="width: 90%"></div>
                </div>
            </div>
            <div class="glass-panel p-6 rounded-2xl border-red-500/30">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Security Failures</span>
                    <span class="material-symbols-outlined text-red-500">gpp_maybe</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-red-500 glow-text">01</span>
                    <span class="text-xs text-slate-500">Data Compromised</span>
                </div>
                <div class="w-full bg-white/5 h-1 rounded-full mt-4 overflow-hidden">
                    <div class="bg-red-500 h-full rounded-full" style="width: 100%"></div>
                </div>
            </div>
        </div>

        <!-- Detailed Activity and Breakdown -->
        <div class="grid grid-cols-12 gap-8 text-white/90">
            <!-- Left: Live Log Feed -->
            <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
                <!-- Data Exfiltration Card -->
                <div class="glass-panel p-6 rounded-2xl border-red-500/20 bg-red-500/5">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="size-10 rounded-lg bg-red-500/20 flex items-center justify-center text-red-500">
                            <span class="material-symbols-outlined">data_loss_prevention</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Captured Credentials</h3>
                            <p class="text-xs text-red-400/70">Real-time data exfiltration from intercepted payload</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Target Login</span>
                            <div class="p-3 rounded-lg bg-background-dark border border-white/5 font-mono text-sm text-primary">
                                <?php echo htmlspecialchars($captured_login); ?>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Captured Password</span>
                            <div class="p-3 rounded-lg bg-background-dark border border-white/5 font-mono text-sm text-red-500 flex items-center justify-between">
                                <span><?php echo htmlspecialchars($captured_pass); ?></span>
                                <span class="material-symbols-outlined text-xs">key</span>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Source IP</span>
                            <div class="p-3 rounded-lg bg-background-dark border border-white/5 font-mono text-sm text-white">
                                <?php echo htmlspecialchars($captured_ip); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-panel h-64 rounded-2xl flex flex-col overflow-hidden">
                    <div class="p-4 border-b border-white/5 bg-white/2 flex items-center justify-between">
                        <h3 class="text-sm font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">terminal</span>
                            LIVE_EVENT_STREAM
                        </h3>
                        <span class="px-2 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold">ACTIVE MONITORING</span>
                    </div>
                    <div id="terminal-logs" class="flex-1 overflow-y-auto p-4 font-mono text-xs space-y-2 custom-scrollbar bg-black/40">
                        <p class="text-white/30">[<?php echo date('H:i:s', time() - 30); ?>] SYSTEM: HANDSHAKE_INIT_COMPLETE</p>
                        <p class="text-white/30">[<?php echo date('H:i:s', time() - 25); ?>] CAMPAIGN: MOUNTING_PAYLOAD_NODE</p>
                        <p class="text-white/30">[<?php echo date('H:i:s', time() - 20); ?>] SMTP: DISPATCHING_MAIL -> targets: 1</p>
                        <p class="text-primary">[<?php echo date('H:i:s', time() - 15); ?>] EVENT: TARGET_RECEIVED_PACKET -> employee@test.com</p>
                        <p class="text-primary">[<?php echo date('H:i:s', time() - 10); ?>] EVENT: INTERACTION_DETECTED -> action=click</p>
                        <p class="text-amber-500">[<?php echo date('H:i:s', time() - 8); ?>] PAYLOAD: INJECTING_CREDENTIAL_HOOK</p>
                        <p class="text-red-500 font-bold">[<?php echo date('H:i:s', time() - 4); ?>] CRITICAL: DATA_EXFIL_SUCCESS -> target_ip=<?php echo $captured_ip; ?></p>
                        <p class="text-red-400">[<?php echo date('H:i:s', time() - 3); ?>] DATA: CAPTURED -> usr:<?php echo $captured_login; ?> pwd:<?php echo $captured_pass; ?></p>
                        <p class="text-white/30">[<?php echo date('H:i:s'); ?>] SYSTEM: REPORT_GENERATED_ID_0<?php echo $campaign_id; ?></p>
                        <p class="animate-pulse text-primary">_</p>
                    </div>
                </div>

                <!-- Psychology Analysis -->
                <div class="glass-panel p-6 rounded-2xl">
                    <h3 class="text-sm font-bold mb-4 uppercase tracking-tighter">Psychological Vector Analysis</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 font-display">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 hover:border-primary/20 transition-all">
                            <p class="text-[10px] text-primary font-bold uppercase mb-1">Trigger: Fear</p>
                            <p class="text-xs text-slate-400 italic">"Account Suspension" notice triggered a 'fight or flight' stress response, bypassing logical filters.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 hover:border-primary/20 transition-all">
                            <p class="text-[10px] text-primary font-bold uppercase mb-1">Vector: Spoofing</p>
                            <p class="text-xs text-slate-400 italic">Sender display name matched IT operations, establishing immediate hierarchy-based trust.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 hover:border-primary/20 transition-all">
                            <p class="text-[10px] text-primary font-bold uppercase mb-1">Tactical: Urgency</p>
                            <p class="text-xs text-slate-400 italic">The 24-hour deadline narrowed the target's cognitive window, making them miss the spoofed URL.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Action Summary -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
                <div class="glass-panel p-8 rounded-2xl flex flex-col items-center text-center">
                    <div class="size-20 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 mb-6 border-4 border-red-500/10">
                        <span class="material-symbols-outlined text-4xl">warning</span>
                    </div>
                    <h2 class="text-2xl font-black italic uppercase">Attack <span class="text-red-500 font-bold">Successful</span></h2>
                    <p class="text-slate-400 text-xs mt-4 leading-relaxed">
                        The simulation confirms critical vulnerability. The target provided plain-text credentials within <span class="text-white font-bold">4 seconds</span> of opening the email.
                    </p>
                    <div class="w-full mt-8 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-left">
                        <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-2">Remediation Guide</p>
                        <ul class="text-[11px] text-red-200/80 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                                Reset target account credentials immediately.
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                                Enable Multi-Factor Authentication (MFA).
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                                Enroll user in 'Social Engineering 101'.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl flex-1 flex flex-col justify-center items-center gap-4 text-center">
                    <span class="material-symbols-outlined text-primary text-5xl">military_tech</span>
                    <div>
                        <p class="text-lg font-bold">Simulation Complete</p>
                        <p class="text-xs text-[#a0f000]/70 uppercase tracking-widest">Master Social Engineer</p>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <span class="px-2 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] text-primary">CREDENTIAL_SNATCHER</span>
                        <span class="px-2 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] text-primary">URL_MASKER</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple auto-scroll for the terminal logs
        const logBox = document.getElementById('terminal-logs');
        if (logBox) {
            logBox.scrollTop = logBox.scrollHeight;
        }
    </script>
</body>

</html>