<?php
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Your existing styles -->
  <link rel="stylesheet" href="../assets/css/header.css">
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


<!-- ── Reset Password Section ── -->
<div class="login-container">

  <div class="login-card">

    <img src="https://cdn-icons-png.flaticon.com/512/3064/3064197.png" class="login-icon" />

    <h2>Reset password</h2>

    <form method="POST">

      <input type="email" name="email" placeholder="Enter your email address" required>

      <button type="submit" name="reset">Send Reset Link</button>

    </form>

    <p class="signup-text">
      Remember your password? <a href="login.php">Sign in</a>
    </p>

  </div>

</div>

</body>
</html>