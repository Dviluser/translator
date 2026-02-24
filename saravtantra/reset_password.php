<?php
require_once 'config/database.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token']) && isset($_POST['new_password'])) {

    $token = $_POST['token'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $current_time = date("Y-m-d H:i:s");

    // Check token
    $stmt_check = $conn->prepare("SELECT id FROM admin_users 
                                  WHERE reset_token = ? 
                                  AND reset_token_expiry > ?");
    $stmt_check->bind_param("ss", $token, $current_time);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {

        $stmt_update = $conn->prepare("UPDATE admin_users 
                                       SET password = ?, 
                                           reset_token = NULL, 
                                           reset_token_expiry = NULL 
                                       WHERE reset_token = ?");
        $stmt_update->bind_param("ss", $newPassword, $token);

        if ($stmt_update->execute()) {
            $message = "Password updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating password.";
            $messageType = "error";
        }

        $stmt_update->close();

    } else {
        $message = "Invalid or expired token.";
        $messageType = "error";
    }

    $stmt_check->close();
}

// For GET request (when coming from email link)
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            width: 400px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #1f4e5f;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        .card-body {
            padding: 30px;
        }

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

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #2b6f84, #1f4e5f);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #1f4e5f;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-header">
        Reset Password
    </div>

    <div class="card-body">

        <?php if (!empty($message)) : ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo $token; ?>">

            <label>New Password *</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <a href="admin_login.php" class="back-link">Back to Login</a>
    </div>
</div>

</body>
</html>
