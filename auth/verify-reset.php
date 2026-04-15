<?php
session_start();
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: reset-password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// ✅ SEND EMAIL
if (empty($_SESSION['reset_otp_sent'])) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'makers0358@gmail.com';
        $mail->Password = 'plqdvmjsrtomputu'; // ⚠️ IMPORTANT

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('YOUR_EMAIL@gmail.com', 'E-Commerce');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Reset Password Code";
        $mail->Body = "<h2>Your OTP Code: <b>{$_SESSION['reset_otp']}</b></h2>";

        $mail->send();

        $_SESSION['reset_otp_sent'] = true;

    } catch (Exception $e) {
        echo "Email failed: {$mail->ErrorInfo}";
    }
}

// ✅ VERIFY OTP
if (isset($_POST['verify'])) {

    $inputOtp =
        $_POST['otp1'] .
        $_POST['otp2'] .
        $_POST['otp3'] .
        $_POST['otp4'] .
        $_POST['otp5'] .
        $_POST['otp6'];

    if ($inputOtp == $_SESSION['reset_otp']) {

        header("Location: new-password.php");
        exit();

    } else {
        echo "<script>alert('Invalid OTP');</script>";
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


<!-- Verification UI -->
<div class="login-container">

  <div class="login-card">

    <h2>Enter verification code</h2>

    <p class="verify-text">
      Your verification code is sent via email to <br>
      <strong><?php echo $email; ?></strong>
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
</script>

</body>
</html>