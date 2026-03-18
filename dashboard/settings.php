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
    $initial = strtoupper(substr(trim($name), 0, 1));
    return '<div class="size-full flex items-center justify-center bg-gradient-to-br from-primary/20 to-primary/5 text-primary font-black text-2xl border border-primary/20 rounded-xl uppercase tracking-tighter">' . $initial . '</div>';
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
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Node Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#a0f000",
                        "background-dark": "#0a0c02",
                        "surface": "#12140a",
                        "neutral-dark": "#16190e",
                        "border-dim": "#23281b",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .terminal-grid {
            background-image: radial-gradient(circle, #a0f00011 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .glass-panel {
            background: rgba(18, 20, 10, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(160, 240, 0, 0.1);
        }
        .glow-text { text-shadow: 0 0 10px rgba(160, 240, 0, 0.5); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #23281b; border-radius: 10px; }
    </style>
</head>
<body class="bg-background-dark text-slate-300 min-h-screen terminal-grid custom-scrollbar overflow-x-hidden">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="hidden md:flex flex-col w-64 border-r border-border-dim bg-neutral-dark/95 p-6 space-y-8 shrink-0">
            <div class="flex items-center gap-3 text-primary px-2 transition-transform hover:scale-105 cursor-pointer">
                <span class="material-symbols-outlined text-3xl">shield_person</span>
                <h1 class="text-white text-xl font-black italic tracking-tighter uppercase">Cyber<span class="text-primary tracking-normal">Shield</span></h1>
            </div>
            <nav class="space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold bg-primary/10 text-primary border-r-2 border-primary">
                    <span class="material-symbols-outlined">settings</span> Config
                </a>
            </nav>
            <div class="mt-auto">
                <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-400 hover:bg-red-400/10 transition-all">
                    <span class="material-symbols-outlined">logout</span> Logoff
                </a>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto p-4 md:p-12 custom-scrollbar">
            <div class="max-w-4xl mx-auto space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-mono text-primary uppercase tracking-[0.3em]">Node: profile_config_01</span>
                        </div>
                        <h2 class="text-3xl font-black text-white uppercase italic">Analyst <span class="text-primary glow-text">Parameters</span></h2>
                        <p class="text-slate-400 text-sm mt-1">Reconfigure operator identity and security protocols.</p>
                    </div>
                    <a href="dashboard.php" class="md:hidden text-primary">
                         <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm font-mono">
                        [ERROR] :: <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl text-primary text-sm font-mono">
                        [SUCCESS] :: <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Profile Management -->
                    <div class="glass-panel rounded-2xl p-8 space-y-8">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">person</span> Identity
                            </h3>
                            <div class="size-16 rounded-2xl bg-surface border border-border-dim overflow-hidden shadow-lg shadow-primary/5">
                                <?php 
                                if ($user['profile_type'] === 'none' || !$user['profile_image']) {
                                    echo getLetterAvatar($user['name']);
                                } elseif ($user['profile_type'] === 'preset') {
                                    echo '<img src="'.$user['profile_image'].'" class="size-full object-cover p-2">';
                                } else {
                                    echo '<img src="../'.$user['profile_image'].'" class="size-full object-cover">';
                                }
                                ?>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">Operator Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                    class="w-full bg-background-dark/50 border border-border-dim rounded-xl py-3.5 px-4 focus:border-primary outline-none transition-all text-sm">
                            </div>

                            <div class="space-y-4">
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">Avatar Protocol</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="none" class="hidden peer" <?php echo $user['profile_type'] === 'none' ? 'checked' : ''; ?>>
                                        <div class="py-3 px-2 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-black uppercase tracking-widest">Fallback</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="preset" class="hidden peer" <?php echo $user['profile_type'] === 'preset' ? 'checked' : ''; ?>>
                                        <div class="py-3 px-2 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-black uppercase tracking-widest">Presets</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="custom" class="hidden peer" <?php echo $user['profile_type'] === 'custom' ? 'checked' : ''; ?>>
                                        <div class="py-3 px-2 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-black uppercase tracking-widest">Upload</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Preset Avatars -->
                            <div id="presets-container" class="<?php echo $user['profile_type'] === 'preset' ? '' : 'hidden'; ?> p-4 bg-background-dark/30 rounded-xl border border-white/5 space-y-3">
                                <label class="text-[10px] font-black uppercase text-slate-600 tracking-widest block">Select Visual Signature</label>
                                <div class="grid grid-cols-4 gap-4">
                                    <?php
                                    $presets = [
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield1',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield2',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield3',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=CyberShield4'
                                    ];
                                    foreach ($presets as $url):
                                    ?>
                                    <label class="cursor-pointer group relative">
                                        <input type="radio" name="preset_icon" value="<?php echo $url; ?>" class="hidden peer" <?php echo $user['profile_image'] === $url ? 'checked' : ''; ?>>
                                        <div class="size-full rounded-lg bg-surface p-1 border-2 border-transparent peer-checked:border-primary peer-checked:bg-primary/5 transition-all">
                                            <img src="<?php echo $url; ?>" class="size-full">
                                        </div>
                                        <div class="absolute -top-1 -right-1 size-4 bg-primary text-background-dark rounded-full items-center justify-center hidden peer-checked:flex">
                                            <span class="material-symbols-outlined text-[10px] font-black">check</span>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Custom File Upload -->
                            <div id="custom-container" class="<?php echo $user['profile_type'] === 'custom' ? '' : 'hidden'; ?> space-y-3">
                                <div class="relative group">
                                    <input type="file" name="custom_image" accept="image/*" id="file_input" class="hidden">
                                    <label for="file_input" class="flex flex-col items-center justify-center py-8 border-2 border-dashed border-border-dim rounded-xl hover:border-primary/50 hover:bg-primary/5 cursor-pointer transition-all">
                                        <span class="material-symbols-outlined text-3xl text-slate-500 mb-2">cloud_upload</span>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Select Image File</span>
                                        <span id="file_name_display" class="text-[9px] text-primary mt-2 hidden">No file selected</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" name="update_profile" class="w-full py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 shadow-lg shadow-primary/10 transition-all uppercase tracking-widest text-xs active:scale-[0.98]">
                                Synchronize Profile data
                            </button>
                        </form>
                    </div>

                    <!-- Security Management -->
                    <div class="glass-panel rounded-2xl p-8 space-y-6">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">lock_reset</span> Security
                        </h3>
                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">Current Secret Key</label>
                                <input type="password" name="current_password" required placeholder="••••••••"
                                    class="w-full bg-background-dark/50 border border-border-dim rounded-xl py-3.5 px-4 focus:border-primary outline-none transition-all text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">New Passphrase</label>
                                <input type="password" name="new_password" required placeholder="Min 8 characters"
                                    class="w-full bg-background-dark/50 border border-border-dim rounded-xl py-3.5 px-4 focus:border-primary outline-none transition-all text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest block">Confirm Transformation</label>
                                <input type="password" name="confirm_password" required placeholder="Repeat new passphrase"
                                    class="w-full bg-background-dark/50 border border-border-dim rounded-xl py-3.5 px-4 focus:border-primary outline-none transition-all text-sm">
                            </div>

                            <button type="submit" name="update_password" class="w-full py-4 bg-white/5 border border-white/10 text-white font-black rounded-xl hover:bg-white/10 transition-all uppercase tracking-[0.2em] text-[10px] active:scale-[0.98]">
                                Update Encryption Key
                            </button>
                        </form>

                        <div class="p-4 bg-surface rounded-xl border border-border-dim mt-4">
                            <div class="flex gap-3">
                                <span class="material-symbols-outlined text-yellow-500">warning</span>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-white uppercase">Security Notice</p>
                                    <p class="text-[9px] text-slate-500 leading-relaxed uppercase tracking-tighter">Changing your encryption key will invalidate all active session tokens on other devices.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('input[name="profile_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const val = e.target.value;
                document.getElementById('presets-container').classList.toggle('hidden', val !== 'preset');
                document.getElementById('custom-container').classList.toggle('hidden', val !== 'custom');
            });
        });

        const fileInput = document.getElementById('file_input');
        const fileNameDisplay = document.getElementById('file_name_display');
        if (fileInput) {
            fileInput.onchange = (e) => {
                if(e.target.files.length > 0) {
                    fileNameDisplay.innerText = "Selected: " + e.target.files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            };
        }
    </script>
</body>
</html>
