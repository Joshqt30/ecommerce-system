<?php
session_start();
require_once "../vendor/autoload.php";

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =========================
   LOAD .ENV
========================= */
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

/* =========================
   SECURITY GUARD
========================= */
if (!isset($_SESSION['reset_email'], $_SESSION['reset_otp'])) {
    header("Location: reset-password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$error = null; // server‑side error message holder

/* =========================
   OTP EXPIRATION (10 MIN)
========================= */
if (!isset($_SESSION['reset_otp_time'])) {
    $_SESSION['reset_otp_time'] = time();
}

if (time() - $_SESSION['reset_otp_time'] > 600) {
    // Targeted cleanup – remove only reset‑related keys
    unset(
        $_SESSION['reset_email'],
        $_SESSION['reset_otp'],
        $_SESSION['reset_otp_time'],
        $_SESSION['reset_otp_sent']
    );

    $error = "OTP expired. Please request a new one.";
    
    // Fallback if header fails – show error on this page
    header("Location: reset-password.php?expired=1");
    exit();
}

/* =========================
   SEND OTP EMAIL ONCE
========================= */
if (empty($_SESSION['reset_otp_sent'])) {

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
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Reset Password Code";
        $mail->Body    = "<h2>Your OTP Code: <b>{$_SESSION['reset_otp']}</b></h2>";

        $mail->send();

        $_SESSION['reset_otp_sent'] = true;

    } catch (Exception $e) {
        // Log the real error, show a user‑friendly fallback
        error_log("OTP Email failed: " . $mail->ErrorInfo);
        $error = "We couldn't send the email right now. Please try again later.";
    }
}

/* =========================
   VERIFY OTP
========================= */
if (isset($_POST['verify'])) {

    $inputs = [
        $_POST['otp1'] ?? '',
        $_POST['otp2'] ?? '',
        $_POST['otp3'] ?? '',
        $_POST['otp4'] ?? '',
        $_POST['otp5'] ?? '',
        $_POST['otp6'] ?? ''
    ];

    // Check if any field is empty
    if (in_array('', $inputs, true)) {
        $error = "Please complete all OTP fields.";
    } else {
        $inputOtp = implode('', $inputs);

        if ($inputOtp == $_SESSION['reset_otp']) {
            // OTP is correct – proceed to new password page
            // Do NOT destroy the session; new-password.php needs reset_email
            header("Location: new-password.php");
            exit();
        } else {
            $error = "Invalid OTP. Please try again.";
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

<!-- Header -->
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


<!-- Verification UI -->
<div class="login-container">

  <div class="login-card">

    <h2>Enter verification code</h2>

    <p class="verify-text">
      Your verification code is sent via email to <br>
      <strong><?php echo htmlspecialchars($email); ?></strong>
    </p>

    <!-- Show server-side error (if any) -->
    <?php if ($error): ?>
      <div class="error-message" style="color: #d93025; margin-bottom: 15px; text-align: center;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

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
</script>

</body>
</html>