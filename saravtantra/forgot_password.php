<?php
require 'config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

$message = "";
$messageType = ""; // success or error

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {

    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $conn->prepare("UPDATE admin_users SET reset_token=?, reset_token_expiry=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $resetLink = "http://localhost/sam/sam/reset_password.php?token=$token";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mamta.techcadd73@gmail.com';
            $mail->Password   = 'euubwsahunktabyc';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('mamta.techcadd73@gmail.com', 'Admin Panel');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                Click the link below to reset your password:<br><br>
                <a href='$resetLink'>$resetLink</a>
                <br><br>This link will expire in 1 hour.
            ";

            $mail->send();

            $message = "Reset link has been sent to your email.";
            $messageType = "success";

        } catch (Exception $e) {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }

    } else {
        $message = "Email not found.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background: linear-gradient(135deg, #e8f1f4, #f4f6f9);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            background: #1f4e5f;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
        }

        .card-body { padding: 30px; }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            background: #e6f4ea;
            color: #1e7e34;
        }

        .alert-error {
            background: #fdecea;
            color: #b02a37;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #2b6f84, #1f4e5f);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-size: 13px;
            text-decoration: none;
            color: #1f4e5f;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        Forgot Password
    </div>

    <div class="card-body">

        <?php if (!empty($message)) : ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Email Address *</label>
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <a href="admin_login.php" class="back-link">Back to Login</a>

    </div>
</div>

</body>
</html>
