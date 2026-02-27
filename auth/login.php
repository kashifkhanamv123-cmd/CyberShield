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

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

    if (mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
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
        <p class="text-xs text-primary/70 font-mono mt-2 uppercase">
            Secure Access Terminal
        </p>
    </div>

    <main class="max-w-[420px] w-full">

        <div class="bg-card-dark/90 border border-white/10 rounded-xl p-8 shadow-2xl">

            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold mb-2">Login</h2>
                <p class="text-slate-400 text-sm">Initialize encrypted handshake to proceed</p>

                <?php if (!empty($error)): ?>
                    <p class="text-red-500 text-sm mt-3"><?php echo $error; ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" class="space-y-5">

                <div>
                    <label class="text-xs text-primary uppercase">Email</label>
                    <input name="email" required type="email"
                        class="w-full mt-2 bg-background-dark/50 border border-white/10 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-primary"
                        placeholder="Enter your email">
                </div>

                <div>
                    <label class="text-xs text-primary uppercase">Password</label>
                    <input name="password" required type="password"
                        class="w-full mt-2 bg-background-dark/50 border border-white/10 rounded-lg py-3 px-4 text-white focus:outline-none focus:border-primary"
                        placeholder="Enter your password">
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

</body>

</html>