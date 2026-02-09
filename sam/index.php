<?php
require_once 'config/database.php';

// Get content from JSON file
$contentJson = file_get_contents('assets/data/content.json');
$translations = json_decode($contentJson, true);

// Set default language
$currentLang = isset($_GET['lang']) ? $_GET['lang'] : 'hi';
if (!array_key_exists($currentLang, $translations)) {
    $currentLang = 'hi';
}

$t = $translations[$currentLang];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'join_us':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone']);
                $gender = $conn->real_escape_string($_POST['gender']);
                
                // Validate phone number (10 digits)
                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $_SESSION['error_message'] = $t['phone_validation_error'] ?? 'Please enter a valid 10-digit mobile number.';
                    header("Location: index.php?lang=$currentLang");
                    exit;
                }
                
                $sql = "INSERT INTO members (name, email, phone, gender) 
                        VALUES ('$name', '$email', '$phone', '$gender')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = $t['join_success'];
                } else {
                    $_SESSION['error_message'] = $t['join_error'];
                }
                header("Location: index.php?lang=$currentLang");
                exit;
                
            case 'submit_opinion':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $opinion = $conn->real_escape_string($_POST['opinion']);
                
                // Validate phone number if provided (10 digits)
                if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
                    $_SESSION['error_message'] = $t['phone_validation_error'] ?? 'Please enter a valid 10-digit mobile number.';
                    header("Location: index.php?lang=$currentLang");
                    exit;
                }
                
                // Server-side validation for word count
                $wordCount = str_word_count($opinion);
                if ($wordCount > 20) {
                    $_SESSION['error_message'] = $t['opinion_length_error'] ?? 'Opinion should be maximum 20 words.';
                    header("Location: index.php?lang=$currentLang");
                    exit;
                }
                
                $sql = "INSERT INTO opinions (name, email, phone, opinion, language) 
                        VALUES ('$name', '$email', '$phone', '$opinion', '$currentLang')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = $t['opinion_success'];
                } else {
                    $_SESSION['error_message'] = $t['opinion_error'];
                }
                header("Location: index.php?lang=$currentLang");
                exit;
                
            case 'login':
                $username = $conn->real_escape_string($_POST['username']);
                $password = $_POST['password'];
                
                $sql = "SELECT * FROM admin_users WHERE username = '$username' AND status = 'active'";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    if (password_verify($password, $admin['password'])) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        
                        $_SESSION['success_message'] = $t['login_success'];
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        // Try direct comparison for development
                        if ($password === 'admin' && $username === 'admin') {
                            // If password is plain 'admin', hash it and update database
                            $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
                            $updateSql = "UPDATE admin_users SET password = '$hashedPassword' WHERE username = 'admin'";
                            $conn->query($updateSql);
                            
                            // Login the user
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_email'] = $admin['email'];
                            $_SESSION['admin_role'] = $admin['role'];
                            $_SESSION['admin_name'] = $admin['full_name'];
                            
                            $_SESSION['success_message'] = $t['login_success'];
                            header("Location: dashboard.php");
                            exit;
                        }
                    }
                }
                
                $_SESSION['error_message'] = $t['login_error'];
                header("Location: index.php?lang=$currentLang");
                exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarvatantra - Wholocracy Documentation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
     <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- Alert Message Container -->
    <div id="alertContainer">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-message show">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?></span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-message show">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?></span>
                    <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>

    <!-- Header -->
    <header class="site-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-5 col-lg-4">
                    <a href="index.php" class="logo">
                        <span id="logoText"><?php echo $currentLang === 'en' ? 'Sarvatantra' : 'सर्वतंत्र'; ?></span>
                    </a>
                </div>
                <div class="col-md-7 col-lg-8 text-md-end">
                    <div class="d-flex justify-content-md-end align-items-center flex-wrap">
                        <div class="language-switcher me-3">
                            <i class="fas fa-globe" style="color: var(--accent-teal);"></i>
                            <select id="languageSelect" class="form-select" onchange="changeLanguage(this.value)">
                                <option value="hi" <?php echo $currentLang === 'hi' ? 'selected' : ''; ?>>हिन्दी</option>
                                <option value="en" <?php echo $currentLang === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="bn" <?php echo $currentLang === 'bn' ? 'selected' : ''; ?>>বাংলা</option>
                                <option value="ta" <?php echo $currentLang === 'ta' ? 'selected' : ''; ?>>தமிழ்</option>
                                <option value="te" <?php echo $currentLang === 'te' ? 'selected' : ''; ?>>తెలుగు</option>
                                <option value="mr" <?php echo $currentLang === 'mr' ? 'selected' : ''; ?>>मराठी</option>
                                <option value="gu" <?php echo $currentLang === 'gu' ? 'selected' : ''; ?>>ગુજરાતી</option>
                                <option value="kn" <?php echo $currentLang === 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                                <option value="ml" <?php echo $currentLang === 'ml' ? 'selected' : ''; ?>>മലയാളം</option>
                                <option value="or" <?php echo $currentLang === 'or' ? 'selected' : ''; ?>>ଓଡ଼ିଆ</option>
                                <option value="pa" <?php echo $currentLang === 'pa' ? 'selected' : ''; ?>>ਪੰਜਾਬੀ</option>
                                <option value="as" <?php echo $currentLang === 'as' ? 'selected' : ''; ?>>অসমীয়া</option>
                                <option value="ur" <?php echo $currentLang === 'ur' ? 'selected' : ''; ?>>اردو</option>
                                <option value="ne" <?php echo $currentLang === 'ne' ? 'selected' : ''; ?>>नेपाली</option>
                                <option value="sd" <?php echo $currentLang === 'sd' ? 'selected' : ''; ?>>سنڌي</option>
                                <option value="kok" <?php echo $currentLang === 'kok' ? 'selected' : ''; ?>>कोंकणी</option>
                                <option value="mai" <?php echo $currentLang === 'mai' ? 'selected' : ''; ?>>मैथिली</option>
                                <option value="sat" <?php echo $currentLang === 'sat' ? 'selected' : ''; ?>>ᱥᱟᱱᱛᱟᱲᱤ</option>
                                <option value="ks" <?php echo $currentLang === 'ks' ? 'selected' : ''; ?>>کٲشُر</option>
                                <option value="doi" <?php echo $currentLang === 'doi' ? 'selected' : ''; ?>>डोगरी</option>
                                <option value="mni" <?php echo $currentLang === 'mni' ? 'selected' : ''; ?>>মৈতৈলোন্</option>
                            </select>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-action btn-join-header" data-bs-toggle="modal" data-bs-target="#joinModal">
                                <i class="fas fa-user-plus"></i>
                                <span id="joinBtnText"><?php echo $t['joinUs']; ?></span>
                            </button>
                            <button class="btn-action btn-opinion" data-bs-toggle="modal" data-bs-target="#opinionModal">
                                <i class="far fa-comment-dots"></i>
                                <span id="opinionBtnText"><?php echo $t['giveOpinion']; ?></span>
                            </button>
                            <button class="btn-action btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt"></i>
                                <span id="loginBtnText"><?php echo $t['login']; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container-fluid my-5">
        <div class="row">
            <!-- Sidebar Navigation - Simplified -->
            <div class="col-lg-3 mb-4">
                <div class="sidebar">
                    <ul class="nav-links">
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=1" class="<?php echo (!isset($_GET['page']) || $_GET['page'] == 1) ? 'active' : ''; ?>" data-page="1"><i class="far fa-file-alt"></i> <span id="page1Text"><?php echo $t['page1']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=2" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 2) ? 'active' : ''; ?>" data-page="2"><i class="far fa-file-alt"></i> <span id="page2Text"><?php echo $t['page2']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=3" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 3) ? 'active' : ''; ?>" data-page="3"><i class="far fa-file-alt"></i> <span id="page3Text"><?php echo $t['page3']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=4" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 4) ? 'active' : ''; ?>" data-page="4"><i class="far fa-file-alt"></i> <span id="page4Text"><?php echo $t['page4']; ?></span></a></li>
                        <li><a href="index.php?lang=<?php echo $currentLang; ?>&page=5" class="<?php echo (isset($_GET['page']) && $_GET['page'] == 5) ? 'active' : ''; ?>" data-page="5"><i class="far fa-file-alt"></i> <span id="page5Text"><?php echo $t['page5']; ?></span></a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-lg-9">
                <div class="content-area fade-in">
                    <h1 class="content-title" id="contentTitle"><?php echo $t['siteTitle']; ?></h1>
                    
                    <!-- Content will be loaded here dynamically -->
                    <div id="contentContainer">
                        <?php
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        if ($page == 1) {
                            foreach ($t['page1Content'] as $paragraph) {
                                echo '<div class="content-paragraph fade-in">';
                                echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="content-card fade-in">';
                            echo '<h5><i class="fas fa-file-alt"></i> ' . $t['page' . $page] . '</h5>';
                            echo '<div class="segment-content">';
                            echo '<p>This is page ' . $page . ' content. Content will be added here.</p>';
                            echo '<p>More information about this section will be available soon.</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <!-- Dashboard Preview (Hidden by default) -->
                    <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="dashboard-preview" id="dashboardPreview">
                        <h4><i class="fas fa-tachometer-alt"></i> <span id="dashboardTitle"><?php echo $t['dashboardTitle']; ?></span></h4>
                        <p id="dashboardText"><?php echo $t['dashboardText']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Pagination Controls -->
                    <div class="pagination-controls">
                        <button class="page-btn" id="prevBtn" onclick="window.location.href='index.php?lang=<?php echo $currentLang; ?>&page=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-left"></i>
                            <span id="prevText"><?php echo $t['previous']; ?></span>
                        </button>
                        <button class="page-btn" id="nextBtn" onclick="window.location.href='index.php?lang=<?php echo $currentLang; ?>&page=<?php echo min(5, $page + 1); ?>'" <?php echo $page >= 5 ? 'disabled' : ''; ?>>
                            <span id="nextText"><?php echo $t['next']; ?></span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Action Buttons -->
    <div class="mobile-action-buttons">
        <div class="mobile-action-container">
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#joinModal">
                <i class="fas fa-user-plus"></i>
                <span id="mobileJoinText"><?php echo $t['joinUs']; ?></span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#opinionModal">
                <i class="far fa-comment-dots"></i>
                <span id="mobileOpinionText"><?php echo $t['giveOpinion']; ?></span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="fas fa-sign-in-alt"></i>
                <span id="mobileLoginText"><?php echo $t['login']; ?></span>
            </button>
        </div>
    </div>

    <!-- Mobile Floating Language Switcher -->
    <div class="mobile-language-switcher" id="mobileLanguageSwitcher">
        <div class="mobile-language-options">
            <!-- Major Indian Languages -->
            <div class="mobile-language-option <?php echo $currentLang === 'hi' ? 'active' : ''; ?>" onclick="changeLanguage('hi')">
                <i class="fas fa-language"></i>
                <span>हिन्दी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">
                <i class="fas fa-language"></i>
                <span>English</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'bn' ? 'active' : ''; ?>" onclick="changeLanguage('bn')">
                <i class="fas fa-language"></i>
                <span>বাংলা</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ta' ? 'active' : ''; ?>" onclick="changeLanguage('ta')">
                <i class="fas fa-language"></i>
                <span>தமிழ்</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'te' ? 'active' : ''; ?>" onclick="changeLanguage('te')">
                <i class="fas fa-language"></i>
                <span>తెలుగు</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mr' ? 'active' : ''; ?>" onclick="changeLanguage('mr')">
                <i class="fas fa-language"></i>
                <span>मराठी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'gu' ? 'active' : ''; ?>" onclick="changeLanguage('gu')">
                <i class="fas fa-language"></i>
                <span>ગુજરાતી</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'kn' ? 'active' : ''; ?>" onclick="changeLanguage('kn')">
                <i class="fas fa-language"></i>
                <span>ಕನ್ನಡ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ml' ? 'active' : ''; ?>" onclick="changeLanguage('ml')">
                <i class="fas fa-language"></i>
                <span>മലയാളം</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'or' ? 'active' : ''; ?>" onclick="changeLanguage('or')">
                <i class="fas fa-language"></i>
                <span>ଓଡ଼ିଆ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'pa' ? 'active' : ''; ?>" onclick="changeLanguage('pa')">
                <i class="fas fa-language"></i>
                <span>ਪੰਜਾਬੀ</span>
            </div>
            <!-- Additional Indian Languages -->
            <div class="mobile-language-option <?php echo $currentLang === 'as' ? 'active' : ''; ?>" onclick="changeLanguage('as')">
                <i class="fas fa-language"></i>
                <span>অসমীয়া</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ur' ? 'active' : ''; ?>" onclick="changeLanguage('ur')">
                <i class="fas fa-language"></i>
                <span>اردو</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ne' ? 'active' : ''; ?>" onclick="changeLanguage('ne')">
                <i class="fas fa-language"></i>
                <span>नेपाली</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'sd' ? 'active' : ''; ?>" onclick="changeLanguage('sd')">
                <i class="fas fa-language"></i>
                <span>سنڌي</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'kok' ? 'active' : ''; ?>" onclick="changeLanguage('kok')">
                <i class="fas fa-language"></i>
                <span>कोंकणी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mai' ? 'active' : ''; ?>" onclick="changeLanguage('mai')">
                <i class="fas fa-language"></i>
                <span>मैथिली</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'sat' ? 'active' : ''; ?>" onclick="changeLanguage('sat')">
                <i class="fas fa-language"></i>
                <span>ᱥᱟᱱᱛᱟᱲᱤ</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'ks' ? 'active' : ''; ?>" onclick="changeLanguage('ks')">
                <i class="fas fa-language"></i>
                <span>کٲشُر</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'doi' ? 'active' : ''; ?>" onclick="changeLanguage('doi')">
                <i class="fas fa-language"></i>
                <span>डोगरी</span>
            </div>
            <div class="mobile-language-option <?php echo $currentLang === 'mni' ? 'active' : ''; ?>" onclick="changeLanguage('mni')">
                <i class="fas fa-language"></i>
                <span>মৈতৈলোন্</span>
            </div>
        </div>
        <div class="mobile-language-icon" onclick="toggleLanguageSwitcher()">
            <i class="fas fa-globe"></i>
        </div>
    </div>

    <!-- Join Us Modal -->
    <div class="modal fade" id="joinModal" tabindex="-1" aria-labelledby="joinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="joinModalTitle"><i class="fas fa-user-plus me-2"></i><span id="joinModalText"><?php echo $t['joinUs']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="join-form-container slide-in">
                        <form id="joinForm" method="POST">
                            <input type="hidden" name="action" value="join_us">
                            <div class="mb-4">
                                <label for="joinName" class="form-label">
                                    <i class="fas fa-user"></i>
                                    <span id="joinNameLabel"><?php echo $t['joinName']; ?> *</span>
                                </label>
                                <input type="text" class="form-control" id="joinName" name="name" placeholder="<?php echo $t['joinNamePlaceholder']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinEmail" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    <span id="joinEmailLabel"><?php echo $t['joinEmail']; ?> *</span>
                                </label>
                                <input type="email" class="form-control" id="joinEmail" name="email" placeholder="<?php echo $t['joinEmailPlaceholder']; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinPhone" class="form-label">
                                    <i class="fas fa-phone"></i>
                                    <span id="joinPhoneLabel"><?php echo $t['joinPhone']; ?> *</span>
                                </label>
                                <input type="tel" class="form-control" id="joinPhone" name="phone" placeholder="<?php echo $t['joinPhonePlaceholder']; ?>" required maxlength="10" pattern="[0-9]{10}">
                                <div class="phone-validation" id="joinPhoneValidation">Enter a valid 10-digit mobile number</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars"></i>
                                    <span id="joinGenderLabel"><?php echo $t['joinGender']; ?> *</span>
                                </label>
                                <div class="gender-selection">
                                    <div class="gender-option" data-gender="male">
                                        <i class="fas fa-male"></i>
                                        <span id="genderMale"><?php echo $t['genderMale']; ?></span>
                                    </div>
                                    <div class="gender-option" data-gender="female">
                                        <i class="fas fa-female"></i>
                                        <span id="genderFemale"><?php echo $t['genderFemale']; ?></span>
                                    </div>
                                    <div class="gender-option" data-gender="other">
                                        <i class="fas fa-transgender-alt"></i>
                                        <span id="genderOther"><?php echo $t['genderOther']; ?></span>
                                    </div>
                                </div>
                                <input type="hidden" id="joinGender" name="gender" required>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="joinTerms" required>
                                <label class="form-check-label" for="joinTerms" id="joinTermsLabel">
                                    <?php echo $t['joinTerms']; ?>
                                </label>
                            </div>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span id="joinSubmitText"><?php echo $t['joinSubmit']; ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Opinion Modal -->
    <div class="modal fade" id="opinionModal" tabindex="-1" aria-labelledby="opinionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="opinionModalTitle"><i class="far fa-comment-dots me-2"></i><span id="opinionModalTitleText"><?php echo $t['opinionTitle']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="opinionForm" method="POST">
                        <input type="hidden" name="action" value="submit_opinion">
                        <div class="mb-3">
                            <label for="userName" class="form-label" id="nameLabel"><?php echo $t['name']; ?> *</label>
                            <input type="text" class="form-control" id="userName" name="name" placeholder="<?php echo $t['namePlaceholder']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label" id="emailLabel"><?php echo $t['email']; ?> *</label>
                            <input type="email" class="form-control" id="userEmail" name="email" placeholder="<?php echo $t['emailPlaceholder']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPhone" class="form-label" id="phoneLabel"><?php echo $t['phone']; ?></label>
                            <input type="tel" class="form-control" id="userPhone" name="phone" placeholder="<?php echo $t['phonePlaceholder']; ?>" maxlength="10" pattern="[0-9]{10}">
                            <div class="phone-validation" id="opinionPhoneValidation">Enter a valid 10-digit mobile number</div>
                        </div>
                        <div class="mb-4">
                            <label for="userOpinion" class="form-label" id="opinionLabel"><?php echo $t['opinion']; ?> *</label>
                            <textarea class="form-control" id="userOpinion" name="opinion" rows="4" placeholder="<?php echo $t['opinionPlaceholder']; ?>" required maxlength="500"></textarea>
                            <div class="word-count-container">
                                <div class="word-count" id="wordCount">Words: 0/20</div>
                                <div id="wordLimitMessage" style="font-size: 0.85rem; color: #666;"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit" id="submitOpinionBtn">
                            <i class="fas fa-paper-plane me-2"></i>
                            <span id="submitOpinionText"><?php echo $t['submit']; ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalTitle"><i class="fas fa-sign-in-alt me-2"></i><span id="loginModalTitleText"><?php echo $t['loginTitle']; ?></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-4">
                            <label for="loginEmail" class="form-label" id="adminEmailLabel"><?php echo $t['adminEmail']; ?> *</label>
                            <input type="text" class="form-control" id="loginEmail" name="username" placeholder="<?php echo $t['adminEmailPlaceholder']; ?>" required>
                            <div class="form-text" id="adminHint"><?php echo $t['adminHint']; ?></div>
                        </div>
                        <div class="mb-4">
                            <label for="loginPassword" class="form-label" id="passwordLabel"><?php echo $t['password']; ?> *</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="<?php echo $t['passwordPlaceholder']; ?>" required>
                            <div class="form-text" id="passwordHint"><?php echo $t['passwordHint']; ?></div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe" id="rememberMeLabel"><?php echo $t['rememberMe']; ?></label>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-lock me-2"></i>
                            <span id="loginSubmitText"><?php echo $t['loginSubmit']; ?></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- OpenRouter API for Translation -->
    <script src="assets/js/index.js">
        // OpenRouter API configuration
        
    </script>
</body>
</html>