<?php

/**
 * CyberShield Admin Panel - Shared Sidebar
 * Determines the current page for active nav highlighting.
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'dashboard'  => ['icon' => 'grid_view',         'label' => 'Dashboard'],
    'users'      => ['icon' => 'group',             'label' => 'Users'],
    'phishing'   => ['icon' => 'alternate_email',   'label' => 'Phishing Lab'],
    'bruteforce' => ['icon' => 'lock_open',         'label' => 'Brute Force Lab'],
    'malware'    => ['icon' => 'bug_report',        'label' => 'Malware Analysis'],
    'ddos'       => ['icon' => 'thunderstorm',      'label' => 'DDoS Simulation'],
    'logs'       => ['icon' => 'receipt_long',      'label' => 'Security Logs'],
    'analytics'  => ['icon' => 'bar_chart',         'label' => 'Analytics'],
    'settings'   => ['icon' => 'settings_suggest',  'label' => 'System Config'],
];
?>
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 border-r border-border-dim bg-neutral-dark/80 backdrop-blur-xl flex flex-col transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300">
    <button id="close-admin-sidebar" class="md:hidden absolute top-4 right-4 text-slate-400 hover:text-white z-50">
        <span class="material-symbols-outlined text-2xl">close</span>
    </button>
    <!-- Logo -->
    <div class="p-6 border-b border-border-dim shrink-0">
        <a href="dashboard.php" class="flex items-center gap-3 text-primary px-2 hover:opacity-80 transition-opacity">
            <div class="size-9 bg-primary/10 border border-primary/20 rounded-lg flex items-center justify-center text-primary shadow-glow">
                <span class="material-symbols-outlined text-2xl font-black">admin_panel_settings</span>
            </div>
            <div>
                <h1 class="text-white text-lg font-black italic uppercase tracking-tighter">Cyber<span class="text-primary not-italic">Shield</span></h1>
                <p class="text-[9px] font-mono text-primary/50 uppercase tracking-[0.2em] font-black">Admin Matrix</p>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto custom-scrollbar">
        <?php foreach ($navItems as $page => $item): ?>
            <a href="<?php echo $page; ?>.php"
                class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-xl text-[13px] font-bold transition-all
                  <?php echo $currentPage === $page
                        ? 'bg-primary/10 text-primary border border-primary/20 shadow-glow'
                        : 'text-slate-500 hover:bg-white/5 hover:text-white border border-transparent'; ?>">
                <span class="material-symbols-outlined text-xl"><?php echo $item['icon']; ?></span>
                <span class="uppercase tracking-widest text-[11px]"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Admin Info & Logout -->
    <div class="p-5 border-t border-border-dim shrink-0 bg-neutral-dark/40 backdrop-blur-md">
        <div class="flex items-center gap-3 mb-5 px-1">
            <div class="size-10 rounded-xl bg-gradient-to-tr from-primary to-lime-600 p-0.5 shadow-glow">
                <div class="size-full bg-background-dark rounded-[10px] flex items-center justify-center text-primary font-black text-xs uppercase">
                    <?php
                    $initials = '';
                    foreach (explode(' ', $adminName) as $p) $initials .= strtoupper($p[0] ?? '');
                    echo substr($initials, 0, 2);
                    ?>
                </div>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-black text-white uppercase tracking-tighter truncate"><?php echo htmlspecialchars($adminName); ?></p>
                <p class="text-[9px] text-primary/60 font-mono font-black uppercase tracking-widest">Operator</p>
            </div>
        </div>
        <a href="../auth/logout.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:bg-red-400/10 transition-all w-full">
            <span class="material-symbols-outlined text-xl">logout</span>
            Terminate Session
        </a>
    </div>
</aside>