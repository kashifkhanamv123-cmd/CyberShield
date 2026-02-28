<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Live Stats
// Safe stats queries using prepared statements

$total_sent = 0;
$total_clicks = 0;
$total_creds = 0;

// Campaign count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_sent = (int)$row['total'];
}
$stmt->close();

// Click count
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM phishing_events pe
    JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
    WHERE pc.user_id = ? AND pe.event_type = 'click'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_clicks = (int)$row['total'];
}
$stmt->close();

// Credential count
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM phishing_events pe
    JOIN phishing_campaigns pc ON pe.campaign_id = pc.id
    WHERE pc.user_id = ? AND pe.event_type = 'credential'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_creds = (int)$row['total'];
}
$stmt->close();

$click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100) : 0;

// Dynamic Audience Counts (Scaling based on user activity)
$all_emp_count = $total_sent * 1;
$eng_count = $total_sent > 1 ? floor($total_sent * 0.4) : 0;
$finance_count = $total_sent > 3 ? floor($total_sent * 0.2) : 0;
$campaign_completed = isset($_GET['completed']);
$show_success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Phishing Simulation Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-dark": "#1c230f",
                        "border-muted": "#343a27",
                        "surface-dark": "#23281b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background: linear-gradient(rgba(10, 10, 10, 0.95), rgba(10, 10, 10, 0.95)),
                url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #343a27;
            border-radius: 10px;
        }

        .terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .audience-btn.active {
            background: rgba(160, 240, 0, 0.1);
            color: #a0f000;
            border-color: rgba(160, 240, 0, 0.2);
        }

        #emailPreviewContainer {
            flex: 1;
            overflow-y: auto;
            height: 100%;
            border-radius: 12px;
            background: white;
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
    </style>
</head>

<body class="text-white font-display terminal-grid min-h-screen flex flex-col overflow-x-hidden custom-scrollbar">
    <?php if ($campaign_completed): ?>
        <div id="completionModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-surface-dark border border-primary/30 rounded-2xl w-full max-w-xl p-8 shadow-2xl">
                <div class="flex items-center gap-4 mb-6">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">verified</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black uppercase">
                            Simulation <span class="text-primary">Completed</span>
                        </h2>
                        <p class="text-xs text-[#b0bc9a]">Campaign lifecycle executed successfully.</p>
                    </div>
                </div>

                <div class="space-y-6 text-sm text-slate-300 leading-relaxed overflow-y-auto max-h-[60vh] custom-scrollbar pr-2">
                    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl">
                        <h4 class="text-xs font-bold text-primary uppercase mb-2">Simulation Summary</h4>
                        <div class="grid grid-cols-2 gap-4 text-[11px] font-mono">
                            <div class="flex justify-between border-b border-white/5 pb-1">
                                <span class="text-[#b0bc9a]">Total Clicks:</span>
                                <span class="text-primary"><?php echo number_format($total_clicks); ?></span>
                            </div>
                            <div class="flex justify-between border-b border-white/5 pb-1">
                                <span class="text-[#b0bc9a]">Credentials Captured:</span>
                                <span class="text-red-500"><?php echo number_format($total_creds); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-white/5 border border-white/10 rounded-xl">
                        <h4 class="text-xs font-bold text-white uppercase mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">school</span>
                            Educational Debrief
                        </h4>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-primary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                    What just happened?
                                </p>
                                <p class="text-[11px] text-[#b0bc9a]">
                                    A realistic phishing email was deployed to your organization. This simulation tracks the "human firewall" strength by monitoring interaction telemetry in real-time.
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-primary flex items-center gap-2">
                                    <span class="material-symbols-outlined text-xs">warning</span>
                                    Risk Awareness
                                </p>
                                <p class="text-[11px] text-[#b0bc9a]">
                                    Attackers use urgency and spoofed identities to bypass technical controls. In this simulation, <span class="text-white font-bold"><?php echo number_format($total_creds); ?></span> sets of credentials were compromised.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <h4 class="text-xs font-bold text-red-500 uppercase mb-2">Prevention Tips</h4>
                        <ul class="space-y-2 list-disc list-inside text-[11px] text-red-200/70">
                            <li>Verify the <span class="text-white font-medium">sender's original email domain</span> (e.g., @microsoft.com vs @micr0soft.com).</li>
                            <li>Always <span class="text-white font-medium">hover over links</span> to inspect the true URL destination.</li>
                            <li>Avoid acting on emails that demand <span class="text-white font-medium">immediate action</span> or convey extreme urgency.</li>
                            <li>Report suspicious activity via the official <span class="text-white font-medium">IT Security Channel</span>.</li>
                        </ul>
                    </div>
                </div>

                <button onclick="closeCompletionModal()"
                    class="w-full mt-8 py-3 bg-primary text-background-dark font-black rounded-xl hover:scale-[1.02] transition-all uppercase tracking-widest shadow-lg shadow-primary/20">
                    Acknowledge & Continue
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($show_success): ?>
        <div id="successModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background-dark/80 backdrop-blur-sm">
            <div class="glass-panel max-w-lg w-full rounded-2xl p-8 border-primary/30 shadow-2xl bg-surface-dark border border-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <div class="size-14 rounded-xl bg-primary/20 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-3xl">verified</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black italic uppercase italic">Campaign <span class="text-primary">Successful!</span></h3>
                        <p class="text-slate-400 text-xs">Phishing simulation execution completed.</p>
                    </div>
                </div>
                <div class="space-y-6 text-sm text-slate-300 leading-relaxed">
                    <p>You have successfully simulated a real-world phishing attack. Lifecycle:</p>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="size-8 rounded-full bg-background-dark border border-border-muted flex items-center justify-center text-xs font-bold text-primary flex-shrink-0">1</div>
                            <p class="text-xs text-slate-400"><span class="text-white font-bold">Attacker sends mail:</span> Craft spoofed email with psychological triggers.</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="size-8 rounded-full bg-background-dark border border-border-muted flex items-center justify-center text-xs font-bold text-primary flex-shrink-0">2</div>
                            <p class="text-xs text-slate-400"><span class="text-white font-bold">User clicks link:</span> Victim interacts with tracking payload.</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="size-8 rounded-full bg-background-dark border border-border-muted flex items-center justify-center text-xs font-bold text-primary flex-shrink-0">3</div>
                            <p class="text-xs text-slate-400"><span class="text-white font-bold">Data Harvested:</span> Attacker receives IP, Login, and Password.</p>
                        </div>
                    </div>
                </div>
                <button onclick="window.location.href='analytics.php?id=<?php echo isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : ''); ?>'" class="w-full mt-8 py-3 bg-primary text-background-dark font-black rounded-xl hover:scale-[1.02] transition-all uppercase tracking-widest">UNDERSTOOD</button>
            </div>
        </div>
    <?php endif; ?>

    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-3 text-primary cursor-pointer transition-transform hover:scale-105" onclick="location.reload()">
                <span class="material-symbols-outlined text-3xl">shield_person</span>
                <h2 class="text-white text-xl font-bold tracking-tight uppercase">CyberShield <span class="text-primary/70 text-xs font-mono">v4.2.0</span></h2>
            </div>
            <div class="hidden lg:flex items-center gap-6">
                <a class="text-primary text-sm font-semibold border-b-2 border-primary pb-1" href="index.php">Simulation</a>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <a href="../../dashboard/dashboard.php" class="px-4 py-1.5 rounded-lg border border-border-muted text-[#b0bc9a] hover:text-white hover:bg-white/5 text-xs font-bold transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">dashboard</span>
                BACK TO DASHBOARD
            </a>
            <div class="flex items-center gap-3 bg-surface-dark px-3 py-1.5 rounded-full border border-border-muted">
                <span class="text-xs font-bold tracking-wider"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'SEC_ADMIN'); ?></span>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden lg:h-[calc(100vh-60px)]">
        <aside class="hidden md:flex w-64 border-r border-border-muted flex-col bg-background-dark/50 p-6 overflow-y-auto custom-scrollbar shrink-0">
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Target Audience</h3>
            <div class="space-y-1 mb-8" id="audienceList">
                <button onclick="switchAudience('all')" class="audience-btn active w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all text-left">
                    <span class="text-sm font-medium">All Employees</span>
                    <span class="text-[10px] font-mono"><?php echo number_format($all_emp_count); ?></span>
                </button>
                <button onclick="switchAudience('engineering')" class="audience-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all text-left">
                    <span class="text-sm font-medium">Engineering</span>
                    <span class="text-[10px] font-mono"><?php echo number_format($eng_count); ?></span>
                </button>
                <button onclick="switchAudience('finance')" class="audience-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all text-left">
                    <span class="text-sm font-medium">Finance</span>
                    <span class="text-[10px] font-mono"><?php echo number_format($finance_count); ?></span>
                </button>
            </div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Threat Vectors</h3>
            <div class="space-y-3 mb-8">
                <div class="flex items-center gap-3">
                    <input checked class="rounded border-border-muted bg-surface-dark text-primary" type="checkbox" />
                    <span class="text-sm text-[#b0bc9a]">Link Tracking</span>
                </div>
                <div class="flex items-center gap-3">
                    <input checked class="rounded border-border-muted bg-surface-dark text-primary" type="checkbox" />
                    <span class="text-sm text-[#b0bc9a]">Credential Capture</span>
                </div>
            </div>
            <div class="mt-auto p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                <span class="text-[10px] font-bold uppercase text-red-500 block mb-1 text-left">System Alert</span>
                <p class="text-[10px] text-red-200/70 text-left">Tracking <span id="activeAudienceLabel" class="font-bold text-white">All Employees</span></p>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <section class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 bg-background-dark/30 border-b border-border-muted shrink-0">
                <div class="p-4 rounded-xl border border-border-muted bg-surface-dark/50">
                    <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-1">Emails Sent</span>
                    <p class="text-2xl font-bold"><?php echo number_format($total_sent); ?></p>
                    <span class="text-[10px] text-primary">Live Data</span>
                </div>
                <div class="p-4 rounded-xl border border-border-muted bg-surface-dark/50">
                    <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-1">Links Clicked</span>
                    <p class="text-2xl font-bold"><?php echo number_format($total_clicks); ?></p>
                    <span class="text-[10px] text-primary"><?php echo $click_rate; ?>% Success</span>
                </div>
                <div class="p-4 rounded-xl border border-border-muted bg-surface-dark/50">
                    <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-1">Credentials Captured</span>
                    <p class="text-2xl font-bold"><?php echo number_format($total_creds); ?></p>
                    <span class="text-[10px] text-red-500">Compromised</span>
                </div>
                <div class="p-4 rounded-xl border border-border-muted bg-surface-dark/50">
                    <span class="text-[#b0bc9a] text-[10px] font-bold uppercase block mb-1">Risk Profile</span>
                    <p class="text-2xl font-bold"><?php echo $total_creds > 0 ? 'CRITICAL' : 'SECURE'; ?></p>
                    <span class="text-[10px] text-primary">Real-time Analysis</span>
                </div>
            </section>

            <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
                <form method="POST" action="process_campaign.php" enctype="multipart/form-data" class="w-full lg:w-1/2 p-6 overflow-y-auto custom-scrollbar border-b lg:border-b-0 lg:border-r border-border-muted h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold flex items-center gap-2 tracking-tight">
                            <span class="material-symbols-outlined text-primary">edit_note</span>
                            Email Creator
                        </h2>
                        <div class="flex gap-2">
                            <select id="templateSelector" onchange="loadTemplate()" class="bg-surface-dark border-border-muted rounded-lg text-xs font-bold text-[#b0bc9a] px-2 outline-none focus:border-primary max-w-[150px]">
                                <option value="default">Custom Template...</option>
                                <optgroup label="General" id="generalTemplates">
                                    <option value="microsoft">Microsoft Account Alert</option>
                                    <option value="google">Google Security Warning</option>
                                </optgroup>
                                <optgroup label="Engineering" id="engineeringTemplates">
                                    <option value="gitlab">GitLab SSH Key Alert</option>
                                    <option value="aws">AWS IAM Policy Update</option>
                                    <option value="jira">Jira Priority Ticket</option>
                                </optgroup>
                                <optgroup label="Finance" id="financeTemplates">
                                    <option value="payroll">Urgent: Payroll Discrepancy</option>
                                    <option value="tax">Tax Compliance Notice 2024</option>
                                    <option value="invoice_overdue">Overdue Invoice - Urgent Payment</option>
                                </optgroup>
                                <optgroup label="My Custom Templates" id="userTemplates">
                                    <!-- Dynamically populated -->
                                </optgroup>
                            </select>
                            <button type="button" onclick="clearForm()" class="px-3 py-1 text-xs font-bold bg-white/5 border border-white/10 text-white rounded-lg hover:bg-white/10" title="Clear Editor">New</button>
                            <button type="button" onclick="restoreTemplate()" id="restoreBtn" class="px-3 py-1 text-xs font-bold bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-lg hover:bg-amber-500/20 hidden" title="Revert Changes">Restore</button>
                            <button type="button" onclick="saveAsCustom()" class="px-3 py-1 text-xs font-bold bg-primary/20 border border-primary/40 text-primary rounded-lg hover:bg-primary/30" title="Save to Library">Save</button>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <span class="text-xs font-bold uppercase text-[#b0bc9a] block">Sender Name</span>
                                <input id="sender_name" name="sender_name" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm focus:border-primary outline-none" type="text" value="IT Security Operations" />
                            </div>
                            <div class="space-y-2">
                                <span class="text-xs font-bold uppercase text-[#b0bc9a] block">Spoof Email</span>
                                <input id="spoof_email" name="spoof_email" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm font-mono focus:border-primary outline-none" type="text" value="admin-no-reply@cybershield-auth.com" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <span class="text-xs font-bold uppercase text-[#b0bc9a] block">Email Subject</span>
                            <input id="subject" name="subject" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm focus:border-primary outline-none" type="text" value="URGENT: Your account requires verification" />
                        </div>

                        <div class="space-y-4 pt-4 border-t border-border-muted/30">
                            <span class="text-xs font-bold uppercase text-primary block tracking-widest">Target Landing Page Preview</span>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <?php
                                $landings = [
                                    ['name' => 'Microsoft 365', 'val' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&q=80&w=1200'],
                                    ['name' => 'Google Account', 'val' => 'https://images.unsplash.com/photo-1573867639040-6dd25fa5f597?auto=format&fit=crop&q=80&w=1200'],
                                    ['name' => 'Corporate HR', 'val' => 'https://images.unsplash.com/photo-1454165833767-027ffea9e78b?auto=format&fit=crop&q=80&w=1200']
                                ];
                                ?>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="landing_image" value="" class="hidden peer" checked>
                                    <div class="p-2 h-full rounded-xl border-2 border-border-muted bg-surface-dark/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all hover:border-primary/50 text-center flex flex-col items-center justify-center">
                                        <span class="material-symbols-outlined text-2xl mb-1 opacity-40">block</span>
                                        <p class="text-[10px] font-bold uppercase text-[#b0bc9a]">None / Custom</p>
                                    </div>
                                </label>
                                <?php foreach ($landings as $idx => $l): ?>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="landing_image" value="<?php echo $l['val']; ?>" class="hidden peer">
                                        <div class="p-2 rounded-xl border-2 border-border-muted bg-surface-dark/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all hover:border-primary/50 text-center">
                                            <div class="aspect-video bg-background-dark rounded-lg overflow-hidden mb-2">
                                                <img src="<?php echo $l['val']; ?>" class="w-full h-full object-cover">
                                            </div>
                                            <p class="text-[10px] font-bold uppercase text-[#b0bc9a] group-hover:text-white"><?php echo $l['name']; ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Custom Upload Option -->
                            <div class="mt-4 p-4 rounded-xl border border-dashed border-border-muted bg-background-dark/30 hover:border-primary/50 transition-all group">
                                <div class="flex items-center justify-between mb-3 text-[10px] font-bold uppercase tracking-widest text-[#b0bc9a]">
                                    <span class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-primary">upload_file</span>
                                        Or Upload Custom HD Image
                                    </span>
                                    <span class="text-white/20 italic">PNG / JPG (MAX 2MB)</span>
                                </div>
                                <div class="flex flex-col gap-4">
                                    <div class="flex items-center gap-4">
                                        <input type="file" id="custom_landing_input" name="custom_landing" accept="image/png, image/jpeg" class="flex-1 text-xs text-[#b0bc9a] file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-primary file:text-background-dark hover:file:bg-primary/80 transition-all cursor-pointer" />

                                        <div id="upload_controls" class="hidden flex items-center gap-2">
                                            <button type="button" id="btn_accept_upload" class="px-3 py-1.5 bg-primary text-background-dark text-[10px] font-black uppercase rounded-lg hover:scale-105 transition-all">OK</button>
                                            <button type="button" id="btn_delete_upload" class="p-1.5 bg-red-500/20 text-red-500 rounded-lg hover:bg-red-500/30 transition-all" title="Delete Image">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="upload_preview_container" class="hidden flex items-center gap-4 p-2 bg-background-dark/50 rounded-lg border border-border-muted">
                                        <div class="size-16 rounded overflow-hidden border border-white/10">
                                            <img id="upload_preview" class="w-full h-full object-cover">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p id="upload_filename" class="text-[10px] font-bold text-white truncate">Image Selected</p>
                                            <p id="upload_status" class="text-[9px] text-amber-500 uppercase font-black">Waiting for confirmation...</p>
                                        </div>
                                        <span id="upload_check" class="hidden material-symbols-outlined text-primary">check_circle</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2 flex flex-col">
                            <span class="text-xs font-bold uppercase text-[#b0bc9a] block">Body (HTML OK)</span>
                            <textarea id="email_body" name="body" class="w-full bg-surface-dark border-border-muted rounded-lg p-4 text-sm font-mono focus:border-primary outline-none custom-scrollbar" rows="8">&lt;p&gt;Dear Employee,&lt;/p&gt;
&lt;p&gt;We have detected unusual activity. Please click below to verify:&lt;/p&gt;
&lt;a href="{{TRACKING_LINK}}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;"&gt;Verify Now&lt;/a&gt;</textarea>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end">
                        <button type="submit" name="launch" class="px-8 py-3 bg-primary text-background-dark rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all uppercase italic">Launch Attack Instance</button>
                    </div>
                </form>

                <section class="w-full lg:w-1/2 p-6 bg-surface-dark/30 flex flex-col overflow-y-auto custom-scrollbar h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold flex items-center gap-2 tracking-tight">
                            <span class="material-symbols-outlined text-primary">visibility</span>
                            Target Preview
                        </h2>
                        <div class="flex gap-1.5">
                            <div class="size-2.5 rounded-full bg-red-500/20"></div>
                            <div class="size-2.5 rounded-full bg-amber-500/20"></div>
                            <div class="size-2.5 rounded-full bg-primary/20"></div>
                        </div>
                    </div>

                    <div id="emailPreviewContainer" class="custom-scrollbar shadow-2xl shrink-0">
                        <div class="flex flex-col text-gray-900 bg-white" id="emailPreviewFrame">
                            <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-[10px] text-gray-500 flex justify-between sticky top-0 z-10 w-full">
                                <span>Preview: Outlook Mobile / Desktop</span>
                                <span class="flex gap-1.5">
                                    <div class="size-2 rounded-full bg-red-400"></div>
                                    <div class="size-2 rounded-full bg-green-400"></div>
                                </span>
                            </div>
                            <div class="p-8 space-y-4">
                                <div class="border-b pb-4">
                                    <p class="text-xs text-gray-500 mb-1">From: <span class="font-bold text-gray-900" id="previewSender">IT Security Operations</span></p>
                                    <p class="text-sm font-bold" id="previewSubject">URGENT: Your account requires verification</p>
                                </div>
                                <div class="text-sm prose prose-sm max-w-none">
                                    <div class="text-[10px] text-gray-400 mb-4 font-mono uppercase tracking-widest">To: <span id="previewRecipient" class="font-bold text-gray-700">All Employees</span> &lt;targets@company-sec-training.com&gt;</div>
                                    <div id="previewBody" class="space-y-4 text-slate-800 mb-6">
                                        <p>Dear Employee,</p>
                                        <p>We detected unusual activity. Please verify your identity.</p>
                                        <p><a href="#" class="inline-block bg-[#007bff] text-white px-6 py-2.5 rounded font-bold no-underline">Verify Now</a></p>
                                    </div>
                                    <div id="landingPreviewContainer" class="hidden mt-4 pt-6 border-t border-gray-100">
                                        <p class="text-[10px] text-gray-400 uppercase font-black mb-2 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-xs">image</span>
                                            Configured Landing Preview
                                        </p>
                                        <img id="landingPreviewImg" class="w-full rounded-xl border border-gray-200 shadow-sm object-cover max-h-48" src="">
                                    </div>
                                </div>
                                <div class="pt-8 border-t border-gray-100 flex items-center gap-2 text-[10px] text-gray-400 italic">
                                    <span class="material-symbols-outlined text-xs">lock</span>
                                    This message was encrypted via CyberShield Secure Mail Gateway.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl grid grid-cols-2 gap-4 shrink-0">
                        <div class="flex items-center gap-2 text-[10px] text-[#b0bc9a]">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            Tracking Armed
                        </div>
                        <div class="flex items-center gap-2 text-[10px] text-yellow-500 text-right justify-end">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            Urgency High
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer class="sticky bottom-0 z-50 px-6 py-2 bg-background-dark border-t border-border-muted flex items-center justify-between text-[10px] text-[#b0bc9a] font-mono">
        <div class="flex gap-4"><span>SERVER: ONLINE</span><span>LATENCY: 12ms</span></div>
        <div class="uppercase tracking-tighter">CyberShield Training Platform © 2024</div>
    </footer>

    <script>
        const systemTemplates = {
            microsoft: {
                name: "Microsoft Security",
                email: "security@microsoft-auth.com",
                subject: "Action Required: Unusual sign-in activity",
                body: `<p>We detected unusual activity on your Microsoft account.</p><p>Location: Sao Paulo, Brazil<br>IP: 191.242.14.92</p><p>Secure your account:</p><p><a href="{{TRACKING_LINK}}" style="background: #00a4ef; color: white; padding: 10px 20px; text-decoration: none; border-radius: 2px;">Review Activity</a></p><p>If this was not you, please report this incident to IT immediately.</p>`
            },
            google: {
                name: "Google Admin",
                email: "noreply-admin@google-security.net",
                subject: "Critical Security Alert",
                body: `<p>A suspicious app was granted access.</p><p>Revoke access immediately to prevent loss.</p><p><a href="{{TRACKING_LINK}}" style="background: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Check Activity</a></p><p>Security Note: Google will never ask for your password via email.</p>`
            },
            gitlab: {
                name: "GitLab.com Support",
                email: "support@gitlab-security.io",
                subject: "[SECURITY] New SSH Key added to your account",
                body: `<p>A new SSH Key (RSA 4096) was added to your GitLab account from a new device.</p><p>If you did not perform this action, your source code access may be compromised.</p><p><a href="{{TRACKING_LINK}}" style="background: #fc6d26; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Revoke SSH Key Now</a></p><p>Device: MacBook Pro (Linux/x86_64)<br>Location: Moscow, RU</p>`
            },
            aws: {
                name: "AWS Billing Support",
                email: "noreply@aws-console-auth.com",
                subject: "URGENT: AWS IAM Policy Violation - Action Required",
                body: `<p>Our automated systems detected an 'AdministratorAccess' policy attached to an unauthorized IAM user in your root account.</p><p>Access must be reviewed immediately to avoid account suspension.</p><p><a href="{{TRACKING_LINK}}" style="background: #ff9900; color: black; padding: 10px 20px; text-decoration: none; border-radius: 2px; font-weight: bold;">Review IAM Policies</a></p><p>Cost Center: DEV-PROD-01<br>Region: us-east-1</p>`
            },
            jira: {
                name: "Jira Cloud",
                email: "jira-notifications@atlassian-corp.com",
                subject: "[JIRA] High Priority Security Vulnerability: CYBER-842 Assigned to You",
                body: `<p>A new High Priority security ticket has been assigned to you. The vulnerability requires immediate patching in production.</p><p>Log in to view the ticket details:</p><p><a href="{{TRACKING_LINK}}" style="background: #0052cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">View Ticket CYBER-842</a></p>`
            },
            payroll: {
                name: "Corporate Payroll Dept",
                email: "finance-noreply@corporate-it.com",
                subject: "URGENT: Payroll Disbursement Discrepancy Error #842",
                body: `<p>Dear Employee,</p><p>Our Q1 audit has flagged a discrepancy in your payroll bank details. Payment for the current cycle has been suspended until the records are verified.</p><p>Please review and confirm your bank details via our secure payroll portal:</p><p><a href="{{TRACKING_LINK}}" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Payroll Details</a></p><p>Note: Failure to verify by EOD will result in a 3-day payment delay.</p>`
            },
            tax: {
                name: "Tax Compliance",
                email: "compliance@finance-services.net",
                subject: "Action Required: Your 2024 Tax Forms are Ready",
                body: `<p>Your annual tax compliance forms (W-2/1099 equivalents) are now available for electronic signature.</p><p>Please sign and download your documents to avoid IRS late filing penalties:</p><p><a href="{{TRACKING_LINK}}" style="background: #004085; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Sign Tax Documents</a></p><p>Standard document ID: TAX-COMP-2024-XJF</p>`
            },
            invoice_overdue: {
                name: "Accounts Payable",
                email: "billing@vendor-system.com",
                subject: "FINAL NOTICE: Invoice #INV-2024-991 Overdue",
                body: `<p>Our records show that Invoice #INV-2024-991 ($14,500.00) is now 30 days past due. We will be initiating a credit hold on your account if payment is not received today.</p><p>Pay now via our secure payment gateway:</p><p><a href="{{TRACKING_LINK}}" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Submit Payment Now</a></p>`
            }
        };

        const audienceData = {
            all: {
                label: "All Employees",
                email: "targets@company-sec-training.com"
            },
            engineering: {
                label: "Engineering Team",
                email: "dev-team@company.io"
            },
            finance: {
                label: "Finance Department",
                email: "billing@company-finance.com"
            }
        };

        let currentAudience = 'all';
        let customTemplates = JSON.parse(localStorage.getItem('phishing_custom_templates') || '{}');

        function initTemplates() {
            const container = document.getElementById('userTemplates');
            container.innerHTML = '';

            Object.keys(customTemplates).forEach(key => {
                const opt = document.createElement('option');
                opt.value = 'custom_' + key;
                opt.innerText = customTemplates[key].subject.substring(0, 30) + (customTemplates[key].subject.length > 30 ? '...' : '');
                container.appendChild(opt);
            });
        }

        function switchAudience(key) {
            currentAudience = key;
            const data = audienceData[key];

            // Update UI
            document.querySelectorAll('.audience-btn').forEach(btn => btn.classList.remove('active'));
            const clickedBtn = Array.from(document.querySelectorAll('.audience-btn')).find(b => b.innerText.toLowerCase().includes(key));
            if (clickedBtn) clickedBtn.classList.add('active');

            document.getElementById('activeAudienceLabel').innerText = data.label;
            document.getElementById('previewRecipient').innerText = data.label;

            // Auto-load relevant template
            const templateSelect = document.getElementById('templateSelector');
            if (key === 'engineering') {
                templateSelect.value = 'gitlab';
            } else if (key === 'finance') {
                templateSelect.value = 'payroll';
            } else {
                templateSelect.value = 'microsoft';
            }
            loadTemplate();
        }

        function loadTemplate() {
            const val = document.getElementById('templateSelector').value;
            const restoreBtn = document.getElementById('restoreBtn');

            if (val === 'default') {
                clearForm();
                restoreBtn.classList.add('hidden');
                return;
            }

            let t;
            if (val.startsWith('custom_')) {
                const key = val.replace('custom_', '');
                t = customTemplates[key];
                restoreBtn.classList.add('hidden');
            } else {
                t = systemTemplates[val];
                restoreBtn.classList.remove('hidden');
            }

            if (t) {
                document.getElementById('sender_name').value = t.name;
                document.getElementById('spoof_email').value = t.email;
                document.getElementById('subject').value = t.subject;
                document.getElementById('email_body').value = t.body;
                updatePreview();
            }
        }

        function restoreTemplate() {
            const val = document.getElementById('templateSelector').value;
            if (val && systemTemplates[val]) {
                const t = systemTemplates[val];
                document.getElementById('sender_name').value = t.name;
                document.getElementById('spoof_email').value = t.email;
                document.getElementById('subject').value = t.subject;
                document.getElementById('email_body').value = t.body;
                updatePreview();
                alert("Original template content restored.");
            }
        }

        function clearForm() {
            document.getElementById('templateSelector').value = 'default';
            document.getElementById('sender_name').value = '';
            document.getElementById('spoof_email').value = '';
            document.getElementById('subject').value = '';
            document.getElementById('email_body').value = '';
            document.getElementById('restoreBtn').classList.add('hidden');

            // Reset Landing Selections
            document.querySelectorAll('input[name="landing_image"]').forEach(radio => radio.checked = radio.value === "");

            // Reset Upload
            deleteUploadedImage();

            updatePreview();
        }

        function saveAsCustom() {
            const name = document.getElementById('sender_name').value;
            const email = document.getElementById('spoof_email').value;
            const subject = document.getElementById('subject').value;
            const body = document.getElementById('email_body').value;

            if (!subject) {
                alert("Please provide at least a subject for the template.");
                return;
            }

            const id = Date.now().toString();
            customTemplates[id] = {
                name,
                email,
                subject,
                body
            };
            localStorage.setItem('phishing_custom_templates', JSON.stringify(customTemplates));

            initTemplates();
            document.getElementById('templateSelector').value = 'custom_' + id;
            document.getElementById('restoreBtn').classList.add('hidden');
            alert("Template saved successfully to your library!");
        }

        function updatePreview() {
            document.getElementById('previewSender').innerText = document.getElementById('sender_name').value || 'Sender Name';
            document.getElementById('previewSubject').innerText = document.getElementById('subject').value || 'Email Subject';
            let body = document.getElementById('email_body').value;
            if (!body) body = '<p class="text-slate-300 italic">Starting new custom pattern...</p>';
            document.getElementById('previewBody').innerHTML = body.replace(/{{TRACKING_LINK}}/g, '#');
        }

        ['sender_name', 'subject', 'email_body'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePreview);
        });

        function closeCompletionModal() {
            document.getElementById('completionModal').remove();
            // Clean up URL parameters
            const url = new URL(window.location);
            url.searchParams.delete('completed');
            window.history.replaceState({}, '', url);
        }

        // Custom Upload Preview & Confirmation logic
        const uploadInput = document.getElementById('custom_landing_input');
        const uploadControls = document.getElementById('upload_controls');
        const uploadPreviewContainer = document.getElementById('upload_preview_container');
        const uploadPreviewImg = document.getElementById('upload_preview');
        const uploadStatus = document.getElementById('upload_status');
        const uploadCheck = document.getElementById('upload_check');
        const uploadFilename = document.getElementById('upload_filename');
        const landingPreviewContainer = document.getElementById('landingPreviewContainer');
        const landingPreviewImg = document.getElementById('landingPreviewImg');

        uploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    uploadPreviewImg.src = event.target.result;
                    uploadFilename.textContent = file.name;
                    uploadPreviewContainer.classList.remove('hidden');
                    uploadControls.classList.remove('hidden');
                    uploadStatus.textContent = "WAITING FOR OK...";
                    uploadStatus.className = "text-[9px] text-amber-500 uppercase font-black";
                    uploadCheck.classList.add('hidden');

                    // Show in target preview immediately for verification
                    if (landingPreviewImg) landingPreviewImg.src = event.target.result;
                    if (landingPreviewContainer) landingPreviewContainer.classList.remove('hidden');

                    // Deselect presets
                    document.querySelector('input[name="landing_image"][value=""]').checked = true;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('btn_accept_upload').addEventListener('click', function() {
            uploadStatus.textContent = "Confirmed & Locked";
            uploadStatus.className = "text-[9px] text-primary uppercase font-black";
            uploadCheck.classList.remove('hidden');
            uploadControls.classList.add('hidden');

            // Highlight the preview to show it's confirmed
            if (landingPreviewContainer) {
                landingPreviewContainer.classList.add('border-primary/50');
                landingPreviewContainer.classList.remove('border-gray-100');
            }
        });

        document.getElementById('btn_delete_upload').addEventListener('click', deleteUploadedImage);

        function deleteUploadedImage() {
            if (uploadInput) uploadInput.value = "";
            if (uploadPreviewContainer) uploadPreviewContainer.classList.add('hidden');
            if (uploadControls) uploadControls.classList.add('hidden');
            if (uploadCheck) uploadCheck.classList.add('hidden');

            // Hide from target preview
            if (landingPreviewContainer) {
                landingPreviewContainer.classList.add('hidden');
                landingPreviewContainer.classList.remove('border-primary/50');
                landingPreviewContainer.classList.add('border-gray-100');
            }
            if (landingPreviewImg) landingPreviewImg.src = "";

            // Re-sync with preset if any
            updateLandingFromPreset();
        }

        function updateLandingFromPreset() {
            const selectedRadio = document.querySelector('input[name="landing_image"]:checked');
            if (selectedRadio && selectedRadio.value !== "" && (!uploadInput || !uploadInput.value)) {
                if (landingPreviewImg) landingPreviewImg.src = selectedRadio.value;
                if (landingPreviewContainer) landingPreviewContainer.classList.remove('hidden');
            } else if (!uploadInput || !uploadInput.value) {
                if (landingPreviewContainer) landingPreviewContainer.classList.add('hidden');
            }
        }

        // Add listeners to radio buttons to update preview
        document.querySelectorAll('input[name="landing_image"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value !== "") {
                    // If a preset is chosen, we should probably clear the custom upload to avoid confusion
                    if (uploadInput && uploadInput.value) {
                        if (confirm("Selecting a preset will clear your custom upload. Proceed?")) {
                            deleteUploadedImage();
                            updateLandingFromPreset();
                        } else {
                            // Re-select None/Custom
                            document.querySelector('input[name="landing_image"][value=""]').checked = true;
                        }
                    } else {
                        updateLandingFromPreset();
                    }
                } else {
                    updateLandingFromPreset();
                }
            });
        });

        initTemplates();
        updatePreview();
        updateLandingFromPreset();

        initTemplates();
        updatePreview();
    </script>
</body>

</html>