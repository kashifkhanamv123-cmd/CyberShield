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
                <button onclick="window.print()" class="px-4 py-2 bg-surface border border-border-dim rounded-lg text-xs font-bold text-slate-400 hover:text-primary transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">print</span> Export Report
                </button>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8">

                <!-- High Level Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-primary">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Avg Response Depth</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo round($perf['avg_ddos'] ?? 0, 1); ?>s</h4>
                        <p class="text-[10px] text-primary mt-2">DDoS Mitigation Efficiency</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-orange-500">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Total Breach Vector</p>
                        <h4 class="text-3xl font-black text-white italic"><?php echo number_format($perf['total_emails'] ?? 0); ?></h4>
                        <p class="text-[10px] text-orange-400 mt-2">Phishing Simulation Volume</p>
                    </div>
                    <div class="glass p-6 rounded-2xl border-l-4 border-l-red-500">
                        <p class="text-[10px] font-black uppercase text-slate-500 tracking-widest mb-1">Brute Success Rate</p>
                        <h4 class="text-3xl font-black text-white italic">
                            <?php
                            $totalBF = (int)($dist['bruteforce'] ?? 0);
                            echo $totalBF > 0 ? round(($perf['bf_wins'] / $totalBF) * 100, 1) : 0;
                            ?>%
                        </h4>
                        <p class="text-[10px] text-red-400 mt-2">Authentication vulnerability index</p>
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
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center px-6">
                <span class="text-[10px] font-mono text-primary italic">CyberShield Admin v1.0 Intelligence Unit</span>
            </footer>
        </main>
    </div>

    <script>
        Chart.defaults.color = '#64748b';
        Chart.defaults.font.family = "'Inter', sans-serif";

        // 1. Operational Pulse
        new Chart(document.getElementById('pulseChart'), {
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
                    pointRadius: 0,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
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
        new Chart(document.getElementById('growthChart'), {
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
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
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
        new Chart(document.getElementById('moduleChart'), {
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
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>

</html>