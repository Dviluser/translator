<?php
require_once 'config/database.php';

if (isset($_POST['token']) && isset($_POST['new_password'])) {

    $token = $_POST['token'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $current_time = date("Y-m-d H:i:s");

    // Check token - using prepared statement
    $stmt_check = $conn->prepare("SELECT id FROM admin_users 
                                  WHERE reset_token = ? 
                                  AND reset_token_expiry > ?");
    $stmt_check->bind_param("ss", $token, $current_time);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Update password - using prepared statement
        $stmt_update = $conn->prepare("UPDATE admin_users 
                                       SET password = ?, 
                                           reset_token = NULL, 
                                           reset_token_expiry = NULL 
                                       WHERE reset_token = ?");
        $stmt_update->bind_param("ss", $newPassword, $token);
        
        if ($stmt_update->execute()) {
            echo "Password updated successfully!";
        } else {
            echo "Error updating password.";
        }
        $stmt_update->close();
    } else {
        echo "Invalid or expired token.";
    }
    $stmt_check->close();
}
?>