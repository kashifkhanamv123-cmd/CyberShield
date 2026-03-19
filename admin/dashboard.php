<?php
require_once __DIR__ . '/admin-auth.php';

// ── Stats ──────────────────────────────────────────────────────
// ── Stats (Prepared Statements) ──────────────────────────────────
function get_count($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $res;
}

$total_users    = get_count($conn, "SELECT COUNT(*) FROM users");
$total_phishing = get_count($conn, "SELECT COUNT(*) FROM phishing_campaigns");
$total_bf       = get_count($conn, "SELECT COUNT(*) FROM bruteforce_logs");
$total_malware  = get_count($conn, "SELECT COUNT(*) FROM malware_samples");
$total_ddos     = get_count($conn, "SELECT COUNT(*) FROM ddos_simulations");
$blocked_users  = get_count($conn, "SELECT COUNT(*) FROM users WHERE status='blocked'");

// ── Recent logs ────────────────────────────────────────────────
// ── Recent logs (Prepared Statement) ─────────────────────────────
$recent_stmt = $conn->prepare("
    SELECT sl.event_type, sl.description, sl.ip_address, sl.created_at, u.name
    FROM security_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.created_at DESC
    LIMIT 8
");
$recent_stmt->execute();
$recent_logs_res = $recent_stmt->get_result();
$recent_stmt->close();

// ── Chart data: last 7 days user registrations ─────────────────
$reg_data = [];
// ── Chart data: last 7 days user registrations (Prepared Statement) 
$reg_data = [];
$reg_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $reg_stmt->bind_param("s", $date);
    $reg_stmt->execute();
    $count = $reg_stmt->get_result()->fetch_row()[0];
    $reg_data['labels'][] = $label;
    $reg_data['data'][] = (int)$count;
}
$reg_stmt->close();

// ── Chart data: lab activity counts ───────────────────────────
$lab_data = [
    'Phishing' => (int)$total_phishing,
    'Brute Force' => (int)$total_bf,
    'Malware' => (int)$total_malware,
    'DDoS' => (int)$total_ddos,
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
            background-color: #020302;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(160, 240, 0, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(0, 240, 255, 0.02) 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
        }

        .glass {
            background: rgba(13, 15, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(160, 240, 0, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        }

        .glass:hover {
            border-color: rgba(160, 240, 0, 0.15);
        }

        .elite-border {
            border: 1px solid rgba(255, 255, 255, 0.03);
            background: linear-gradient(135deg, rgba(255,255,255,0.02), transparent);
        }

        .glow {
            text-shadow: 0 0 20px rgba(160, 240, 0, 0.4);
        }

        .shadow-glow {
            box-shadow: 0 0 20px -5px rgba(160, 240, 0, 0.2);
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
        <main class="flex-1 flex flex-col overflow-hidden relative">
            <!-- Top Bar -->
            <header class="shrink-0 sticky top-0 z-20 bg-[#050604]/80 backdrop-blur-xl border-b border-border-dim px-10 py-6 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <button id="mobile-admin-btn" class="md:hidden text-white hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-2xl">menu</span>
                    </button>
                    <div>
                        <p class="text-[9px] font-mono text-primary/50 uppercase tracking-[0.3em] font-black">Operator Session: Active</p>
                        <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter">Command <span class="text-primary glow not-italic">Center</span></h2>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex flex-col items-end hidden sm:flex">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest leading-none mb-1">System Core Status</span>
                        <div class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-primary animate-pulse shadow-glow"></span>
                            <span class="text-[11px] font-mono text-primary uppercase font-bold tracking-widest">Nominal</span>
                        </div>
                    </div>
                    <div class="h-10 w-px bg-border-dim hidden sm:block"></div>
                    <span class="text-xs text-slate-500 font-mono font-bold tracking-widest hidden lg:block"><?php echo date('Y.m.d // H:i:s'); ?></span>
                </div>
            </header>


            <!-- Content -->
            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8">

                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <?php
                    $cards = [
                        ['Users',     'total_users',    'group',           'from-blue-500/10   to-blue-900/5',  'text-blue-500',   'border-blue-500/10'],
                        ['Phishing',  'total_phishing', 'alternate_email', 'from-primary/10    to-primary/5',    'text-primary',    'border-primary/10'],
                        ['Brute Force', 'total_bf',       'lock_open',       'from-orange-500/10 to-orange-900/5', 'text-orange-500', 'border-orange-500/10'],
                        ['Malware', 'total_malware',  'bug_report',      'from-red-500/10    to-red-900/5',   'text-red-500',    'border-red-500/10'],
                        ['DDoS',       'total_ddos',     'thunderstorm',    'from-purple-500/10 to-purple-900/5', 'text-purple-500', 'border-purple-500/10'],
                        ['Blocked',   'blocked_users',  'block',           'from-slate-500/10  to-slate-900/5', 'text-slate-500',  'border-slate-500/10'],
                    ];
                    foreach ($cards as [$label, $id, $icon, $grad, $color, $border]):
                        $val = 0;
                        if ($id == 'total_users') $val = $total_users;
                        if ($id == 'total_phishing') $val = $total_phishing;
                        if ($id == 'total_bf') $val = $total_bf;
                        if ($id == 'total_malware') $val = $total_malware;
                        if ($id == 'total_ddos') $val = $total_ddos;
                        if ($id == 'blocked_users') $val = $blocked_users;
                    ?>
                        <div class="stat-card glass rounded-[2rem] p-6 bg-gradient-to-br <?php echo $grad; ?> border <?php echo $border; ?> relative overflow-hidden group">
                            <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <span class="material-symbols-outlined text-[6rem]"><?php echo $icon; ?></span>
                            </div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="size-10 rounded-xl bg-white/5 flex items-center justify-center border border-white/5">
                                    <span class="material-symbols-outlined text-xl <?php echo $color; ?>"><?php echo $icon; ?></span>
                                </div>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em]"><?php echo $label; ?></p>
                            </div>
                            <p id="stat-<?php echo $id; ?>" class="text-3xl font-black text-white tracking-tighter"><?php echo number_format($val); ?></p>
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
                        <div class="h-64">
                            <canvas id="regChart"></canvas>
                        </div>
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
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="labChart"></canvas>
                        </div>
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
                            <tbody id="logs-tbody" class="divide-y divide-border-dim/50">
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
                    <span id="realtime-status" class="text-slate-500">Admin session active</span>
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
        const regChart = new Chart(document.getElementById('regChart'), {
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
                responsive: true,
                maintainAspectRatio: false,
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
        const labChart = new Chart(document.getElementById('labChart'), {
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
                cutout: '65%',
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        const eventColors = <?php echo json_encode($event_colors); ?>;

        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function updateDashboard() {
            try {
                const res = await fetch('api_stats.php');
                const data = await res.json();

                // Update Stats
                document.getElementById('stat-total_users').innerText = data.counts.users.toLocaleString();
                document.getElementById('stat-total_phishing').innerText = data.counts.phishing.toLocaleString();
                document.getElementById('stat-total_bf').innerText = data.counts.bruteforce.toLocaleString();
                document.getElementById('stat-total_malware').innerText = data.counts.malware.toLocaleString();
                document.getElementById('stat-total_ddos').innerText = data.counts.ddos.toLocaleString();
                document.getElementById('stat-blocked_users').innerText = data.counts.blocked.toLocaleString();

                // Update reg chart
                regChart.data.labels = data.charts.registrations.labels;
                regChart.data.datasets[0].data = data.charts.registrations.data;
                regChart.update('none');

                // Update lab chart
                labChart.data.datasets[0].data = [
                    data.counts.phishing,
                    data.counts.bruteforce,
                    data.counts.malware,
                    data.counts.ddos
                ];
                labChart.update('none');

                // Update Logs
                const tbody = document.getElementById('logs-tbody');
                tbody.innerHTML = data.recent_logs.map(log => {
                    const color = eventColors[log.event_type] || 'bg-slate-500/10 text-slate-400';
                    return `
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${color}">
                                    ${escapeHTML(log.event_type.replace(/_/g, ' '))}
                                </span>
                            </td>
                            <td class="py-3 text-white font-medium">${escapeHTML(log.name || 'System')}</td>
                            <td class="py-3 text-slate-400 max-w-xs truncate">${escapeHTML(log.description || '-')}</td>
                            <td class="py-3 font-mono text-xs text-slate-500">${escapeHTML(log.ip_address || '-')}</td>
                            <td class="py-3 font-mono text-xs text-slate-500">${escapeHTML(log.time_ago)}</td>
                        </tr>
                    `;
                }).join('');

                const status = document.getElementById('realtime-status');
                status.innerText = 'Connected: Sync successful';
                status.classList.add('text-primary');
                setTimeout(() => status.classList.remove('text-primary'), 1000);

            } catch (e) {
                console.error("Dashboard Sync Failed", e);
                document.getElementById('realtime-status').innerText = 'Sync Error: Reconnecting...';
            }
        }

        // Poll every 3 seconds for demo (normally 10-30s)
        setInterval(updateDashboard, 5000);

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', () => {
            const mobileBtn = document.getElementById('mobile-admin-btn');
            const closeBtn = document.getElementById('close-admin-sidebar');
            const sidebar = document.getElementById('admin-sidebar');
            if (mobileBtn && sidebar) {
                mobileBtn.addEventListener('click', () => sidebar.classList.remove('-translate-x-full'));
            }
            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));
            }
        });
    </script>
</body>

</html>