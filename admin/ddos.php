<?php
require_once __DIR__ . '/admin-auth.php';

// ── Fetch DDoS simulations ──────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where  = "1";
$params = [];
$types  = "";

if (in_array($statusFilter, ['running', 'completed', 'failed', 'aborted'])) {
    $where .= " AND ds.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($search) {
    $like = "%$search%";
    $where .= " AND (ds.target_server LIKE ? OR u.name LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sql = "SELECT ds.*, u.name as user_name
        FROM ddos_simulations ds
        JOIN users u ON u.id = ds.user_id
        WHERE $where
        ORDER BY ds.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$simulations = $stmt->get_result();

// Stats for DDoS (Prepared Statement) ───────────────────────────
$stats_stmt = $conn->prepare("
    SELECT
        COUNT(*) as total,
        SUM(status='completed') as completed,
        SUM(status='running') as running,
        SUM(requests_sent) as total_requests
    FROM ddos_simulations
");
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | DDoS Simulation Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Module: ddos_simulation_monitor</p>
                    <h2 class="text-xl font-black text-white italic uppercase">DDoS <span class="text-primary glow">Simulation Monitor</span></h2>
                </div>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-6">

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="glass rounded-2xl p-5 bg-gradient-to-br from-purple-500/20 to-purple-900/10">
                        <span class="material-symbols-outlined text-2xl text-purple-400">thunderstorm</span>
                        <p class="text-2xl font-black text-white mt-2"><?php echo $stats['total'] ?? 0; ?></p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Total Simulations</p>
                    </div>
                    <div class="glass rounded-2xl p-5 bg-gradient-to-br from-primary/20 to-primary/5">
                        <span class="material-symbols-outlined text-2xl text-primary">data_usage</span>
                        <p class="text-2xl font-black text-white mt-2"><?php echo $stats['running'] ?? 0; ?></p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Active Attacks</p>
                    </div>
                    <div class="glass rounded-2xl p-5 bg-gradient-to-br from-blue-500/20 to-blue-900/10">
                        <span class="material-symbols-outlined text-2xl text-blue-400">check_circle</span>
                        <p class="text-2xl font-black text-white mt-2"><?php echo $stats['completed'] ?? 0; ?></p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Successful Simulations</p>
                    </div>
                    <div class="glass rounded-2xl p-5 bg-gradient-to-br from-orange-500/20 to-orange-900/10">
                        <span class="material-symbols-outlined text-2xl text-orange-400">speed</span>
                        <p class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['total_requests'] ?? 0); ?></p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Total Requests Sent</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-2 items-center">
                    <form method="GET" class="flex items-center gap-2">
                        <input name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search target or analyst…"
                            class="bg-surface border border-border-dim rounded-lg px-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-primary w-64" />
                        <button type="submit" class="px-4 py-2 bg-primary text-background-dark rounded-lg font-bold text-sm">Filter</button>
                    </form>
                    <div class="flex gap-2">
                        <?php foreach (['all' => 'All', 'running' => 'Running', 'completed' => 'Completed', 'aborted' => 'Aborted'] as $val => $lbl): ?>
                            <a href="?status=<?php echo $val; ?>&search=<?php echo urlencode($search); ?>"
                                class="px-4 py-2 rounded-lg text-xs font-bold uppercase transition-all
                          <?php echo $statusFilter === $val ? 'bg-primary text-background-dark' : 'bg-surface border border-border-dim text-slate-400 hover:text-white'; ?>">
                                <?php echo $lbl; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Simulation Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dim">
                        <h3 class="font-bold text-white">Active & Past Simulations</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="px-6 py-3">ID</th>
                                    <th class="px-6 py-3">Analyst</th>
                                    <th class="px-6 py-3">Target Server</th>
                                    <th class="px-6 py-3">Attack Type</th>
                                    <th class="px-6 py-3">Duration</th>
                                    <th class="px-6 py-3">Requests Sent</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php if ($simulations->num_rows > 0):
                                    while ($s = $simulations->fetch_assoc()):
                                        $statusClr = [
                                            'running'   => 'text-primary bg-primary/10 border-primary/20',
                                            'completed' => 'text-blue-400 bg-blue-500/10 border-blue-500/20',
                                            'failed'    => 'text-red-400 bg-red-500/10 border-red-500/20',
                                            'aborted'   => 'text-slate-400 bg-slate-500/10 border-slate-500/20'
                                        ][$s['status']] ?? 'text-slate-500';
                                ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500">#<?php echo $s['id']; ?></td>
                                            <td class="px-6 py-4 text-white font-medium"><?php echo htmlspecialchars($s['user_name']); ?></td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-300"><?php echo htmlspecialchars($s['target_server']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-neutral-dark border border-border-dim text-slate-400">
                                                    <?php echo htmlspecialchars($s['attack_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-xs"><?php echo $s['duration_sec']; ?>s</td>
                                            <td class="px-6 py-4 font-mono text-xs text-primary"><?php echo number_format($s['requests_sent']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border <?php echo $statusClr; ?>">
                                                    <?php echo $s['status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500"><?php echo date('Y-m-d H:i', strtotime($s['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">No DDoS simulations found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center px-6">
                <span class="text-[10px] font-mono text-primary italic">CyberShield Admin v1.0</span>
            </footer>
        </main>
    </div>
</body>

</html>