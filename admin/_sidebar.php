<?php

/**
 * CyberShield Admin Panel - Shared Sidebar
 * Determines the current page for active nav highlighting.
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$navItems = [
    'dashboard'  => ['icon' => 'dashboard',       'label' => 'Dashboard'],
    'users'      => ['icon' => 'group',            'label' => 'Users'],
    'phishing'   => ['icon' => 'alternate_email',  'label' => 'Phishing Lab'],
    'bruteforce' => ['icon' => 'lock_open',        'label' => 'Brute Force Lab'],
    'malware'    => ['icon' => 'bug_report',       'label' => 'Malware Analysis'],
    'ddos'       => ['icon' => 'thunderstorm',     'label' => 'DDoS Simulation'],
    'logs'       => ['icon' => 'receipt_long',     'label' => 'Security Logs'],
    'analytics'  => ['icon' => 'bar_chart',        'label' => 'Analytics'],
];
?>
<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 border-r border-border-dim bg-neutral-dark/95 backdrop-blur-xl flex flex-col transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300">
    <button id="close-admin-sidebar" class="md:hidden absolute top-4 right-4 text-slate-400 hover:text-white z-50">
        <span class="material-symbols-outlined text-2xl">close</span>
    </button>
    <!-- Logo -->
    <div class="p-6 border-b border-border-dim shrink-0">
        <a href="" class="flex items-center gap-3 text-primary px-2 hover:opacity-80 transition-opacity">
            <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
            <div>
                <h1 class="text-white text-lg font-black italic uppercase tracking-tight">Cyber<span class="text-primary">Shield</span></h1>
                <p class="text-[9px] font-mono text-primary/60 uppercase tracking-[0.2em]">Admin Control</p>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto custom-scrollbar">
        <?php foreach ($navItems as $page => $item): ?>
            <a href="<?php echo $page; ?>.php"
                class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all
                  <?php echo $currentPage === $page
                        ? 'active text-primary font-bold'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white'; ?>">
                <span class="material-symbols-outlined text-xl"><?php echo $item['icon']; ?></span>
                <?php echo $item['label']; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Admin Info & Logout -->
    <div class="p-4 border-t border-border-dim shrink-0 bg-neutral-dark/50">
        <div class="flex items-center gap-3 mb-4 px-2">
            <div class="size-8 rounded-full bg-gradient-to-tr from-primary to-lime-600 flex items-center justify-center text-background-dark font-bold text-xs shrink-0">
                <?php
                $initials = '';
                foreach (explode(' ', $adminName) as $p) $initials .= strtoupper($p[0] ?? '');
                echo substr($initials, 0, 2);
                ?>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($adminName); ?></p>
                <p class="text-[10px] text-primary font-mono">ADMIN</p>
            </div>
        </div>
        <a href="../auth/logout.php"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:bg-red-400/10 transition-all w-full">
            <span class="material-symbols-outlined text-xl">logout</span>
            Terminate Session
        </a>
    </div>
</aside>