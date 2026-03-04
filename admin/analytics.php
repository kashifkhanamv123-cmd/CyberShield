<?php
require_once __DIR__ . '/admin-auth.php';

/**
 * Analytics Data Acquisition
 */

// 1. Lab Activity Distribution (Pie Chart)
$dist = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM phishing_campaigns) as phishing,
        (SELECT COUNT(*) FROM bruteforce_logs) as bruteforce,
        (SELECT COUNT(*) FROM malware_samples) as malware,
        (SELECT COUNT(*) FROM ddos_simulations) as ddos
")->fetch_assoc();

// 2. User Growth over life (Monthly)
$growthData = [];
$growthRes = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
");
while ($row = $growthRes->fetch_assoc()) $growthData[] = $row;

// 3. Daily Events (Last 14 Days) - Activity Trend
$trends = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $c = $conn->query("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at)='$date'")->fetch_row()[0];
    $trends[] = ['date' => date('M d', strtotime($date)), 'count' => (int)$c];
}

// 4. Lab Performance Metrics
$perf = $conn->query("
    SELECT
        (SELECT AVG(duration_sec) FROM ddos_simulations) as avg_ddos,
        (SELECT SUM(emails_sent) FROM phishing_campaigns) as total_emails,
        (SELECT SUM(success) FROM bruteforce_logs) as bf_wins
")->fetch_assoc();

// 5. Global Summary for Report
$globalStats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM users) as users,
        (SELECT COUNT(*) FROM phishing_campaigns) as phishing,
        (SELECT COUNT(*) FROM bruteforce_logs) as bruteforce,
        (SELECT COUNT(*) FROM malware_samples) as malware,
        (SELECT COUNT(*) FROM ddos_simulations) as ddos
")->fetch_assoc();

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

        /* ── Print/Export Styles ───────────────────────────────── */
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }

            body {
                background: white !important;
                color: black !important;
                overflow: visible !important;
                height: auto !important;
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
                overflow: visible !important;
                width: 100% !important;
                position: static !important;
            }

            section {
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }

            .flex,
            .grid {
                display: block !important;
            }

            .glass {
                background: white !important;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                margin-bottom: 20px !important;
                page-break-inside: avoid;
            }

            .print-header {
                display: block !important;
                border-bottom: 3px solid #000;
                padding-bottom: 15px;
                margin-bottom: 30px;
            }

            .print-footer {
                display: block !important;
                position: fixed;
                bottom: 0;
                width: 100%;
                border-top: 1px solid #ddd;
                font-size: 10px;
                padding: 10px 0;
                text-align: center;
            }

            h2,
            h3,
            h4,
            p {
                color: black !important;
                text-shadow: none !important;
            }

            canvas {
                max-width: 100% !important;
                height: 250px !important;
            }

            .grid-cols-1,
            .grid-cols-2,
            .grid-cols-3,
            .lg:grid-cols-2,
            .lg:grid-cols-3 {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                /* Forces 2 columns on print */
                gap: 20px !important;
            }

            .summary-card {
                grid-template-columns: repeat(3, 1fr) !important;
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