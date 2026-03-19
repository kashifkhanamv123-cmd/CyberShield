<?php
require_once __DIR__ . '/admin-auth.php';

$success = '';
$error = '';

// Fetch all settings
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $to_update = [
        'site_title' => $_POST['site_title'] ?? 'CyberShield',
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
        'admin_notification_email' => $_POST['admin_notification_email'] ?? '',
    ];

    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($to_update as $key => $val) {
        $stmt->bind_param("ss", $val, $key);
        $stmt->execute();
    }
    $stmt->close();
    
    $success = "System configuration synchronized successfully.";
    
    // Refresh settings array
    foreach ($to_update as $key => $val) {
        $settings[$key] = $val;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | System Configuration</title>
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
                        "background-dark": "#020302",
                        surface: "#0d0f0a",
                        "neutral-dark": "#050604",
                        "border-dim": "#1a1d14",
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
            background-color: #020302;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(160, 240, 0, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(0, 240, 255, 0.02) 0%, transparent 50%);
        }
        .glass {
            background: rgba(13, 15, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(160, 240, 0, 0.08);
        }
        .shadow-glow { box-shadow: 0 0 20px -5px rgba(160, 240, 0, 0.2); }
        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #1a1d14; transition: .4s; border-radius: 24px; border: 1px solid rgba(160, 240, 0, 0.1);
        }
        .slider:before {
            position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px;
            background-color: #4a4e41; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: rgba(160, 240, 0, 0.2); border-color: #a0f000; }
        input:checked + .slider:before { transform: translateX(20px); background-color: #a0f000; box-shadow: 0 0 10px #a0f000; }
    </style>
</head>
<body class="bg-background-dark text-slate-300 font-display min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include '_sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="shrink-0 sticky top-0 z-20 bg-[#050604]/80 backdrop-blur-xl border-b border-border-dim px-10 py-6 flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-mono text-primary/50 uppercase tracking-[0.3em] font-black">System Matrix / Configuration</p>
                    <h2 class="text-2xl font-black text-white italic uppercase tracking-tighter">System <span class="text-primary not-italic">Config</span></h2>
                </div>
            </header>

            <section class="flex-1 overflow-y-auto custom-scrollbar p-10">
                <div class="max-w-4xl mx-auto space-y-8">
                    
                    <?php if ($success): ?>
                        <div class="p-4 glass border-primary/30 rounded-2xl flex items-center gap-4 animate-fade-in">
                            <span class="material-symbols-outlined text-primary">verified</span>
                            <p class="text-xs font-black text-primary uppercase tracking-widest"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8">
                        
                        <!-- Core Platform Settings -->
                        <div class="glass rounded-[2.5rem] p-10 relative overflow-hidden group">
                            <div class="absolute -right-10 -top-10 opacity-5 group-hover:opacity-10 transition-opacity">
                                <span class="material-symbols-outlined text-[15rem]">settings_input_component</span>
                            </div>
                            
                            <h3 class="text-lg font-black text-white uppercase tracking-tight mb-8 flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary">hub</span>
                                Core Platform Pulse
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Platform Label</label>
                                    <input type="text" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? 'CyberShield'); ?>" 
                                        class="w-full bg-[#0a0c06] border border-border-dim rounded-2xl px-6 py-4 text-white font-bold focus:outline-none focus:border-primary/50 transition-all">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Admin Alert Gateway</label>
                                    <input type="email" name="admin_notification_email" value="<?php echo htmlspecialchars($settings['admin_notification_email'] ?? ''); ?>" 
                                        class="w-full bg-[#0a0c06] border border-border-dim rounded-2xl px-6 py-4 text-white font-bold focus:outline-none focus:border-primary/50 transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Operational Toggles -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <div class="glass rounded-[2.5rem] p-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="size-12 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500">
                                        <span class="material-symbols-outlined">construction</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <h4 class="text-sm font-black text-white uppercase tracking-widest mb-2">Maintenance Protocol</h4>
                                <p class="text-[10px] text-slate-500 font-bold leading-relaxed uppercase tracking-wider">Restricts platform access to administrative nodes only. Active users will be redirected.</p>
                            </div>

                            <div class="glass rounded-[2.5rem] p-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="size-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-glow">
                                        <span class="material-symbols-outlined">person_add</span>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="registration_enabled" <?php echo ($settings['registration_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <h4 class="text-sm font-black text-white uppercase tracking-widest mb-2">New Node Admission</h4>
                                <p class="text-[10px] text-slate-500 font-bold leading-relaxed uppercase tracking-wider">Permits new registrations on the platform. If disabled, only manual onboarding will be possible.</p>
                            </div>

                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="update_settings" class="bg-primary hover:bg-lime-400 text-background-dark font-black py-5 px-12 rounded-2xl uppercase tracking-[0.3em] text-xs shadow-glow hover:scale-105 transition-all">
                                Sync Configuration
                            </button>
                        </div>

                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
