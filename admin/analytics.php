<?php
require_once __DIR__ . '/admin-auth.php';

/**
 * Analytics Data Acquisition
 */

// 1. Lab Activity Distribution (Pie Chart - Prepared Statements)
function get_analytics_count($conn, $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $res;
}

$dist = [];
$dist['phishing']   = get_analytics_count($conn, "SELECT COUNT(*) FROM phishing_campaigns");
$dist['bruteforce'] = get_analytics_count($conn, "SELECT COUNT(*) FROM bruteforce_logs");
$dist['malware']    = get_analytics_count($conn, "SELECT COUNT(*) FROM malware_samples");
$dist['ddos']       = get_analytics_count($conn, "SELECT COUNT(*) FROM ddos_simulations");

// 2. User Growth over life (Monthly - Prepared Statement)
$growthData = [];
$growth_stmt = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
");
$growth_stmt->execute();
$growthRes = $growth_stmt->get_result();
while ($row = $growthRes->fetch_assoc()) $growthData[] = $row;
$growth_stmt->close();

// 3. Daily Events (Last 14 Days) - Activity Trend
$trends = [];
$trend_stmt = $conn->prepare("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = ?");
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_stmt->bind_param("s", $date);
    $trend_stmt->execute();
    $c = $trend_stmt->get_result()->fetch_row()[0];
    $trends[] = ['date' => date('M d', strtotime($date)), 'count' => (int)$c];
}
$trend_stmt->close();

// 4. Lab Performance Metrics (Prepared Statements)
$perf = [];
$perf['avg_ddos']     = get_analytics_count($conn, "SELECT AVG(duration_sec) FROM ddos_simulations");
$perf['total_emails'] = get_analytics_count($conn, "SELECT SUM(emails_sent) FROM phishing_campaigns");
$perf['bf_wins']      = get_analytics_count($conn, "SELECT SUM(success) FROM bruteforce_logs");

// 5. Global Summary for Report (Prepared Statements)
$globalStats = [];
$globalStats['users']      = get_analytics_count($conn, "SELECT COUNT(*) FROM users");
$globalStats['phishing']   = get_analytics_count($conn, "SELECT COUNT(*) FROM phishing_campaigns");
$globalStats['bruteforce'] = get_analytics_count($conn, "SELECT COUNT(*) FROM bruteforce_logs");
$globalStats['malware']    = get_analytics_count($conn, "SELECT COUNT(*) FROM malware_samples");
$globalStats['ddos']       = get_analytics_count($conn, "SELECT COUNT(*) FROM ddos_simulations");

// 6. Detailed User Roster (Prepared Statement)
$roster_stmt = $conn->prepare("SELECT name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 100");
$roster_stmt->execute();
$userRoster = $roster_stmt->get_result();
$roster_stmt->close();

// 7. Security Audit Trail (Recent Logs - Prepared Statement)
$recent_stmt = $conn->prepare("
    SELECT sl.*, u.name as analyst
    FROM security_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.created_at DESC
    LIMIT 50
");
$recent_stmt->execute();
$recentLogs = $recent_stmt->get_result();
$recent_stmt->close();

// 8. Module Deep Dive Data (Prepared Statements)
function get_deep_dive($conn, $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res;
}

$phishingDetail = get_deep_dive($conn, "SELECT pc.*, u.name as creator FROM phishing_campaigns pc JOIN users u ON u.id = pc.user_id ORDER BY pc.created_at DESC LIMIT 5");
$bruteDetail    = get_deep_dive($conn, "SELECT bl.*, u.name as analyst FROM bruteforce_logs bl JOIN users u ON u.id = bl.user_id ORDER BY bl.created_at DESC LIMIT 5");
$malwareDetail  = get_deep_dive($conn, "SELECT ms.*, u.name as analyst FROM malware_samples ms JOIN users u ON u.id = ms.user_id ORDER BY ms.upload_date DESC LIMIT 5");
$ddosDetail     = get_deep_dive($conn, "SELECT ds.*, u.name as analyst FROM ddos_simulations ds JOIN users u ON u.id = ds.user_id ORDER BY ds.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Security Intelligence Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#a0f000",
                        "background-dark": "#0a0c02",
                        surface: "#12140a",
                        "neutral-dark": "#16190e",
                        "border-dim": "#23281b"
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .glass {
            background: rgba(18, 20, 10, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }

        .nav-item.active {
            background: rgba(160, 240, 0, 0.1);
            border-right: 2px solid #a0f000;
        }

        .glow {
            text-shadow: 0 0 10px rgba(160, 240, 0, 0.5);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #23281b;
            border-radius: 10px;
        }

        /* ── Print/Export Styles (Neon SOC Aesthetic) ────────── */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: #0a0c02 !important;
                color: #cbd5e1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                overflow: visible !important;
            }

            #admin-sidebar,
            header,
            footer,
            .export-btn,
            .status-bar-print-hide {
                display: none !important;
            }

            main {
                display: block !important;
                width: 100% !important;
                background: #0a0c02 !important;
                position: static !important;
                overflow: visible !important;
            }

            section {
                padding: 40px !important;
                background: #0a0c02 !important;
                overflow: visible !important;
            }

            .flex,
            .grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px !important;
            }

            .glass {
                background: #12140a !important;
                border: 1px solid rgba(160, 240, 0, 0.2) !important;
                box-shadow: none !important;
                margin-bottom: 24px !important;
                page-break-inside: avoid;
                color: #cbd5e1 !important;
            }

            .print-header {
                display: block !important;
                background: #16190e !important;
                border-bottom: 2px solid #a0f000 !important;
                padding: 40px !important;
                margin-bottom: 30px;
            }

            .print-footer {
                display: block !important;
                position: fixed;
                bottom: 0;
                width: 100%;
                background: #16190e !important;
                border-top: 1px solid #23281b !important;
                color: #a0f000 !important;
                font-size: 10px;
                padding: 10px 0;
                text-align: center;
            }

            h1,
            h2,
            h3,
            h4 {
                color: #a0f000 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.1em !important;
                text-shadow: none !important;
            }

            p {
                color: inherit !important;
                text-shadow: none !important;
            }

            .text-white {
                color: #ffffff !important;
            }

            .text-primary {
                color: #a0f000 !important;
            }

            .text-slate-500 {
                color: #64748b !important;
            }

            canvas {
                max-width: 100% !important;
                height: 280px !important;
            }

            .summary-card {
                grid-template-columns: repeat(5, 1fr) !important;
            }

            .print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 15px !important;
            }

            .print-table th,
            .print-table td {
                border: 1px solid rgba(160, 240, 0, 0.2) !important;
                padding: 8px !important;
                text-align: left !important;
                font-size: 10px !important;
            }

            .print-table th {
                background: #16190e !important;
                color: #a0f000 !important;
                text-transform: uppercase !important;
            }

            .page-break {
                page-break-before: always !important;
            }
        }

        .print-header,
        .print-footer {
            display: none;
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include '_sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Analytics Core: csh_intelligence_engine</p>
                    <h2 class="text-xl font-black text-white italic uppercase">Security <span class="text-primary glow">Intelligence</span></h2>
                </div>
                <button onclick="window.print()" class="export-btn px-4 py-2 bg-surface border border-border-dim rounded-lg text-xs font-bold text-slate-400 hover:text-primary transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span> Export Report
                </button>
            </header>

            <!-- Printable Report Header -->
            <div class="print-header px-8 pt-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-black flex items-center justify-center rounded">
                            <span class="material-symbols-outlined text-primary text-4xl">shield</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black uppercase tracking-tighter">Cyber<span class="text-primary">Shield</span></h1>
                            <p class="text-[10px] font-mono uppercase tracking-widest">Security Intelligence Unit</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-lg font-bold text-black uppercase">Intelligence Report</h2>
                        <p class="text-xs text-slate-500 font-mono">ID: REF-<?php echo strtoupper(substr(md5(time()), 0, 8)); ?></p>
                    </div>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-8 border-t border-black pt-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-500">Report Entity</p>
                        <p class="text-sm font-bold">CyberShield SOC Management</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-500">Generated By</p>
                        <p class="text-sm font-bold"><?php echo htmlspecialchars($adminName); ?> (SOC Admin)</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-500">Timestamp</p>
                        <p class="text-sm font-bold font-mono"><?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-500">Security Clearance</p>
                        <p class="text-sm font-bold text-red-600">LEVEL 4 - INTERNAL ONLY</p>
                    </div>
                </div>
            </div>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8">

                <!-- High Level Metrics -->
                <div class="summary-card grid grid-cols-1 md:grid-cols-5 gap-6">
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-primary">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Total Operators</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($globalStats['users']); ?></h4>
                        <p class="text-[10px] text-primary mt-2">Active Analyst Base</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-orange-500">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Phishing Labs</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($globalStats['phishing']); ?></h4>
                        <p class="text-[10px] text-orange-400 mt-2">Social Engineering Nodes</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-red-500">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Brute Sessions</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($globalStats['bruteforce']); ?></h4>
                        <p class="text-[10px] text-red-400 mt-2">Auth Breach Attempts</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-red-600">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Malware Base</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($globalStats['malware']); ?></h4>
                        <p class="text-[10px] text-red-500 mt-2">Analyzed Threat Samples</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-blue-500">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">DDoS Activity</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($globalStats['ddos']); ?></h4>
                        <p class="text-[10px] text-blue-400 mt-2">Volumetric Sims</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass p-6 rounded-2xl border-t-2 border-t-primary/30">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Avg Response Depth</p>
                        <h4 id="perf-ddos" class="text-3xl font-black text-white italic"><?php echo round($perf['avg_ddos'] ?? 0, 1); ?>s</h4>
                    </div>
                    <div class="glass p-6 rounded-2xl border-t-2 border-t-orange-500/30">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Total Breach Vector</p>
                        <h4 id="perf-emails" class="text-3xl font-black text-white italic"><?php echo number_format($perf['total_emails'] ?? 0); ?></h4>
                    </div>
                    <div class="glass p-6 rounded-2xl border-t-2 border-t-red-500/30">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Brute Success Rate</p>
                        <h4 id="perf-bf" class="text-3xl font-black text-white italic">
                            <?php
                            $totalBF = (int)($dist['bruteforce'] ?? 0);
                            echo $totalBF > 0 ? round(($perf['bf_wins'] / $totalBF) * 100, 1) : 0;
                            ?>%
                        </h4>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Activity Trend -->
                    <div class="glass p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em]">Operational Pulse (14D)</h3>
                            <span class="material-symbols-outlined text-primary">timeline</span>
                        </div>
                        <div class="h-64">
                            <canvas id="pulseChart"></canvas>
                        </div>
                    </div>

                    <!-- Growth Graph -->
                    <div class="glass p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em]">Platform Adoption Curve</h3>
                            <span class="material-symbols-outlined text-primary">trending_up</span>
                        </div>
                        <div class="h-64">
                            <canvas id="growthChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Data Distribution -->
                    <div class="lg:col-span-1 glass p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em]">Module Engagement</h3>
                        </div>
                        <div class="h-80 flex items-center justify-center">
                            <canvas id="moduleChart"></canvas>
                        </div>
                    </div>

                    <!-- Analysis Summary -->
                    <div class="lg:col-span-2 glass p-6 rounded-2xl">
                        <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6">Intelligence Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-neutral-dark/40 border border-border-dim p-4 rounded-xl">
                                <h5 class="text-xs font-bold text-primary mb-2">Social Engineering</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Phishing simulations show high initial engagement. Suggesting increased training focus on email header verification and domain spoofing detection.</p>
                            </div>
                            <div class="bg-neutral-dark/40 border border-border-dim p-4 rounded-xl">
                                <h5 class="text-xs font-bold text-orange-400 mb-2">Endpoint Security</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Brute force attacks are predominantly targeting legacy protocols. Analysis of analyst behavior shows efficient response to SSH vector simulations.</p>
                            </div>
                            <div class="bg-neutral-dark/40 border border-border-dim p-4 rounded-xl">
                                <h5 class="text-xs font-bold text-red-400 mb-2">Threat Mitigation</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">Malware analysis lab workload has increased by 14%. Majority of samples classified as droppers or second-stage payloads.</p>
                            </div>
                            <div class="bg-neutral-dark/40 border border-border-dim p-4 rounded-xl">
                                <h5 class="text-xs font-bold text-blue-400 mb-2">Network Resilience</h5>
                                <p class="text-xs text-slate-400 leading-relaxed">DDoS simulations at the application layer are proving the most challenging for automated defenses. Scrubbing center latency remains within target range.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- V. Analyst Infrastructure (User Roster) -->
                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm">groups</span> Analyst Infrastructure
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">Name</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Email</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Role</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Status</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($u = $userRoster->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2 text-white"><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-400"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td class="px-4 py-2 uppercase font-bold text-primary"><?php echo $u['role']; ?></td>
                                        <td class="px-4 py-2"><?php echo ucfirst($u['status']); ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-500"><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VI. Security Audit Trail (Recent Logs) -->
                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-sm">history_edu</span> Security Audit Trail
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">Event</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Subject</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Description</th>
                                    <th class="px-4 py-2 border-b border-border-dim">IP Address</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($l = $recentLogs->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded bg-primary/10 text-primary uppercase text-[9px] font-bold"><?php echo str_replace('_', ' ', $l['event_type']); ?></span></td>
                                        <td class="px-4 py-2 text-white"><?php echo htmlspecialchars($l['analyst'] ?? 'SYSTEM'); ?></td>
                                        <td class="px-4 py-2 text-slate-400"><?php echo htmlspecialchars($l['description']); ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-500"><?php echo htmlspecialchars($l['ip_address']); ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-600 text-[10px]"><?php echo $l['created_at']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- VII. Module Deep Dives -->
                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-orange-400 text-sm">alternate_email</span> Phishing Simulation Deep Dive
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">Subject</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Sender</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Creator</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Status</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = $phishingDetail->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2 text-white"><?php echo htmlspecialchars($p['subject']); ?></td>
                                        <td class="px-4 py-2 text-slate-400"><?php echo htmlspecialchars($p['sender_email']); ?></td>
                                        <td class="px-4 py-2 font-bold text-primary"><?php echo htmlspecialchars($p['creator']); ?></td>
                                        <td class="px-4 py-2 uppercase text-[9px]"><?php echo $p['status']; ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-500"><?php echo date('Y-m-d', strtotime($p['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-500 text-sm">security</span> Brute Force Simulation Logs
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">Target</th>
                                    <th class="px-4 py-2 border-b border-border-dim">User Tried</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Analyst</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Result</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Attempts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($b = $bruteDetail->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2 text-white font-mono"><?php echo htmlspecialchars($b['target_system']); ?></td>
                                        <td class="px-4 py-2 text-slate-400 font-mono"><?php echo htmlspecialchars($b['username_tried']); ?></td>
                                        <td class="px-4 py-2 font-bold text-primary"><?php echo htmlspecialchars($b['analyst']); ?></td>
                                        <td class="px-4 py-2">
                                            <span class="<?php echo $b['success'] ? 'text-primary' : 'text-red-400'; ?> font-bold uppercase text-[9px]">
                                                <?php echo $b['success'] ? 'Success' : 'Failed'; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-slate-500"><?php echo $b['attempts']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600 text-sm">pest_control</span> Malware Analysis Records
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">File Name</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Analyst</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Result</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Type</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Upload Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($m = $malwareDetail->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2 text-white font-mono"><?php echo htmlspecialchars($m['file_name']); ?></td>
                                        <td class="px-4 py-2 font-bold text-primary"><?php echo htmlspecialchars($m['analyst']); ?></td>
                                        <td class="px-4 py-2 uppercase text-[9px] font-bold"><?php echo $m['analysis_result']; ?></td>
                                        <td class="px-4 py-2 text-slate-400 font-mono"><?php echo htmlspecialchars($m['file_type']); ?></td>
                                        <td class="px-4 py-2 font-mono text-slate-500"><?php echo date('Y-m-d', strtotime($m['upload_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="page-break glass p-6 rounded-2xl">
                    <h3 class="font-bold text-white uppercase text-xs tracking-[0.2em] mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500 text-sm">hub</span> DDoS Volumetric Logs
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="print-table w-full text-xs">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-widest text-[10px]">
                                    <th class="px-4 py-2 border-b border-border-dim">Target</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Analyst</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Attack Type</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Status</th>
                                    <th class="px-4 py-2 border-b border-border-dim">Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = $ddosDetail->fetch_assoc()): ?>
                                    <tr class="border-b border-border-dim/30">
                                        <td class="px-4 py-2 text-white font-mono"><?php echo htmlspecialchars($d['target_server']); ?></td>
                                        <td class="px-4 py-2 font-bold text-primary"><?php echo htmlspecialchars($d['analyst']); ?></td>
                                        <td class="px-4 py-2 text-slate-400 font-mono text-[9px]"><?php echo htmlspecialchars($d['attack_type']); ?></td>
                                        <td class="px-4 py-2 uppercase text-[9px]"><?php echo $d['status']; ?></td>
                                        <td class="px-4 py-2 font-mono text-primary font-bold"><?php echo number_format($d['requests_sent']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center px-6 justify-between">
                <span class="text-[10px] font-mono text-primary italic">CyberShield Admin v1.0 Intelligence Unit</span>
                <span id="realtime-status" class="text-[10px] font-mono text-slate-500">System Ready</span>
            </footer>

            <!-- Printable Report Footer -->
            <div class="print-footer">
                <div class="px-8 flex items-center justify-between">
                    <span class="text-primary font-bold">CYBERSHIELD | CONFIDENTIAL</span>
                    <span>Page 1 of 1</span>
                    <span>v1.0.4-LTS</span>
                </div>
            </div>
        </main>
    </div>

    <script>
        Chart.defaults.color = '#64748b';
        Chart.defaults.font.family = "'Inter', sans-serif";

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        };

        // 1. Operational Pulse
        const pulseChart = new Chart(document.getElementById('pulseChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trends, 'date')); ?>,
                datasets: [{
                    label: 'System Events',
                    data: <?php echo json_encode(array_column($trends, 'count')); ?>,
                    borderColor: '#a0f000',
                    backgroundColor: 'rgba(160,240,0,0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // 2. Growth Adoption
        const growthChart = new Chart(document.getElementById('growthChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($growthData, 'month')); ?>,
                datasets: [{
                    label: 'Active Operators',
                    data: <?php echo json_encode(array_column($growthData, 'count')); ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // 3. Modue Distribution
        const moduleChart = new Chart(document.getElementById('moduleChart'), {
            type: 'doughnut',
            data: {
                labels: ['Phishing', 'Brute Force', 'Malware', 'DDoS'],
                datasets: [{
                    data: [
                        <?php echo $dist['phishing']; ?>,
                        <?php echo $dist['bruteforce']; ?>,
                        <?php echo $dist['malware']; ?>,
                        <?php echo $dist['ddos']; ?>
                    ],
                    backgroundColor: ['#a0f000', '#f97316', '#ef4444', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            color: '#94a3b8'
                        }
                    }
                },
                cutout: '70%'
            }
        });

        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function updateAnalytics() {
            try {
                const res = await fetch('api_stats.php');
                const data = await res.json();

                // Update Metrics
                document.getElementById('perf-ddos').innerText = data.performance.avg_ddos + 's';
                document.getElementById('perf-emails').innerText = data.performance.total_emails.toLocaleString();

                const bfTotal = data.counts.bruteforce;
                const bfRate = bfTotal > 0 ? ((data.performance.bf_wins / bfTotal) * 100).toFixed(1) : 0;
                document.getElementById('perf-bf').innerText = bfRate + '%';

                // Update Charts
                pulseChart.data.labels = data.charts.trends.labels;
                pulseChart.data.datasets[0].data = data.charts.trends.data;
                pulseChart.update('none');

                growthChart.data.labels = data.charts.growth.labels;
                growthChart.data.datasets[0].data = data.charts.growth.data;
                growthChart.update('none');

                moduleChart.data.datasets[0].data = [
                    data.counts.phishing,
                    data.counts.bruteforce,
                    data.counts.malware,
                    data.counts.ddos
                ];
                moduleChart.update('none');

                const status = document.getElementById('realtime-status');
                status.innerText = 'Sync: Active (' + new Date().toLocaleTimeString() + ')';
                status.classList.add('text-primary');
                setTimeout(() => status.classList.remove('text-primary'), 1000);

            } catch (e) {
                console.error("Analytics Sync Failed", e);
                document.getElementById('realtime-status').innerText = 'Sync Error: Retrying...';
            }
        }

        setInterval(updateAnalytics, 15000); // 15s updates for analytics
    </script>
</body>

</html>