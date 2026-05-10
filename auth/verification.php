<?php
session_start();
require_once "../vendor/autoload.php";

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   LOAD ENVIRONMENT
========================= */
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

/* =========================
   SECURITY GUARD
========================= */
if (!isset($_SESSION['reg_email'], $_SESSION['otp'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['reg_email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid or missing email address.");
}

$error = null;

/* =========================
   OTP EXPIRY (60 seconds)
========================= */
if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
    // Remove expired OTP data
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_sent']);
    $error = "Verification code has expired. Please register again.";
    // Optional: redirect after a short delay
    // header("Refresh:3; url=register.php");
}

/* =========================
   SEND OTP EMAIL ONCE
========================= */
if (empty($_SESSION['otp_sent']) && !$error) {    // Only send if no expiry error
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_NAME']);
        $mail->addAddress(trim($email));

        $mail->isHTML(true);
        $mail->Subject = "Your Verification Code";
        $mail->Body    = "<h2>Your OTP Code is: <b>{$_SESSION['otp']}</b></h2>";

        $mail->send();

        $_SESSION['otp_sent'] = true;

    } catch (Exception $e) {
        // Never show real SMTP errors to users – log them instead
        error_log("OTP Email failed: " . $mail->ErrorInfo);
        // Show a generic, user‑friendly message
        $error = "We could not send the verification email at this moment. Please try again later.";
    }
}

/* =========================
   VERIFY OTP
========================= */
if (isset($_POST['verify'])) {

    // Re‑check expiry explicitly
    if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_sent']);
        $error = "OTP expired. Please request a new one.";
    } else {

        $inputs = [
            $_POST['otp1'] ?? '',
            $_POST['otp2'] ?? '',
            $_POST['otp3'] ?? '',
            $_POST['otp4'] ?? '',
            $_POST['otp5'] ?? '',
            $_POST['otp6'] ?? ''
        ];

        // Check for empty fields
        if (in_array('', $inputs, true)) {
            $error = "Please fill in all six digits.";
        } else {
            $inputOtp = implode('', $inputs);

            if ($inputOtp == $_SESSION['otp']) {

                require_once "../config/db.php";
                /** @var resource|\PgSql\Connection $conn */

                $username = $_SESSION['reg_username'];
                $email    = $_SESSION['reg_email'];
                $password = password_hash($_SESSION['reg_password'], PASSWORD_DEFAULT);

                $query  = "INSERT INTO users (username, email, password) VALUES ($1, $2, $3)";
                $result = pg_query_params($conn, $query, [$username, $email, $password]);

                if ($result) {
                    // Clean only registration session data
                    unset(
                        $_SESSION['reg_username'],
                        $_SESSION['reg_email'],
                        $_SESSION['reg_password'],
                        $_SESSION['otp'],
                        $_SESSION['otp_expiry'],
                        $_SESSION['otp_sent']
                    );

                    // Redirect with success flag instead of JS alert
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    // Log the real error, show a generic message
                    error_log("User registration insert failed: " . pg_last_error($conn));
                    $error = "Registration failed. Please try again.";
                }

            } else {
                $error = "Invalid verification code.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verification</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/header.css">
  <link rel="stylesheet" href="../assets/css/login.css">
  <link rel="stylesheet" href="../assets/css/verification.css">
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

    <h2>Enter verification code</h2>

    <?php if ($error): ?>
      <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p class="verify-text">
      Your verification code is sent via email to <br>
      <strong><?= htmlspecialchars($email) ?></strong>
    </p>

    <form method="POST">
      <div class="otp-inputs">
        <input name="otp1" maxlength="1" required>
        <input name="otp2" maxlength="1" required>
        <input name="otp3" maxlength="1" required>
        <input name="otp4" maxlength="1" required>
        <input name="otp5" maxlength="1" required>
        <input name="otp6" maxlength="1" required>
      </div>

      <button type="submit" name="verify">Verify</button>
    </form>

  </div>
</div>

<script>
const inputs = document.querySelectorAll('.otp-inputs input');
inputs.forEach((input, index) => {
  input.addEventListener('input', () => {
    if (input.value.length === 1 && index < inputs.length - 1) {
      inputs[index + 1].focus();
    }
  });
  input.addEventListener('keydown', (e) => {
    if (e.key === "Backspace" && input.value === "" && index > 0) {
      inputs[index - 1].focus();
    }
  });
});

// Auto-fade error messages after 2 seconds
window.addEventListener("DOMContentLoaded", () => {
  const messages = document.querySelectorAll(".error-message");
  messages.forEach(msg => {
    setTimeout(() => {
      msg.style.transition = "opacity 0.3s ease";
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 300);
    }, 2000);
  });
});
</script>

</body>
</html>