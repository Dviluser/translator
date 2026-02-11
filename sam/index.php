<?php
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'join_us':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone']);
                $gender = $conn->real_escape_string($_POST['gender']);

                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $_SESSION['error_message'] = 'Please enter a valid 10-digit mobile number.';
                    header("Location: index.php");
                    exit;
                }

                $sql = "INSERT INTO members (name, email, phone, gender)
                        VALUES ('$name', '$email', '$phone', '$gender')";

                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = 'Successfully joined! We will contact you soon.';
                } else {
                    $_SESSION['error_message'] = 'Error! Please try again.';
                }
                header("Location: index.php");
                exit;

            case 'submit_opinion':
                $name = $conn->real_escape_string($_POST['name']);
                $email = $conn->real_escape_string($_POST['email']);
                $phone = $conn->real_escape_string($_POST['phone'] ?? '');
                $opinion = $conn->real_escape_string($_POST['opinion']);

                if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
                    $_SESSION['error_message'] = 'Please enter a valid 10-digit mobile number.';
                    header("Location: index.php");
                    exit;
                }

                $wordCount = str_word_count($opinion);
                if ($wordCount > 20) {
                    $_SESSION['error_message'] = 'Opinion should be maximum 20 words.';
                    header("Location: index.php");
                    exit;
                }

                $sql = "INSERT INTO opinions (name, email, phone, opinion, language)
                        VALUES ('$name', '$email', '$phone', '$opinion', 'en')";

                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = 'Your opinion has been submitted successfully!';
                } else {
                    $_SESSION['error_message'] = 'Error! Please try again.';
                }
                header("Location: index.php");
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

                        $_SESSION['success_message'] = 'Login successful! Redirecting to dashboard...';
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        if ($password === 'admin' && $username === 'admin') {
                            $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
                            $updateSql = "UPDATE admin_users SET password = '$hashedPassword' WHERE username = 'admin'";
                            $conn->query($updateSql);

                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_email'] = $admin['email'];
                            $_SESSION['admin_role'] = $admin['role'];
                            $_SESSION['admin_name'] = $admin['full_name'];

                            $_SESSION['success_message'] = 'Login successful! Redirecting to dashboard...';
                            header("Location: dashboard.php");
                            exit;
                        }
                    }
                }

                $_SESSION['error_message'] = "Invalid credentials. Please use username 'admin' and password 'admin'.";
                header("Location: index.php");
                exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarvatantra - Wholocracy Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        <span>Sarvatantra</span>
                    </a>
                </div>
                <div class="col-md-7 col-lg-8 text-md-end">
                    <div class="d-flex justify-content-md-end align-items-center flex-wrap">
                        <div class="action-buttons">
                            <button class="btn-action btn-join-header" data-bs-toggle="modal" data-bs-target="#joinModal">
                                <i class="fas fa-user-plus"></i>
                                <span>Join Us</span>
                            </button>
                            <button class="btn-action btn-opinion" data-bs-toggle="modal" data-bs-target="#opinionModal">
                                <i class="far fa-comment-dots"></i>
                                <span>Give Opinion</span>
                            </button>
                            <button class="btn-action btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
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
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="sidebar">
                    <ul class="nav-links">
                        <?php $page = isset($_GET['page']) ? intval($_GET['page']) : 1; ?>
                        <li><a href="index.php?page=1" class="<?php echo ($page == 1) ? 'active' : ''; ?>" data-page="1"><i class="far fa-file-alt"></i> <span>Page 1</span></a></li>
                        <li><a href="index.php?page=2" class="<?php echo ($page == 2) ? 'active' : ''; ?>" data-page="2"><i class="far fa-file-alt"></i> <span>Page 2</span></a></li>
                        <li><a href="index.php?page=3" class="<?php echo ($page == 3) ? 'active' : ''; ?>" data-page="3"><i class="far fa-file-alt"></i> <span>Page 3</span></a></li>
                        <li><a href="index.php?page=4" class="<?php echo ($page == 4) ? 'active' : ''; ?>" data-page="4"><i class="far fa-file-alt"></i> <span>Page 4</span></a></li>
                        <li><a href="index.php?page=5" class="<?php echo ($page == 5) ? 'active' : ''; ?>" data-page="5"><i class="far fa-file-alt"></i> <span>Page 5</span></a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-lg-9">
                <div class="content-area fade-in">
                    <h1 class="content-title">Sarvatantra - Wholocracy Documentation</h1>

                    <div id="contentContainer">
                        <?php
                        if ($page == 1) {
                            $paragraphs = [
                                "Sarvatantra (wholocracy) is a people-controlled, leader-free, transparent, simple and peaceful path. By walking on this path, the entire human race will be freed from all suffocating problems and life-threatening distortions. A system suitable for human life will be established on earth. Sarvatantra will be established without any destruction, without shedding a single drop of blood. Discrimination, oppression, exploitation, suppression, scarcity, starvation, suicide, murder, war and population will become things of the past. There will be no displacement or migration.",
                                "Sarvatantra neither supports nor opposes any previous ideology. Sarvatantra will start a new chapter of human civilization, in which the world will be free from conflict. Human values will be at the highest level in society. Man will be free from animal tendencies and cruelty. Humans will be motivated by universal welfare rather than self-interest.",
                                "Every child born on earth brings with him the complete energy and power of the universe. Instead of wasting his energy in fulfilling petty selfishness and destruction, he will use it for construction and public welfare.",
                                "Sarvatantra is the system of the next generation, which will free humans from mental, economic and political slavery. Politics enslaves people, Sarvaniti (universal policy) liberates. All the rights and powers of the oppressors will be transferred to the hands of all people. Every person will become powerful from powerless. Sarvatantra will unite divided humans. There will be no bully, no oppressor. Now neither tears will flow nor blood will flow. Humans will be free from sorrow, fear, worry and violence.",
                                "Cause of distortions: The root cause of all distortions in human society is toxic 'wealth' (currency) and 'power'. Both, concentrated in the hands of a few limited people, generate a toxic feeling of dominance, which stimulates jealousy, malice, deceit, intrigue and cruelty. Selfishness becomes pervasive. It divides humans. It is easy to enslave divided people.",
                                "Solution: By making 'currency' and 'power' abstract and free from toxicity, humans will be free from all problems and distortions. The devaluation of humans will end. A dignified life will be accessible to all.",
                                "Process: A1 - To make 'currency' abstract, it will be mandatory to do all transactions online or through mobile. Providing apps, mobile and ubiquitous network will be the responsibility of the system. There will be no provision of cash. Reasons will have to be given for making all types of payments. By making currency abstract and free from toxicity, humans will be free from all distortions. Any secret transaction will be illegal, all payments will be transparent.",
                                "2 We have already taken a step in the direction of making currency abstract, many people today are doing transactions through mobile.",
                                "3 By making this step ubiquitous, we will make currency non-toxic.",
                                "4 As soon as currency becomes non-toxic, direct control of all the wealth and resources of water, land and sky will be established in the hands of all people. Humans will be freed from cruel economic slavery and scarcity. Sources of exploitation will end. Individualism will end. Selfishness and mirage will not remain, as a result, there will be no fighting, quarreling and enmity. Instead of economy, work system will be established. Man will be freed from the monkey trap.",
                                "Everyone's respect will be equal, meaning everyone's salary will be the same online. From minister to watchman, officer to peon, public or private, everyone's salary will be the same online. As a result, show-off, frustration, inferiority complex, suffocation, anger and mental agitation will disappear."
                            ];
                            foreach ($paragraphs as $paragraph) {
                                echo '<div class="content-paragraph fade-in">';
                                echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="content-card fade-in">';
                            echo '<h5><i class="fas fa-file-alt"></i> Page ' . $page . '</h5>';
                            echo '<div class="segment-content">';
                            echo '<p>This is page ' . $page . ' content. Content will be added here.</p>';
                            echo '<p>More information about this section will be available soon.</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="dashboard-preview" id="dashboardPreview">
                        <h4><i class="fas fa-tachometer-alt"></i> <span>Dashboard Preview</span></h4>
                        <p>After successful login, you will be redirected to our advanced dashboard where you can access document management, user data and analytics.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Pagination Controls -->
                    <div class="pagination-controls">
                        <button class="page-btn" onclick="window.location.href='index.php?page=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-left"></i>
                            <span>Previous</span>
                        </button>
                        <button class="page-btn" onclick="window.location.href='index.php?page=<?php echo min(5, $page + 1); ?>'" <?php echo $page >= 5 ? 'disabled' : ''; ?>>
                            <span>Next</span>
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
                <span>Join Us</span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#opinionModal">
                <i class="far fa-comment-dots"></i>
                <span>Give Opinion</span>
            </button>
            <button class="mobile-action-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </button>
        </div>
    </div>

    <!-- Join Us Modal -->
    <div class="modal fade" id="joinModal" tabindex="-1" aria-labelledby="joinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><span>Join Us</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="join-form-container slide-in">
                        <form id="joinForm" method="POST">
                            <input type="hidden" name="action" value="join_us">
                            <div class="mb-4">
                                <label for="joinName" class="form-label">
                                    <i class="fas fa-user"></i>
                                    <span>Full Name *</span>
                                </label>
                                <input type="text" class="form-control" id="joinName" name="name" placeholder="Enter your full name" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinEmail" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email Address *</span>
                                </label>
                                <input type="email" class="form-control" id="joinEmail" name="email" placeholder="name@example.com" required>
                            </div>
                            <div class="mb-4">
                                <label for="joinPhone" class="form-label">
                                    <i class="fas fa-phone"></i>
                                    <span>Mobile Number *</span>
                                </label>
                                <input type="tel" class="form-control" id="joinPhone" name="phone" placeholder="+91 9876543210" required maxlength="10" pattern="[0-9]{10}">
                                <div class="phone-validation" id="joinPhoneValidation">Enter a valid 10-digit mobile number</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars"></i>
                                    <span>Gender *</span>
                                </label>
                                <div class="gender-selection">
                                    <div class="gender-option" data-gender="male">
                                        <i class="fas fa-male"></i>
                                        <span>Male</span>
                                    </div>
                                    <div class="gender-option" data-gender="female">
                                        <i class="fas fa-female"></i>
                                        <span>Female</span>
                                    </div>
                                    <div class="gender-option" data-gender="other">
                                        <i class="fas fa-transgender-alt"></i>
                                        <span>Other</span>
                                    </div>
                                </div>
                                <input type="hidden" id="joinGender" name="gender" required>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="joinTerms" required>
                                <label class="form-check-label" for="joinTerms">
                                    I agree to the terms and conditions
                                </label>
                            </div>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span>Submit Membership</span>
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
                    <h5 class="modal-title"><i class="far fa-comment-dots me-2"></i><span>Give Your Opinion</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="opinionForm" method="POST">
                        <input type="hidden" name="action" value="submit_opinion">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="userName" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="userEmail" name="email" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="userPhone" name="phone" placeholder="+91 9876543210" maxlength="10" pattern="[0-9]{10}">
                            <div class="phone-validation" id="opinionPhoneValidation">Enter a valid 10-digit mobile number</div>
                        </div>
                        <div class="mb-4">
                            <label for="userOpinion" class="form-label">Your Opinion *</label>
                            <textarea class="form-control" id="userOpinion" name="opinion" rows="4" placeholder="Please share your opinion in detail..." required maxlength="500"></textarea>
                            <div class="word-count-container">
                                <div class="word-count" id="wordCount">Words: 0/20</div>
                                <div id="wordLimitMessage" style="font-size: 0.85rem; color: #666;"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit" id="submitOpinionBtn">
                            <i class="fas fa-paper-plane me-2"></i>
                            <span>Submit Opinion</span>
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
                    <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i><span>Admin Login</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-4">
                            <label for="loginEmail" class="form-label">Admin Username *</label>
                            <input type="text" class="form-control" id="loginEmail" name="username" placeholder="admin" required>
                            <div class="form-text">Default: admin</div>
                        </div>
                        <div class="mb-4">
                            <label for="loginPassword" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="admin" required>
                            <div class="form-text">Default: admin</div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-lock me-2"></i>
                            <span>Log In</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/index.js"></script>

    <!-- Custom Language Selector (powered by Google Translate) -->
    <div class="gt-widget" id="gtWidget">
        <div class="gt-toggle" id="gtToggle" onclick="toggleGTDropdown(event)">
            <i class="fas fa-globe gt-icon"></i>
            <span class="gt-current" id="gtCurrentLang">Hindi</span>
            <i class="fas fa-chevron-down gt-arrow" id="gtArrow"></i>
        </div>
        <div class="gt-dropdown" id="gtDropdown">
            <div class="gt-dropdown-header">Select Language</div>
            <div class="gt-options" id="gtOptions"></div>
        </div>
        <div id="google_translate_element" style="display:none !important;height:0 !important;overflow:hidden !important;"></div>
    </div>

    <script type="text/javascript">
        // Language list
        var gtLanguages = [
            {code:'en', name:'English'},
            {code:'hi', name:'Hindi'},
            {code:'bn', name:'Bengali'},
            {code:'ta', name:'Tamil'},
            {code:'te', name:'Telugu'},
            {code:'mr', name:'Marathi'},
            {code:'gu', name:'Gujarati'},
            {code:'kn', name:'Kannada'},
            {code:'ml', name:'Malayalam'},
            {code:'or', name:'Odia'},
            {code:'pa', name:'Punjabi'},
            {code:'as', name:'Assamese'},
            {code:'ur', name:'Urdu'},
            {code:'ne', name:'Nepali'},
            {code:'sd', name:'Sindhi'},
            {code:'fr', name:'French'},
            {code:'es', name:'Spanish'},
            {code:'ar', name:'Arabic'},
            {code:'zh-CN', name:'Chinese'},
            {code:'ru', name:'Russian'},
            {code:'de', name:'German'},
            {code:'ja', name:'Japanese'},
            {code:'pt', name:'Portuguese'}
        ];

        // Set Hindi as default language on first visit
        (function() {
            var match = document.cookie.match(/googtrans=([^;]*)/);
            if (!match || !match[1] || match[1] === '') {
                document.cookie = "googtrans=/en/hi; path=/";
                document.cookie = "googtrans=/en/hi; path=/; domain=." + location.hostname;
            }
        })();

        // Detect current language from cookie
        function getCurrentLang() {
            var match = document.cookie.match(/googtrans=\/en\/([^;]*)/);
            return match ? match[1] : 'hi';
        }

        // Build the custom dropdown options
        function buildGTDropdown() {
            var container = document.getElementById('gtOptions');
            var currentLang = getCurrentLang();
            var html = '';
            gtLanguages.forEach(function(lang) {
                var isActive = lang.code === currentLang;
                html += '<div class="gt-option' + (isActive ? ' active' : '') + '" data-lang="' + lang.code + '" onclick="selectLanguage(\'' + lang.code + '\')">' +
                    '<span class="gt-option-name">' + lang.name + '</span>' +
                    (isActive ? '<i class="fas fa-check"></i>' : '') +
                '</div>';
            });
            container.innerHTML = html;

            // Update toggle button text
            var current = gtLanguages.find(function(l) { return l.code === currentLang; });
            if (current) {
                document.getElementById('gtCurrentLang').textContent = current.name;
            }
        }

        // Toggle dropdown with timing guard to prevent open/close flicker
        var gtLastToggle = 0;
        function toggleGTDropdown(e) {
            e.stopPropagation();
            e.preventDefault();
            gtLastToggle = Date.now();
            var dropdown = document.getElementById('gtDropdown');
            var arrow = document.getElementById('gtArrow');
            dropdown.classList.toggle('show');
            arrow.classList.toggle('open');
        }

        // Select a language - programmatically trigger Google Translate
        function selectLanguage(langCode) {
            gtLastToggle = Date.now();
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = langCode;
                combo.dispatchEvent(new Event('change'));
            } else {
                // Fallback: set cookie and reload
                document.cookie = "googtrans=/en/" + langCode + "; path=/";
                document.cookie = "googtrans=/en/" + langCode + "; path=/; domain=." + location.hostname;
                location.reload();
            }

            // Update UI
            var langObj = gtLanguages.find(function(l) { return l.code === langCode; });
            if (langObj) {
                document.getElementById('gtCurrentLang').textContent = langObj.name;
            }
            document.getElementById('gtDropdown').classList.remove('show');
            document.getElementById('gtArrow').classList.remove('open');

            // Rebuild to update active state
            setTimeout(function() { buildGTDropdown(); }, 100);
        }

        // Close dropdown when clicking outside (with timing guard)
        document.addEventListener('click', function(e) {
            if (Date.now() - gtLastToggle < 400) return;
            var widget = document.getElementById('gtWidget');
            if (widget && !widget.contains(e.target)) {
                document.getElementById('gtDropdown').classList.remove('show');
                document.getElementById('gtArrow').classList.remove('open');
            }
        });

        // Google Translate init (hidden, only used as engine)
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'hi,bn,ta,te,mr,gu,kn,ml,or,pa,as,ur,ne,sd,en,fr,es,ar,zh-CN,ru,de,ja,pt',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }

        // Hide ALL Google Translate UI (we use our own custom dropdown)
        function hideAllGTUI() {
            var kids = document.body.children;
            for (var i = 0; i < kids.length; i++) {
                if (kids[i].classList && kids[i].classList.contains('skiptranslate') && kids[i].id !== 'gtWidget') {
                    kids[i].style.setProperty('display', 'none', 'important');
                    kids[i].style.setProperty('height', '0', 'important');
                }
            }
            document.querySelectorAll('.goog-te-banner-frame, .VIpgJd-ZVi9od-ORHb-OEVmcd, #goog-gt-tt').forEach(function(el) {
                el.style.setProperty('display', 'none', 'important');
            });
            document.body.style.setProperty('top', '0px', 'important');
        }
        setInterval(hideAllGTUI, 100);

        // Build dropdown on load
        document.addEventListener('DOMContentLoaded', buildGTDropdown);
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <style>
        /* Custom Language Widget */
        .gt-widget {
            position: fixed;
            bottom: 90px;
            right: 20px;
            z-index: 9999;
            font-family: 'Inter', sans-serif;
        }
        .gt-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 24px rgba(6, 30, 41, 0.10), 0 1px 4px rgba(6, 30, 41, 0.06);
            border: 1.5px solid rgba(95, 149, 152, 0.15);
            transition: all 0.3s ease;
            user-select: none;
        }
        .gt-toggle:hover {
            box-shadow: 0 8px 32px rgba(6, 30, 41, 0.16);
            transform: translateY(-2px);
            border-color: rgba(95, 149, 152, 0.35);
        }
        .gt-toggle:active {
            transform: translateY(0);
        }
        .gt-icon {
            color: #5f9598;
            font-size: 1.1rem;
        }
        .gt-current {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1d546d;
        }
        .gt-arrow {
            font-size: 0.65rem;
            color: #999;
            transition: transform 0.3s ease;
            margin-left: 2px;
        }
        .gt-arrow.open {
            transform: rotate(180deg);
        }
        .gt-dropdown {
            position: absolute;
            bottom: calc(100% + 10px);
            right: 0;
            width: 230px;
            max-height: 340px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(6, 30, 41, 0.16), 0 2px 8px rgba(6, 30, 41, 0.06);
            border: 1px solid rgba(95, 149, 152, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(0.97);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }
        .gt-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .gt-dropdown-header {
            padding: 14px 18px 10px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5f9598;
            border-bottom: 1px solid rgba(95, 149, 152, 0.1);
        }
        .gt-options {
            max-height: 280px;
            overflow-y: auto;
            padding: 6px 0;
        }
        .gt-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 18px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-size: 0.88rem;
            color: #444;
        }
        .gt-option:hover {
            background: rgba(95, 149, 152, 0.08);
            color: #1d546d;
        }
        .gt-option.active {
            color: #1d546d;
            font-weight: 700;
            background: rgba(95, 149, 152, 0.06);
        }
        .gt-option .fa-check {
            color: #5f9598;
            font-size: 0.7rem;
        }
        .gt-options::-webkit-scrollbar {
            width: 4px;
        }
        .gt-options::-webkit-scrollbar-track {
            background: transparent;
        }
        .gt-options::-webkit-scrollbar-thumb {
            background: rgba(95, 149, 152, 0.2);
            border-radius: 10px;
        }
        /* Hide ALL Google Translate UI */
        body > .skiptranslate {
            display: none !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        .goog-te-banner-frame {
            display: none !important;
        }
        #goog-gt-tt {
            display: none !important;
        }
        #google_translate_element {
            display: none !important;
        }
        body {
            top: 0px !important;
        }
        @media (max-width: 768px) {
            .gt-widget {
                bottom: 80px;
                right: 12px;
            }
            .gt-toggle {
                padding: 10px 16px;
                gap: 8px;
            }
            .gt-current {
                font-size: 0.82rem;
            }
            .gt-dropdown {
                width: 200px;
                right: 0;
            }
        }
    </style>
</body>
</html>
