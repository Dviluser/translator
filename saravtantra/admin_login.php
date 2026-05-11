<?php
require_once 'config/database.php';

/* -------------------- SESSION SAFE START -------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------- LOAD JSON SAFELY -------------------- */
$jsonFile = __DIR__ . '/assets/data/sarvatantra-content.json';
$translations = [];

if (file_exists($jsonFile)) {
    $contentJson = file_get_contents($jsonFile);
    $decoded = json_decode($contentJson, true);
    if (is_array($decoded)) {
        $translations = $decoded;
    }
}

/* -------------------- SET DEFAULT LANGUAGE -------------------- */
$currentLang = $_GET['lang'] ?? 'hi';
if (!isset($translations[$currentLang])) {
    $currentLang = 'hi';
}

$t = $translations[$currentLang] ?? [];

/* -------------------- SAFE TRANSLATION FUNCTION -------------------- */
function trans($key, $default = '')
{
    global $t;
    return htmlspecialchars($t[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

/* -------------------- HANDLE LOGIN -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Hardcoded default admin credentials
    $default_admin_user = 'taranandsingh9@gmail.com';
    $default_admin_pass = 'taranandsingh@1326';

    if ($username === $default_admin_user && $password === $default_admin_pass) {
        
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['admin_email'] = $default_admin_user;
        $_SESSION['admin_role'] = 'superadmin';
        $_SESSION['admin_name'] = 'Administrator';

        $_SESSION['success_message'] = trans('login_success', 'Login successful');

        header("Location: dashboard.php");
        exit;
        
    } else {
        $_SESSION['error_message'] = trans('login_error', 'Invalid login credentials');
        header("Location: admin_login.php?lang=" . urlencode($currentLang));
        exit;
    }
}

/* -------------------- ALREADY LOGGED IN -------------------- */
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo trans('siteTitle', 'Sarvatantra'); ?> - <?php echo trans('loginTitle', 'Admin Login'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #061e29, #1d546d);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            background: #fff;
        }
        .btn-submit {
            background: linear-gradient(135deg, #5f9598, #1d546d);
            color: white;
            font-weight: 600;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #1d546d;
        }
    </style>
</head>

<body>

<div class="login-card">

    <h3 class="text-center mb-4">
        <i class="fas fa-sign-in-alt"></i>
        <?php echo trans('loginTitle', 'Admin Login'); ?>
    </h3>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form method="POST" action="admin_login.php?lang=<?php echo urlencode($currentLang); ?>">
        <input type="hidden" name="action" value="login">

        <div class="mb-3">
            <label class="form-label">
                <?php echo trans('adminEmail', 'Email'); ?>
            </label>
            <input type="email"
                   name="username"
                   class="form-control"
                   required
                   placeholder="<?php echo trans('adminEmailPlaceholder', 'Enter Email'); ?>"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">
                <?php echo trans('password', 'Password'); ?>
            </label>
            <div class="password-field">
                <input type="password"
                       name="password"
                       id="passwordField"
                       class="form-control"
                       required
                       placeholder="<?php echo trans('passwordPlaceholder', 'Enter Password'); ?>">
                <i class="fas fa-eye password-toggle" id="togglePassword" onclick="togglePasswordVisibility()"></i>
            </div>
        </div>
        <a style="text-decoration: none;" href="forgot_password.php">Forgot Password</a>

        <button type="submit" class="btn btn-submit w-100">
            <?php echo trans('loginSubmit', 'Login'); ?>
        </button>

        <div class="text-center mt-3">
            <a style="text-decoration: none;" href="index.php?lang=<?php echo urlencode($currentLang); ?>">
                <?php echo trans('backToHome', 'Back to Home'); ?>
            </a>
        </div>
    </form>

</div>

<script>
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('passwordField');
        const toggleIcon = document.getElementById('togglePassword');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Also allow Enter key to submit form
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.target.form.submit();
        }
    });
</script>

</body>
</html>
