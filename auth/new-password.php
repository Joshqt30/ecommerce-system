<?php
session_start();
require_once "../config/db.php";

/* =========================
   SECURITY CHECK
========================= */
if (!isset($_SESSION['reset_email'])) {
    header("Location: reset-password.php");
    exit();
}

if (isset($_POST['update_password'])) {

    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    /* =========================
       BASIC VALIDATION
    ========================= */
    if (strlen($newPass) < 6) {
        echo "<script>alert('Password must be at least 6 characters');</script>";
    } elseif ($newPass !== $confirmPass) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {

        $email = $_SESSION['reset_email'];
        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

        /* =========================
           POSTGRESQL UPDATE
        ========================= */
        $query = "UPDATE users SET password = $1 WHERE email = $2";
        $result = pg_query_params($conn, $query, [
            $hashedPassword,
            $email
        ]);

        if ($result) {

            // only remove reset session (NOT everything)
            unset($_SESSION['reset_email']);

            echo "<script>
                alert('Password updated successfully!');
                window.location.href='login.php';
            </script>";

        } else {
            echo "<script>alert('Error updating password');</script>";
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

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Your styles -->
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


<!-- ── New Password Section ── -->
<div class="login-container">

  <div class="login-card">

    <h2>Set new password</h2>

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

</body>
</html>