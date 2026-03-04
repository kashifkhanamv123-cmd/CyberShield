<?php
require_once __DIR__ . '/admin-auth.php';

$message = '';
$msgType = '';

// ── Handle POST actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid    = intval($_POST['uid'] ?? 0);

    if ($uid > 0 && $uid !== $adminId) {  // Prevent self-modification

        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
            logAdminAction($conn, $adminId, 'user_deleted', "Deleted user ID: $uid");
            $message = "User deleted successfully.";
            $msgType = 'success';
        } elseif ($action === 'block') {
            $newStatus = $_POST['status'] === 'active' ? 'blocked' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $uid);
            $stmt->execute();
            $stmt->close();
            $action_name = $newStatus === 'blocked' ? 'user_blocked' : 'admin_action';
            logAdminAction($conn, $adminId, $action_name, "Changed status of user ID $uid to $newStatus");
            $message = "User status changed to $newStatus.";
            $msgType = 'success';
        } elseif ($action === 'role') {
            $newRole = $_POST['role'] === 'admin' ? 'user' : 'admin';

            if ($newRole === 'admin') {
                // Check current admin count
                $adminCountRes = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $adminCount = $adminCountRes->fetch_row()[0];

                if ($adminCount >= 3) {
                    $message = "Maximum administrator limit reached (Max: 3).";
                    $msgType = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $newRole, $uid);
                    $stmt->execute();
                    $stmt->close();
                    logAdminAction($conn, $adminId, 'role_changed', "Changed role of user ID $uid to $newRole");
                    $message = "User role changed to $newRole.";
                    $msgType = 'success';
                }
            } else {
                // Demote to user
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $newRole, $uid);
                $stmt->execute();
                $stmt->close();
                logAdminAction($conn, $adminId, 'role_changed', "Changed role of user ID $uid to $newRole");
                $message = "User role changed to $newRole.";
                $msgType = 'success';
            }
        }
    } else {
        $message = "Action not allowed on your own account.";
        $msgType = 'error';
    }
}

// ── Fetch all users ────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
if ($search) {
    $s = "%$search%";
    $stmt = $conn->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $s, $s);
    $stmt->execute();
    $users_res = $stmt->get_result();
} else {
    $users_res = $conn->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | User Management</title>
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
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Module: user_management</p>
                    <h2 class="text-xl font-black text-white italic uppercase">User <span class="text-primary glow">Management</span></h2>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <input name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users…"
                        class="bg-surface border border-border-dim rounded-lg px-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-primary w-64" />
                    <button type="submit" class="px-4 py-2 bg-primary text-background-dark rounded-lg font-bold text-sm">Search</button>
                    <?php if ($search): ?><a href="users.php" class="px-4 py-2 bg-surface border border-border-dim text-slate-400 rounded-lg text-sm">Clear</a><?php endif; ?>
                </form>
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
                        <h3 class="font-bold text-white">Registered Users</h3>
                        <span class="text-xs text-slate-500 font-mono"><?php echo $users_res->num_rows ?? 0; ?> records</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="text-left px-6 py-3">#ID</th>
                                    <th class="text-left px-6 py-3">Name</th>
                                    <th class="text-left px-6 py-3">Email</th>
                                    <th class="text-left px-6 py-3">Role</th>
                                    <th class="text-left px-6 py-3">Status</th>
                                    <th class="text-left px-6 py-3">Registered</th>
                                    <th class="text-right px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php if ($users_res && $users_res->num_rows > 0):
                                    while ($u = $users_res->fetch_assoc()):
                                        $isSelf = ($u['id'] == $adminId);
                                ?>
                                        <tr>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500">#<?php echo $u['id']; ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="size-8 rounded-full bg-gradient-to-tr from-primary/30 to-lime-600/30 flex items-center justify-center text-primary font-bold text-xs">
                                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                                    </div>
                                                    <span class="font-medium text-white"><?php echo htmlspecialchars($u['name']); ?></span>
                                                    <?php if ($isSelf): ?><span class="text-[10px] text-primary font-mono">(you)</span><?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-400"><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                <?php echo $u['role'] === 'admin' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?>">
                                                    <?php echo $u['role']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase
                                <?php echo ($u['status'] ?? 'active') === 'active' ? 'text-primary' : 'text-red-400'; ?>">
                                                    <span class="size-1.5 rounded-full inline-block <?php echo ($u['status'] ?? 'active') === 'active' ? 'bg-primary' : 'bg-red-400'; ?>"></span>
                                                    <?php echo ucfirst($u['status'] ?? 'active'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs text-slate-500"><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <?php if (!$isSelf): ?>
                                                    <div class="flex items-center justify-end gap-2">
                                                        <!-- Toggle Role -->
                                                        <form method="POST">
                                                            <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="role" value="<?php echo $u['role']; ?>">
                                                            <input type="hidden" name="action" value="role">
                                                            <button type="submit" title="Toggle role"
                                                                class="p-1.5 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 transition-colors">
                                                                <span class="material-symbols-outlined text-base">manage_accounts</span>
                                                            </button>
                                                        </form>
                                                        <!-- Toggle Block -->
                                                        <form method="POST">
                                                            <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $u['status'] ?? 'active'; ?>">
                                                            <input type="hidden" name="action" value="block">
                                                            <button type="submit" title="Toggle block"
                                                                class="p-1.5 rounded-lg <?php echo ($u['status'] ?? 'active') === 'blocked' ? 'bg-primary/10 text-primary' : 'bg-orange-500/10 text-orange-400'; ?> hover:opacity-80 transition-colors">
                                                                <span class="material-symbols-outlined text-base"><?php echo ($u['status'] ?? 'active') === 'blocked' ? 'lock_open' : 'block'; ?></span>
                                                            </button>
                                                        </form>
                                                        <!-- Delete -->
                                                        <form method="POST" onsubmit="return confirm('Permanently delete this user?')">
                                                            <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" title="Delete user"
                                                                class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-colors">
                                                                <span class="material-symbols-outlined text-base">delete</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-600 font-mono italic">current session</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 text-sm">No users found.</td>
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