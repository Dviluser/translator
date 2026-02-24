<?php
require_once 'config/database.php';

$full_name = "Super Admin";
$username  = "admin";
$email     = "admin@example.com";
$plainPass = "Admin@12345"; // change it

$hash = password_hash($plainPass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin_users (username, full_name, email, password, role, status)
                        VALUES (?, ?, ?, ?, 'admin', 'active')");
$stmt->bind_param("ssss", $username, $full_name, $email, $hash);

if ($stmt->execute()) {
    echo "✅ Admin created successfully.<br>";
    echo "Email: {$email}<br>";
    echo "Password: {$plainPass}<br>";
} else {
    echo "❌ Error: " . $stmt->error;
}
