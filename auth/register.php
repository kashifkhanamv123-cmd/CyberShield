<?php
require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/db.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format!";
}
elseif ($password !== $confirm) {
    $error = "Passwords do not match!";
}
else {

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered!";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $name, $email, $hashedPassword);

            if ($insert->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong!";
            }

            $insert->close();
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CyberShield | Register</title>

  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#a0f000",
            "background-dark": "#0d0f0a",
            "card-dark": "#161810",
          }
        }
      }
    }
  </script>
  <style>
    .terminal-bg {
      background: linear-gradient(rgba(13, 15, 10, 0.95), rgba(13, 15, 10, 0.95)),
        url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?...');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>

<body class="terminal-bg min-h-screen flex items-center justify-center text-white">

 <div class="bg-card-dark/70 backdrop-blur-lg border border-white/10 rounded-2xl p-10 w-full max-w-md shadow-2xl">

    <h2 class="text-xl font-bold text-center mb-6 text-primary">Register</h2>

    <?php if ($error): ?>
      <p class="text-red-500 text-sm mb-4 text-center"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="text-green-500 text-sm mb-4 text-center"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

      <input type="text" name="name" required placeholder="Full Name"
        class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4">

      <input type="email" name="email" required placeholder="Email"
        class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4">

     <div class="relative">
  <input type="password" id="password" name="password" required placeholder="Password"
    class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 pr-12">

  <span onclick="togglePassword('password', this)"
    class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary">
    visibility
  </span>
</div>

        <div class="h-1 bg-white/10 rounded mt-2">
  <div id="strengthBar" class="h-full bg-red-500 rounded transition-all"></div>
</div>
<p id="strengthText" class="text-xs text-slate-400 mt-1">Weak password</p>
       <div class="relative">
  <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password"
    class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 pr-12">

  <span onclick="togglePassword('confirm_password', this)"
    class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary">
    visibility
  </span>
</div>

      <button name="register" type="submit"
        class="w-full bg-primary text-black font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-95 transition-all">
        Create Account
      </button>

    </form>

    <div class="mt-4 text-center text-sm text-slate-400">
      Already have account?
      <a href="login.php" class="text-primary ml-1">Login</a>
    </div>

  </div>
<script>
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

  switch(score) {
    case 0:
    case 1:
      bar.style.width = "20%";
      bar.className = "h-full bg-red-600 rounded transition-all duration-300";
      text.innerText = "Very Weak";
      break;

    case 2:
      bar.style.width = "40%";
      bar.className = "h-full bg-orange-500 rounded transition-all duration-300";
      text.innerText = "Weak";
      break;

    case 3:
      bar.style.width = "60%";
      bar.className = "h-full bg-yellow-500 rounded transition-all duration-300";
      text.innerText = "Medium";
      break;

    case 4:
      bar.style.width = "80%";
      bar.className = "h-full bg-blue-500 rounded transition-all duration-300";
      text.innerText = "Strong";
      break;

    case 5:
      bar.style.width = "100%";
      bar.className = "h-full bg-primary rounded transition-all duration-300";
      text.innerText = "Very Strong 🔥";
      break;
  }
});
</script>
<script>
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
</script>
</body>

</html>