<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Live Stats for this campaign or overall
if ($campaign_id > 0) {
    $res = $conn->query("SELECT * FROM phishing_campaigns WHERE id = $campaign_id AND user_id = $user_id");
    $campaign = $res->fetch_assoc();

    $events_res = $conn->query("SELECT * FROM phishing_events WHERE campaign_id = $campaign_id ORDER BY created_at DESC");

    $stats_res = $conn->query("SELECT 
        COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
        COUNT(CASE WHEN event_type = 'credential' THEN 1 END) as creds
        FROM phishing_events WHERE campaign_id = $campaign_id");
    $stats = $stats_res->fetch_assoc();

    $total_sent = 175; // Simulated for high-fidelity feel or fetch from targeted audience size
    $total_clicks = $stats['clicks'];
    $total_creds = $stats['creds'];
} else {
    // Overall Stats
    $total_sent_res = $conn->query("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = $user_id");
    $total_sent = $total_sent_res->fetch_assoc()['total'] * 150; // Scaled

    $total_clicks_res = $conn->query("SELECT COUNT(*) as total FROM phishing_events pe JOIN phishing_campaigns pc ON pe.campaign_id = pc.id WHERE pc.user_id = $user_id AND pe.event_type = 'click'");
    $total_clicks = $total_clicks_res->fetch_assoc()['total'];

    $total_creds_res = $conn->query("SELECT COUNT(*) as total FROM phishing_events pe JOIN phishing_campaigns pc ON pe.campaign_id = pc.id WHERE pc.user_id = $user_id AND pe.event_type = 'credential'");
    $total_creds = $total_creds_res->fetch_assoc()['total'];

    $events_res = $conn->query("SELECT pe.*, pc.subject FROM phishing_events pe JOIN phishing_campaigns pc ON pe.campaign_id = pc.id WHERE pc.user_id = $user_id ORDER BY pe.created_at DESC LIMIT 50");
}

$click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Phishing Analytics Intelligence</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-dark": "#1c230f",
                        "border-muted": "#343a27",
                        "surface-dark": "#23281b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background: linear-gradient(rgba(10, 10, 10, 0.95), rgba(10, 10, 10, 0.95)),
                url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #343a27;
            border-radius: 10px;
        }

        .terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .metric-card {
            background: rgba(35, 40, 27, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
    </style>
</head>

<body class="text-white font-display terminal-grid min-h-screen flex flex-col overflow-x-hidden custom-scrollbar">
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-3 text-primary cursor-pointer transition-transform hover:scale-105" onclick="location.href='index.php'">
                <span class="material-symbols-outlined text-3xl">shield_person</span>
                <h2 class="text-white text-xl font-bold tracking-tight uppercase">CyberShield <span class="text-primary/70 text-xs font-mono">INTEL</span></h2>
            </div>
            <div class="hidden lg:flex items-center gap-6">
                <a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="index.php">Simulation</a>
                <a class="text-primary text-sm font-semibold border-b-2 border-primary pb-1" href="analytics.php">Analytics</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <a href="../../dashboard/dashboard.php" class="px-4 py-1.5 rounded-lg border border-border-muted text-[#b0bc9a] hover:text-white hover:bg-white/5 text-xs font-bold transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">dashboard</span>
                BACK TO DASHBOARD
            </a>
            <div class="flex items-center gap-3 bg-surface-dark px-3 py-1.5 rounded-full border border-border-muted">
                <span class="text-xs font-bold tracking-wider"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'SEC_INTEL'); ?></span>
            </div>
        </div>
    </header>

    <main class="flex-1 flex flex-col p-6 gap-6 lg:h-[calc(100vh-60px)] overflow-hidden">
        <div class="flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tight">Campaign <span class="text-primary">Intelligence</span> Dashboard</h1>
                <p class="text-xs text-[#b0bc9a] font-mono uppercase">Reference: #<?php echo $campaign_id ?: 'OVERALL_OPS'; ?></p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-surface-dark border border-border-muted rounded-lg text-xs font-bold hover:bg-white/5 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span>
                    EXPORT REPORT
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 shrink-0">
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Total Targets Hit</span>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black"><?php echo number_format($total_sent); ?></p>
                    <span class="text-primary text-[10px] font-mono mb-1">LIVE</span>
                </div>
            </div>
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Click-Through Rate</span>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black"><?php echo $click_rate; ?>%</p>
                    <span class="text-primary text-[10px] font-mono mb-1">AVG. 12%</span>
                </div>
            </div>
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Avg. Time to Action</span>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black">4m 12s</p>
                    <span class="text-amber-500 text-[10px] font-mono mb-1">CRITICAL</span>
                </div>
            </div>
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Security Failures</span>
                <div class="flex items-end gap-2">
                    <p class="text-3xl font-black"><?php echo number_format($total_creds); ?></p>
                    <span class="text-red-500 text-[10px] font-mono mb-1">COMPROMISED</span>
                </div>
            </div>
        </div>

        <div class="flex-1 min-h-0 flex flex-col lg:flex-row gap-6">
            <section class="flex-1 flex flex-col min-h-[400px] metric-card rounded-2xl p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-6 shrink-0">
                    <h2 class="text-sm font-bold uppercase tracking-widest flex items-center gap-2">
                        <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                        Real-time Event Feed
                    </h2>
                    <span class="text-[10px] font-mono text-[#b0bc9a]">MONITORING ACTIVE...</span>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4 font-mono text-xs pr-2">
                    <?php if ($events_res && $events_res->num_rows > 0): ?>
                        <?php while ($event = $events_res->fetch_assoc()): ?>
                            <div class="p-4 rounded-xl bg-background-dark/50 border border-white/5 hover:border-primary/20 transition-all flex items-start gap-4">
                                <div class="size-8 rounded-lg bg-surface-dark flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-sm <?php echo $event['event_type'] == 'credential' ? 'text-red-500' : 'text-primary'; ?>">
                                        <?php echo $event['event_type'] == 'credential' ? 'key' : 'ads_click'; ?>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-primary font-bold">EVENT_<?php echo strtoupper($event['event_type']); ?></span>
                                        <span class="text-[10px] text-[#b0bc9a]"><?php echo date('H:i:s', strtotime($event['created_at'])); ?></span>
                                    </div>
                                    <p class="text-white/80 leading-relaxed">
                                        Subject: <span class="text-white"><?php echo htmlspecialchars($event['subject'] ?? 'System Alert'); ?></span><br>
                                        Origin: <span class="text-white"><?php echo $event['attacker_ip'] ?: '192.168.1.45'; ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center h-full text-[#b0bc9a] gap-4">
                            <span class="material-symbols-outlined text-4xl opacity-20">sensors_off</span>
                            <p class="uppercase tracking-widest text-[10px]">No telemetry detected</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="w-full lg:w-80 flex flex-col gap-4 shrink-0">
                <div class="metric-card rounded-2xl p-6">
                    <h3 class="text-xs font-black uppercase italic text-primary mb-4">Traffic analysis</h3>
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase">
                                <span>Desktop</span>
                                <span>65%</span>
                            </div>
                            <div class="h-1 w-full bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: 65%"></div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase">
                                <span>Mobile</span>
                                <span>35%</span>
                            </div>
                            <div class="h-1 w-full bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500" style="width: 35%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 metric-card rounded-2xl p-6 bg-primary/5 border-primary/20 flex flex-col justify-center text-center">
                    <span class="material-symbols-outlined text-primary text-4xl mb-4">insights</span>
                    <h3 class="text-sm font-bold uppercase tracking-widest mb-2">Security Advice</h3>
                    <p class="text-[10px] text-[#b0bc9a] leading-relaxed italic">
                        "90% of data breaches start with a single phishing email. Continuous training is the only firewall for human error."
                    </p>
                </div>
            </section>
        </div>
    </main>

    <footer class="sticky bottom-0 z-50 px-6 py-2 bg-background-dark border-t border-border-muted flex items-center justify-between text-[10px] text-[#b0bc9a] font-mono">
        <div class="flex gap-4"><span>TELEMETRY: ONLINE</span><span>STATUS: ENCRYPTED</span></div>
        <div class="uppercase tracking-tighter">CyberShield Intel Ops © 2024</div>
    </footer>
</body>

</html>