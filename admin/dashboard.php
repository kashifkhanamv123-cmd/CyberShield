<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/admin-auth.php';

// ── Stats ──────────────────────────────────────────────────────
$total_users     = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_phishing  = $conn->query("SELECT COUNT(*) FROM phishing_campaigns")->fetch_row()[0];
$total_bf        = $conn->query("SELECT COUNT(*) FROM bruteforce_logs")->fetch_row()[0];
$total_malware   = $conn->query("SELECT COUNT(*) FROM malware_samples")->fetch_row()[0];
$total_ddos      = $conn->query("SELECT COUNT(*) FROM ddos_simulations")->fetch_row()[0];
$blocked_users   = $conn->query("SELECT COUNT(*) FROM users WHERE status='blocked'")->fetch_row()[0];

// ── Recent logs ────────────────────────────────────────────────
$recent_logs_res = $conn->query("
    SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.name
    FROM security_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.created_at DESC
    LIMIT 8
");

// ── Chart data: last 7 days user registrations ─────────────────
$reg_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $count = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)='$date'")->fetch_row()[0];
    $reg_data['labels'][] = $label;
    $reg_data['data'][]   = (int)$count;
}

// ── Chart data: lab activity counts ───────────────────────────
$lab_data = [
    'Phishing'    => (int)$total_phishing,
    'Brute Force' => (int)$total_bf,
    'Malware'     => (int)$total_malware,
    'DDoS'        => (int)$total_ddos,
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Admin Dashboard</title>
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
                        "border-dim": "#23281b",
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

        .stat-card {
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 20px rgba(160, 240, 0, 0.1);
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">

        <?php include '_sidebar.php'; ?>

        <!-- Main -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Node: csh_admin_01</p>
                    <h2 class="text-xl font-black text-white italic uppercase">Admin <span class="text-primary glow">Control Centre</span></h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-[10px] font-bold text-primary font-mono uppercase tracking-widest animate-pulse">● LIVE</span>
                    <span class="text-xs text-slate-500 font-mono"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </header>

            <!-- Content -->
            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8">

                <!-- Stat Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">
                    <?php
                    $cards = [
                        ['Total Users',     $total_users,    'group',           'from-blue-500/20   to-blue-900/10',  'text-blue-400'],
                        ['Phishing Camps',  $total_phishing, 'alternate_email', 'from-primary/20    to-primary/5',    'text-primary'],
                        ['Brute Force Logs', $total_bf,       'lock_open',       'from-orange-500/20 to-orange-900/10', 'text-orange-400'],
                        ['Malware Samples', $total_malware,  'bug_report',      'from-red-500/20    to-red-900/10',   'text-red-400'],
                        ['DDoS Sims',       $total_ddos,     'thunderstorm',    'from-purple-500/20 to-purple-900/10', 'text-purple-400'],
                        ['Blocked Users',   $blocked_users,  'block',           'from-slate-500/20  to-slate-900/10', 'text-slate-400'],
                    ];
                    foreach ($cards as [$label, $value, $icon, $grad, $color]):
                    ?>
                        <div class="stat-card glass rounded-2xl p-5 bg-gradient-to-br <?php echo $grad; ?>">
                            <span class="material-symbols-outlined text-2xl <?php echo $color; ?>"><?php echo $icon; ?></span>
                            <p class="text-2xl font-black text-white mt-2"><?php echo number_format($value); ?></p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5"><?php echo $label; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- User Registrations Chart -->
                    <div class="lg:col-span-2 glass rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Analytics</p>
                                <h3 class="text-base font-bold text-white">User Registrations (Last 7 Days)</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary">show_chart</span>
                        </div>
                        <canvas id="regChart" height="120"></canvas>
                    </div>

                    <!-- Lab Usage Doughnut -->
                    <div class="glass rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Lab Usage</p>
                                <h3 class="text-base font-bold text-white">Activity Distribution</h3>
                            </div>
                            <span class="material-symbols-outlined text-primary">donut_large</span>
                        </div>
                        <canvas id="labChart" height="160"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Audit Trail</p>
                            <h3 class="text-base font-bold text-white">Recent Security Events</h3>
                        </div>
                        <a href="logs.php" class="text-xs text-primary hover:underline font-mono">View All →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="text-left pb-3">Event</th>
                                    <th class="text-left pb-3">User</th>
                                    <th class="text-left pb-3">Description</th>
                                    <th class="text-left pb-3">IP Address</th>
                                    <th class="text-left pb-3">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php
                                $event_colors = [
                                    'login_success'  => 'bg-primary/10 text-primary',
                                    'login_failed'   => 'bg-red-500/10 text-red-400',
                                    'logout'         => 'bg-slate-500/10 text-slate-400',
                                    'register'       => 'bg-blue-500/10 text-blue-400',
                                    'password_reset' => 'bg-orange-500/10 text-orange-400',
                                    'admin_action'   => 'bg-purple-500/10 text-purple-400',
                                    'phishing_lab'   => 'bg-yellow-500/10 text-yellow-400',
                                    'bruteforce_lab' => 'bg-red-500/10 text-red-400',
                                    'malware_lab'    => 'bg-red-700/10 text-red-500',
                                    'ddos_lab'       => 'bg-orange-700/10 text-orange-500',
                                ];
                                if ($recent_logs_res && $recent_logs_res->num_rows > 0):
                                    while ($row = $recent_logs_res->fetch_assoc()):
                                        $badgeCls = $event_colors[$row['event_type']] ?? 'bg-slate-500/10 text-slate-400';
                                ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="py-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo $badgeCls; ?>">
                                                    <?php echo str_replace('_', ' ', $row['event_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 text-white font-medium"><?php echo htmlspecialchars($row['name'] ?? 'System'); ?></td>
                                            <td class="py-3 text-slate-400 max-w-xs truncate"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                                            <td class="py-3 font-mono text-xs text-slate-500"><?php echo htmlspecialchars($row['ip_address'] ?? '-'); ?></td>
                                            <td class="py-3 font-mono text-xs text-slate-500"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-500 text-xs">No events logged yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <!-- Status Bar -->
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center justify-between px-6">
                <div class="flex items-center gap-4 text-[10px] font-mono">
                    <span class="text-primary">Console:</span>
                    <span class="text-slate-500">Admin session active</span>
                </div>
                <span class="text-[10px] font-mono text-primary italic">CyberShield Admin v1.0</span>
            </footer>
        </main>
    </div>

    <script>
        const chartDefaults = {
            color: '#94a3b8',
            plugins: {
                legend: {
                    labels: {
                        color: '#94a3b8',
                        font: {
                            family: 'Inter',
                            size: 11
                        }
                    }
                }
            }
        };

        // Registration line chart
        new Chart(document.getElementById('regChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($reg_data['labels']); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($reg_data['data']); ?>,
                    borderColor: '#a0f000',
                    backgroundColor: 'rgba(160,240,0,0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: '#a0f000',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    x: {
                        ticks: {
                            color: '#64748b'
                        },
                        grid: {
                            color: '#23281b'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#64748b'
                        },
                        grid: {
                            color: '#23281b'
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        // Lab doughnut chart
        new Chart(document.getElementById('labChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($lab_data)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($lab_data)); ?>,
                    backgroundColor: ['#a0f000', '#f97316', '#ef4444', '#a855f7'],
                    borderColor: '#12140a',
                    borderWidth: 2
                }]
            },
            options: {
                ...chartDefaults,
                cutout: '65%'
            }
        });
    </script>
</body>

</html>