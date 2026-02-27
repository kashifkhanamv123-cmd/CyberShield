<?php
require_once __DIR__ . "/../../config/session.php";
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Live Stats
$campaigns_res = $conn->query("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = $user_id");
$campaigns_row = $campaigns_res->fetch_assoc();
$total_sent = $campaigns_row['total'];

$clicks_res = $conn->query("SELECT COUNT(*) as total FROM phishing_events pe JOIN phishing_campaigns pc ON pe.campaign_id = pc.id WHERE pc.user_id = $user_id AND pe.event_type = 'click'");
$clicks_row = $clicks_res->fetch_assoc();
$total_clicks = $clicks_row['total'];

$creds_res = $conn->query("SELECT COUNT(*) as total FROM phishing_events pe JOIN phishing_campaigns pc ON pe.campaign_id = pc.id WHERE pc.user_id = $user_id AND pe.event_type = 'credential'");
$creds_row = $creds_res->fetch_assoc();
$total_creds = $creds_row['total'];

$click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100) : 0;

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
                        "background-light": "#f7f8f5",
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
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #1c230f;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #343a27;
            border-radius: 10px;
        }

        .terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-white font-display overflow-x-hidden">
    <?php if ($show_success): ?>
        <div id="successModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background-dark/80 backdrop-blur-sm">
            <div class="glass-panel max-w-lg w-full rounded-2xl p-8 border-primary/30 shadow-2xl animate-in fade-in zoom-in duration-300 bg-surface-dark border border-white/10">
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
                <button onclick="document.getElementById('successModal').remove()" class="w-full mt-8 py-3 bg-primary text-background-dark font-black rounded-xl hover:scale-[1.02] transition-all uppercase tracking-widest">UNDERSTOOD</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="relative flex h-screen w-full flex-col overflow-hidden">
        <header class="flex items-center justify-between border-b border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md z-10">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3 text-primary">
                    <span class="material-symbols-outlined text-3xl">shield_person</span>
                    <h2 class="text-white text-xl font-bold tracking-tight uppercase">CyberShield <span class="text-primary/70 text-xs font-mono">v4.2.0</span></h2>
                </div>
                <div class="hidden lg:flex items-center gap-6">
                    <a class="text-primary text-sm font-semibold border-b-2 border-primary pb-1" href="#">Simulation</a>
                    <a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="#">Analytics</a>
                    <a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="#">Templates</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden sm:block">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#b0bc9a] text-sm">search</span>
                    <input class="bg-surface-dark border-border-muted rounded-lg pl-9 pr-4 py-1.5 text-sm w-64 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all" placeholder="Search logs..." type="text" />
                </div>
                <div class="flex items-center gap-3 bg-surface-dark px-3 py-1.5 rounded-full border border-border-muted">
                    <span class="text-xs font-bold tracking-wider">SEC_ADMIN</span>
                </div>
            </div>
        </header>

        <main class="flex-1 flex overflow-hidden terminal-grid">
            <aside class="w-64 border-r border-border-muted flex flex-col bg-background-dark/50 p-6 overflow-y-auto custom-scrollbar">
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Target Audience</h3>
                <div class="space-y-1 mb-8">
                    <button class="w-full flex items-center justify-between px-3 py-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                        <span class="text-sm font-medium">All Employees</span>
                        <span class="text-[10px] font-mono">1,240</span>
                    </button>
                    <button class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] hover:bg-surface-dark hover:text-white">
                        <span class="text-sm font-medium">Engineering</span>
                        <span class="text-[10px] font-mono">412</span>
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
                    <span class="text-[10px] font-bold uppercase text-red-500 block mb-1">System Alert</span>
                    <p class="text-[10px] text-red-200/70">Training mode active. Interaction logged.</p>
                </div>
            </aside>

            <div class="flex-1 flex flex-col overflow-hidden">
                <section class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 bg-background-dark/30 border-b border-border-muted">
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

                <div class="flex-1 flex overflow-hidden">
                    <form method="POST" action="process_campaign.php" class="w-1/2 p-6 overflow-y-auto custom-scrollbar border-r border-border-muted">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-bold flex items-center gap-2 tracking-tight">
                                <span class="material-symbols-outlined text-primary">edit_note</span>
                                Email Creator
                            </h2>
                            <div class="flex gap-2">
                                <select id="templateSelector" class="bg-surface-dark border-border-muted rounded-lg text-xs font-bold text-[#b0bc9a] px-2 outline-none focus:border-primary">
                                    <option value="default">Custom Template...</option>
                                    <option value="microsoft">Microsoft Account Alert</option>
                                    <option value="google">Google Security Warning</option>
                                    <option value="invoice">Unpaid Invoice #842</option>
                                    <option value="hr">New HR Policy (Q1 2024)</option>
                                </select>
                                <button type="button" onclick="loadTemplate()" class="px-3 py-1 text-xs font-bold bg-primary text-background-dark rounded-lg hover:brightness-110">Load</button>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <span class="text-xs font-bold uppercase text-[#b0bc9a]">Sender Name</span>
                                    <input id="sender_name" name="sender_name" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm focus:border-primary outline-none" type="text" value="IT Security Operations" />
                                </div>
                                <div class="space-y-2">
                                    <span class="text-xs font-bold uppercase text-[#b0bc9a]">Spoof Email</span>
                                    <input id="spoof_email" name="spoof_email" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm font-mono focus:border-primary outline-none" type="text" value="admin-no-reply@cybershield-auth.com" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <span class="text-xs font-bold uppercase text-[#b0bc9a]">Email Subject</span>
                                <input id="subject" name="subject" class="w-full bg-surface-dark border-border-muted rounded-lg p-3 text-sm focus:border-primary outline-none" type="text" value="URGENT: Your account requires verification" />
                            </div>
                            <div class="space-y-2 flex flex-col flex-1">
                                <span class="text-xs font-bold uppercase text-[#b0bc9a]">Body (HTML OK)</span>
                                <textarea id="email_body" name="body" class="flex-1 min-h-[300px] w-full bg-surface-dark border-border-muted rounded-lg p-4 text-sm font-mono focus:border-primary outline-none custom-scrollbar" rows="10">&lt;p&gt;Dear Employee,&lt;/p&gt;
&lt;p&gt;We have detected unusual activity. Please click below to verify:&lt;/p&gt;
&lt;a href="{{TRACKING_LINK}}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;"&gt;Verify Now&lt;/a&gt;</textarea>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <button type="submit" name="launch" class="px-8 py-3 bg-primary text-background-dark rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all">Launch Attack</button>
                        </div>
                    </form>

                    <section class="w-1/2 p-6 bg-surface-dark/30 overflow-y-auto custom-scrollbar">
                        <h2 class="text-lg font-bold mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">visibility</span>
                            Target Preview
                        </h2>
                        <div class="bg-white rounded-xl overflow-hidden shadow-2xl flex flex-col text-gray-900 border border-white/10">
                            <div class="bg-gray-100 px-4 py-2 border-b border-gray-200 text-[10px] text-gray-500 flex justify-between">
                                <span>Preview: Outlook Mobile / Desktop</span>
                                <span class="flex gap-1.5">
                                    <div class="size-2 rounded-full bg-red-400"></div>
                                    <div class="size-2 rounded-full bg-green-400"></div>
                                </span>
                            </div>
                            <div class="p-8 space-y-4">
                                <div class="border-b pb-4">
                                    <p class="text-xs text-gray-500 mb-1">From: <span class="font-bold text-gray-900">IT Security Operations</span></p>
                                    <p class="text-sm font-bold">URGENT: Your account requires verification</p>
                                </div>
                                <div class="text-sm prose prose-sm max-w-none">
                                    <p>Dear Employee,</p>
                                    <p>We detected unusual activity. Please verify your identity.</p>
                                    <p><a href="#" class="inline-block bg-[#007bff] text-white px-4 py-2 rounded no-underline">Verify Now</a></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 p-4 bg-primary/5 border border-primary/20 rounded-xl grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2 text-[10px] text-[#b0bc9a]">
                                <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                                Tracking Armed
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-yellow-500">
                                <span class="material-symbols-outlined text-sm">warning</span>
                                Urgency High
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <footer class="px-6 py-2 bg-background-dark border-t border-border-muted flex items-center justify-between text-[10px] text-[#b0bc9a] font-mono">
            <div class="flex gap-4"><span>SERVER: ONLINE</span><span>LATENCY: 12ms</span></div>
            <div class="uppercase tracking-tighter">CyberShield Training Platform © 2024</div>
        </footer>
    </div>

    <script>
        const templates = {
            microsoft: {
                name: "Microsoft Security",
                email: "security@microsoft-auth.com",
                subject: "Action Required: Unusual sign-in activity",
                body: `<p>We detected unusual activity on your Microsoft account.</p><p>Location: Sao Paulo, Brazil<br>IP: 191.242.14.92</p><p>Secure your account:</p><a href="{{TRACKING_LINK}}" style="background: #00a4ef; color: white; padding: 10px 20px; text-decoration: none; border-radius: 2px;">Review Activity</a>`
            },
            google: {
                name: "Google Admin",
                email: "noreply-admin@google-security.net",
                subject: "Critical Security Alert",
                body: `<p>A suspicious app was granted access.</p><p>Revoke access immediately to prevent loss.</p><a href="{{TRACKING_LINK}}" style="background: #4285f4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Check Activity</a>`
            },
            invoice: {
                name: "Accounts Payable",
                email: "billing@corporate-finance.com",
                subject: "Overdue Invoice: #INV-2024-842",
                body: `<p>Invoice #INV-2024-842 is 15 days past due.</p><p>Pay via secure portal:</p><a href="{{TRACKING_LINK}}" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Pay Now</a>`
            },
            hr: {
                name: "HR Communications",
                email: "hr@corporate-it.com",
                subject: "New Policy Update: Remote Work 2024",
                body: `<p>Review and sign the updated policy.</p><p>Delays may affect payroll.</p><a href="{{TRACKING_LINK}}" style="background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Sign Document</a>`
            }
        };

        function loadTemplate() {
            const val = document.getElementById('templateSelector').value;
            if (val === 'default') return;
            const t = templates[val];
            document.getElementById('sender_name').value = t.name;
            document.getElementById('spoof_email').value = t.email;
            document.getElementById('subject').value = t.subject;
            document.getElementById('email_body').value = t.body;
        }
    </script>
</body>

</html>