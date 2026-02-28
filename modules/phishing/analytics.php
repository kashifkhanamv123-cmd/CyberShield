<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id     = $_SESSION['user_id'];
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$campaign    = null;
$events      = [];
$ip_list     = [];
$tracking_url = '';
$landing_img  = '';
$total_sent   = 0;
$total_clicks = 0;
$total_creds  = 0;
$click_rate   = 0;

if ($campaign_id > 0) {
    // Fetch campaign details – prepared statement
    $stmt = $conn->prepare(
        "SELECT * FROM phishing_campaigns WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $campaign_id, $user_id);
    $stmt->execute();
    $campaign = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Build tracking URL from campaign data
    $tracking_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . '/modules/phishing/track.php?id=' . $campaign_id;

    // Landing image placeholder (could be stored in campaigns table)
    $landing_img = $campaign ? ($campaign['landing_image'] ?? '') : '';

    // Stats – prepared statement
    $stmt = $conn->prepare(
        "SELECT
            COUNT(CASE WHEN event_type = 'click' THEN 1 END) AS clicks,
            COUNT(CASE WHEN event_type = 'credential' THEN 1 END) AS creds
         FROM phishing_events WHERE campaign_id = ?"
    );
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM phishing_events WHERE campaign_id = ?");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $total_sent = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $total_clicks = (int)$stats['clicks'];
    $total_creds  = (int)$stats['creds'];

    // Fetch events with IP addresses
    $stmt = $conn->prepare(
        "SELECT * FROM phishing_events WHERE campaign_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
        if (!empty($row['attacker_ip'])) {
            $ip_list[] = $row['attacker_ip'];
        }
    }
    $stmt->close();
} else {
    // Overall stats – prepared statements
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM phishing_campaigns WHERE user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_campaigns = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $total_sent = $total_campaigns * 150;

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM phishing_events pe
         JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
         WHERE pc.user_id = ? AND pe.event_type = 'click'"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_clicks = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM phishing_events pe
         JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
         WHERE pc.user_id = ? AND pe.event_type = 'credential'"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_creds = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Events feed with subject
    $stmt = $conn->prepare(
        "SELECT pe.*, pc.subject FROM phishing_events pe
         JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
         WHERE pc.user_id = ?
         ORDER BY pe.created_at DESC LIMIT 50"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
        if (!empty($row['attacker_ip'])) {
            $ip_list[] = $row['attacker_ip'];
        }
    }
    $stmt->close();
}

$click_rate  = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100, 1) : 0;
$unique_ips  = array_unique($ip_list);
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Phishing Analytics Intelligence</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
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
        #analytics-app {
            background: linear-gradient(rgba(10, 10, 10, 0.95), rgba(10, 10, 10, 0.95)),
                url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        #analytics-app .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        #analytics-app .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        #analytics-app .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #343a27;
            border-radius: 10px;
        }

        #analytics-app.terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        #analytics-app .metric-card {
            background: rgba(35, 40, 27, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
    </style>
    <style>
        @media print {
            body#analytics-app {
                /* Keep the dark theme for print to maintain enterprise look */
                background-color: #12160a !important;
                background-image: none !important;
                /* Strip heavy background images to save ink, keep solid dark */
                color: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #analytics-app .metric-card {
                background-color: #1c230f !important;
                border: 1px solid rgba(160, 240, 0, 0.2) !important;
                box-shadow: none !important;
                break-inside: avoid;
            }

            /* Hide unnecessary UI elements during export */
            header,
            aside,
            footer,
            button[onclick="window.print()"],
            .group-hover\:flex,
            /* Hide beta overlays */
            .animate-pulse {
                /* Stop animations */
                display: none !important;
            }

            /* Fix layout expansion to full width */
            main {
                padding: 0 !important;
                gap: 1.5rem !important;
            }

            /* Force all background colors and utilities to render in PDF */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Specifically hide the preview sections to keep the report clean */
            aside,
            .metric-card.relative.group {
                display: none !important;
            }

            /* Make layout more linear for standard paper sizes */
            @page {
                size: A4 portrait;
                margin: 1cm;
            }

            .grid-cols-2 {
                grid-template-columns: 1fr !important;
            }

            .lg\:flex-row {
                flex-direction: column !important;
            }
        }
    </style>
</head>

<body id="analytics-app" class="text-white font-display terminal-grid min-h-screen flex flex-col overflow-x-hidden custom-scrollbar">

    <!-- HEADER -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md shrink-0">
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
            <div class="flex items-center gap-4">

                <a href="index.php?completed=1"
                    class="px-4 py-1.5 rounded-lg border border-border-muted text-[#b0bc9a] hover:text-white hover:bg-white/5 text-xs font-bold transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    BACK TO SIMULATION
                </a>

            </div>
            <div class="flex items-center gap-3 bg-surface-dark px-3 py-1.5 rounded-full border border-border-muted">
                <span class="text-xs font-bold tracking-wider"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'SEC_INTEL'); ?></span>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col p-6 gap-6">

        <!-- Page Title -->
        <div class="flex items-center justify-between shrink-0">
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tight">Campaign <span class="text-primary">Intelligence</span> Dashboard</h1>
                <p class="text-xs text-[#b0bc9a] font-mono uppercase">Reference: #<?php echo $campaign_id ?: 'OVERALL_OPS'; ?></p>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-surface-dark border border-border-muted rounded-lg text-xs font-bold hover:bg-surface-dark/5 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span>
                    EXPORT REPORT
                </button>
            </div>
        </div>
        <?php if ($campaign_id > 0 && $campaign): ?>
            <div class="metric-card p-6 rounded-2xl">
                <h2 class="text-sm font-bold uppercase tracking-widest mb-4 text-primary">
                    Campaign Metadata
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                    <div>
                        <span class="text-[#b0bc9a]">Campaign ID:</span>
                        <span class="text-white">#<?php echo $campaign['id']; ?></span>
                    </div>

                    <div>
                        <span class="text-[#b0bc9a]">Launched By:</span>
                        <span class="text-white">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-[#b0bc9a]">Sender Name:</span>
                        <span class="text-white">
                            <?php echo htmlspecialchars($campaign['sender_name']); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-[#b0bc9a]">Spoof Email:</span>
                        <span class="text-white">
                            <?php echo htmlspecialchars($campaign['spoof_email']); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-[#b0bc9a]">Subject Line:</span>
                        <span class="text-white">
                            <?php echo htmlspecialchars($campaign['subject']); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-[#b0bc9a]">Launch Time:</span>
                        <span class="text-white">
                            <?php echo date('Y-m-d H:i:s'); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 shrink-0">
            <!-- Total Targets -->
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Total Targets Hit</span>
                <div class="flex items-end gap-2">
                    <p id="kpi-targets" class="text-3xl font-black"><?php echo number_format($total_sent); ?></p>
                    <span class="text-primary text-[10px] font-mono mb-1">LIVE</span>
                </div>
            </div>
            <!-- Total Clicks -->
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Total Clicks</span>
                <div class="flex items-end gap-2">
                    <p id="kpi-clicks" class="text-3xl font-black"><?php echo number_format($total_clicks); ?></p>
                    <span id="kpi-ctr" class="text-primary text-[10px] font-mono mb-1"><?php echo $click_rate; ?>% CTR</span>
                </div>
            </div>
            <!-- Credential Captures -->
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Credential Captures</span>
                <div class="flex items-end gap-2">
                    <p id="kpi-creds" class="text-3xl font-black"><?php echo number_format($total_creds); ?></p>
                    <span class="text-red-500 text-[10px] font-mono mb-1">COMPROMISED</span>
                </div>
            </div>
            <!-- Unique IPs -->
            <div class="metric-card p-6 rounded-2xl">
                <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-2 tracking-widest">Unique IPs Recorded</span>
                <div class="flex items-end gap-2">
                    <p id="kpi-ips" class="text-3xl font-black"><?php echo count($unique_ips); ?></p>
                    <span class="text-amber-500 text-[10px] font-mono mb-1">TRACKED</span>
                </div>
            </div>
        </div>

        <!-- Tracking URL + Landing Image Row (campaign-specific) -->
        <?php if ($campaign_id > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start shrink-0">
                <div class="flex flex-col gap-4">
                    <!-- Tracking URL -->
                    <div class="metric-card p-5 rounded-2xl flex flex-col gap-4">
                        <span class="text-[#b0bc9a] text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-primary">link</span>
                            Tracking URL
                        </span>
                        <div>
                            <div class="flex items-center gap-2 bg-background-dark/60 border border-border-muted rounded-lg px-3 py-3">
                                <span class="text-primary font-mono text-xs break-all flex-1"><?php echo htmlspecialchars($tracking_url); ?></span>
                                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($tracking_url, ENT_QUOTES); ?>')" title="Copy URL"
                                    class="shrink-0 text-[#b0bc9a] hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-sm">content_copy</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Intelligence (Realistic Filler) -->
                    <div class="metric-card p-6 rounded-2xl flex flex-col gap-4 shadow-xl border-l-4 border-l-primary/30 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-background-dark/80 backdrop-blur-sm z-10 hidden group-hover:flex items-center justify-center transition-all">
                            <div class="bg-primary/20 text-primary border border-primary/50 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">construction</span>
                                Module In Development
                            </div>
                        </div>

                        <div class="flex items-center justify-between opacity-50 group-hover:opacity-10 transition-opacity">
                            <span class="text-[#b0bc9a] text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-primary">psychology</span>
                                Target Profiling
                            </span>
                            <span class="text-[9px] px-2 py-0.5 rounded bg-primary/10 text-primary font-bold uppercase tracking-widest">Preview</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 opacity-50 group-hover:opacity-10 transition-opacity">
                            <div class="space-y-3">
                                <p class="text-[9px] font-bold text-[#b0bc9a] uppercase tracking-wider opacity-50">Browser Distribution</p>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#b0bc9a]">Chrome</span>
                                        <span class="text-primary font-mono">68%</span>
                                    </div>
                                    <div class="w-full h-1 bg-background-dark/80 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary" style="width: 68%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-[#b0bc9a]">Edge</span>
                                        <span class="text-[#b0bc9a]/60 font-mono">22%</span>
                                    </div>
                                    <div class="w-full h-1 bg-background-dark/80 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary opacity-40" style="width: 22%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <p class="text-[9px] font-bold text-[#b0bc9a] uppercase tracking-wider opacity-50">Top Risk Dept.</p>
                                <div class="p-3 bg-background-dark/40 rounded-xl border border-border-muted/30 text-center">
                                    <span class="material-symbols-outlined text-xl text-primary/60 mb-1">payments</span>
                                    <p class="text-xs font-bold text-white uppercase tracking-tight">Finance / Ops</p>
                                    <p class="text-[8px] text-red-400 font-bold uppercase tracking-widest mt-1">High Vulnerability</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 p-3 bg-primary/5 rounded-xl border border-primary/10 opacity-50 group-hover:opacity-10 transition-opacity">
                            <p class="text-[10px] text-primary/80 font-medium italic">"Targets exhibit high trust in Corporate SSO lures. Recommend follow-up MFA simulation to test lateral movement resilience."</p>
                        </div>
                    </div>
                </div>
                <!-- Landing Image -->
                <div class="metric-card p-5 rounded-2xl flex flex-col gap-2">
                    <span class="text-[#b0bc9a] text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-primary">image</span>
                        Landing Page Preview
                    </span>
                    <?php
                    if (!empty($landing_img)):
                        // Resolve path: if it doesn't start with http, it's a local path relative to root
                        $display_path = (strpos($landing_img, 'http') === 0) ? $landing_img : "../../" . $landing_img;
                    ?>
                        <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Landing page preview"
                            class="w-full max-h-[400px] object-cover object-top rounded-lg border border-border-muted shadow-lg" />
                    <?php else: ?>
                        <div class="flex items-center justify-center h-20 bg-background-dark/50 border border-border-muted rounded-lg text-[#b0bc9a] text-xs font-mono gap-2">
                            <span class="material-symbols-outlined text-sm opacity-40">hide_image</span>
                            No landing image configured
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Event Feed + Sidebar Row -->
        <div class="flex-1 flex flex-col lg:flex-row gap-6">

            <!-- Real-time Event Feed -->
            <section class="flex-1 metric-card rounded-2xl p-6 flex flex-col min-h-[380px]">
                <div class="flex items-center justify-between mb-6 shrink-0">
                    <h2 class="text-sm font-bold uppercase tracking-widest flex items-center gap-2">
                        <span class="size-2 rounded-full bg-primary animate-pulse"></span>
                        Real-time Event Feed
                    </h2>
                    <span class="text-[10px] font-mono text-[#b0bc9a]">MONITORING ACTIVE...</span>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar space-y-4 font-mono text-xs pr-2">
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <div class="p-4 rounded-xl bg-background-dark/50 border border-white/5 hover:border-primary/20 transition-all flex items-start gap-4">
                                <div class="size-8 rounded-lg bg-surface-dark flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-sm <?php echo $event['event_type'] === 'credential' ? 'text-red-500' : 'text-primary'; ?>">
                                        <?php echo $event['event_type'] === 'credential' ? 'key' : 'ads_click'; ?>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1 gap-2">
                                        <span class="text-primary font-bold truncate">EVENT_<?php echo strtoupper(htmlspecialchars($event['event_type'])); ?></span>
                                        <span class="text-[10px] text-[#b0bc9a] shrink-0"><?php echo date('H:i:s', strtotime($event['created_at'])); ?></span>
                                    </div>
                                    <p class="text-white/80 leading-relaxed">
                                        Subject: <span class="text-white"><?php echo htmlspecialchars($event['subject'] ?? 'System Alert'); ?></span><br>
                                        Origin IP: <span class="text-amber-400"><?php echo htmlspecialchars($event['attacker_ip'] ?? '—'); ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center h-full text-[#b0bc9a] gap-4">
                            <span class="material-symbols-outlined text-4xl opacity-20">sensors_off</span>
                            <p class="uppercase tracking-widest text-[10px]">No telemetry detected</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Sidebar -->
            <section class="w-full lg:w-80 flex flex-col gap-4 shrink-0">

                <!-- IP Address Log -->
                <div class="metric-card rounded-2xl p-6">
                    <h3 class="text-xs font-black uppercase italic text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">travel_explore</span>
                        Captured IP Addresses
                    </h3>
                    <?php if (!empty($unique_ips)): ?>
                        <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                            <?php foreach ($unique_ips as $ip): ?>
                                <div class="flex items-center justify-between bg-background-dark/60 border border-border-muted rounded-lg px-3 py-2">
                                    <span class="font-mono text-xs text-amber-400"><?php echo htmlspecialchars($ip); ?></span>
                                    <span class="text-[10px] text-[#b0bc9a] uppercase">Logged</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[10px] text-[#b0bc9a] font-mono uppercase">No IPs captured yet</p>
                    <?php endif; ?>
                </div>

                <!-- Traffic Analysis (Preview Placeholder) -->
                <div class="metric-card rounded-2xl p-6 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-background-dark/80 backdrop-blur-sm z-10 hidden group-hover:flex items-center justify-center transition-all">
                        <div class="bg-primary/20 text-primary border border-primary/50 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">science</span>
                            Beta Feature
                        </div>
                    </div>

                    <h3 class="text-xs font-black uppercase italic text-primary mb-4 opacity-50 group-hover:opacity-10 transition-opacity">Traffic Analysis</h3>
                    <div class="space-y-4 opacity-50 group-hover:opacity-10 transition-opacity">
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase">
                                <span>Desktop</span><span id="stat-desktop">65%</span>
                            </div>
                            <div class="h-1 w-full bg-surface-dark/5 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width:65%"></div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase">
                                <span>Mobile</span><span id="stat-mobile">35%</span>
                            </div>
                            <div class="h-1 w-full bg-surface-dark/5 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500" style="width:35%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Advice (Preview Placeholder) -->
                <div class="flex-1 metric-card rounded-2xl p-6 bg-primary/5 border-primary/20 flex flex-col justify-center text-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-background-dark/80 backdrop-blur-sm z-10 hidden group-hover:flex items-center justify-center transition-all">
                        <span class="text-[10px] font-bold text-primary uppercase tracking-widest border border-primary/30 px-3 py-1 rounded">AI Model Training...</span>
                    </div>

                    <div class="opacity-50 group-hover:opacity-10 transition-opacity">
                        <span class="material-symbols-outlined text-primary text-4xl mb-4">insights</span>
                        <h3 class="text-sm font-bold uppercase tracking-widest mb-2">Automated SOC Advice</h3>
                        <p class="text-[10px] text-[#b0bc9a] leading-relaxed italic">
                            "90% of data breaches start with a single phishing email. Continuous training is the only firewall for human error."
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="shrink-0 px-6 py-2 bg-background-dark border-t border-border-muted flex items-center justify-between text-[10px] text-[#b0bc9a] font-mono">
        <div class="flex gap-4"><span>TELEMETRY: <span id="telemetry-status" class="text-primary">ONLINE</span></span><span>STATUS: ENCRYPTED</span></div>
        <div class="uppercase tracking-tighter">CyberShield Intel Ops © 2024</div>
    </footer>

    <script>
        const campaignId = <?php echo (int)$campaign_id; ?>;

        // Use Server-Sent Events (SSE) instead of setInterval polling
        if (campaignId > 0) {
            const eventSource = new EventSource(`live_feed.php?id=${campaignId}`);

            eventSource.onopen = function() {
                document.getElementById('telemetry-status').textContent = 'ONLINE';
                document.getElementById('telemetry-status').className = 'text-primary';
                console.log("SSE connection established.");
            };

            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    updateDashboard(data);
                } catch (e) {
                    console.error("Error parsing SSE data:", e, event.data);
                }
            };

            eventSource.onerror = function() {
                console.error("SSE connection lost. Reconnecting...");
                document.getElementById('telemetry-status').textContent = 'OFFLINE';
                document.getElementById('telemetry-status').className = 'text-red-500 animate-pulse';
            };

            // Clean up connection when leaving the page
            window.addEventListener('beforeunload', () => {
                eventSource.close();
            });
        }

        function updateDashboard(data) {
            const feedContainer = document.querySelector('.flex-1.overflow-y-auto.custom-scrollbar.space-y-4');
            const ipLogContainer = document.querySelector('.space-y-2.max-h-40.overflow-y-auto.custom-scrollbar');

            // Re-render feed
            if (data.events && data.events.length > 0) {
                let html = '';
                let ipsHtml = '';
                const uniqueIps = new Set();

                data.events.forEach(event => {
                    const time = new Date(event.created_at).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    const isCred = event.event_type === 'credential';

                    html += `
                        <div class="p-4 rounded-xl bg-background-dark/50 border border-white/5 hover:border-primary/20 transition-all flex items-start gap-4">
                            <div class="size-8 rounded-lg bg-surface-dark flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-sm ${isCred ? 'text-red-500' : 'text-primary'}">
                                    ${isCred ? 'key' : 'ads_click'}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1 gap-2">
                                    <span class="text-primary font-bold truncate">EVENT_${event.event_type.toUpperCase()}</span>
                                    <span class="text-[10px] text-[#b0bc9a] shrink-0">${time}</span>
                                </div>
                                <p class="text-white/80 leading-relaxed">
                                    Origin IP: <span class="text-amber-400">${event.attacker_ip}</span>
                                </p>
                            </div>
                        </div>`;

                    if (event.attacker_ip && !uniqueIps.has(event.attacker_ip)) {
                        uniqueIps.add(event.attacker_ip);
                        ipsHtml += `
                            <div class="flex items-center justify-between bg-background-dark/60 border border-border-muted rounded-lg px-3 py-2">
                                <span class="font-mono text-xs text-amber-400">${event.attacker_ip}</span>
                                <span class="text-[10px] text-[#b0bc9a] uppercase">Logged</span>
                            </div>`;
                    }
                });

                if (feedContainer) feedContainer.innerHTML = html;
                if (ipLogContainer) ipLogContainer.innerHTML = ipsHtml;
            }

            // Update stats
            if (data.stats) {
                const s = data.stats;
                const updateKpi = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val.toLocaleString();
                };

                updateKpi('kpi-targets', s.sent);
                updateKpi('kpi-clicks', s.clicks);
                updateKpi('kpi-creds', s.creds);
                updateKpi('kpi-ips', s.ips);

                const ctrEl = document.getElementById('kpi-ctr');
                if (ctrEl && s.sent > 0) {
                    const rate = Math.round((s.clicks / s.sent) * 100);
                    ctrEl.textContent = `${rate}% CTR`;
                }
            }
        }
    </script>
</body>

</html>