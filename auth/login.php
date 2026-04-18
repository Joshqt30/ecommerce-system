<?php
session_start();
require_once "../config/db.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

$logoutMessage = false;

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logoutMessage = true;
}

if ($logoutMessage) {
    echo "<script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>";
}

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ✅ PostgreSQL prepared query
    $query = "SELECT id, username, password FROM users WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if (!$result) {
        die("Query failed: " . pg_last_error($conn));
    }

    if (pg_num_rows($result) === 1) {

        $user = pg_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../user/dashboard.php");
            exit();

        } else {
            echo "<script>alert('Incorrect password');</script>";
        }

    } else {
        echo "<script>alert('Username not found');</script>";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Your existing styles -->
  <link rel="stylesheet" href="../assets/css/header.css">

  <!-- Login CSS -->
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

<!-- ── Login Section ── -->
<div class="login-container">

  <div class="login-card">

    <img src="https://cdn-icons-png.flaticon.com/512/1170/1170678.png" class="login-icon" />

    <h2>Sign in</h2>

    <?php if ($logoutMessage): ?>
        <div class="logout-success">
            ✅ Logged out successfully!
        </div>
    <?php endif; ?>

    <form method="POST">

      <input type="text" name="username" placeholder="Username" required>

      <input type="password" name="password" placeholder="Password" required>

      <button type="submit" name="login">Sign In</button>

      <a href="reset-password.php" class="forgot">Forgot password?</a>

    </form>

    <p class="signup-text">
      New to E-commerce? <a href="register.php">Sign up</a>
    </p>

  </div>

</div>


<script>
window.addEventListener("DOMContentLoaded", () => {
    const msg = document.querySelector(".logout-success");

    if (msg) {
        setTimeout(() => {
            msg.style.transition = "opacity 0.3s ease";
            msg.style.opacity = "0";

            setTimeout(() => {
                msg.remove();
            }, 300);
        }, 1000); // 1 second
    }
});
</script>

</body>
</html>