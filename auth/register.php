<?php
session_start();
include '../config/db.php';

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {

        // ✅ Hash password (IMPORTANT)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // ✅ Check if username/email already exists
        $checkQuery = "SELECT id FROM users WHERE username = $1 OR email = $2";
        $checkResult = pg_query_params($conn, $checkQuery, [$username, $email]);

        if (pg_num_rows($checkResult) > 0) {
            echo "<script>alert('Username or Email already exists');</script>";
        } else {

            // ✅ Insert into PostgreSQL
            $insertQuery = "INSERT INTO users (username, email, password) VALUES ($1, $2, $3)";
            $insertResult = pg_query_params($conn, $insertQuery, [
                $username,
                $email,
                $hashedPassword
            ]);

            if ($insertResult) {
                echo "<script>alert('Registration successful'); window.location.href='login.php';</script>";
            } else {
                echo "<script>alert('Registration failed');</script>";
            }
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