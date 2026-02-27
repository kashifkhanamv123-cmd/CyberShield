<?php
session_start();
include("../../config/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>

<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>CyberShield | Phishing Simulation Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
        }
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
<div class="relative flex h-screen w-full flex-col overflow-hidden">
<!-- Top Navigation Bar -->
<header class="flex items-center justify-between border-b border-solid border-border-muted px-6 py-3 bg-background-dark/80 backdrop-blur-md z-10">
<div class="flex items-center gap-8">
<div class="flex items-center gap-3 text-primary">
<div class="size-8 flex items-center justify-center">
<span class="material-symbols-outlined text-3xl">shield_person</span>
</div>
<h2 class="text-white text-xl font-bold tracking-tight uppercase">CyberShield <span class="text-primary/70 text-xs font-mono">v4.2.0</span></h2>
</div>
<div class="hidden lg:flex items-center gap-6">
<a class="text-primary text-sm font-semibold border-b-2 border-primary pb-1" href="#">Simulation</a>
<a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="#">Analytics</a>
<a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="#">Templates</a>
<a class="text-[#b0bc9a] hover:text-white text-sm font-medium transition-colors" href="#">Target Groups</a>
</div>
</div>
<div class="flex items-center gap-4">
<div class="relative hidden sm:block">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#b0bc9a] text-sm">search</span>
<input class="bg-surface-dark border-border-muted rounded-lg pl-9 pr-4 py-1.5 text-sm w-64 focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all" placeholder="Search logs..." type="text"/>
</div>
<button class="p-2 rounded-lg bg-surface-dark text-[#b0bc9a] hover:text-primary transition-colors">
<span class="material-symbols-outlined">notifications</span>
</button>
<div class="h-8 w-px bg-border-muted mx-1"></div>
<div class="flex items-center gap-3 bg-surface-dark px-3 py-1.5 rounded-full border border-border-muted">
<div class="size-6 bg-primary/20 rounded-full flex items-center justify-center border border-primary/40">
<span class="material-symbols-outlined text-primary text-xs">admin_panel_settings</span>
</div>
<span class="text-xs font-bold tracking-wider">SEC_ADMIN</span>
</div>
</div>
</header>
<!-- Main Content Area -->
<main class="flex-1 flex overflow-hidden terminal-grid">
<!-- Left Sidebar: Target Groups -->
<aside class="w-64 border-r border-border-muted flex flex-col bg-background-dark/50">
<div class="p-6 border-b border-border-muted">
<h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Target Audience</h3>
<div class="space-y-1">
<button class="w-full flex items-center justify-between px-3 py-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-xl">groups</span>
<span class="text-sm font-medium">All Employees</span>
</div>
<span class="text-[10px] font-mono">1,240</span>
</button>
<button class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] hover:bg-surface-dark hover:text-white transition-all">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-xl">code</span>
<span class="text-sm font-medium">Engineering</span>
</div>
<span class="text-[10px] font-mono">412</span>
</button>
<button class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] hover:bg-surface-dark hover:text-white transition-all">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-xl">payments</span>
<span class="text-sm font-medium">Finance</span>
</div>
<span class="text-[10px] font-mono">84</span>
</button>
<button class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[#b0bc9a] hover:bg-surface-dark hover:text-white transition-all">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-xl">support_agent</span>
<span class="text-sm font-medium">Sales</span>
</div>
<span class="text-[10px] font-mono">156</span>
</button>
</div>
</div>
<div class="p-6">
<h3 class="text-xs font-bold uppercase tracking-widest text-[#b0bc9a] mb-4">Threat Vectors</h3>
<div class="space-y-3">
<div class="flex items-center gap-3">
<input checked="" class="rounded border-border-muted bg-surface-dark text-primary focus:ring-primary" type="checkbox"/>
<span class="text-sm text-[#b0bc9a]">Link Tracking</span>
</div>
<div class="flex items-center gap-3">
<input checked="" class="rounded border-border-muted bg-surface-dark text-primary focus:ring-primary" type="checkbox"/>
<span class="text-sm text-[#b0bc9a]">Credential Capture</span>
</div>
<div class="flex items-center gap-3">
<input class="rounded border-border-muted bg-surface-dark text-primary focus:ring-primary" type="checkbox"/>
<span class="text-sm text-[#b0bc9a]">Attachment Payload</span>
</div>
</div>
</div>
<div class="mt-auto p-4">
<div class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
<div class="flex items-center gap-2 text-red-500 mb-1">
<span class="material-symbols-outlined text-sm">warning</span>
<span class="text-[10px] font-bold uppercase">System Alert</span>
</div>
<p class="text-[10px] text-red-200/70">Training mode is active. All interactions will be logged for compliance.</p>
</div>
</div>
</aside>
<!-- Center & Right: Dashboard Workspace -->
<div class="flex-1 flex flex-col overflow-hidden">
<!-- Metrics Bar -->
<section class="p-6 grid grid-cols-4 gap-4 bg-background-dark/30 border-b border-border-muted">
<div class="flex flex-col gap-1 p-4 rounded-xl border border-border-muted bg-surface-dark/50 hover:border-primary/30 transition-all group">
<div class="flex items-center justify-between">
<span class="text-[#b0bc9a] text-xs font-semibold uppercase">Emails Sent</span>
<span class="material-symbols-outlined text-primary text-sm">send</span>
</div>
<div class="flex items-baseline gap-2"><p class="text-2xl font-bold">0</p>
<span class="text-[10px] text-primary">0%</span></div>
</div>
<div class="flex flex-col gap-1 p-4 rounded-xl border border-border-muted bg-surface-dark/50 hover:border-primary/30 transition-all group">
<div class="flex items-center justify-between">
<span class="text-[#b0bc9a] text-xs font-semibold uppercase">Links Clicked</span>
<span class="material-symbols-outlined text-primary text-sm">ads_click</span>
</div>
<div class="flex items-baseline gap-2"><p class="text-2xl font-bold">0</p>
<span class="text-[10px] text-primary">0%</span></div>
</div>
<div class="flex flex-col gap-1 p-4 rounded-xl border border-border-muted bg-surface-dark/50 hover:border-primary/30 transition-all group">
<div class="flex items-center justify-between">
<span class="text-[#b0bc9a] text-xs font-semibold uppercase">Credentials</span>
<span class="material-symbols-outlined text-red-500 text-sm">key</span>
</div>
<div class="flex items-baseline gap-2"><p class="text-2xl font-bold">0</p>
<span class="text-[10px] text-[#b0bc9a]">None</span></div>
</div>
<div class="flex flex-col gap-1 p-4 rounded-xl border border-border-muted bg-surface-dark/50 hover:border-primary/30 transition-all group">
<div class="flex items-center justify-between">
<span class="text-[#b0bc9a] text-xs font-semibold uppercase">Avg Risk Score</span>
<span class="material-symbols-outlined text-primary text-sm">speed</span>
</div>
<div class="flex items-baseline gap-2"><p class="text-2xl font-bold">0%</p>
<span class="text-[10px] text-primary">Target: 20%</span></div>
</div>
</section>
<!-- Editor Workspace -->
<div class="flex-1 flex overflow-hidden">
<!-- Phishing Email Creator -->
<form method="POST" action="process_campaign.php" class="w-1/2 p-6 overflow-y-auto">
<div class="flex items-center justify-between mb-6">
<h2 class="text-lg font-bold flex items-center gap-2 tracking-tight">
<span class="material-symbols-outlined text-primary">edit_note</span>
                                Email Creator
                            </h2>
<div class="flex gap-2">
<button class="px-3 py-1.5 text-xs font-bold border border-border-muted rounded-lg hover:bg-surface-dark transition-all">Reset</button>
<button class="px-3 py-1.5 text-xs font-bold bg-primary/20 text-primary border border-primary/40 rounded-lg hover:bg-primary/30 transition-all">Load Template</button>
</div>
</div>
<div class="space-y-6">
<div class="grid grid-cols-2 gap-4">
<label class="flex flex-col gap-2">
<span class="text-xs font-bold uppercase text-[#b0bc9a]">Sender Display Name</span>
<input class="bg-surface-dark border-border-muted rounded-lg p-3 text-sm focus:border-primary outline-none focus:ring-1 focus:ring-primary/20" type="text" value="IT Security Operations"/>
</label>
<label class="flex flex-col gap-2">
<span class="text-xs font-bold uppercase text-[#b0bc9a]">Spoofed Email Address</span>
<input class="bg-surface-dark border-border-muted rounded-lg p-3 text-sm font-mono focus:border-primary outline-none focus:ring-1 focus:ring-primary/20" type="text" value="admin-no-reply@cybershield-auth.com"/>
</label>
</div>
<label class="flex flex-col gap-2">
<span class="text-xs font-bold uppercase text-[#b0bc9a]">Email Subject</span>
<input class="bg-surface-dark border-border-muted rounded-lg p-3 text-sm font-medium focus:border-primary outline-none focus:ring-1 focus:ring-primary/20" type="text" value="URGENT: Your account requires immediate verification"/>
</label>
<div class="flex flex-col gap-2">
<div class="flex items-center justify-between">
<span class="text-xs font-bold uppercase text-[#b0bc9a]">Email Body (HTML/Rich Text)</span>
<div class="flex gap-1">
<button class="p-1 hover:bg-surface-dark rounded text-[#b0bc9a]"><span class="material-symbols-outlined text-lg">format_bold</span></button>
<button class="p-1 hover:bg-surface-dark rounded text-[#b0bc9a]"><span class="material-symbols-outlined text-lg">format_italic</span></button>
<button class="p-1 hover:bg-surface-dark rounded text-[#b0bc9a]"><span class="material-symbols-outlined text-lg">link</span></button>
<button class="p-1 hover:bg-surface-dark rounded text-primary"><span class="material-symbols-outlined text-lg">code</span></button>
</div>
</div>
<div class="relative group">
<textarea class="w-full bg-surface-dark border-border-muted rounded-lg p-4 text-sm font-mono focus:border-primary outline-none focus:ring-1 focus:ring-primary/20 custom-scrollbar" rows="10">&lt;p&gt;Dear Employee,&lt;/p&gt;

&lt;p&gt;We have detected an unusual login attempt on your CyberShield account from a new location in Eastern Europe. For your security, your account access has been temporarily restricted.&lt;/p&gt;

&lt;p&gt;Please click the button below to verify your identity and restore access:&lt;/p&gt;

&lt;a href="{{TRACKING_LINK}}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;"&gt;Verify Account Now&lt;/a&gt;

&lt;p&gt;Failure to verify within 24 hours will result in permanent account suspension.&lt;/p&gt;

&lt;p&gt;Regards,&lt;br&gt;IT Security Team&lt;/p&gt;</textarea>
<div class="absolute bottom-4 right-4 text-[10px] text-[#b0bc9a] font-mono bg-background-dark/80 px-2 py-1 rounded">214 Words | HTML OK</div>
</div>
</div>
</div>
<div class="mt-8 pt-8 border-t border-border-muted flex items-center justify-between">
<button class="flex items-center gap-2 px-6 py-3 border border-border-muted rounded-xl font-bold hover:bg-surface-dark transition-all">
<span class="material-symbols-outlined">save</span>
                                Save Draft
                            </button>
<button class="flex items-center gap-2 px-8 py-3 bg-primary text-background-dark rounded-xl font-bold shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
<span class="material-symbols-outlined">rocket_launch</span>
                               <button type="submit" name="launch">
Launch Campaign
</button>
</div>
</section>
<!-- Preview Panel -->
<section class="w-1/2 p-6 bg-surface-dark/30 flex flex-col">
<div class="flex items-center justify-between mb-6">
<h2 class="text-lg font-bold flex items-center gap-2 tracking-tight">
<span class="material-symbols-outlined text-primary">visibility</span>
                                Target Preview
                            </h2>
<div class="bg-background-dark p-1 rounded-lg border border-border-muted flex gap-1">
<button class="px-2 py-1 rounded bg-surface-dark text-primary"><span class="material-symbols-outlined text-sm">desktop_windows</span></button>
<button class="px-2 py-1 rounded hover:bg-surface-dark transition-all"><span class="material-symbols-outlined text-sm">smartphone</span></button>
<button class="px-2 py-1 rounded hover:bg-surface-dark transition-all"><span class="material-symbols-outlined text-sm">tablet</span></button>
</div>
</div>
<!-- Simulated Outlook/Gmail View -->
<div class="flex-1 bg-[#f1f3f4] rounded-xl overflow-hidden shadow-2xl flex flex-col border border-white/10">
<!-- Browser Header -->
<div class="bg-white px-4 py-2 border-b border-gray-200 flex items-center gap-4">
<div class="flex gap-1.5">
<div class="size-2.5 rounded-full bg-red-400"></div>
<div class="size-2.5 rounded-full bg-yellow-400"></div>
<div class="size-2.5 rounded-full bg-green-400"></div>
</div>
<div class="bg-gray-100 px-4 py-1 rounded text-[10px] text-gray-500 flex-1 truncate">outlook.office.com/mail/inbox/id=842...</div>
</div>
<!-- Email View Area -->
<div class="flex-1 bg-white p-8 overflow-y-auto custom-scrollbar text-gray-900">
<div class="mb-6">
<h1 class="text-xl font-bold text-gray-900 mb-4">URGENT: Your account requires immediate verification</h1>
<div class="flex items-center gap-3">
<div class="size-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">IT</div>
<div>
<div class="flex items-center gap-2">
<span class="text-sm font-bold">IT Security Operations</span>
<span class="text-[10px] text-gray-500">&lt;admin-no-reply@cybershield-auth.com&gt;</span>
</div>
<div class="text-[10px] text-gray-500">To: Employee Name &lt;target@company.com&gt;</div>
</div>
</div>
</div>
<div class="text-sm leading-relaxed space-y-4">
<p>Dear Employee,</p>
<p>We have detected an unusual login attempt on your CyberShield account from a new location in Eastern Europe. For your security, your account access has been temporarily restricted.</p>
<p>Please click the button below to verify your identity and restore access:</p>
<div>
<a class="inline-block bg-[#007bff] text-white px-6 py-2.5 rounded font-medium text-sm no-underline shadow-sm" href="#">Verify Account Now</a>
</div>
<p>Failure to verify within 24 hours will result in permanent account suspension.</p>
<p class="pt-4 text-gray-500">Regards,<br/>IT Security Team</p>
</div>
</div>
</div>
<!-- Analysis / Red Flags -->
<div class="mt-6 p-4 rounded-xl border border-primary/20 bg-primary/5">
<h3 class="text-xs font-bold uppercase text-primary mb-3 flex items-center gap-2">
<span class="material-symbols-outlined text-sm">biotech</span>
                                Simulation Analysis
                            </h3>
<div class="grid grid-cols-2 gap-3">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-sm">check_circle</span>
<span class="text-[10px] text-[#b0bc9a]">Spoof successful</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-sm">check_circle</span>
<span class="text-[10px] text-[#b0bc9a]">Tracking pixel armed</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-yellow-500 text-sm">report</span>
<span class="text-[10px] text-[#b0bc9a]">Urgency flags detected (3)</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-sm">verified_user</span>
<span class="text-[10px] text-[#b0bc9a]">SSL bypass active</span>
</div>
</div>
</div>
</section>
</div>
<!-- Footer / Legal -->
<footer class="px-6 py-2 bg-background-dark border-t border-border-muted flex items-center justify-between">
<div class="flex items-center gap-4 text-[10px] text-[#b0bc9a] font-mono">
<span class="flex items-center gap-1"><span class="size-1.5 rounded-full bg-primary animate-pulse"></span> SERVER_NODE_01: ONLINE</span>
<span>LATENCY: 12ms</span>
<span>ENCRYPTION: AES-256</span>
</div>
<div class="text-[10px] text-[#b0bc9a]/50 uppercase tracking-tighter">
                        Authorized Use Only • CyberShield Security Training Platform © 2024
                    </div>
</footer>
</div>
</main>
</div>
</body></html>
