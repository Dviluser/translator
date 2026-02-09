<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error_message'] = 'Please login first';
    header('Location: index.php');
    exit;
}

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
$admin_email = $_SESSION['admin_email'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];

// Handle language switching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_language') {
    $language = $_POST['language'];
    $_SESSION['dashboard_language'] = $language;
    echo json_encode(['success' => true, 'language' => $language]);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Get current password from database
                $sql = "SELECT password FROM admin_users WHERE id = $admin_id";
                $result = $conn->query($sql);
                $admin = $result->fetch_assoc();
                
                if (password_verify($current_password, $admin['password'])) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin_users SET password = '$hashed_password' WHERE id = $admin_id";
                        
                        if ($conn->query($sql)) {
                            $_SESSION['success_message'] = 'Password changed successfully!';
                        } else {
                            $_SESSION['error_message'] = 'Error changing password';
                        }
                    } else {
                        $_SESSION['error_message'] = 'New passwords do not match';
                    }
                } else {
                    $_SESSION['error_message'] = 'Current password is incorrect';
                }
                break;
                
            case 'create_admin':
                $username = $conn->real_escape_string($_POST['username']);
                $email = $conn->real_escape_string($_POST['email']);
                $full_name = $conn->real_escape_string($_POST['full_name']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $conn->real_escape_string($_POST['role']);
                
                $sql = "INSERT INTO admin_users (username, email, full_name, password, role, created_by) 
                        VALUES ('$username', '$email', '$full_name', '$password', '$role', '$admin_username')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = 'Admin user created successfully!';
                } else {
                    $_SESSION['error_message'] = 'Error creating admin user: ' . $conn->error;
                }
                break;
                
            case 'fetch_opinion':
                // Fetch opinion data for modal
                if (isset($_POST['opinion_id']) && is_numeric($_POST['opinion_id'])) {
                    $opinion_id = intval($_POST['opinion_id']);
                    $sql = "SELECT * FROM opinions WHERE id = $opinion_id";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        $opinion = $result->fetch_assoc();
                        
                        // Format date
                        $submission_date = date('d M Y, h:i A', strtotime($opinion['submission_date']));
                        
                        // Get language name
                        $language_names = [
                            'en' => 'English',
                            'hi' => 'हिन्दी',
                            'es' => 'Español',
                            'fr' => 'Français',
                            'de' => 'Deutsch',
                            'ja' => '日本語'
                        ];
                        $language = isset($language_names[$opinion['language']]) ? 
                                   $language_names[$opinion['language']] : 
                                   ucfirst($opinion['language']);
                        
                        // Return opinion data as JSON
                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'id' => $opinion['id'],
                                'name' => htmlspecialchars($opinion['name']),
                                'email' => htmlspecialchars($opinion['email']),
                                'phone' => htmlspecialchars($opinion['phone'] ?: 'N/A'),
                                'category' => htmlspecialchars($opinion['category'] ?: 'General'),
                                'opinion' => htmlspecialchars($opinion['opinion']),
                                'language' => $language,
                                'submission_date' => $submission_date,
                                'status' => ucfirst($opinion['status'])
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Opinion not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid opinion ID']);
                }
                exit;
                break;
        }
        header('Location: dashboard.php');
        exit;
    }
}

// Get statistics
$members_count = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$opinions_count = $conn->query("SELECT COUNT(*) as count FROM opinions")->fetch_assoc()['count'];

// Calculate growth percentage (dummy calculation)
$growth_percentage = rand(5, 25);

// Get members for table (with pagination and search)
$members_page = isset($_GET['members_page']) ? intval($_GET['members_page']) : 1;
$members_per_page = 12; // Changed from 6 to 12
$members_offset = ($members_page - 1) * $members_per_page;

// Search functionality for members
$members_search = isset($_GET['members_search']) ? $conn->real_escape_string($_GET['members_search']) : '';
$members_where = '';
if (!empty($members_search)) {
    $members_where = " WHERE name LIKE '%$members_search%' OR email LIKE '%$members_search%' OR phone LIKE '%$members_search%'";
}

$members_query = "SELECT * FROM members $members_where ORDER BY join_date DESC LIMIT $members_offset, $members_per_page";
$members_result = $conn->query($members_query);

// Get total members count with search
$total_members_query = "SELECT COUNT(*) as count FROM members $members_where";
$total_members_count = $conn->query($total_members_query)->fetch_assoc()['count'];
$total_members_pages = ceil($total_members_count / $members_per_page);

// Get opinions for table (with pagination and search)
$opinions_page = isset($_GET['opinions_page']) ? intval($_GET['opinions_page']) : 1;
$opinions_per_page = 12; // Changed from 6 to 12
$opinions_offset = ($opinions_page - 1) * $opinions_per_page;

// Search functionality for opinions
$opinions_search = isset($_GET['opinions_search']) ? $conn->real_escape_string($_GET['opinions_search']) : '';
$opinions_where = '';
if (!empty($opinions_search)) {
    $opinions_where = " WHERE name LIKE '%$opinions_search%' OR email LIKE '%$opinions_search%' OR phone LIKE '%$opinions_search%' OR category LIKE '%$opinions_search%' OR opinion LIKE '%$opinions_search%'";
}

$opinions_query = "SELECT * FROM opinions $opinions_where ORDER BY submission_date DESC LIMIT $opinions_offset, $opinions_per_page";
$opinions_result = $conn->query($opinions_query);

// Get total opinions count with search
$total_opinions_query = "SELECT COUNT(*) as count FROM opinions $opinions_where";
$total_opinions_count = $conn->query($total_opinions_query)->fetch_assoc()['count'];
$total_opinions_pages = ceil($total_opinions_count / $opinions_per_page);

// Get admin users
$admin_users_result = $conn->query("SELECT * FROM admin_users ORDER BY created_date DESC");

// Get current language from session or default to English
$current_language = isset($_SESSION['dashboard_language']) ? $_SESSION['dashboard_language'] : 'en';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sarvatantra</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body data-language="<?php echo $current_language; ?>">
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
    
    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo">
                <i class="fas fa-democrat"></i>
                <span class="translatable" data-key="logo">सर्वतंत्र</span>
            </a>
        </div>
        
        <ul class="nav-links">
            <li><a href="dashboard.php" class="<?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="translatable" data-key="dashboard">Dashboard</span>
            </a></li>
            <li><a href="dashboard.php?section=members" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'members') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="translatable" data-key="members">Members</span>
            </a></li>
            <li><a href="dashboard.php?section=opinions" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'opinions') ? 'active' : ''; ?>">
                <i class="far fa-comment-dots"></i>
                <span class="translatable" data-key="opinions">Opinions</span>
            </a></li>
            <li><a href="dashboard.php?section=admin_users" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'admin_users') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span class="translatable" data-key="admin_users">Admin Users</span>
            </a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key"></i>
                <span class="translatable" data-key="change_password">Change Password</span>
            </a></li>
        </ul>
        
        <div class="logout-btn" id="logoutBtn" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span class="translatable" data-key="logout">Logout</span>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header fade-in">
            <h1 id="pageTitle">
                <?php 
                $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
                switch($section) {
                    case 'members':
                        echo '<span class="translatable" data-key="members_title">Members</span>';
                        break;
                    case 'opinions':
                        echo '<span class="translatable" data-key="opinions_title">Opinions</span>';
                        break;
                    case 'admin_users':
                        echo '<span class="translatable" data-key="admin_users_title">Admin Users</span>';
                        break;
                    default:
                        echo '<span class="translatable" data-key="dashboard_title">Dashboard</span>';
                        break;
                }
                ?>
            </h1>
            <div class="user-info">
                <div class="user-avatar">
                    <span id="userInitial"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></span>
                </div>
                <div>
                    <div style="font-weight: 700;" id="userName"><?php echo $admin_name; ?></div>
                    <div style="font-size: 0.85rem; color: #666;" id="userEmail"><?php echo $admin_email; ?></div>
                </div>
            </div>
        </div>
        
        <?php
        $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
        
        if ($section === 'dashboard'): ?>
        <!-- Dashboard Stats -->
        <div class="stats-cards fade-in" id="dashboardStats">
            <div class="stat-card members">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="translatable" data-key="total_members">Total Members</h3>
                <div class="value" id="totalMembers"><?php echo $members_count; ?></div>
            </div>
            <div class="stat-card opinions">
                <div class="icon">
                    <i class="far fa-comment-dots"></i>
                </div>
                <h3 class="translatable" data-key="total_opinions">Total Opinions</h3>
                <div class="value" id="totalOpinions"><?php echo $opinions_count; ?></div>
            </div>
            <div class="stat-card growth">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="translatable" data-key="growth_month">Growth This Month</h3>
                <div class="value" id="growthValue">+<?php echo $growth_percentage; ?>%</div>
            </div>
        </div>
        
        <!-- Members Section -->
        <div class="content-section fade-in" id="membersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> <span class="translatable" data-key="all_members">All Members</span></h2>
                <div class="section-subtitle" id="membersCount"><span id="membersCountValue"><?php echo $members_count; ?></span> <span class="translatable" data-key="members_found">members found</span></div>
            </div>
            
            <!-- Search Box for Members -->
            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="members">
                    <input type="text" 
                           class="search-input" 
                           name="members_search" 
                           placeholder="<?php echo htmlspecialchars(translatePlaceholder('Search members by name, email or phone...', $current_language)); ?>"
                           value="<?php echo isset($_GET['members_search']) ? htmlspecialchars($_GET['members_search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        <span class="translatable" data-key="search">Search</span>
                    </button>
                    <?php if (!empty($members_search)): ?>
                    <a href="dashboard.php?section=members" class="clear-search-btn">
                        <i class="fas fa-times"></i>
                        <span class="translatable" data-key="clear">Clear</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (!empty($members_search)): ?>
            <div class="search-results-info fade-in">
                <div>
                    <span class="translatable" data-key="search_results_for">Search results for:</span>
                    <span class="search-term">"<?php echo htmlspecialchars($members_search); ?>"</span>
                    <span class="translatable" data-key="found">found</span>
                    <strong><?php echo $total_members_count; ?></strong>
                    <span class="translatable" data-key="results">results</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="join_date">JOIN DATE</th>
                            <th class="translatable" data-key="gender">GENDER</th>
                        </tr>
                    </thead>
                    <tbody id="membersTableBody">
                        <?php
                        $count = 1;
                        while ($member = $members_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $members_offset; ?></td>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                            <td><?php echo ucfirst($member['gender']); ?></td>
                        </tr>
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($members_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_members_found">No members found</div>
                                <?php if (!empty($members_search)): ?>
                                <div class="mt-2">
                                    <a href="dashboard.php?section=members" class="translatable" data-key="clear_search_and_show_all" style="color: var(--accent-teal); text-decoration: none;">
                                        Clear search and show all members
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="membersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($total_members_count, $members_offset + 1); ?>-<?php echo min($total_members_count, $members_offset + $members_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $total_members_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>" 
                       <?php echo $members_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=members&members_page=<?php echo $i; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_members_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>" 
                       <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'members'): ?>
        <!-- Members Section -->
        <div class="content-section fade-in" id="membersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-users"></i> <span class="translatable" data-key="all_members">All Members</span></h2>
                <div class="section-subtitle" id="membersCount"><span id="membersCountValue"><?php echo $total_members_count; ?></span> <span class="translatable" data-key="members_found">members found</span></div>
            </div>
            
            <!-- Search Box for Members -->
            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="members">
                    <input type="text" 
                           class="search-input" 
                           name="members_search" 
                           placeholder="<?php echo htmlspecialchars(translatePlaceholder('Search members by name, email or phone...', $current_language)); ?>"
                           value="<?php echo isset($_GET['members_search']) ? htmlspecialchars($_GET['members_search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        <span class="translatable" data-key="search">Search</span>
                    </button>
                    <?php if (!empty($members_search)): ?>
                    <a href="dashboard.php?section=members" class="clear-search-btn">
                        <i class="fas fa-times"></i>
                        <span class="translatable" data-key="clear">Clear</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (!empty($members_search)): ?>
            <div class="search-results-info fade-in">
                <div>
                    <span class="translatable" data-key="search_results_for">Search results for:</span>
                    <span class="search-term">"<?php echo htmlspecialchars($members_search); ?>"</span>
                    <span class="translatable" data-key="found">found</span>
                    <strong><?php echo $total_members_count; ?></strong>
                    <span class="translatable" data-key="results">results</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="join_date">JOIN DATE</th>
                            <th class="translatable" data-key="gender">GENDER</th>
                        </tr>
                    </thead>
                    <tbody id="membersTableBody">
                        <?php
                        $count = 1;
                        while ($member = $members_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $members_offset; ?></td>
                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td><?php echo date('d M Y', strtotime($member['join_date'])); ?></td>
                            <td><?php echo ucfirst($member['gender']); ?></td>
                        </tr>
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($members_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_members_found">No members found</div>
                                <?php if (!empty($members_search)): ?>
                                <div class="mt-2">
                                    <a href="dashboard.php?section=members" class="translatable" data-key="clear_search_and_show_all" style="color: var(--accent-teal); text-decoration: none;">
                                        Clear search and show all members
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="membersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($total_members_count, $members_offset + 1); ?>-<?php echo min($total_members_count, $members_offset + $members_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $total_members_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $members_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page - 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>" 
                       <?php echo $members_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_members_pages); $i++): ?>
                    <a class="page-btn <?php echo $members_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=members&members_page=<?php echo $i; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_members_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=members&members_page=<?php echo $members_page + 1; ?><?php echo !empty($members_search) ? '&members_search=' . urlencode($members_search) : ''; ?>" 
                       <?php echo $members_page >= $total_members_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'opinions'): ?>
        <!-- Opinions Section -->
        <div class="content-section fade-in" id="opinionsSection">
            <div class="section-header">
                <h2 class="section-title"><i class="far fa-comment-dots"></i> <span class="translatable" data-key="opinions">Opinions</span></h2>
                <div class="section-subtitle" id="opinionsCount"><span id="opinionsCountValue"><?php echo $total_opinions_count; ?></span> <span class="translatable" data-key="opinions_found">opinions found</span></div>
            </div>
            
            <!-- Search Box for Opinions -->
            <div class="search-container">
                <form method="GET" action="dashboard.php" class="search-box">
                    <input type="hidden" name="section" value="opinions">
                    <input type="text" 
                           class="search-input" 
                           name="opinions_search" 
                           placeholder="<?php echo htmlspecialchars(translatePlaceholder('Search opinions by name, email, phone, category or content...', $current_language)); ?>"
                           value="<?php echo isset($_GET['opinions_search']) ? htmlspecialchars($_GET['opinions_search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                        <span class="translatable" data-key="search">Search</span>
                    </button>
                    <?php if (!empty($opinions_search)): ?>
                    <a href="dashboard.php?section=opinions" class="clear-search-btn">
                        <i class="fas fa-times"></i>
                        <span class="translatable" data-key="clear">Clear</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (!empty($opinions_search)): ?>
            <div class="search-results-info fade-in">
                <div>
                    <span class="translatable" data-key="search_results_for">Search results for:</span>
                    <span class="search-term">"<?php echo htmlspecialchars($opinions_search); ?>"</span>
                    <span class="translatable" data-key="found">found</span>
                    <strong><?php echo $total_opinions_count; ?></strong>
                    <span class="translatable" data-key="results">results</span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="translatable" data-key="sr_no">SR. NO.</th>
                            <th class="translatable" data-key="name">NAME</th>
                            <th class="translatable" data-key="email">EMAIL</th>
                            <th class="translatable" data-key="phone">PHONE</th>
                            <th class="translatable" data-key="category">CATEGORY</th>
                            <th class="translatable" data-key="date">DATE</th>
                            <th class="translatable" data-key="action">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="opinionsTableBody">
                        <?php
                        $count = 1;
                        while ($opinion = $opinions_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $count + $opinions_offset; ?></td>
                            <td><?php echo htmlspecialchars($opinion['name']); ?></td>
                            <td><?php echo htmlspecialchars($opinion['email']); ?></td>
                            <td><?php echo htmlspecialchars($opinion['phone'] ?: 'N/A'); ?></td>
                            <td><span class="badge badge-category"><?php echo htmlspecialchars($opinion['category'] ?: 'General'); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($opinion['submission_date'])); ?></td>
                            <td>
                                <button class="action-btn btn-view" onclick="viewOpinion(<?php echo $opinion['id']; ?>)">
                                    <i class="fas fa-eye"></i> <span class="translatable" data-key="view">View</span>
                                </button>
                            </td>
                        </tr>
                        <?php 
                        $count++;
                        endwhile; 
                        
                        if ($opinions_result->num_rows == 0):
                        ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                <i class="far fa-comment-dots fa-2x mb-3" style="color: #ddd;"></i>
                                <div class="translatable" data-key="no_opinions_found">No opinions found</div>
                                <?php if (!empty($opinions_search)): ?>
                                <div class="mt-2">
                                    <a href="dashboard.php?section=opinions" class="translatable" data-key="clear_search_and_show_all" style="color: var(--accent-teal); text-decoration: none;">
                                        Clear search and show all opinions
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="opinionsPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> <?php echo min($total_opinions_count, $opinions_offset + 1); ?>-<?php echo min($total_opinions_count, $opinions_offset + $opinions_per_page); ?> <span class="translatable" data-key="of">of</span> <?php echo $total_opinions_count; ?>
                </div>
                <div class="pagination-buttons">
                    <a class="page-btn translatable" data-key="previous" <?php echo $opinions_page <= 1 ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page - 1; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>" 
                       <?php echo $opinions_page <= 1 ? 'disabled' : ''; ?>>
                        Previous
                    </a>
                    
                    <?php for ($i = 1; $i <= min(4, $total_opinions_pages); $i++): ?>
                    <a class="page-btn <?php echo $opinions_page == $i ? 'active' : ''; ?>" 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $i; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($total_opinions_pages > 4): ?>
                    <span class="page-btn disabled">...</span>
                    <?php endif; ?>
                    
                    <a class="page-btn translatable" data-key="next" <?php echo $opinions_page >= $total_opinions_pages ? 'disabled' : ''; ?> 
                       href="dashboard.php?section=opinions&opinions_page=<?php echo $opinions_page + 1; ?><?php echo !empty($opinions_search) ? '&opinions_search=' . urlencode($opinions_search) : ''; ?>" 
                       <?php echo $opinions_page >= $total_opinions_pages ? 'disabled' : ''; ?>>
                        Next
                    </a>
                </div>
            </div>
        </div>
        
        <?php elseif ($section === 'admin_users'): ?>
        <!-- Admin Users Section -->
        <div class="content-section fade-in" id="adminUsersSection">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-user-shield"></i> <span class="translatable" data-key="admin_users">Admin Users</span></h2>
                <button class="action-btn btn-create-admin" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                    <i class="fas fa-user-plus"></i> <span class="translatable" data-key="create_admin">Create Admin User</span>
                </button>
            </div>
            
            <div class="admin-users-container" id="adminUsersContainer">
                <?php while ($admin_user = $admin_users_result->fetch_assoc()): 
                    $role_label = ucfirst(str_replace('_', ' ', $admin_user['role']));
                    $status_color = $admin_user['status'] == 'active' ? 'var(--success-color)' : '#e74c3c';
                ?>
                <div class="admin-user-card">
                    <div class="admin-user-header">
                        <div class="admin-user-avatar"><?php echo strtoupper(substr($admin_user['username'], 0, 1)); ?></div>
                        <div class="admin-user-info">
                            <h4><?php echo htmlspecialchars($admin_user['username']); ?></h4>
                            <p><?php echo htmlspecialchars($admin_user['full_name']); ?></p>
                        </div>
                    </div>
                    <div class="admin-user-details">
                        <div class="detail-item">
                            <label class="translatable" data-key="role">Role</label>
                            <div class="value"><?php echo $role_label; ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="email">Email</label>
                            <div class="value"><?php echo htmlspecialchars($admin_user['email']); ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="created">Created</label>
                            <div class="value"><?php echo $admin_user['created_by'] ? 'By ' . htmlspecialchars($admin_user['created_by']) : 'System'; ?></div>
                        </div>
                        <div class="detail-item">
                            <label class="translatable" data-key="status">Status</label>
                            <div class="value" style="color: <?php echo $status_color; ?>;"><?php echo ucfirst($admin_user['status']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info" id="adminUsersPaginationInfo">
                    <span class="translatable" data-key="showing">Showing</span> 1-<?php echo $admin_users_result->num_rows; ?> <span class="translatable" data-key="of">of</span> <?php echo $admin_users_result->num_rows; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Opinion Detail Modal -->
    <div class="modal fade" id="opinionDetailModal" tabindex="-1" aria-labelledby="opinionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="far fa-comment-dots me-2"></i><span class="translatable" data-key="opinion_details">Opinion Details</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="opinionDetailBody">
                    <!-- Opinion details will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i><span class="translatable" data-key="change_password">Change Password</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label"><span class="translatable" data-key="current_password">Current Password</span></label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter current password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label"><span class="translatable" data-key="new_password">New Password</span></label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter new password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label"><span class="translatable" data-key="confirm_password">Confirm New Password</span></label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Confirm new password', $current_language)); ?>" required>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-key me-2"></i><span class="translatable" data-key="change_password_btn">Change Password</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Admin User Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><span class="translatable" data-key="create_admin_user">Create Admin User</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAdminForm" method="POST">
                        <input type="hidden" name="action" value="create_admin">
                        <div class="mb-3">
                            <label for="adminUsername" class="form-label"><span class="translatable" data-key="username">Username</span></label>
                            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter username', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminEmail" class="form-label"><span class="translatable" data-key="email_address">Email Address</span></label>
                            <input type="email" class="form-control" id="adminEmail" name="email" placeholder="admin@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminFullName" class="form-label"><span class="translatable" data-key="full_name">Full Name</span></label>
                            <input type="text" class="form-control" id="adminFullName" name="full_name" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter full name', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label"><span class="translatable" data-key="password">Password</span></label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Enter password', $current_language)); ?>" required>
                            <div class="form-text translatable" data-key="password_hint">Password must be at least 6 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="adminConfirmPassword" class="form-label"><span class="translatable" data-key="confirm_password">Confirm Password</span></label>
                            <input type="password" class="form-control" id="adminConfirmPassword" name="confirm_password" placeholder="<?php echo htmlspecialchars(translatePlaceholder('Confirm password', $current_language)); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminRole" class="form-label"><span class="translatable" data-key="role">Role</span></label>
                            <select class="form-control" id="adminRole" name="role" required>
                                <option value="admin" class="translatable" data-key="administrator">Administrator</option>
                                <option value="moderator" class="translatable" data-key="moderator">Moderator</option>
                                <option value="viewer" class="translatable" data-key="viewer">Viewer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-user-plus me-2"></i><span class="translatable" data-key="create_admin_user_btn">Create Admin User</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="assets/js/dashboard.js">
        // Translation configuration
       
    </script>
</body>
</html>

<?php
// Helper function for server-side placeholder translation (optional)
function translatePlaceholder($text, $language) {
    // Simple placeholder translations - in production, use a proper translation system
    $translations = [
        'en' => [
            'Enter current password' => 'Enter current password',
            'Enter new password' => 'Enter new password',
            'Confirm new password' => 'Confirm new password',
            'Enter username' => 'Enter username',
            'Enter full name' => 'Enter full name',
            'Enter password' => 'Enter password',
            'Confirm password' => 'Confirm password',
            'Search members by name, email or phone...' => 'Search members by name, email or phone...',
            'Search opinions by name, email, phone, category or content...' => 'Search opinions by name, email, phone, category or content...',
            'Search results for:' => 'Search results for:',
            'found' => 'found',
            'results' => 'results',
            'Clear search and show all members' => 'Clear search and show all members',
            'Clear search and show all opinions' => 'Clear search and show all opinions',
            'No members found' => 'No members found',
            'No opinions found' => 'No opinions found'
        ],
        'hi' => [
            'Enter current password' => 'वर्तमान पासवर्ड दर्ज करें',
            'Enter new password' => 'नया पासवर्ड दर्ज करें',
            'Confirm new password' => 'नए पासवर्ड की पुष्टि करें',
            'Enter username' => 'उपयोगकर्ता नाम दर्ज करें',
            'Enter full name' => 'पूरा नाम दर्ज करें',
            'Enter password' => 'पासवर्ड दर्ज करें',
            'Confirm password' => 'पासवर्ड की पुष्टि करें',
            'Search members by name, email or phone...' => 'नाम, ईमेल या फोन से सदस्य खोजें...',
            'Search opinions by name, email, phone, category or content...' => 'नाम, ईमेल, फोन, श्रेणी या विषय से राय खोजें...',
            'Search results for:' => 'खोज परिणाम:',
            'found' => 'पाए गए',
            'results' => 'परिणाम',
            'Clear search and show all members' => 'खोज साफ करें और सभी सदस्य दिखाएं',
            'Clear search and show all opinions' => 'खोज साफ करें और सभी राय दिखाएं',
            'No members found' => 'कोई सदस्य नहीं मिला',
            'No opinions found' => 'कोई राय नहीं मिली'
        ],
        'es' => [
            'Enter current password' => 'Ingrese la contraseña actual',
            'Enter new password' => 'Ingrese nueva contraseña',
            'Confirm new password' => 'Confirmar nueva contraseña',
            'Enter username' => 'Ingrese nombre de usuario',
            'Enter full name' => 'Ingrese nombre completo',
            'Enter password' => 'Ingrese contraseña',
            'Confirm password' => 'Confirmar contraseña',
            'Search members by name, email or phone...' => 'Buscar miembros por nombre, email o teléfono...',
            'Search opinions by name, email, phone, category or content...' => 'Buscar opiniones por nombre, email, teléfono, categoría o contenido...',
            'Search results for:' => 'Resultados de búsqueda para:',
            'found' => 'encontrados',
            'results' => 'resultados',
            'Clear search and show all members' => 'Limpiar búsqueda y mostrar todos los miembros',
            'Clear search and show all opinions' => 'Limpiar búsqueda y mostrar todas las opiniones',
            'No members found' => 'No se encontraron miembros',
            'No opinions found' => 'No se encontraron opiniones'
        ] 
        
    ];
    
    return isset($translations[$language][$text]) ? $translations[$language][$text] : $text;
}