<?php
require_once __DIR__ . '/admin-auth.php';

$message = '';
$msgType = '';

// ── Handle Create Campaign ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    $sender  = trim($_POST['sender_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');
    $status  = in_array($_POST['status'], ['draft', 'active', 'completed']) ? $_POST['status'] : 'draft';

    if ($sender && $subject && $body) {
        $stmt = $conn->prepare("INSERT INTO phishing_campaigns (user_id, sender_email, subject, body, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $adminId, $sender, $subject, $body, $status);
        $stmt->execute();
        $stmt->close();
        logAdminAction($conn, $adminId, 'phishing_lab', "Created phishing campaign: $subject");
        $message = "Campaign created successfully.";
        $msgType = 'success';
    } else {
        $message = "Please fill in all fields.";
        $msgType = 'error';
    }
}

// ── Handle Delete Campaign ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_campaign'])) {
    $cid = intval($_POST['campaign_id']);
    $stmt = $conn->prepare("DELETE FROM phishing_campaigns WHERE id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $stmt->close();
    logAdminAction($conn, $adminId, 'phishing_lab', "Deleted campaign ID: $cid");
    $message = "Campaign deleted.";
    $msgType = 'success';
}

// ── Fetch campaigns ────────────────────────────────────────────
$campaigns = $conn->query("
    SELECT pc.*, u.name as creator
    FROM phishing_campaigns pc
    JOIN users u ON u.id = pc.user_id
    ORDER BY pc.created_at DESC
");
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Phishing Lab Management</title>
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

        input,
        textarea,
        select {
            transition: border-color .2s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #a0f000 !important;
        }
    </style>
</head>

<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">

        <?php include '_sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="shrink-0 sticky top-0 z-10 bg-background-dark/80 backdrop-blur-md border-b border-border-dim px-8 py-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-mono text-primary uppercase tracking-widest">Module: phishing_management</p>
                    <h2 class="text-xl font-black text-white italic uppercase">Phishing <span class="text-primary glow">Lab Control</span></h2>
                </div>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="flex items-center gap-2 px-4 py-2 bg-primary text-background-dark rounded-lg font-bold text-sm hover:brightness-110 transition-all">
                    <span class="material-symbols-outlined text-base">add</span>
                    New Campaign
                </button>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-6">

                <?php if ($message): ?>
                    <div class="px-4 py-3 rounded-lg border text-sm font-medium
                <?php echo $msgType === 'success' ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-red-500/10 border-red-500/30 text-red-400'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Campaigns Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-border-dim flex items-center justify-between">
                        <h3 class="font-bold text-white">Phishing Campaigns</h3>
                        <span class="text-xs text-slate-500 font-mono"><?php echo $campaigns->num_rows ?? 0; ?> campaigns</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border-dim text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <th class="text-left px-6 py-3">#</th>
                                    <th class="text-left px-6 py-3">Subject</th>
                                    <th class="text-left px-6 py-3">Sender</th>
                                    <th class="text-left px-6 py-3">Creator</th>
                                    <th class="text-left px-6 py-3">Status</th>
                                    <th class="text-left px-6 py-3">Sent</th>
                                    <th class="text-left px-6 py-3">Opened</th>
                                    <th class="text-left px-6 py-3">Clicked</th>
                                    <th class="text-left px-6 py-3">Date</th>
                                    <th class="text-right px-6 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-dim/50">
                                <?php if ($campaigns && $campaigns->num_rows > 0):
                                    $statusColors = [
                                        'draft'     => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                        'active'    => 'bg-primary/10 text-primary border-primary/20',
                                        'completed' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    ];
                                    while ($c = $campaigns->fetch_assoc()):
                                        $sc = $statusColors[$c['status']] ?? '';
                                        $openRate = $c['emails_sent'] > 0 ? round(($c['emails_opened'] / $c['emails_sent']) * 100) : 0;
                                ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-3 font-mono text-xs text-slate-500">#<?php echo $c['id']; ?></td>
                                            <td class="px-6 py-3 font-medium text-white max-w-xs truncate" title="<?php echo htmlspecialchars($c['subject']); ?>"><?php echo htmlspecialchars($c['subject']); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs text-slate-400"><?php echo htmlspecialchars($c['sender_email']); ?></td>
                                            <td class="px-6 py-3 text-slate-300"><?php echo htmlspecialchars($c['creator']); ?></td>
                                            <td class="px-6 py-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border <?php echo $sc; ?>"><?php echo $c['status']; ?></span>
                                            </td>
                                            <td class="px-6 py-3 font-mono text-xs"><?php echo number_format($c['emails_sent']); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs">
                                                <?php echo number_format($c['emails_opened']); ?>
                                                <?php if ($openRate > 0): ?><span class="text-primary text-[10px]"> (<?php echo $openRate; ?>%)</span><?php endif; ?>
                                            </td>
                                            <td class="px-6 py-3 font-mono text-xs"><?php echo number_format($c['links_clicked']); ?></td>
                                            <td class="px-6 py-3 font-mono text-xs text-slate-500"><?php echo date('Y-m-d', strtotime($c['created_at'])); ?></td>
                                            <td class="px-6 py-3 text-right">
                                                <form method="POST" onsubmit="return confirm('Delete this campaign?')">
                                                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                                                    <button name="delete_campaign" type="submit"
                                                        class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 transition-colors">
                                                        <span class="material-symbols-outlined text-base">delete</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                else: ?>
                                    <tr>
                                        <td colspan="10" class="px-6 py-12 text-center text-sm text-slate-500">No campaigns yet. Create one above.</td>
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

    <!-- Create Campaign Modal -->
    <div id="createModal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-neutral-dark border border-border-dim rounded-2xl w-full max-w-xl">
            <div class="px-6 py-4 border-b border-border-dim flex items-center justify-between">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">alternate_email</span>
                    Create Phishing Campaign
                </h3>
                <button onclick="document.getElementById('createModal').classList.add('hidden')"
                    class="text-slate-500 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <div>
                    <label class="text-xs text-primary uppercase font-bold block mb-1">Sender Email</label>
                    <input type="email" name="sender_email" required placeholder="security@company.com"
                        class="w-full bg-surface border border-border-dim rounded-lg px-4 py-2.5 text-white text-sm placeholder-slate-600 outline-none" />
                </div>
                <div>
                    <label class="text-xs text-primary uppercase font-bold block mb-1">Subject Line</label>
                    <input type="text" name="subject" required placeholder="Your account requires immediate verification"
                        class="w-full bg-surface border border-border-dim rounded-lg px-4 py-2.5 text-white text-sm placeholder-slate-600 outline-none" />
                </div>
                <div>
                    <label class="text-xs text-primary uppercase font-bold block mb-1">Email Body</label>
                    <textarea name="body" required rows="5" placeholder="Compose your phishing simulation email…"
                        class="w-full bg-surface border border-border-dim rounded-lg px-4 py-2.5 text-white text-sm placeholder-slate-600 outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="text-xs text-primary uppercase font-bold block mb-1">Initial Status</label>
                    <select name="status" class="w-full bg-surface border border-border-dim rounded-lg px-4 py-2.5 text-white text-sm outline-none">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button name="create_campaign" type="submit"
                        class="flex-1 bg-primary text-background-dark font-bold py-2.5 rounded-lg uppercase tracking-widest text-sm hover:brightness-110 transition-all">
                        Deploy Campaign
                    </button>
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                        class="px-6 bg-surface border border-border-dim text-slate-400 rounded-lg text-sm hover:text-white transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>