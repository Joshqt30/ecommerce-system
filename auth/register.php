<?php
session_start();
include '../config/db.php';

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {

        // check duplicates
        $check = pg_query_params($conn,
            "SELECT id FROM users WHERE username = $1 OR email = $2",
            [$username, $email]
        );

        if (pg_num_rows($check) > 0) {
            $error = "Username or Email already exists!";
        } else {

            // store TEMP data only
            $_SESSION['reg_username'] = $username;
            $_SESSION['reg_email'] = $email;
            $_SESSION['reg_password'] = $password;

            // generate OTP if not yet created
            if (!isset($_SESSION['otp'], $_SESSION['otp_time'])) {
            $_SESSION['otp'] = random_int(100000, 999999);
            $_SESSION['otp_time'] = time();
        }

            header("Location: confirm-register.php");
            exit();
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Your existing styles -->
  <link rel="stylesheet" href="../assets/css/header.css">

  <!-- Reuse login styles -->
  <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

<!-- ── Header (same as dashboard but simpler) ── -->
<header class="header">
  <div class="nav-bar">
    <a href="../index.php" class="logo-wrap">
      <img class="logo-icon" src="https://cdn.codia.ai/figma/DNIGD5YlSaH0gJQnZ0iH7f/img-40e47e05667e0932.png" />
      <span class="logo-text">E-Commerce</span>
    </a>

    <nav class="nav-links">
      <a href="#">About</a>
      <a href="#">Shop</a>
      <a href="#">Help</a>
    </nav>
  </div>
</header>


<!-- ── Register Section ── -->
<div class="login-container">

  <div class="login-card">

    <img src="https://cdn-icons-png.flaticon.com/512/747/747376.png" class="login-icon" />

    <h2>Sign up</h2>

    <form method="POST">

      <input type="text" name="username" placeholder="Username" required>

      <input type="email" name="email" placeholder="Email address" required>

      <input type="password" name="password" placeholder="Password" required>

      <input type="password" name="confirm_password" placeholder="Confirm password" required>

      <button type="submit" name="register">Sign Up</button>

    </form>

    <p class="signup-text">
      Already have an account? <a href="login.php">Sign in</a>
    </p>

  </div>

</div>

</body>
</html>