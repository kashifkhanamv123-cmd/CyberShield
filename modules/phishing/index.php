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

// Dynamic Audience Counts (Scaling based on user activity)
$all_emp_count = $total_sent * 1;
$eng_count = $total_sent > 1 ? floor($total_sent * 0.4) : 0;
$finance_count = $total_sent > 3 ? floor($total_sent * 0.2) : 0;

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
            max-height: calc(100vh - 280px);
            border-radius: 12px;
            background: white;
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
    </style>
</head>

<body class="text-white font-display overflow-x-hidden terminal-grid custom-scrollbar">
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
                <button onclick="document.getElementById('successModal').remove()" class="w-full mt-8 py-3 bg-primary text-background-dark font-black rounded-xl hover:scale-[1.02] transition-all uppercase tracking-widest">UNDERSTOOD</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="relative flex h-screen w-full flex-col overflow-hidden">
        <header class="flex items-center justify-between border-b border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md z-10">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3 text-primary cursor-pointer transition-transform hover:scale-105" onclick="location.reload()">
                    <span class="material-symbols-outlined text-3xl">shield_person</span>
                    <h2 class="text-white text-xl font-bold tracking-tight uppercase">CyberShield <span class="text-primary/70 text-xs font-mono">v4.2.0</span></h2>
                </div>
                <div class="hidden lg:flex items-center gap-6">
                    <a class="text-primary text-sm font-semibold border-b-2 border-primary pb-1" href="index.php">Simulation</a>
                    <a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="analytics.php">Analytics</a>
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

        <main class="flex-1 flex overflow-hidden">
            <aside class="w-64 border-r border-border-muted flex flex-col bg-background-dark/50 p-6 overflow-y-auto custom-scrollbar">
                <h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Target Audience</h3>
                <div class="space-y-1 mb-8" id="audienceList">
                    <button onclick="switchAudience('all')" class="audience-btn active w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all">
                        <span class="text-sm font-medium">All Employees</span>
                        <span class="text-[10px] font-mono"><?php echo number_format($all_emp_count); ?></span>
                    </button>
                    <button onclick="switchAudience('engineering')" class="audience-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all">
                        <span class="text-sm font-medium">Engineering</span>
                        <span class="text-[10px] font-mono"><?php echo number_format($eng_count); ?></span>
                    </button>
                    <button onclick="switchAudience('finance')" class="audience-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] border border-transparent hover:bg-surface-dark hover:text-white transition-all">
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
                    <span class="text-[10px] font-bold uppercase text-red-500 block mb-1">System Alert</span>
                    <p class="text-[10px] text-red-200/70">Tracking <span id="activeAudienceLabel" class="font-bold text-white">All Employees</span></p>
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
                    <form method="POST" action="process_campaign.php" class="w-1/2 p-6 overflow-y-auto custom-scrollbar border-r border-border-muted h-full">
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
                            <button type="submit" name="launch" class="px-8 py-3 bg-primary text-background-dark rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all uppercase italic">Launch Attack Instance</button>
                        </div>
                    </form>

                    <section class="w-1/2 p-6 bg-surface-dark/30 flex flex-col overflow-hidden">
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

                        <div id="emailPreviewContainer" class="custom-scrollbar shadow-2xl overflow-y-auto">
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
                                        <div id="previewBody" class="space-y-4 text-slate-800">
                                            <p>Dear Employee,</p>
                                            <p>We detected unusual activity. Please verify your identity.</p>
                                            <p><a href="#" class="inline-block bg-[#007bff] text-white px-6 py-2.5 rounded font-bold no-underline">Verify Now</a></p>
                                        </div>
                                    </div>
                                    <div class="pt-8 border-t border-gray-100 flex items-center gap-2 text-[10px] text-gray-400 italic">
                                        <span class="material-symbols-outlined text-xs">lock</span>
                                        This message was encrypted via CyberShield Secure Mail Gateway.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl grid grid-cols-2 gap-4">
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
                restoreBtn.classList.add('hidden'); // No restore for custom (they just edit)
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
            // Use innerHTML but handle the replacement
            let body = document.getElementById('email_body').value;
            if (!body) body = '<p class="text-slate-300 italic">Starting new custom pattern...</p>';
            document.getElementById('previewBody').innerHTML = body.replace(/{{TRACKING_LINK}}/g, '#');
        }

        // Add real-time listeners
        ['sender_name', 'subject', 'email_body'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePreview);
        });

        // Initialize
        initTemplates();
        updatePreview();
    </script>
</body>

</html>