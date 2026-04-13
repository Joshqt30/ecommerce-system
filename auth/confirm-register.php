<?php
/* Temporary data (later from POST or SESSION) */
$username = $_POST['username'] ?? "Juan123";
$email = $_POST['email'] ?? "juan@email.com";
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Confirm Details</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="stylesheet" href="../assets/css/confirm.css">
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


<!-- Confirm UI -->
<div class="login-container">

  <div class="login-card">

    <h2>Confirm your details</h2>

    <div class="confirm-info">

      <div class="info-row">
        <span>Username</span>
        <strong><?php echo $username; ?></strong>
      </div>

      <div class="info-row">
        <span>Email</span>
        <strong><?php echo $email; ?></strong>
      </div>

      <div class="info-row">
        <span>Password</span>
        <strong>••••••••</strong>
      </div>

    </div>

    <form method="POST" action="register.php">

      <!-- pass data forward -->
      <input type="hidden" name="username" value="<?php echo $username; ?>">
      <input type="hidden" name="email" value="<?php echo $email; ?>">

      <label class="terms">
        <input type="checkbox" required>
        I agree to the Terms & Conditions
      </label>

      <button type="submit">Confirm & Create Account</button>

    </form>

    <p class="signup-text">
      Want to edit? <a href="register.php">Go back</a>
    </p>

  </div>

</div>

</body>
</html>