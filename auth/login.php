<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$error = "";

// If already logged in → go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, "@gmail.com")) {
        $error = "Invalid email format!";
    } else {

        // Use prepared statement
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../dashboard/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>CyberShield | Secure Access Terminal</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23a0f000'><path d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.47 4.34-3.1 8.25-7 9.53V12H5V6.3l7-3.11v8.8z'/></svg>">
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
    </style>

</head>

<body class="min-h-screen flex flex-col items-center justify-center p-4 font-display text-white">

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold uppercase tracking-widest text-primary">
            CyberShield
        </h1>
        <p id="typing" class="text-green-400 font-mono text-sm mt-2"></p>
        <p class="text-xs text-primary/70 font-mono mt-2 uppercase">
            Secure Access Terminal
        </p>
    </div>

    <main class="max-w-[420px] w-full px-4 sm:px-0">

        <div class="bg-card-dark/90 border border-white/10 rounded-xl p-8 shadow-2xl">

            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold mb-2">Login</h2>
                <p class="text-slate-400 text-sm">Initialize encrypted handshake to proceed</p>

                <?php if (!empty($error)): ?>
                    <p class="text-red-500 text-sm mt-3"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" class="space-y-5">

                <div>
                    <label class="text-xs text-primary uppercase">Email</label>
                    <input name="email" required type="email"
                        class="w-full mt-2 bg-background-dark/50 border border-white/10 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-primary"
                        placeholder="Enter your email">
                </div>

                <div class="relative">
                    <label class="text-xs text-primary uppercase">Password</label>

                    <input id="password" name="password" required type="password"
                        class="w-full mt-2 bg-background-dark/50 border border-white/10 rounded-lg py-3 px-4 pr-12 text-white focus:outline-none focus:border-primary"
                        placeholder="Enter your password">

                    <span onclick="togglePassword()"
                        class="material-symbols-outlined absolute right-4 top-[38px] cursor-pointer text-slate-400 hover:text-primary">
                        visibility
                    </span>
                </div>
                <div class="text-right text-sm mt-2">
                    <a href="forgot-password.php" class="text-primary hover:underline">
                        Forgot Password?
                    </a>
                </div>
                <button name="login" type="submit"
                    class="w-full bg-primary hover:bg-primary/90 text-black font-bold py-3 rounded-lg uppercase tracking-widest transition-all">
                    Initialize Connection
                </button>

            </form>

            <div class="mt-6 text-center text-sm text-slate-400">
                New operator?
                <a href="register.php" class="text-primary hover:underline ml-1">
                    Register Here
                </a>
            </div>

        </div>

    </main>
    <script>
        function togglePassword() {
            const input = document.getElementById("password");

            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
    </script>
    <script>
        const text = "Establishing Secure Connection...";
        let i = 0;

        function typeWriter() {
            if (i < text.length) {
                document.getElementById("typing").innerHTML += text.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        }

    </script>
    <!-- Scroll Buttons -->
    <div class="fixed bottom-8 right-8 flex flex-col gap-3 z-[100]">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="size-10 rounded-full bg-card-dark border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-black transition-all shadow-glow group">
            <span class="material-symbols-outlined text-sm group-hover:animate-bounce">arrow_upward</span>
        </button>
        <button onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" class="size-10 rounded-full bg-card-dark border border-primary/30 text-primary flex items-center justify-center hover:bg-primary hover:text-black transition-all shadow-glow group">
            <span class="material-symbols-outlined text-sm group-hover:animate-bounce">arrow_downward</span>
        </button>
    </div>
</body>

</html>