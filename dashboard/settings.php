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
$stmt = $conn->prepare("SELECT name, email, profile_type, profile_image FROM users WHERE id = ?");
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
        $update = $conn->prepare("UPDATE users SET name = ?, profile_type = ?, profile_image = ? WHERE id = ?");
        $update->bind_param("sssi", $newName, $profile_type, $profile_image, $user_id);
        if ($update->execute()) {
            $success = "Directive executed: Profile parameters synchronized.";
            $_SESSION['user_name'] = $newName;
            $user['name'] = $newName;
            $user['profile_type'] = $profile_type;
            $user['profile_image'] = $profile_image;
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
                        "neutral-dark": "#0d0f0a",
                        "surface": "#161810",
                        "border-dim": "#2a2e21",
                        "bg-dark": "#080906"
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: theme('colors.bg-dark');
            font-family: 'Inter', sans-serif;
            color: #fff;
        }
        .glass-panel {
            background: rgba(22, 24, 16, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
        .form-input {
            background: rgba(13, 15, 10, 0.5);
            border: 1px solid theme('colors.border-dim');
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: theme('colors.primary');
            box-shadow: 0 0 0 1px theme('colors.primary');
            outline: none;
        }
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
    <aside class="w-20 md:w-64 flex flex-col border-r border-border-dim bg-neutral-dark shrink-0 transition-all duration-300">
        <div class="h-20 flex items-center px-6 border-b border-border-dim">
            <div class="size-8 bg-primary rounded flex items-center justify-center shrink-0 shadow-[0_0_15px_-5px_#a0f000]">
                <span class="material-symbols-outlined text-neutral-dark text-xl font-bold">shield</span>
            </div>
            <span class="ml-3 font-black tracking-tighter uppercase text-xl md:block hidden italic text-white">Shield</span>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">dashboard</span>
                <span class="text-sm font-bold md:block hidden">Dashboard</span>
            </a>
            <div class="pt-4 pb-2 px-4">
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest md:block hidden">Configuration</p>
                <div class="h-px bg-border-dim w-full md:hidden"></div>
            </div>
            <div class="flex items-center gap-4 px-4 py-3 rounded-xl bg-primary/10 text-primary border border-primary/20 transition-all shadow-[inset_0_0_20px_-10px_#a0f000]">
                <span class="material-symbols-outlined text-xl">settings</span>
                <span class="text-sm font-bold md:block hidden">Profile Node</span>
            </div>
            <a href="../labs/ddos.php" class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-white/5 text-slate-400 hover:text-white transition-all group">
                <span class="material-symbols-outlined text-xl group-hover:text-primary">security</span>
                <span class="text-sm font-bold md:block hidden">Labs Access</span>
            </a>
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
        <header class="h-20 flex items-center justify-between px-8 bg-neutral-dark/50 backdrop-blur-md border-b border-border-dim shrink-0 z-10">
            <div class="flex flex-col">
                <h1 class="text-lg font-black uppercase tracking-tight text-white">Security <span class="text-primary italic">Node</span> Config</h1>
                <p class="text-[10px] font-mono text-slate-500 uppercase tracking-widest">Operator: <?php echo htmlspecialchars($user['name']); ?> // Port: 443</p>
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
                    <div class="glass-panel rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-12 opacity-5 pointer-events-none group-hover:scale-110 transition-transform duration-700">
                            <span class="material-symbols-outlined text-[10rem]">person_search</span>
                        </div>

                        <div class="flex flex-col lg:flex-row items-center lg:items-start gap-12 relative z-10">
                            
                            <!-- Large Avatar Preview -->
                            <div class="shrink-0">
                                <div class="size-48 rounded-[3rem] overflow-hidden border-2 border-primary/30 p-2 bg-bg-dark shadow-2xl transition-all duration-500 hover:rotate-2 hover:scale-105">
                                    <div id="preview-container" class="size-full rounded-[2.2rem] overflow-hidden bg-surface relative">
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-primary uppercase tracking-[0.25em] ml-1">Operator Profile Name</label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary/40 text-lg">badge</span>
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                                class="w-full form-input rounded-2xl pl-12 pr-6 py-4 text-sm font-bold text-white transition-all shadow-inner">
                                        </div>
                                    </div>
                                    <div class="space-y-3 opacity-60">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-[0.25em] ml-1">Encrypted Mail Relay</label>
                                        <div class="relative">
                                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-lg">alternate_email</span>
                                            <input type="email" disabled value="<?php echo htmlspecialchars($user['email']); ?>"
                                                class="w-full form-input rounded-2xl pl-12 pr-6 py-4 text-sm font-bold cursor-not-allowed">
                                        </div>
                                    </div>
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
                            <div class="relative group border-2 border-dashed border-border-dim rounded-[2rem] p-12 text-center hover:border-primary/30 hover:bg-primary/5 transition-all">
                                <input type="file" name="custom_image" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                                <div class="space-y-4">
                                    <div class="size-20 mx-auto rounded-[1.5rem] bg-surface flex items-center justify-center text-slate-600 group-hover:text-primary transition-all group-hover:scale-110">
                                        <span class="material-symbols-outlined text-5xl">cloud_upload</span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-black text-white uppercase tracking-tight">Access Local Storage</p>
                                        <p id="file-status" class="text-[10px] text-primary font-mono uppercase tracking-[0.2em]">Ready for stream ingestion...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12">
                            <button type="submit" name="update_profile" class="w-full bg-primary text-neutral-dark font-black py-5 rounded-3xl uppercase tracking-[0.25em] shadow-[0_15px_40px_-15px_#a0f000] hover:scale-[1.01] hover:brightness-110 active:scale-[0.99] transition-all flex items-center justify-center gap-3">
                                <span class="material-symbols-outlined font-black">sync_alt</span>
                                Synchronize Node Identity
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Footer Section: Security & Status -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    
                    <!-- Password Update -->
                    <form method="POST" class="lg:col-span-7 glass-panel rounded-[2.5rem] p-10 space-y-8 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-5 text-red-500">
                            <span class="material-symbols-outlined text-[8rem]">security</span>
                        </div>
                        
                        <div class="flex items-center gap-4 relative z-10">
                            <div class="size-12 rounded-2xl bg-red-500/10 flex items-center justify-center text-red-500 border border-red-500/20 shadow-lg">
                                <span class="material-symbols-outlined">key</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-white">Encryption Key Rotation</h3>
                                <p class="text-[9px] font-mono text-red-500 uppercase tracking-widest">High Security Sector</p>
                            </div>
                        </div>

                        <div class="space-y-6 relative z-10 pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Current Secret</label>
                                    <input type="password" name="current_password" required placeholder="••••••••"
                                        class="w-full form-input rounded-2xl px-6 py-4 text-sm font-bold tracking-[0.2em] focus:tracking-normal">
                                </div>
                                <div class="hidden md:block self-center p-4 bg-white/[0.02] border border-white/5 rounded-2xl text-[9px] text-slate-500 uppercase leading-relaxed tracking-tighter">
                                    Confirm authorization before modifying global encryption parameters.
                                </div>
                            </div>
                            
                            <div class="h-px bg-border-dim w-full opacity-50"></div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-primary uppercase tracking-widest ml-1">New System Passphrase</label>
                                    <input type="password" name="new_password" required placeholder="New Entry"
                                        class="w-full form-input rounded-2xl px-6 py-4 text-sm font-bold tracking-[0.2em] focus:tracking-normal border-primary/20">
                                </div>
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-primary uppercase tracking-widest ml-1">Verify Passphrase</label>
                                    <input type="password" name="confirm_password" required placeholder="Verify Entry"
                                        class="w-full form-input rounded-2xl px-6 py-4 text-sm font-bold tracking-[0.2em] focus:tracking-normal border-primary/20">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_password" class="w-full bg-surface hover:bg-white/10 text-white font-black py-4 rounded-2xl uppercase tracking-[0.2em] text-[10px] transition-all border border-border-dim hover:border-primary/30 relative z-10 mt-4 active:scale-[0.98]">
                            Apply Security Transformation
                        </button>
                    </form>

                    <!-- Node Metadata -->
                    <div class="lg:col-span-5 glass-panel rounded-[2.5rem] p-10 flex flex-col justify-between relative overflow-hidden group">
                        <div class="absolute inset-0 bg-primary/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        
                        <div class="space-y-8 relative z-10">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary text-2xl font-black">hub</span>
                                <h3 class="text-xs font-black uppercase tracking-[0.25em] text-white">Node Identity Metadata</h3>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-4 border-b border-border-dim">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Authorization</span>
                                    <span class="text-[10px] font-black text-primary uppercase tracking-[0.1em]"><?php echo $rank; ?></span>
                                </div>
                                <div class="flex justify-between items-center py-4 border-b border-border-dim">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Node IP</span>
                                    <span class="text-[10px] font-black text-white uppercase tracking-[0.1em]"><?php echo $user_ip; ?></span>
                                </div>
                                <div class="flex justify-between items-center py-4 border-b border-border-dim">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">System Time</span>
                                    <span id="live-clock" class="text-[10px] font-black text-white uppercase tracking-[0.1em]">Initializing...</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 rounded-[1.5rem] bg-neutral-dark/80 border border-border-dim mt-10 relative z-10">
                            <div class="flex gap-4">
                                <span class="material-symbols-outlined text-primary">analytics</span>
                                <p class="text-[10px] text-slate-400 font-medium leading-relaxed uppercase tracking-tighter">
                                    Shield Protocol V4.2 active. Access is monitored for anomalous behavioral patterns.
                                </p>
                            </div>
                        </div>
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
