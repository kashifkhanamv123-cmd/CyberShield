<?php
require_once __DIR__ . '/admin-auth.php';

$message = '';
$msgType = '';

// ── Handle POST actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $action = $_POST['action'] ?? '';
    $rid    = intval($_POST['rid'] ?? 0);

    if ($rid > 0) {
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM system_reports WHERE id = ?");
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $stmt->close();
            logAdminAction($conn, $adminId, 'admin_action', "Deleted report ID: $rid");
            $message = "Report deleted successfully.";
            $msgType = 'success';
        } elseif ($action === 'status') {
            $newStatus = $_POST['status'] ?? 'pending';
            $stmt = $conn->prepare("UPDATE system_reports SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $rid);
            $stmt->execute();
            $stmt->close();
            logAdminAction($conn, $adminId, 'admin_action', "Changed status of report ID $rid to $newStatus");
            $message = "Report status updated to $newStatus.";
            $msgType = 'success';
        }
    }
}

// ── Fetch all reports ──────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
if ($filterStatus) {
    $stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM system_reports r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = ? ORDER BY r.created_at DESC");
    $stmt->bind_param("s", $filterStatus);
    $stmt->execute();
    $reports_res = $stmt->get_result();
} else {
    $reports_res = $conn->query("SELECT r.*, u.name as user_name FROM system_reports r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | System Reports</title>
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

        tr:hover td {
            background: rgba(160, 240, 0, 0.02);
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">

        <?php include '_sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Module: system_reports</p>
                    <h2 class="text-xl font-black text-white italic uppercase">System <span class="text-primary glow">Reports</span></h2>
                </div>
                <div class="flex items-center gap-4">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="status" class="bg-surface border border-border-dim rounded-lg px-4 py-2 text-sm text-white focus:outline-none focus:border-primary">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filterStatus === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-primary text-background-dark rounded-lg font-bold text-sm">Filter</button>
                    </form>
                </div>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8">

                <?php if ($message): ?>
                    <div class="mb-6 px-4 py-3 rounded-lg border text-sm font-medium
                <?php echo $msgType === 'success' ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dim flex items-center justify-between">
                        <h3 class="font-bold text-white">Issue Logs</h3>
                        <span class="text-xs text-slate-500 font-mono"><?php echo $reports_res->num_rows ?? 0; ?> records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="text-left px-6 py-3">#ID</th>
                                    <th class="text-left px-6 py-3">User</th>
                                    <th class="text-left px-6 py-3">Subject / Description</th>
                                    <th class="text-left px-6 py-3">Priority</th>
                                    <th class="text-left px-6 py-3">Status</th>
                                    <th class="text-left px-6 py-3">Created</th>
                                    <th class="text-right px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php if ($reports_res && $reports_res->num_rows > 0):
                                    while ($r = $reports_res->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500">#<?php echo $r['id']; ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-white font-medium"><?php echo htmlspecialchars($r['user_name'] ?? 'Guest'); ?></span>
                                                    <span class="text-[10px] text-slate-500 font-mono"><?php echo $r['ip_address']; ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 max-w-xs">
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-primary font-bold uppercase text-[11px]"><?php echo htmlspecialchars($r['subject']); ?></span>
                                                    <span class="text-slate-400 text-xs line-clamp-2"><?php echo htmlspecialchars($r['description']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                $pColor = 'bg-slate-500/10 text-slate-400';
                                                if ($r['priority'] === 'high') $pColor = 'bg-orange-500/10 text-orange-400';
                                                if ($r['priority'] === 'critical') $pColor = 'bg-red-500/10 text-red-400 border border-red-500/20';
                                                ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo $pColor; ?>">
                                                    <?php echo $r['priority']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase
                                                <?php
                                                if ($r['status'] === 'pending') echo 'text-orange-400';
                                                elseif ($r['status'] === 'in_progress') echo 'text-blue-400';
                                                elseif ($r['status'] === 'resolved') echo 'text-primary';
                                                else echo 'text-slate-500';
                                                ?>">
                                                    <span class="size-1.5 rounded-full inline-block 
                                                    <?php
                                                    if ($r['status'] === 'pending') echo 'bg-orange-400';
                                                    elseif ($r['status'] === 'in_progress') echo 'bg-blue-400';
                                                    elseif ($r['status'] === 'resolved') echo 'bg-primary';
                                                    else echo 'bg-slate-500';
                                                    ?>"></span>
                                                    <?php echo str_replace('_', ' ', $r['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500"><?php echo date('M d, H:i', strtotime($r['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <!-- Update Status -->
                                                    <form method="POST" class="flex items-center gap-1">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="rid" value="<?php echo $r['id']; ?>">
                                                        <input type="hidden" name="action" value="status">
                                                        <select name="status" onchange="this.form.submit()" class="bg-surface border border-border-dim rounded p-1 text-[10px] text-slate-400 focus:outline-none focus:border-primary">
                                                            <option value="pending" <?php echo $r['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="in_progress" <?php echo $r['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                            <option value="resolved" <?php echo $r['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                            <option value="closed" <?php echo $r['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                                        </select>
                                                    </form>
                                                    <!-- Delete -->
                                                    <form method="POST" onsubmit="return confirm('Permanently delete this report?')">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="rid" value="<?php echo $r['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" title="Delete report"
                                                            class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-colors">
                                                            <span class="material-symbols-outlined text-base">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 text-sm">No system reports found.</td>
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
