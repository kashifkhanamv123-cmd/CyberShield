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
        $profile_image = $_POST['preset_icon'];
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
            $success = "Profile updated successfully!";
            $_SESSION['user_name'] = $newName;
            $user['name'] = $newName;
            $user['profile_type'] = $profile_type;
            $user['profile_image'] = $profile_image;
        } else {
            $error = "Update failed.";
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
        $error = "Current password incorrect.";
    } elseif ($new_pwd !== $confirm_pwd) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed, $user_id);
        if ($update->execute()) {
            $success = "Password updated successfully!";
        } else {
            $error = "Password update failed.";
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
    <title>CyberShield | User Settings</title>
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
    </style>
</head>
<body class="bg-background-dark text-slate-300 min-h-screen terminal-grid">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar placeholder or simplified navigation -->
        <aside class="hidden md:flex flex-col w-64 border-r border-border-dim bg-neutral-dark/95 p-6 space-y-8">
            <div class="flex items-center gap-3 text-primary px-2">
                <span class="material-symbols-outlined text-3xl">shield_person</span>
                <h1 class="text-white text-xl font-black italic tracking-tighter uppercase">Cyber<span class="text-primary tracking-normal">Shield</span></h1>
            </div>
            <nav class="space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium hover:bg-white/5 transition-all text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold bg-primary/10 text-primary border-r-2 border-primary">
                    <span class="material-symbols-outlined">settings</span> Settings
                </a>
            </nav>
            <div class="mt-auto">
                <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-400 hover:bg-red-400/10 transition-all">
                    <span class="material-symbols-outlined">logout</span> Logout
                </a>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto p-4 md:p-12">
            <div class="max-w-4xl mx-auto space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-black text-white uppercase italic">Account <span class="text-primary glow-text">Settings</span></h2>
                        <p class="text-slate-400 text-sm mt-1">Manage your identity and security parameters.</p>
                    </div>
                    <a href="dashboard.php" class="md:hidden text-primary">
                         <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500 text-sm">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="p-4 bg-primary/10 border border-primary/20 rounded-xl text-primary text-sm">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Profile Update Form -->
                    <div class="glass-panel rounded-2xl p-8 space-y-6">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">person</span> Identity Profile
                        </h3>
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                    class="w-full bg-background-dark border border-border-dim rounded-xl py-3 px-4 focus:border-primary outline-none transition-all">
                            </div>

                            <div class="space-y-4">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Profile Picture Type</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="none" class="hidden peer" <?php echo $user['profile_type'] === 'none' ? 'checked' : ''; ?>>
                                        <div class="p-3 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-bold uppercase">None</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="preset" class="hidden peer" <?php echo $user['profile_type'] === 'preset' ? 'checked' : ''; ?>>
                                        <div class="p-3 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-bold uppercase">Preset</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="profile_type" value="custom" class="hidden peer" <?php echo $user['profile_type'] === 'custom' ? 'checked' : ''; ?>>
                                        <div class="p-3 text-center rounded-xl border border-border-dim group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary/10 transition-all">
                                            <span class="text-[10px] font-bold uppercase">Custom</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Presets -->
                            <div id="presets-container" class="<?php echo $user['profile_type'] === 'preset' ? '' : 'hidden'; ?> space-y-3">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Select Cyber Avatar</label>
                                <div class="grid grid-cols-4 gap-4">
                                    <?php
                                    $presets = [
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=Cyber1',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=Cyber2',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=Cyber3',
                                        'https://api.dicebear.com/7.x/bottts-neutral/svg?seed=Cyber4'
                                    ];
                                    foreach ($presets as $url):
                                    ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="preset_icon" value="<?php echo $url; ?>" class="hidden peer" <?php echo $user['profile_image'] === $url ? 'checked' : ''; ?>>
                                        <img src="<?php echo $url; ?>" class="size-full rounded-lg border-2 border-transparent peer-checked:border-primary bg-surface p-1">
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Custom Upload -->
                            <div id="custom-container" class="<?php echo $user['profile_type'] === 'custom' ? '' : 'hidden'; ?> space-y-3">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Upload Image</label>
                                <input type="file" name="custom_image" accept="image/*"
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            </div>

                            <button type="submit" name="update_profile" class="w-full py-4 bg-primary text-background-dark font-black rounded-xl hover:brightness-110 transition-all uppercase tracking-widest text-xs">
                                Save Profile Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password Update Form -->
                    <div class="glass-panel rounded-2xl p-8 space-y-6">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">lock</span> Security Credentials
                        </h3>
                        <form method="POST" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Current Password</label>
                                <input type="password" name="current_password" required
                                    class="w-full bg-background-dark border border-border-dim rounded-xl py-3 px-4 focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">New Password</label>
                                <input type="password" name="new_password" required
                                    class="w-full bg-background-dark border border-border-dim rounded-xl py-3 px-4 focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full bg-background-dark border border-border-dim rounded-xl py-3 px-4 focus:border-primary outline-none transition-all">
                            </div>

                            <button type="submit" name="update_password" class="w-full py-4 bg-white/5 border border-white/10 text-white font-black rounded-xl hover:bg-white/10 transition-all uppercase tracking-widest text-xs">
                                Update Security Key
                            </button>
                        </form>
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
    </script>
</body>
</html>
