<?php
session_start();
require_once "../config/db.php";
/** @var resource|\PgSql\Connection $conn */

$error = null;

if (isset($_POST['reset'])) {

    $email = trim($_POST['email']);

    // Validate email format (basic)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $query = "SELECT id FROM users WHERE email = $1";
        $result = pg_query_params($conn, $query, [$email]);

        // Check that the query ran successfully & the email exists
        if ($result && pg_num_rows($result) > 0) {
            $_SESSION['reset_email'] = $email;

            // Secure OTP generation
            $_SESSION['reset_otp']        = random_int(100000, 999999);
            $_SESSION['reset_otp_expiry'] = time() + 60;
            $_SESSION['reset_otp_sent']   = false;

            header("Location: verify-reset.php");
            exit();
        } else {
            // Generic message – prevents email enumeration
            $error = "If that email exists, we've sent a reset code.";
            // Log the real event for admin/developer
            error_log("Password reset attempt for unknown email: $email");
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password</title>

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
    <img src="https://cdn-icons-png.flaticon.com/512/3064/3064197.png" class="login-icon" />
    <h2>Reset password</h2>

    <?php if ($error): ?>
      <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Enter your email address" required>
      <button type="submit" name="reset">Send Reset Link</button>
    </form>

    <p class="signup-text">
      Remember your password? <a href="login.php">Sign in</a>
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