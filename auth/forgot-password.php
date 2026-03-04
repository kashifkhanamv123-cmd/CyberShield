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

$message = "";
$messageType = ""; // "success" or "error"
$submitted = false;

if (isset($_POST['reset'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "error";
    } else {
        // Always show the same message to prevent email enumeration
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token  = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
            $update->bind_param("sss", $token, $expiry, $email);
            $update->execute();
            $update->close();

            // ──────────────────────────────────────────────────────
            // In a real deployment, send an email here.
            // For local dev, we expose the link directly.
            // ──────────────────────────────────────────────────────
            $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host      = $_SERVER['HTTP_HOST'];
            $basePath  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $resetLink = $proto . '://' . $host . $basePath . '/auth/reset-password.php?token=' . $token;

            // Store link and token in session just for demo display
            $_SESSION['demo_reset_link']  = $resetLink;
            $_SESSION['demo_reset_token'] = $token;
        }

        $stmt->close();
        $submitted = true;
        $message = "If an account with that email exists, a password reset link has been generated below.";
        $messageType = "success";
    }
}

$demoLink  = $_SESSION['demo_reset_link']  ?? null;
$demoToken = $_SESSION['demo_reset_token'] ?? null;
if ($submitted) {
    unset($_SESSION['demo_reset_link'], $_SESSION['demo_reset_token']);
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Forgot Password</title>

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

        .link-box {
            word-break: break-all;
            background: rgba(160, 240, 0, 0.05);
            border: 1px solid rgba(160, 240, 0, 0.25);
            border-radius: 8px;
            padding: 10px 12px;
            font-family: monospace;
            font-size: 0.72rem;
            color: #a0f000;
            margin-top: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .link-box:hover {
            background: rgba(160, 240, 0, 0.12);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4 font-display text-white">

    <!-- Logo / Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold uppercase tracking-widest text-primary">
            CyberShield
        </h1>
        <p class="text-xs text-primary/70 font-mono mt-2 uppercase">
            Password Recovery Protocol
        </p>
    </div>

    <main class="max-w-[420px] w-full">

        <div class="bg-card-dark/90 border border-white/10 rounded-xl p-8 shadow-2xl animate-pulse-border">

            <!-- Icon -->
            <div class="flex justify-center mb-5">
                <div class="w-14 h-14 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-3xl">lock_reset</span>
                </div>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold mb-1">Forgot Password?</h2>
                <p class="text-slate-400 text-sm">Enter your registered email to receive a reset link.</p>
            </div>

            <?php if (!$submitted): ?>
                <!-- Request Form -->
                <form method="POST" class="space-y-5">

                    <?php if (!empty($message) && $messageType === 'error'): ?>
                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg px-4 py-3 text-red-400 text-sm fade-in">
                            <span class="material-symbols-outlined text-sm align-middle mr-1">error</span>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="text-xs text-primary uppercase tracking-wide">Registered Email</label>
                        <div class="relative mt-2">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">mail</span>
                            <input name="email" required type="email"
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-3 pl-10 pr-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                placeholder="you@gmail.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <button name="reset" type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        Send Reset Link
                    </button>

                </form>

            <?php else: ?>
                <!-- Success State -->
                <div class="fade-in space-y-4">
                    <div class="bg-primary/10 border border-primary/30 rounded-lg px-4 py-4 text-center">
                        <span class="material-symbols-outlined text-primary text-3xl block mb-2">mark_email_read</span>
                        <p class="text-sm text-slate-300"><?php echo $message; ?></p>
                    </div>

                    <?php if ($demoLink): ?>
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wide mb-1">
                                <span class="material-symbols-outlined text-[13px] align-middle text-yellow-400">warning</span>
                                Dev Mode — Reset Link (click to copy)
                            </p>
                            <div class="link-box" id="resetLink" onclick="copyLink()" title="Click to copy">
                                <?php echo htmlspecialchars($demoLink); ?>
                            </div>
                            <p class="text-xs text-slate-600 mt-1 text-center" id="copyMsg"></p>
                        </div>
                    <?php endif; ?>

                    <a href="reset-password.php?token=<?php echo htmlspecialchars($demoToken ?? ''); ?>"
                        class="<?php echo $demoToken ? '' : 'hidden'; ?> w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all flex items-center justify-center gap-2 mt-2">
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        Go to Reset Page
                    </a>

                    <a href="forgot-password.php"
                        class="w-full border border-white/10 hover:border-primary/40 text-slate-400 hover:text-primary py-3 rounded-lg uppercase tracking-widest text-sm font-semibold transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">refresh</span>
                        Try Another Email
                    </a>
                </div>
            <?php endif; ?>

        </div>

        <!-- Back to login -->
        <p class="text-center text-sm text-slate-500 mt-5">
            Remember your password?
            <a href="login.php" class="text-primary hover:underline ml-1">Back to Login</a>
        </p>

    </main>

    <script>
        function copyLink() {
            const text = document.getElementById('resetLink').innerText.trim();
            navigator.clipboard.writeText(text).then(() => {
                document.getElementById('copyMsg').textContent = '✓ Copied to clipboard!';
                setTimeout(() => document.getElementById('copyMsg').textContent = '', 2000);
            });
        }
    </script>

</body>

</html>