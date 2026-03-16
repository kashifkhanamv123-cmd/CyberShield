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
  $country = trim($_POST['country'] ?? '');
  $organization = trim($_POST['organization'] ?? '');
  $program_level = $_POST['program_level'] ?? '';
  $gender = trim($_POST['gender'] ?? '');

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

      $insert = $conn->prepare("INSERT INTO users (name, email, password, country, organization, program_level, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $insert->bind_param("sssssss", $name, $email, $hashedPassword, $country, $organization, $program_level, $gender);

      if ($insert->execute()) {
        $success = "Registration successful! You can now login.";
      } else {
        $error = "Registration failed. Please try again or contact support.";
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

  <div class="bg-card-dark/70 backdrop-blur-lg border border-white/10 rounded-2xl p-6 sm:p-10 mx-4 sm:mx-0 w-full max-w-md shadow-2xl">

    <h2 class="text-xl font-bold text-center mb-6 text-primary">Register</h2>

    <?php if ($error): ?>
      <p class="text-red-500 text-sm mb-4 text-center"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="text-green-500 text-sm mb-4 text-center"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

      <input type="text" name="name" required placeholder="Full Name"
        class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all">

      <input type="email" name="email" required placeholder="Email"
        class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all">

      <div class="relative">
        <input type="password" id="password" name="password" required placeholder="Password"
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 pr-12 focus:border-primary outline-none transition-all">
        <span onclick="togglePassword('password', this)"
          class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary">
          visibility
        </span>
      </div>

      <div class="h-1 bg-white/10 rounded mt-2 overflow-hidden">
        <div id="strengthBar" class="h-full bg-red-500 rounded transition-all duration-300 w-0"></div>
      </div>
      <p id="strengthText" class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Password Strength</p>

      <div class="relative">
        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password"
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 pr-12 focus:border-primary outline-none transition-all">
        <span onclick="togglePassword('confirm_password', this)"
          class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer text-slate-400 hover:text-primary">
          visibility
        </span>
      </div>

      <!-- Reference Image Style reCAPTCHA Placeholder -->
      <div class="bg-card-dark border border-white/10 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <input type="checkbox" required class="w-5 h-5 rounded border-white/10 bg-background-dark text-primary focus:ring-primary">
          <span class="text-sm text-slate-300">I'm not a robot</span>
        </div>
        <div class="flex flex-col items-center">
          <img src="https://www.gstatic.com/recaptcha/api2/logo_48.png" alt="reCAPTCHA" class="w-8 h-8 opacity-70">
          <span class="text-[8px] text-slate-500">reCAPTCHA</span>
        </div>
      </div>

      <div class="space-y-4 pt-2">
        <label class="text-xs text-slate-400 font-medium px-1">Country *</label>
        <input type="text" name="country" required placeholder="Country (e.g. Pakistan)"
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all">

        <label class="text-xs text-slate-400 font-medium px-1">School / Organization Name (optional)</label>
        <input type="text" name="organization" placeholder="University or Organization"
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all">

        <label class="text-xs text-slate-400 font-medium px-1">Program Level *</label>
        <select name="program_level" required
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all text-slate-300 appearance-none">
          <option value="" disabled selected>Select Program Level</option>
          <option value="Middle School">Middle School</option>
          <option value="High School">High School</option>
          <option value="Undergraduate">Undergraduate</option>
          <option value="Graduate">Graduate</option>
          <option value="Other">Other</option>
        </select>

        <label class="text-xs text-slate-400 font-medium px-1">Gender *</label>
        <input type="text" name="gender" required placeholder="Gender identity"
          class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4 focus:border-primary outline-none transition-all">
      </div>

      <button name="register" type="submit"
        class="w-full bg-primary text-black font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-primary/20 mt-4">
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

      switch (score) {
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