<?php
require_once __DIR__ . '/admin-auth.php';

// ── Fetch brute force logs ──────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where  = "1";
$params = [];
$types  = "";

if ($filter === 'success') {
    $where .= " AND bl.success = 1";
}
if ($filter === 'failed') {
    $where .= " AND bl.success = 0";
}
if ($search) {
    $like = "%$search%";
    $where .= " AND (bl.target_system LIKE ? OR bl.username_tried LIKE ? OR u.name LIKE ?)";
    $params = [$like, $like, $like];
    $types  = "sss";
}

$sql = "SELECT bl.*, u.name as user_name
        FROM bruteforce_logs bl
        JOIN users u ON u.id = bl.user_id
        WHERE $where
        ORDER BY bl.created_at DESC
        LIMIT 200";

if ($search) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $logs = $stmt->get_result();
} else {
    $logs = $conn->query($sql);
}

// Aggregate stats
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(success) as successes,
        SUM(attempts) as total_attempts,
        MAX(attempts) as max_attempts
    FROM bruteforce_logs
")->fetch_assoc();
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Brute Force Lab Monitor</title>
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

        .attempt-bar {
            height: 4px;
            background: rgba(160, 240, 0, 0.15);
            border-radius: 2px;
        }

        .attempt-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width .5s;
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">

        <?php include '_sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Module: bruteforce_monitor</p>
                    <h2 class="text-xl font-black text-white italic uppercase">Brute Force <span class="text-primary glow">Lab Monitor</span></h2>
                </div>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-6">

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php
                    $sc = [
                        ['Total Sessions',   $stats['total'] ?? 0,          'lock_open',   'from-orange-500/20 to-orange-900/10', 'text-orange-400'],
                        ['Successful Cracks', $stats['successes'] ?? 0,       'key',         'from-red-500/20 to-red-900/10',       'text-red-400'],
                        ['Total Attempts',   number_format($stats['total_attempts'] ?? 0), 'repeat', 'from-yellow-500/20 to-yellow-900/10', 'text-yellow-400'],
                        ['Peak Attempts',    number_format($stats['max_attempts'] ?? 0),    'trending_up', 'from-primary/20 to-primary/5', 'text-primary'],
                    ];
                    foreach ($sc as [$label, $val, $icon, $grad, $color]):
                    ?>
                        <div class="glass rounded-2xl p-5 bg-gradient-to-br <?php echo $grad; ?>">
                            <span class="material-symbols-outlined text-2xl <?php echo $color; ?>"><?php echo $icon; ?></span>
                            <p class="text-2xl font-black text-white mt-2"><?php echo $val; ?></p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-0.5"><?php echo $label; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filter Bar -->
                <div class="flex flex-wrap gap-3 items-center">
                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search target, username, user…"
                            class="bg-surface border border-border-dim rounded-lg px-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-primary w-64" />
                        <button type="submit" class="px-4 py-2 bg-primary text-background-dark rounded-lg font-bold text-sm">Search</button>
                    </form>
                    <div class="flex gap-2">
                        <?php foreach (['all' => 'All', 'success' => 'Successful', 'failed' => 'Failed'] as $val => $label): ?>
                            <a href="?filter=<?php echo $val; ?>&search=<?php echo urlencode($search); ?>"
                                class="px-3 py-2 rounded-lg text-xs font-bold uppercase transition-all
                          <?php echo $filter === $val ? 'bg-primary text-background-dark' : 'bg-surface border border-border-dim text-slate-400 hover:text-white'; ?>">
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dim flex items-center justify-between">
                        <h3 class="font-bold text-white">Attack Simulation Logs</h3>
                        <span class="text-xs text-slate-500 font-mono"><?php echo $logs->num_rows ?? 0; ?> records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="text-left px-6 py-3">#</th>
                                    <th class="text-left px-6 py-3">Analyst</th>
                                    <th class="text-left px-6 py-3">Target System</th>
                                    <th class="text-left px-6 py-3">Username Tried</th>
                                    <th class="text-left px-6 py-3">Attempts</th>
                                    <th class="text-left px-6 py-3">Result</th>
                                    <th class="text-left px-6 py-3">Source IP</th>
                                    <th class="text-left px-6 py-3">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php
                                $maxAttempts = max(1, (int)($stats['max_attempts'] ?? 1));
                                if ($logs && $logs->num_rows > 0):
                                    while ($row = $logs->fetch_assoc()):
                                        $barWidth = min(100, round(($row['attempts'] / $maxAttempts) * 100));
                                ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-3 font-mono text-xs text-slate-500">#<?php echo $row['id']; ?></td>
                                            <td class="px-6 py-3 text-white font-medium"><?php echo htmlspecialchars($row['user_name']); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs text-slate-300"><?php echo htmlspecialchars($row['target_system']); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs text-yellow-400"><?php echo htmlspecialchars($row['username_tried']); ?></td>
                                            <td class="px-6 py-3 min-w-[120px]">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs text-white w-10 shrink-0"><?php echo number_format($row['attempts']); ?></span>
                                                    <div class="attempt-bar flex-1">
                                                        <div class="attempt-bar-fill <?php echo $row['success'] ? 'bg-red-500' : 'bg-orange-500'; ?>"
                                                            style="width: <?php echo $barWidth; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3">
                                                <?php if ($row['success']): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-500/10 text-red-400 border border-red-500/20">
                                                        ⚠ Success
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                                        ✗ Failed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 font-mono text-xs text-slate-500"><?php echo htmlspecialchars($row['ip_address'] ?? '-'); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs text-slate-500"><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">No brute force logs found.</td>
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