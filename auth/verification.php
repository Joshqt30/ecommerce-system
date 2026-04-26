<?php
session_start();
require_once "../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['reg_email'], $_SESSION['otp'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['reg_email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid or missing email!");
}

if (!isset($_SESSION['otp_sent'])) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
       $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_NAME']);
        $mail->addAddress(trim($email));

        $mail->isHTML(true);
        $mail->Subject = "Your Verification Code";
        $mail->Body = "<h2>Your OTP Code is: <b>{$_SESSION['otp']}</b></h2>";

        $mail->send();

        $_SESSION['otp_sent'] = true;

    } catch (Exception $e) {
    error_log("OTP Email failed: " . $mail->ErrorInfo);
  }
}

// VERIFY OTP
if (isset($_POST['verify'])) {

    $inputOtp =
        $_POST['otp1'] .
        $_POST['otp2'] .
        $_POST['otp3'] .
        $_POST['otp4'] .
        $_POST['otp5'] .
        $_POST['otp6'];

    if ($inputOtp == $_SESSION['otp']) {

        require_once "../config/db.php";

        $username = $_SESSION['reg_username'];
        $email = $_SESSION['reg_email'];
        $password = password_hash($_SESSION['reg_password'], PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password) VALUES ($1, $2, $3)";
        $result = pg_query_params($conn, $query, [
            $username,
            $email,
            $password
        ]);

        if ($result) {

            session_destroy();

            echo "<script>
                alert('Registration successful!');
                window.location.href='login.php';
            </script>";

        } else {
            echo "<script>
                alert('Registration failed!');
            </script>";
        }

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