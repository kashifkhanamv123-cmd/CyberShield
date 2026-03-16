<?php
require_once __DIR__ . '/admin-auth.php';

// ── Pagination ──────────────────────────────────────────────────
$limit = 25;
$page  = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($page - 1) * $limit;

// ── Filtering ───────────────────────────────────────────────────
$eventType = $_GET['type'] ?? 'all';
$search    = trim($_GET['search'] ?? '');

$where = "1";
$params = [];
$types = "";

if ($eventType !== 'all') {
    $where .= " AND sl.event_type = ?";
    $params[] = $eventType;
    $types .= "s";
}

if ($search) {
    $like = "%$search%";
    $where .= " AND (sl.description LIKE ? OR u.name LIKE ? OR sl.ip_address LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

// ── Data Fetch ─────────────────────────────────────────────────
$sql = "SELECT sl.*, u.name as user_name
        FROM security_logs sl
        LEFT JOIN users u ON u.id = sl.user_id
        WHERE $where
        ORDER BY sl.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();

// ── Count for Pagination (Prepared Statement) ───────────────────
$countSql = "SELECT COUNT(*) FROM security_logs sl LEFT JOIN users u ON u.id = sl.user_id WHERE $where";
$countStmt = $conn->prepare($countSql);
if ($types !== "ii") {
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    if ($countTypes) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_row()[0];
$countStmt->close();
$totalPages = ceil($totalLogs / $limit);

// ── Event Type Options ─────────────────────────────────────────
$eventOptions = [
    'login_success'  => 'Login Success',
    'login_failed'   => 'Login Failed',
    'admin_action'   => 'Admin Actions',
    'phishing_lab'   => 'Phishing Lab',
    'bruteforce_lab' => 'Brute Force',
    'malware_lab'    => 'Malware Lab',
    'ddos_lab'       => 'DDoS Lab',
    'user_blocked'   => 'Blocks',
    'role_changed'   => 'Roles'
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Security Audit Logs</title>
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
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4">
                <p class="text-[10px] font-mono text-primary uppercase tracking-widest">System Architecture: secure_audit_infrastructure</p>
                <h2 class="text-xl font-black text-white italic uppercase">Security <span class="text-primary">Audit Logs</span></h2>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-6">

                <!-- Filters & Search -->
                <div class="flex flex-wrap gap-4 items-end bg-surface/50 p-6 rounded-2xl border border-border-dim">
                    <form method="GET" class="flex flex-wrap gap-4 items-end w-full">
                        <div class="flex-1 min-w-[300px]">
                            <label class="text-[10px] uppercase font-bold text-slate-500 mb-1.5 block">Search Records</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-sm">search</span>
                                <input name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search description, analyst, IP…"
                                    class="w-full bg-background-dark border border-border-dim rounded-lg pl-9 pr-4 py-2 text-sm text-white focus:border-primary outline-none transition-all" />
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-slate-500 mb-1.5 block">Event Class</label>
                            <select name="type" class="bg-background-dark border border-border-dim rounded-lg px-4 py-2 text-sm text-slate-300 focus:border-primary outline-none min-w-[180px]">
                                <option value="all">All Event Types</option>
                                <?php foreach ($eventOptions as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $eventType === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-6 py-2 bg-primary text-background-dark font-bold rounded-lg text-sm hover:brightness-110 transition-all">Submit Query</button>
                            <?php if ($search || $eventType !== 'all'): ?>
                                <a href="logs.php" class="px-4 py-2 bg-surface border border-border-dim text-slate-400 rounded-lg text-sm hover:text-white transition-all">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Logs Container -->
                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dim flex items-center justify-between">
                        <h3 class="font-bold text-white uppercase text-xs tracking-widest">Master Audit Trail</h3>
                        <span class="text-[10px] font-mono text-slate-500"><?php echo number_format($totalLogs); ?> ENTRIES FOUND</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-400 bg-neutral-dark/40">
                                    <th class="px-6 py-4">Event Type</th>
                                    <th class="px-6 py-4">Subject (Analyst)</th>
                                    <th class="px-6 py-4">Description</th>
                                    <th class="px-6 py-4">Network IP</th>
                                    <th class="px-6 py-4">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/30">
                                <?php if ($logs->num_rows > 0):
                                    while ($l = $logs->fetch_assoc()):
                                        $typeCls = [
                                            'login_success'  => 'text-primary bg-primary/5',
                                            'login_failed'   => 'text-red-400 bg-red-400/5',
                                            'admin_action'   => 'text-purple-400 bg-purple-400/5',
                                            'phishing_lab'   => 'text-yellow-400 bg-yellow-400/5',
                                            'bruteforce_lab' => 'text-orange-400 bg-orange-400/5',
                                            'malware_lab'    => 'text-red-500 bg-red-500/5',
                                            'ddos_lab'       => 'text-blue-400 bg-blue-400/5',
                                        ][$l['event_type']] ?? 'text-slate-400 bg-slate-400/5';
                                ?>
                                        <tr class="hover:bg-white/[0.015] transition-colors border-l-2 border-l-transparent hover:border-l-primary/50">
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter <?php echo $typeCls; ?>">
                                                    <?php echo str_replace('_', ' ', $l['event_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="size-6 rounded-full bg-surface border border-border-dim flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                        <?php echo strtoupper(substr($l['user_name'] ?? 'S', 0, 1)); ?>
                                                    </div>
                                                    <span class="text-white font-medium text-xs"><?php echo htmlspecialchars($l['user_name'] ?? 'SYSTEM_KERNEL'); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-xs text-slate-400 font-mono tracking-tight"><?php echo htmlspecialchars($l['description']); ?></td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500"><?php echo htmlspecialchars($l['ip_address'] ?? '127.0.0.1'); ?></td>
                                            <td class="px-6 py-4 font-mono text-[11px] text-slate-600"><?php echo $l['created_at']; ?></td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-600 italic">Static empty result set. No matching audit logs found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 border-t border-border-dim flex items-center justify-between bg-neutral-dark/20 text-xs">
                            <span class="text-slate-500">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?p=<?php echo $page - 1; ?>&type=<?php echo $eventType; ?>&search=<?php echo urlencode($search); ?>"
                                        class="px-3 py-1 bg-surface border border-border-dim rounded hover:text-white transition-all transition-all">&larr; PREVIOUS</a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?p=<?php echo $page + 1; ?>&type=<?php echo $eventType; ?>&search=<?php echo urlencode($search); ?>"
                                        class="px-3 py-1 bg-surface border border-border-dim rounded hover:text-white transition-all transition-all">NEXT &rarr;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </section>
            <footer class="shrink-0 h-8 bg-neutral-dark border-t border-border-dim flex items-center px-6">
                <span class="text-[10px] font-mono text-primary italic">CyberShield Admin v1.0</span>
            </footer>
        </main>
    </div>
</body>

</html>