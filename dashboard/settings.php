<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Helper for letter avatar
function getLetterAvatar($name) {
    $initial = strtoupper(substr(trim($name ?? 'U'), 0, 1));
    return '<div class="size-full flex items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5 text-primary font-black text-4xl border border-primary/20 rounded-2xl uppercase tracking-tighter">' . $initial . '</div>';
}

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, profile_type, profile_image, bio, mfa_enabled, login_alerts_enabled FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $profile_type = $_POST['profile_type'];
    $profile_image = $user['profile_image'];

    if ($profile_type === 'preset') {
        $profile_image = $_POST['preset_icon'] ?? $user['profile_image'];
    } elseif ($profile_type === 'custom' && isset($_FILES['custom_image']) && $_FILES['custom_image']['error'] === 0) {
        $target_dir = "../uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['custom_image']['name'], PATHINFO_EXTENSION);
        $file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['custom_image']['tmp_name'], $target_file)) {
            $profile_image = "uploads/profiles/" . $file_name;
        } else {
            $error = "Failed to upload image.";
        }
    } elseif ($profile_type === 'none') {
        $profile_image = null;
    }

    if (!$error) {
        $bio = $_POST['bio'] ?? '';
        $mfa = isset($_POST['mfa_enabled']) ? 1 : 0;
        $alerts = isset($_POST['login_alerts_enabled']) ? 1 : 0;

        $update = $conn->prepare("UPDATE users SET name = ?, profile_type = ?, profile_image = ?, bio = ?, mfa_enabled = ?, login_alerts_enabled = ? WHERE id = ?");
        $update->bind_param("ssssiii", $newName, $profile_type, $profile_image, $bio, $mfa, $alerts, $user_id);
        if ($update->execute()) {
            $success = "Directive executed: Profile parameters synchronized.";
            $_SESSION['user_name'] = $newName;
            $user['name'] = $newName;
            $user['profile_type'] = $profile_type;
            $user['profile_image'] = $profile_image;
            $user['bio'] = $bio;
            $user['mfa_enabled'] = $mfa;
            $user['login_alerts_enabled'] = $alerts;
        } else {
            $error = "Synchronization error: Database rejection.";
        }
        $update->close();
    }
}

if (isset($_POST['update_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $db_pwd = $stmt->get_result()->fetch_assoc()['password'];
    $stmt->close();

    if (!password_verify($current_pwd, $db_pwd)) {
        $error = "Security breach: Current credential invalid.";
    } elseif ($new_pwd !== $confirm_pwd) {
        $error = "Mismatch error: Recovery key confirmation failed.";
    } else {
        $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed, $user_id);

        if ($update->execute()) {
            $success = "Security protocol updated: New key active.";
        } else {
            $error = "System failure: Password encryption failed.";
        }
        $update->close();
    }
}

// Lab completion stats for ranking
$phishing_stmt = $conn->prepare("SELECT COUNT(*) as total FROM phishing_campaigns WHERE user_id = ?");
$phishing_stmt->bind_param("i", $user_id);
$phishing_stmt->execute();
$phishing_count = $phishing_stmt->get_result()->fetch_row()[0];

$bruteforce_stmt = $conn->prepare("SELECT MAX(success) as has_success FROM bruteforce_logs WHERE user_id = ?");
$bruteforce_stmt->bind_param("i", $user_id);
$bruteforce_stmt->execute();
$brute_success = (int)$bruteforce_stmt->get_result()->fetch_assoc()['has_success'];

$ddos_stmt = $conn->prepare("SELECT MAX(mitigated) as has_success FROM ddos_logs WHERE user_id = ?");
$ddos_stmt->bind_param("i", $user_id);
$ddos_stmt->execute();
$ddos_success = (int)$ddos_stmt->get_result()->fetch_assoc()['has_success'];

$mal_stmt = $conn->prepare("SELECT MAX(correct) as has_success FROM malware_logs WHERE user_id = ?");
$mal_stmt->bind_param("i", $user_id);
$mal_stmt->execute();
$mal_success = (int)$mal_stmt->get_result()->fetch_assoc()['has_success'];

$total_completed = ($phishing_count > 0 ? 1 : 0) + ($brute_success ? 1 : 0) + ($ddos_success ? 1 : 0) + ($mal_success ? 1 : 0);

// Determine rank
if ($total_completed === 0) $rank = "Untrusted Node";
elseif ($total_completed == 1) $rank = "Lvl_01 Analyst";
elseif ($total_completed == 2) $rank = "Lvl_02 Operative";
elseif ($total_completed == 3) $rank = "Lvl_03 Specialist";
else $rank = "Lvl_04 Commander";

// Get user IP
$user_ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];

$presets = [
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield1',
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield2',
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield3',
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield4',
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield5',
    'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield6'
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Node Config | <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#a0f000",
                        secondary: "#00f0ff",
                        "neutral-dark": "#050604",
                        "surface": "#0d0f0a",
                        "surface-light": "#161810",
                        "border-dim": "#1e2216",
                        "bg-dark": "#020302"
                    },
                    boxShadow: {
                        'glow': '0 0 20px -5px rgba(160, 240, 0, 0.3)',
                        'glow-heavy': '0 0 40px -10px rgba(160, 240, 0, 0.5)',
                        'glow-cyan': '0 0 20px -5px rgba(0, 240, 255, 0.3)',
                        'glow-red': '0 0 20px -5px rgba(239, 68, 68, 0.3)',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: theme('colors.bg-dark');
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(160, 240, 0, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(0, 240, 255, 0.03) 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
            color: #fff;
        }
        .glass-panel {
            background: rgba(13, 15, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(160, 240, 0, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        }
        .glass-panel:hover {
            border-color: rgba(160, 240, 0, 0.2);
        }
        .elite-border {
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: linear-gradient(135deg, rgba(255,255,255,0.05), transparent);
        }
        .form-input {
            background: rgba(5, 6, 4, 0.8);
            border: 1px solid theme('colors.border-dim');
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-input:focus {
            border-color: theme('colors.primary');
            box-shadow: 0 0 0 1px theme('colors.primary'), 0 0 20px -5px theme('colors.primary');
            outline: none;
            transform: translateY(-1px);
        }
        .btn-elite {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-elite::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(160, 240, 0, 0.1), transparent);
            transform: rotate(45deg);
            transition: 0.5s;
        }
        .btn-elite:hover::after {
            left: 100%;
        }
        /* Custom Checkbox/Toggle */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #1e2216;
            transition: .4s;
            border-radius: 24px;
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 16px; width: 16px;
            left: 3px; bottom: 3px;
            background-color: #4a4e41;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: rgba(160, 240, 0, 0.2); border-color: #a0f000; }
        input:checked + .slider:before { transform: translateX(20px); background-color: #a0f000; box-shadow: 0 0 10px #a0f000; }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: theme('colors.border-dim');
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: theme('colors.primary');
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden selection:bg-primary selection:text-neutral-dark text-slate-300">

    <!-- Sidebar Navigation -->
    <aside class="w-20 md:w-72 flex flex-col border-r border-border-dim bg-neutral-dark shrink-0 transition-all duration-300">
        <div class="h-24 flex items-center px-8 border-b border-border-dim">
            <div class="size-10 bg-primary rounded-xl flex items-center justify-center shrink-0 shadow-glow">
                <span class="material-symbols-outlined text-neutral-dark text-2xl font-black">shield</span>
            </div>
            <span class="ml-4 font-black tracking-tighter uppercase text-2xl md:block hidden italic text-white">Shield</span>
        </div>
        
        <nav class="flex-1 p-5 space-y-3">
            <a href="dashboard.php" class="flex items-center gap-5 px-5 py-4 rounded-2xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-2xl group-hover:text-primary transition-transform group-hover:scale-110">security</span>
                <span class="text-sm font-black md:block hidden uppercase tracking-widest">Labs Access</span>
            </a>
            <div class="pt-6 pb-2 px-5">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.3em] md:block hidden">Configuration</p>
                <div class="h-px bg-border-dim w-full md:hidden"></div>
            </div>
            <div class="flex items-center gap-5 px-5 py-4 rounded-2xl bg-primary/10 text-primary border border-primary/20 transition-all shadow-glow-heavy">
                <span class="material-symbols-outlined text-2xl">settings</span>
                <span class="text-sm font-black md:block hidden uppercase tracking-widest">Profile Node</span>
            </div>
        </nav>

        <div class="p-4 border-t border-border-dim">
            <a href="../auth/logout.php" class="flex items-center gap-4 px-4 py-3 rounded-xl text-red-400 hover:bg-red-400/10 transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform">power_settings_new</span>
                <span class="text-sm font-bold md:block hidden uppercase tracking-wider">Terminate</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col relative overflow-hidden bg-bg-dark">
        
        <!-- Top Bar -->
        <header class="h-24 flex items-center justify-between px-10 bg-neutral-dark/50 backdrop-blur-md border-b border-border-dim shrink-0 z-10">
            <div class="flex flex-col">
                <h1 class="text-xl font-black uppercase tracking-tight text-white italic">Node <span class="text-primary not-italic">Config</span></h1>
                <p class="text-[10px] font-mono text-slate-500 uppercase tracking-[0.2em] font-black">Operator: <?php echo htmlspecialchars($user['name']); ?> // Port: 443 // <span class="text-primary animate-pulse">Online</span></p>
            </div>

            <div class="flex items-center gap-4">
                <div class="size-11 rounded-xl overflow-hidden border border-border-dim shadow-xl">
                    <?php
                    if ($user['profile_type'] === 'none' || !$user['profile_image']) {
                        echo getLetterAvatar($user['name']);
                    } elseif ($user['profile_type'] === 'preset') {
                        echo '<img src="'.$user['profile_image'].'" class="size-full object-cover p-1.5 bg-surface">';
                    } else {
                        echo '<img src="../'.$user['profile_image'].'" class="size-full object-cover">';
                    }
                    ?>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-8">
            <div class="max-w-5xl mx-auto space-y-8 pb-12">

                <!-- Alert Feedback -->
                <?php if ($success): ?>
                    <div class="p-4 glass-panel border-primary/30 rounded-2xl flex items-center gap-4 animate-fade-in">
                        <div class="size-10 rounded-xl bg-primary/20 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined text-2xl">verified</span>
                        </div>
                        <p class="text-xs font-mono text-primary uppercase tracking-widest"><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-4 glass-panel border-red-500/30 rounded-2xl flex items-center gap-4 animate-fade-in">
                        <div class="size-10 rounded-xl bg-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                            <span class="material-symbols-outlined text-2xl">warning</span>
                        </div>
                        <p class="text-xs font-mono text-red-500 uppercase tracking-widest"><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    
                    <!-- Identity Section -->
                    <div class="glass-panel rounded-[3rem] p-10 md:p-14 relative overflow-hidden group elite-border">
                        <div class="absolute top-0 right-0 p-16 opacity-5 pointer-events-none group-hover:scale-110 group-hover:opacity-10 transition-all duration-700">
                            <span class="material-symbols-outlined text-[15rem]">account_circle</span>
                        </div>

                        <div class="flex flex-col lg:flex-row items-center lg:items-start gap-16 relative z-10">
                            
                            <!-- Large Avatar Preview -->
                            <div class="shrink-0">
                                <div class="size-56 rounded-[3.5rem] overflow-hidden border-2 border-primary/30 p-2.5 bg-bg-dark shadow-glow-heavy transition-all duration-500 hover:rotate-2 hover:scale-105">
                                    <div id="preview-container" class="size-full rounded-[2.8rem] overflow-hidden bg-surface relative">

                                        <?php
                                        if ($user['profile_type'] === 'none' || !$user['profile_image']) {
                                            echo getLetterAvatar($user['name']);
                                        } elseif ($user['profile_type'] === 'preset') {
                                            echo '<img src="'.$user['profile_image'].'" class="size-full object-cover p-4">';
                                        } else {
                                            echo '<img src="../'.$user['profile_image'].'" class="size-full object-cover">';
                                        }
                                        ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-bg-dark/40 to-transparent pointer-events-none"></div>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-center gap-2">
                                    <div class="px-3 py-1 bg-primary/10 border border-primary/20 rounded-full text-[9px] font-black text-primary uppercase tracking-widest">Active Alias</div>
                                </div>
                            </div>

                            <div class="flex-1 w-full space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                    <div class="space-y-4">
                                        <label class="text-[11px] font-black text-primary uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">terminal</span>
                                            Operator Alias
                                        </label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-primary/40 text-xl">badge</span>
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                                class="w-full form-input rounded-2xl pl-14 pr-7 py-5 text-base font-bold text-white shadow-inner">
                                        </div>
                                    </div>
                                    <div class="space-y-4 opacity-70">
                                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">alternate_email</span>
                                            Encrypted Relay
                                        </label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 text-xl">mail</span>
                                            <input type="email" disabled value="<?php echo htmlspecialchars($user['email']); ?>"
                                                class="w-full form-input rounded-2xl pl-14 pr-7 py-5 text-base font-bold cursor-not-allowed">
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 pt-2">
                                    <label class="text-[11px] font-black text-primary uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">description</span>
                                        Operator Briefing (Bio)
                                    </label>
                                    <textarea name="bio" rows="2" placeholder="Describe your specialization..."
                                        class="w-full form-input rounded-2xl px-6 py-4 text-sm font-medium text-slate-300 resize-none h-24"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>


                                <!-- Avatar Type Selection -->
                                <div class="space-y-4">
                                    <label class="text-[10px] font-black text-primary uppercase tracking-[0.25em] ml-1">Visual Identity Protocol</label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="profile_type" value="none" class="sr-only peer" <?php echo ($user['profile_type'] === 'none') ? 'checked' : ''; ?>>
                                            <div class="p-4 rounded-2xl glass-panel text-center transition-all peer-checked:bg-primary/10 peer-checked:border-primary group-hover:border-primary/50 relative overflow-hidden">
                                                <div class="absolute inset-0 bg-primary/5 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                <span class="material-symbols-outlined text-2xl block mb-2 text-slate-500 peer-checked:text-primary transition-colors">abc</span>
                                                <span class="text-[10px] font-black uppercase text-slate-500 peer-checked:text-white transition-colors">Letter Fallback</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="profile_type" value="preset" class="sr-only peer" <?php echo ($user['profile_type'] === 'preset') ? 'checked' : ''; ?>>
                                            <div class="p-4 rounded-2xl glass-panel text-center transition-all peer-checked:bg-primary/10 peer-checked:border-primary group-hover:border-primary/50 relative overflow-hidden">
                                                <div class="absolute inset-0 bg-primary/5 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                <span class="material-symbols-outlined text-2xl block mb-2 text-slate-500 peer-checked:text-primary transition-colors">smart_toy</span>
                                                <span class="text-[10px] font-black uppercase text-slate-500 peer-checked:text-white transition-colors">Neural Presets</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="profile_type" value="custom" class="sr-only peer" <?php echo ($user['profile_type'] === 'custom') ? 'checked' : ''; ?>>
                                            <div class="p-4 rounded-2xl glass-panel text-center transition-all peer-checked:bg-primary/10 peer-checked:border-primary group-hover:border-primary/50 relative overflow-hidden">
                                                <div class="absolute inset-0 bg-primary/5 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                <span class="material-symbols-outlined text-2xl block mb-2 text-slate-500 peer-checked:text-primary transition-colors">upload_file</span>
                                                <span class="text-[10px] font-black uppercase text-slate-500 peer-checked:text-white transition-colors">File Upload</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Presets Sub-section -->
                        <div id="presets-section" class="mt-12 pt-10 border-t border-border-dim animate-fade-in <?php echo ($user['profile_type'] === 'preset') ? '' : 'hidden'; ?>">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-black uppercase tracking-widest text-white">Available neural signifiers</h3>
                                <p class="text-[9px] font-mono text-primary/50 uppercase tracking-widest italic">Source: DICEBEAR_OS_V7</p>
                            </div>
                            <div class="flex gap-6 overflow-x-auto pb-4 custom-scrollbar snap-x">
                                <?php foreach ($presets as $p): ?>
                                    <label class="shrink-0 cursor-pointer group snap-start">
                                        <input type="radio" name="preset_icon" value="<?php echo $p; ?>" class="sr-only peer" <?php echo ($user['profile_image'] === $p) ? 'checked' : ''; ?>>
                                        <div class="size-28 rounded-3xl glass-panel p-3 transition-all peer-checked:bg-primary/20 peer-checked:border-primary peer-checked:scale-110 peer-checked:rotate-3 group-hover:brightness-125 hover:shadow-[0_0_30px_-10px_#a0f000]">
                                            <img src="<?php echo $p; ?>" class="size-full object-contain">
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Upload Sub-section -->
                        <div id="upload-section" class="mt-12 pt-10 border-t border-border-dim animate-fade-in <?php echo ($user['profile_type'] === 'custom') ? '' : 'hidden'; ?>">
                            <h3 class="text-xs font-black uppercase tracking-widest text-white mb-6">System binary ingestion</h3>
                            <div class="relative group border-2 border-dashed border-border-dim rounded-3xl p-8 text-center hover:border-primary/30 hover:bg-primary/5 transition-all">
                                <input type="file" name="custom_image" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="size-14 rounded-2xl bg-surface flex items-center justify-center text-slate-600 group-hover:text-primary transition-all group-hover:scale-110">
                                        <span class="material-symbols-outlined text-3xl">cloud_upload</span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs font-black text-white uppercase tracking-tight">Access Local Storage</p>
                                        <p id="file-status" class="text-[9px] text-primary font-mono uppercase tracking-[0.1em]">Ready for stream ingestion...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12">
                        <div class="mt-14 flex justify-center">
                            <button type="submit" name="update_profile" class="btn-elite w-full max-w-sm bg-primary text-neutral-dark font-black py-4 rounded-2xl uppercase tracking-[0.3em] shadow-glow hover:shadow-glow-heavy hover:scale-[1.05] active:scale-[0.98] transition-all flex items-center justify-center gap-3 text-xs">
                                <span class="material-symbols-outlined text-xl font-black">sync_alt</span>
                                Synchronize Node Identity
                            </button>
                        </div>
                    </div>

                <!-- Essential Settings Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Security Sector -->
                    <div class="glass-panel rounded-[3rem] p-10 space-y-8 relative overflow-hidden group elite-border">
                        <div class="absolute top-0 right-0 p-10 opacity-5 text-secondary pointer-events-none group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[10rem]">verified_user</span>
                        </div>
                        
                        <div class="flex items-center gap-5 relative z-10">
                            <div class="size-16 rounded-[1.5rem] bg-secondary/10 flex items-center justify-center text-secondary border border-secondary/20 shadow-glow-cyan">
                                <span class="material-symbols-outlined text-3xl">security</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-widest text-white">Security Sector</h3>
                                <p class="text-[10px] font-mono text-secondary uppercase tracking-[0.2em]">Hardening Protocols</p>
                            </div>
                        </div>

                        <div class="space-y-6 relative z-10 pt-4">
                            <div class="flex items-center justify-between p-6 bg-surface-light/50 border border-border-dim rounded-3xl hover:border-secondary/30 transition-all group/item">
                                <div class="flex items-center gap-4">
                                    <span class="material-symbols-outlined text-slate-500 group-hover/item:text-secondary transition-colors">vibration</span>
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-widest text-white">Multi-Factor Auth</p>
                                        <p class="text-[9px] text-slate-500 uppercase tracking-tighter">Enhanced biometric verification</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="mfa_enabled" <?php echo ($user['mfa_enabled']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-6 bg-surface-light/50 border border-border-dim rounded-3xl hover:border-secondary/30 transition-all group/item">
                                <div class="flex items-center gap-4">
                                    <span class="material-symbols-outlined text-slate-500 group-hover/item:text-secondary transition-colors">notifications_active</span>
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-widest text-white">Login Alerts</p>
                                        <p class="text-[9px] text-slate-500 uppercase tracking-tighter">Notify on unauthorized access</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="login_alerts_enabled" <?php echo ($user['login_alerts_enabled']) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="w-full bg-surface-light hover:bg-secondary/10 text-slate-400 hover:text-white font-black py-4 rounded-2xl uppercase tracking-[0.2em] text-[10px] transition-all border border-border-dim hover:border-secondary/30 relative z-10">
                            Update Security Schema
                        </button>
                    </div>

                    <!-- Session Management -->
                    <div class="glass-panel rounded-[3rem] p-10 space-y-8 relative overflow-hidden group elite-border">
                        <div class="absolute top-0 right-0 p-10 opacity-5 text-primary pointer-events-none group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[10rem]">devices</span>
                        </div>

                        <div class="flex items-center gap-5 relative z-10">
                            <div class="size-16 rounded-[1.5rem] bg-primary/10 flex items-center justify-center text-primary border border-primary/20 shadow-glow">
                                <span class="material-symbols-outlined text-3xl">hub</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-widest text-white">Session Matrix</h3>
                                <p class="text-[10px] font-mono text-primary uppercase tracking-[0.2em]">Active Connections</p>
                            </div>
                        </div>

                        <div class="space-y-4 relative z-10 pt-4">
                            <!-- Current Session -->
                            <div class="flex items-center justify-between p-5 bg-primary/5 border border-primary/20 rounded-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="size-10 rounded-xl bg-primary/20 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined text-xl">laptop_mac</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-white uppercase tracking-wider">Windows NT 10.0 (Current)</p>
                                        <p class="text-[9px] text-primary/60 font-mono"><?php echo $user_ip; ?> // ID: <?php echo substr(session_id(), 0, 8); ?></p>
                                    </div>
                                </div>
                                <span class="text-[8px] font-black bg-primary/20 text-primary px-2 py-1 rounded uppercase tracking-widest">Active</span>
                            </div>

                            <!-- Simulated Session -->
                            <div class="flex items-center justify-between p-5 bg-surface-light/30 border border-border-dim rounded-2xl opacity-60 hover:opacity-100 transition-opacity">
                                <div class="flex items-center gap-4">
                                    <div class="size-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-500">
                                        <span class="material-symbols-outlined text-xl">smartphone</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Mobile Operative (iPhone)</p>
                                        <p class="text-[9px] text-slate-600 font-mono">192.168.1.45 // ID: d8a2b3c4</p>
                                    </div>
                                </div>
                                <button class="text-red-500 hover:text-red-400">
                                    <span class="material-symbols-outlined text-lg">logout</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button class="w-full text-red-400 hover:text-red-300 font-black text-[9px] uppercase tracking-[0.3em] py-2 flex items-center justify-center gap-2 hover:bg-red-500/5 rounded-xl transition-all">
                                <span class="material-symbols-outlined text-sm font-black text-red-500">bolt</span>
                                Terminate All Remote Sessions
                            </button>
                        </div>
                    </div>
                </div>
                </form>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch pt-4">
                    
                    <!-- Password Update -->
                    <form method="POST" class="lg:col-span-8 glass-panel rounded-[3rem] p-12 space-y-10 relative overflow-hidden group elite-border">
                        <div class="absolute top-0 right-0 p-10 opacity-5 text-red-500 pointer-events-none">
                            <span class="material-symbols-outlined text-[12rem]">vpn_key</span>
                        </div>
                        
                        <div class="flex items-center gap-6 relative z-10">
                            <div class="size-16 rounded-[1.5rem] bg-red-500/10 flex items-center justify-center text-red-500 border border-red-500/20 shadow-glow-red">
                                <span class="material-symbols-outlined text-3xl">key</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-widest text-white">Encryption Key Rotation</h3>
                                <p class="text-[10px] font-mono text-red-500 uppercase tracking-[0.2em]">High Security Clearance Required</p>
                            </div>
                        </div>

                        <div class="space-y-8 relative z-10">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">lock_open</span>
                                        Current Secret
                                    </label>
                                    <input type="password" name="current_password" required placeholder="••••••••"
                                        class="w-full form-input rounded-2xl px-6 py-5 text-base font-bold tracking-[0.3em] focus:tracking-normal">
                                </div>
                                <div class="hidden md:flex items-center p-6 bg-red-500/5 border border-red-500/10 rounded-3xl text-[10px] text-red-400/80 uppercase leading-relaxed tracking-wider">
                                    <span class="material-symbols-outlined mr-3 text-red-500">info</span>
                                    Rotation of global encryption keys requires re-authentication of all active nodes.
                                </div>
                            </div>
                            
                            <div class="h-px bg-white/5 w-full"></div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <label class="text-[11px] font-black text-primary uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">add_moderator</span>
                                        New System Passphrase
                                    </label>
                                    <input type="password" name="new_password" required placeholder="New Entry"
                                        class="w-full form-input rounded-2xl px-6 py-5 text-base font-bold tracking-[0.3em] focus:tracking-normal border-primary/20">
                                </div>
                                <div class="space-y-4">
                                    <label class="text-[11px] font-black text-primary uppercase tracking-[0.3em] ml-1 flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">verified</span>
                                        Verify Passphrase
                                    </label>
                                    <input type="password" name="confirm_password" required placeholder="Verify Entry"
                                        class="w-full form-input rounded-2xl px-6 py-5 text-base font-bold tracking-[0.3em] focus:tracking-normal border-primary/20">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_password" class="w-full bg-surface-light hover:bg-white/10 text-white font-black py-5 rounded-2xl uppercase tracking-[0.3em] text-[11px] transition-all border border-border-dim hover:border-primary/30 relative z-10 mt-4 active:scale-[0.98]">
                            Apply Global Encryption Update
                        </button>
                    </form>

                    <!-- Account Deletion / Data Purge -->
                    <div class="lg:col-span-4 glass-panel rounded-[3rem] p-10 flex flex-col justify-between border-red-500/20 hover:border-red-500/40 transition-all group overflow-hidden relative">
                        <div class="absolute inset-0 bg-red-500/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        
                        <div class="space-y-8 relative z-10">
                            <div class="flex items-center gap-4">
                                <div class="size-14 rounded-2xl bg-red-500/10 flex items-center justify-center text-red-500">
                                    <span class="material-symbols-outlined text-3xl font-black">skull</span>
                                </div>
                                <h3 class="text-sm font-black uppercase tracking-[0.25em] text-red-500">Data Purge</h3>
                            </div>

                            <p class="text-[10px] text-slate-500 font-medium leading-relaxed uppercase tracking-tighter">
                                Warning: This action will permanently decommission this node and erase all operational history from the central mainframe.
                            </p>

                            <div class="space-y-3 pt-4">
                                <div class="h-1 bg-neutral-dark rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500/40 w-full animate-pulse"></div>
                                </div>
                                <p class="text-[8px] font-mono text-red-500/60 uppercase tracking-widest text-center">Status: Destructive Action Armed</p>
                            </div>
                        </div>

                        <button onclick="confirm('Permanent Deletion Protocol: Are you sure you wish to purge all node data?') && alert('Destruction scheduled.')" 
                            class="w-full bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-neutral-dark font-black py-4 rounded-2xl uppercase tracking-[0.2em] text-[10px] transition-all border border-red-500/30 mt-10 active:scale-[0.95]">
                            Initiate Core Purge
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- System Footer Bar -->
        <footer class="h-10 bg-neutral-dark border-t border-border-dim flex items-center justify-between px-8 z-30 shrink-0">
            <div class="flex items-center gap-6 text-[9px] font-mono text-slate-500 uppercase tracking-widest">
                <div class="flex items-center gap-2">
                    <span class="text-primary font-black">//_SYS:</span>
                    <span>ONLINE</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-primary font-black">//_LATENCY:</span>
                    <span>24ms</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-primary font-black">//_BUFF:</span>
                    <span>NOMINAL</span>
                </div>
            </div>
            <div class="text-[9px] font-mono text-slate-500 uppercase tracking-widest font-black">
                CyberShield Operations Center @ <?php echo date('Y'); ?>
            </div>
        </footer>
    </main>

    <script>
        // State Management
        const profileTypes = document.querySelectorAll('input[name="profile_type"]');
        const presetSection = document.getElementById('presets-section');
        const uploadSection = document.getElementById('upload-section');
        const previewContainer = document.getElementById('preview-container');
        const nameInput = document.querySelector('input[name="name"]');
        const fileInput = document.querySelector('input[name="custom_image"]');
        const fileStatus = document.getElementById('file-status');

        function updatePreview() {
            const selectedType = document.querySelector('input[name="profile_type"]:checked').value;
            const name = nameInput.value || 'U';
            
            if (selectedType === 'none') {
                const initial = name.trim().charAt(0).toUpperCase();
                // We use matching style to the PHP helper
                previewContainer.innerHTML = `<div class="size-full flex items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5 text-primary font-black text-4xl border border-primary/20 rounded-2xl uppercase tracking-tighter animate-fade-in">${initial}</div>`;
            } else if (selectedType === 'preset') {
                const selectedPreset = document.querySelector('input[name="preset_icon"]:checked')?.value;
                if (selectedPreset) {
                    previewContainer.innerHTML = `<img src="${selectedPreset}" class="size-full object-cover p-4 animate-fade-in">`;
                }
            }
        }

        profileTypes.forEach(radio => {
            radio.addEventListener('change', () => {
                presetSection.classList.toggle('hidden', radio.value !== 'preset');
                uploadSection.classList.toggle('hidden', radio.value !== 'custom');
                updatePreview();
            });
        });

        // Listen for preset clicks
        document.querySelectorAll('input[name="preset_icon"]').forEach(p => {
            p.addEventListener('change', updatePreview);
        });

        // Live name update for letter fallback
        nameInput.addEventListener('input', () => {
             if (document.querySelector('input[name="profile_type"]:checked').value === 'none') {
                 updatePreview();
             }
        });

        // Handle file browse preview
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                fileStatus.innerText = "Target Ingested: " + this.files[0].name.toUpperCase();
                fileStatus.classList.replace('text-primary/50', 'text-primary');
                
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="size-full object-cover animate-fade-in rounded-[2.2rem]">`;
                }
                reader.readAsDataURL(this.files[0]);
                
                // Force custom type selection
                document.querySelector('input[name="profile_type"][value="custom"]').checked = true;
                presetSection.classList.add('hidden');
                uploadSection.classList.remove('hidden');
            }
        });

        // Live Zulu Clock
        function updateClock() {
            const now = new Date();
            const hours = String(now.getUTCHours()).padStart(2, '0');
            const minutes = String(now.getUTCMinutes()).padStart(2, '0');
            const seconds = String(now.getUTCSeconds()).padStart(2, '0');
            document.getElementById('live-clock').innerText = `${hours}:${minutes}:${seconds} ZULU`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Smooth reveal on load
        window.addEventListener('load', () => {
            document.querySelector('main').classList.add('animate-fade-in');
        });
    </script>
</body>
</html>
