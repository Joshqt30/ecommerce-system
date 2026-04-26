<?php
session_start();
require_once "../config/db.php";

/* =========================
   INIT STATE
========================= */
$error = null;
$logoutMessage = isset($_GET['logout']) && $_GET['logout'] === 'success';

/* =========================
   REDIRECT IF ALREADY LOGGED IN
========================= */
if (isset($_SESSION['user_id'])) {
    if (strtolower($_SESSION['role'] ?? 'user') === 'admin') {
        header("Location: ../admin/admindashboard.php");
        exit();
    }
    header("Location: ../user/dashboard.php");
    exit();
}

/* =========================
   LOGIN HANDLER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /* validate empty input */
    if ($username === '' || $password === '') {
        $error = "Please fill in all fields.";
    } else {

        $query = "SELECT id, username, password, COALESCE(role, 'user') AS role 
                  FROM users 
                  WHERE username = $1 
                  LIMIT 1";

        $result = pg_query_params($conn, $query, [$username]);

        if ($result && pg_num_rows($result) === 1) {

            $user = pg_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {

                /* =========================
                   SECURITY: NEW SESSION ID
                ========================= */
                session_regenerate_id(true);

                /* =========================
                   STORE SESSION
                ========================= */
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                /* =========================
                   REDIRECT BY ROLE
                ========================= */
            if (strtolower($_SESSION['role'] ?? 'user') === 'admin') {
                header("Location: ../admin/admindashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }

                exit();

            } else {
                $error = "Invalid credentials.";
            }

        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

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

<div class="login-container">
  <div class="login-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1170/1170678.png" class="login-icon" />
    <h2>Sign in</h2>

    <?php if ($logoutMessage): ?>
        <div class="logout-success">✅ Logged out successfully!</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Sign In</button>
      <a href="reset-password.php" class="forgot">Forgot password?</a>
    </form>
    <p class="signup-text">New to E-commerce? <a href="register.php">Sign up</a></p>
  </div>
</div>

<script>
window.addEventListener("DOMContentLoaded", () => {
    const msg = document.querySelector(".logout-success");
    if (msg) {
        setTimeout(() => {
            msg.style.transition = "opacity 0.3s ease";
            msg.style.opacity = "0";
            setTimeout(() => msg.remove(), 300);
        }, 1000);
    }
});
</script>
</body>
</html>