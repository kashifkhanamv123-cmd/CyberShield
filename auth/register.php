<?php
require_once __DIR__ . "/../config/session.php";
include(__DIR__ . "/../config/db.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {

  $name = mysqli_real_escape_string($conn, trim($_POST['name']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $password = $_POST['password'];

  // Check if email already exists
  $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");

  if (mysqli_num_rows($check) > 0) {
    $error = "Email already registered!";
  } else {

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = mysqli_query($conn, "INSERT INTO users (name,email,password) 
                                       VALUES ('$name','$email','$hashedPassword')");

    if ($insert) {
      $success = "Registration successful! You can now login.";
    } else {
      $error = "Something went wrong!";
    }
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
</head>

<body class="terminal-bg min-h-screen flex items-center justify-center text-white">

  <div class="bg-card-dark border border-white/10 rounded-xl p-8 w-full max-w-md">

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

      <input type="password" name="password" required placeholder="Password"
        class="w-full bg-background-dark border border-white/10 rounded-lg py-3 px-4">

      <button name="register" type="submit"
        class="w-full bg-primary text-black font-bold py-3 rounded-lg">
        Create Account
      </button>

    </form>

    <div class="mt-4 text-center text-sm text-slate-400">
      Already have account?
      <a href="login.php" class="text-primary ml-1">Login</a>
    </div>

  </div>

</body>

</html>