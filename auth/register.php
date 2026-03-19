<?php
require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/db.php";

$error = "";
$success = "";

// Global Registration Check
$r_res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'registration_enabled'");
$registration_enabled = ($r_res && $r_res->num_rows > 0) ? ($r_res->fetch_assoc()['setting_value'] === '1') : true;

if (isset($_POST['register'])) {
    if (!$registration_enabled) {
        $error = "Registration node is currently offline. Admission denied.";
    } else {
        $name     = trim($_POST['name']);
        $email    = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm  = $_POST['confirm_password'];
        $country  = trim($_POST['country'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format!";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match!";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO users (name, email, password, country, gender) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("sssss", $name, $email, $hashedPassword, $country, $gender);

                if ($insert->execute()) {
                    $success = "Registration successful! Initializing encrypted access...";
                } else {
                    $error = "Registration failed. Please try again or contact support.";
                }
                $insert->close();
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CyberShield | Register New Operator</title>

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
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(rgba(13, 15, 10, 0.95), rgba(13, 15, 10, 0.95)),
                url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=2070');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* reCAPTCHA Animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .recaptcha-spinner {
            border: 2px solid rgba(160, 240, 0, 0.1);
            border-left-color: #a0f000;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4 font-display text-white">

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold uppercase tracking-widest text-primary">
            CyberShield
        </h1>
        <p id="typing" class="text-green-400 font-mono text-sm mt-2"></p>
        <p class="text-xs text-primary/70 font-mono mt-2 uppercase">
            New Operator Registration
        </p>
    </div>

    <main class="max-w-[480px] w-full">
        <div class="bg-card-dark/90 border border-white/10 rounded-xl p-8 shadow-2xl backdrop-blur-md">

            <div class="text-center mb-8">
                <h2 class="text-lg font-semibold mb-1">Create Account</h2>
                <p class="text-slate-400 text-sm">Join the elite cybersecurity training platform</p>

                <?php if (!$registration_enabled): ?>
                    <div class="mt-4 p-4 bg-orange-500/10 border border-orange-500/20 rounded-xl flex items-center gap-3 animate-pulse">
                        <span class="material-symbols-outlined text-orange-500">lock</span>
                        <p class="text-orange-500 text-[10px] font-black uppercase tracking-widest text-left leading-relaxed">Admission Protocol Offline. New operator enrollment is currently restricted.</p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                        <p class="text-red-500 text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mt-4 p-3 bg-primary/10 border border-primary/20 rounded-lg">
                        <p class="text-primary text-sm font-medium"><?php echo htmlspecialchars($success); ?></p>
                        <script>
                            setTimeout(() => {
                                window.location.href = "login.php";
                            }, 3000);
                        </script>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="space-y-6 <?php echo !$registration_enabled ? 'opacity-40 pointer-events-none grayscale' : ''; ?>">

                <!-- Account Essentials -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-[10px] text-primary uppercase font-bold tracking-wider mb-1 block">Full Name</label>
                            <input type="text" name="name" required placeholder="John Doe"
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-2.5 px-4 focus:border-primary outline-none transition-all text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] text-primary uppercase font-bold tracking-wider mb-1 block">Email Address</label>
                            <input type="email" name="email" required placeholder="operator@cybershield.com"
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-2.5 px-4 focus:border-primary outline-none transition-all text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="text-[10px] text-primary uppercase font-bold tracking-wider mb-1 block">Password</label>
                            <input type="password" id="password" name="password" required
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-2.5 px-4 pr-10 focus:border-primary outline-none transition-all text-sm">
                            <span onclick="togglePassword('password', this)"
                                class="material-symbols-outlined absolute right-3 top-[32px] cursor-pointer text-slate-400 hover:text-primary text-lg">
                                visibility
                            </span>
                        </div>
                        <div class="relative">
                            <label class="text-[10px] text-primary uppercase font-bold tracking-wider mb-1 block">Confirm</label>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="w-full bg-background-dark/50 border border-white/10 rounded-lg py-2.5 px-4 pr-10 focus:border-primary outline-none transition-all text-sm">
                            <span onclick="togglePassword('confirm_password', this)"
                                class="material-symbols-outlined absolute right-3 top-[32px] cursor-pointer text-slate-400 hover:text-primary text-lg">
                                visibility
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden mt-1">
                            <div id="strengthBar" class="h-full bg-red-500 rounded-full transition-all duration-500 w-0"></div>
                        </div>
                        <p id="strengthText" class="text-[9px] text-slate-500 mt-1 uppercase tracking-tighter">Password Complexity: NULL</p>
                    </div>
                </div>

                <!-- Profile Metadata -->
                <div class="p-4 bg-white/[0.02] border border-white/5 rounded-xl space-y-4">
                    <h3 class="text-[11px] text-slate-400 font-bold uppercase border-b border-white/5 pb-2">Profile Details</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Country</label>
                            <input type="text" name="country" required placeholder="Pakistan"
                                class="w-full bg-background-dark/30 border border-white/10 rounded-lg py-2 px-3 focus:border-primary outline-none transition-all text-xs">
                        </div>
                        <div>
                            <label class="text-[10px] text-slate-500 uppercase font-bold mb-1 block">Gender</label>
                            <input type="text" name="gender" required placeholder="Identity"
                                class="w-full bg-background-dark/30 border border-white/10 rounded-lg py-2 px-3 focus:border-primary outline-none transition-all text-xs">
                        </div>
                    </div>
                </div>

                <!-- Captcha Placeholder -->
                <div class="bg-black/20 border border-white/5 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative flex items-center justify-center w-6 h-6">
                            <input type="checkbox" id="captchaCheck" required class="peer w-5 h-5 appearance-none border border-white/20 bg-background-dark rounded checked:bg-primary checked:border-primary transition-all cursor-pointer">
                            <span id="captchaIcon" class="material-symbols-outlined absolute pointer-events-none text-black text-sm font-bold opacity-0 peer-checked:opacity-100">check</span>
                            <div id="captchaLoader" class="recaptcha-spinner absolute inset-0 hidden"></div>
                        </div>
                        <span id="captchaText" class="text-xs text-slate-400">Verifying human operator...</span>
                    </div>
                    <img src="https://www.gstatic.com/recaptcha/api2/logo_48.png" alt="reCAPTCHA" class="w-6 h-6 opacity-40 grayscale">
                </div>

                <button id="submitBtn" name="register" type="submit" disabled
                    class="w-full bg-primary/20 text-black/50 font-bold py-3 rounded-lg uppercase tracking-widest transition-all shadow-lg active:scale-[0.98] cursor-not-allowed">
                    Initialize Operator Profile
                </button>

            </form>

            <div class="mt-8 text-center text-xs text-slate-500">
                Already registered in the system?
                <a href="login.php" class="text-primary hover:underline ml-1 font-semibold uppercase tracking-wider">Secure Login</a>
            </div>

        </div>
    </main>

    <script>
        // Typing Effect
        const typeText = "Establishing encrypted handshake...";
        let i = 0;

        function typeWriter() {
            if (i < typeText.length) {
                document.getElementById("typing").innerHTML += typeText.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        }
        window.onload = typeWriter;

        // Password Strength
        const passwordInput = document.getElementById("password");
        const bar = document.getElementById("strengthBar");
        const text = document.getElementById("strengthText");

        passwordInput.addEventListener("input", function() {
            const value = passwordInput.value;
            let score = 0;

            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            const states = [
                { width: '20%', color: 'bg-red-600', label: 'Critical' },
                { width: '40%', color: 'bg-orange-500', label: 'Weak' },
                { width: '60%', color: 'bg-yellow-500', label: 'Moderate' },
                { width: '80%', color: 'bg-blue-500', label: 'Secure' },
                { width: '100%', color: 'bg-primary', label: 'Max Security' }
            ];

            if (value.length === 0) {
                bar.style.width = '0%';
                text.innerText = "Complexity: NULL";
                return;
            }

            const state = states[Math.min(score, 4)];
            bar.style.width = state.width;
            bar.className = `h-full ${state.color} rounded-full transition-all duration-500`;
            text.innerText = `Complexity: ${state.label}`;
            text.className = `text-[9px] mt-1 uppercase tracking-tighter ${state.color.replace('bg-', 'text-')}`;
        });

        // Visibility Toggle
        function togglePassword(fieldId, icon) {
            const input = document.getElementById(fieldId);
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "visibility_off";
            } else {
                input.type = "password";
                icon.textContent = "visibility";
            }
        }

        // Animated reCAPTCHA
        const captchaCheck = document.getElementById('captchaCheck');
        const captchaIcon = document.getElementById('captchaIcon');
        const captchaLoader = document.getElementById('captchaLoader');
        const captchaText = document.getElementById('captchaText');
        const submitBtn = document.getElementById('submitBtn');

        captchaCheck.addEventListener('change', function() {
            if (this.checked) {
                this.classList.add('hidden');
                captchaLoader.classList.remove('hidden');
                captchaText.innerText = "Analyzing biometric patterns...";
                
                setTimeout(() => {
                    captchaLoader.classList.add('hidden');
                    captchaIcon.classList.remove('opacity-0');
                    captchaIcon.classList.add('opacity-100');
                    captchaCheck.classList.remove('hidden');
                    captchaCheck.checked = true;
                    captchaCheck.disabled = true;
                    captchaText.innerText = "Human verified";
                    captchaText.classList.replace('text-slate-400', 'text-primary');
                    
                    submitBtn.disabled = false;
                    submitBtn.classList.replace('bg-primary/20', 'bg-primary');
                    submitBtn.classList.replace('text-black/50', 'text-black');
                    submitBtn.classList.replace('cursor-not-allowed', 'cursor-pointer');
                }, 1800);
            }
        });
    </script>
</body>

</html>