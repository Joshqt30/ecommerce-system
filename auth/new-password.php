<?php
session_start();
require_once "../config/db.php";
/** @var resource|\PgSql\Connection $conn */

/* =========================
   SECURITY CHECK
========================= */
if (!isset($_SESSION['reset_email'])) {
    header("Location: reset-password.php");
    exit();
}

$error = null;

if (isset($_POST['update_password'])) {

    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    /* =========================
       VALIDATION
    ========================= */
    if (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match";
    } else {
        // Valid – hash and update the password
        $email          = $_SESSION['reset_email'];
        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

        $query  = "UPDATE users SET password = $1 WHERE email = $2";
        $result = pg_query_params($conn, $query, [$hashedPassword, $email]);

        if ($result) {
            // Remove only the reset‑related session data
            unset($_SESSION['reset_email']);
            // Optional: clear any OTP data that might still exist
            unset($_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_otp_sent']);

            // Redirect to login with a success flag
            header("Location: login.php?reset=success");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>New Password</title>

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
    <h2>Set new password</h2>

    <?php if ($error): ?>
      <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="password" name="new_password" placeholder="New password" required>
      <input type="password" name="confirm_password" placeholder="Confirm password" required>
      <button type="submit" name="update_password">Update Password</button>
    </form>

    <p class="signup-text">
      Back to <a href="login.php">Sign in</a>
    </p>
  </div>
</div>

<script>
  // Auto‑fade error message after 1.5 seconds
  window.addEventListener("DOMContentLoaded", () => {
    const messages = document.querySelectorAll(".error-message");
    messages.forEach(msg => {
      setTimeout(() => {
        msg.style.transition = "opacity 0.3s ease";
        msg.style.opacity = "0";
        setTimeout(() => msg.remove(), 300);
      }, 1500);
    });
  });
</script>

</body>
</html>