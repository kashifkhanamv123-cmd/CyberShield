<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

$error   = "";
$success = false;

// Validate token from GET
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    $error = "NO_TOKEN";
} else {
    $token = trim($_GET['token']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "INVALID_TOKEN";
    } else {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle password update
if (!isset($error) || $error === "") {
    if (isset($_POST['update'])) {
        $password = $_POST['password'];
        $confirm  = $_POST['confirm_password'];

        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?");
            $update->bind_param("si", $hashed, $user['id']);
            $update->execute();
            $update->close();

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Reset Password</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#a0f000",
                        "background-dark": "#0d0f0a",
                        "card-dark": "#161810",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>

    <style>
        body {
            background: linear-gradient(rgba(13, 15, 10, 0.95), rgba(13, 15, 10, 0.95)),
                url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
        }

        @keyframes pulse-border {

            0%,
            100% {
                border-color: rgba(160, 240, 0, 0.2);
            }

            50% {
                border-color: rgba(160, 240, 0, 0.6);
            }
        }

        .animate-pulse-border {
            animation: pulse-border 2.5s ease-in-out infinite;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        /* Password strength bar */
        #strength-bar {
            transition: width 0.3s ease, background 0.3s ease;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4 font-display text-white">

    <!-- Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold uppercase tracking-widest text-primary">
            CyberShield
        </h1>
        <p class="text-xs text-primary/70 font-mono mt-2 uppercase">
            Secure Password Reset
        </p>
    </div>

    <main class="max-w-[420px] w-full">

        <div class="bg-card-dark/90 border border-white/10 rounded-xl p-8 shadow-2xl animate-pulse-border">

            <!-- Icon -->
            <div class="flex justify-center mb-5">
                <div class="w-14 h-14 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-3xl">
                        <?php echo ($error === 'NO_TOKEN' || $error === 'INVALID_TOKEN') ? 'gpp_bad' : ($success ? 'verified_user' : 'key'); ?>
                    </span>
                </div>
            </div>

            <?php if ($error === 'NO_TOKEN' || $error === 'INVALID_TOKEN'): ?>
                <!-- ── Invalid / expired token state ── -->
                <div class="text-center fade-in">
                    <h2 class="text-lg font-semibold mb-2 text-red-400">
                        <?php echo $error === 'NO_TOKEN' ? 'No Reset Token' : 'Link Expired or Invalid'; ?>
                    </h2>
                    <p class="text-slate-400 text-sm mb-6">
                        <?php echo $error === 'NO_TOKEN'
                            ? 'No reset token was provided in the URL.'
                            : 'This password reset link has expired or already been used. Reset links are valid for 1 hour.'; ?>
                    </p>
                    <a href="forgot-password.php"
                        class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">refresh</span>
                        Request New Link
                    </a>
                </div>

            <?php elseif ($success): ?>
                <!-- ── Success state ── -->
                <div class="text-center fade-in space-y-4">
                    <h2 class="text-lg font-semibold text-primary">Password Updated!</h2>
                    <div class="bg-primary/10 border border-primary/30 rounded-lg px-4 py-4">
                        <p class="text-sm text-slate-300">
                            Your password has been reset successfully. You can now log in with your new credentials.
                        </p>
                    </div>
                    <a href="login.php"
                        class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all flex items-center justify-center gap-2 mt-2">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Back to Login
                    </a>
                </div>

            <?php else: ?>
                <!-- ── Reset form ── -->
                <div class="text-center mb-6">
                    <h2 class="text-lg font-semibold mb-1">Set New Password</h2>
                    <p class="text-slate-400 text-sm">Choose a strong password to secure your account.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-red-400 text-sm mb-4 fade-in">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">error</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">

                    <!-- New Password -->
                    <div>
                        <label class="text-xs text-primary uppercase tracking-wide">New Password</label>
                        <div class="relative mt-2">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">lock</span>
                            <input id="password" name="password" required type="password" minlength="8"
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-3 pl-10 pr-12 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                placeholder="Min. 8 characters"
                                oninput="checkStrength(this.value)">
                            <span onclick="toggleVis('password', this)"
                                class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary select-none">
                                visibility
                            </span>
                        </div>
                        <!-- Strength bar -->
                        <div class="mt-2 h-1 bg-white/10 rounded-full overflow-hidden">
                            <div id="strength-bar" class="h-full rounded-full w-0 bg-red-500"></div>
                        </div>
                        <p id="strength-label" class="text-xs text-slate-500 mt-1"></p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="text-xs text-primary uppercase tracking-wide">Confirm Password</label>
                        <div class="relative mt-2">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">lock_clock</span>
                            <input id="confirm_password" name="confirm_password" required type="password"
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-3 pl-10 pr-12 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                placeholder="Re-enter password"
                                oninput="checkMatch()">
                            <span onclick="toggleVis('confirm_password', this)"
                                class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary select-none">
                                visibility
                            </span>
                        </div>
                        <p id="match-msg" class="text-xs mt-1"></p>
                    </div>

                    <button name="update" type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

        </div>

        <!-- Back to login -->
        <p class="text-center text-sm text-slate-500 mt-5">
            Remembered your password?
            <a href="login.php" class="text-primary hover:underline ml-1">Back to Login</a>
        </p>

    </main>

    <script>
        function toggleVis(id, icon) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        function checkStrength(val) {
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{
                    pct: '20%',
                    color: '#ef4444',
                    text: 'Very Weak'
                },
                {
                    pct: '40%',
                    color: '#f97316',
                    text: 'Weak'
                },
                {
                    pct: '60%',
                    color: '#eab308',
                    text: 'Fair'
                },
                {
                    pct: '80%',
                    color: '#84cc16',
                    text: 'Strong'
                },
                {
                    pct: '100%',
                    color: '#a0f000',
                    text: 'Very Strong'
                },
            ];

            const lvl = levels[Math.max(0, score - 1)] || levels[0];
            bar.style.width = val.length ? lvl.pct : '0%';
            bar.style.background = val.length ? lvl.color : '';
            label.textContent = val.length ? lvl.text : '';
            label.style.color = val.length ? lvl.color : '';

            checkMatch();
        }

        function checkMatch() {
            const pw = document.getElementById('password').value;
            const cp = document.getElementById('confirm_password').value;
            const msg = document.getElementById('match-msg');
            if (!cp) {
                msg.textContent = '';
                return;
            }
            if (pw === cp) {
                msg.textContent = '✓ Passwords match';
                msg.style.color = '#a0f000';
            } else {
                msg.textContent = '✗ Passwords do not match';
                msg.style.color = '#ef4444';
            }
        }
    </script>

</body>

</html>