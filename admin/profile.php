<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Profile Settings";
$current_page = "profile";

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

$admin_id = $_SESSION["user_id"];
$error = "";
$success = "";

// Fetch admin details
$stmt = $pdo->prepare("SELECT name, email FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    redirect("../logout.php");
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $name = sanitize_input($_POST["name"]);
    $email = sanitize_input($_POST["email"]);

    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "This email is already taken by another admin.";
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $admin_id])) {
                $_SESSION["user_name"] = $name;
                $success = "Profile updated successfully!";
                $admin["name"] = $name;
                $admin["email"] = $email;
            } else {
                $error = "Error updating profile.";
            }
        }
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $current_password = sanitize_input($_POST["current_password"]);
    $new_password = sanitize_input($_POST["new_password"]);
    $confirm_new_password = sanitize_input($_POST["confirm_new_password"]);

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_new_password) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $hashed_password = $stmt->fetchColumn();

        if (verify_password($current_password, $hashed_password)) {
            $new_hashed_password = hash_password($new_password);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_hashed_password, $admin_id])) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Get admin statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM employees");
$total_employees = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM departments");
$total_departments = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) = CURDATE()");
$today_attendance = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . $company_name; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        --kb-gold: #d4af37;
        --kb-dark-gold: #b8860b;
        --kb-dark: #1a1a1a;
        --kb-dark-alt: #2d2d2d;
    }

    body {
        background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    #wrapper {
        display: flex;
        min-height: 100vh;
    }

    /* SIDEBAR */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        border-right: 2px solid var(--kb-gold);
        transition: margin 0.3s;
        display: flex;
        flex-direction: column;
    }

    .sidebar-logo {
        padding: 1.5rem 1rem;
        text-align: center;
        background: rgba(0, 0, 0, 0.3);
        border-bottom: 2px solid rgba(212, 175, 55, 0.3);
    }

    .company-logo {
        max-width: 120px;
        border-radius: 15px;
        border: 2px solid rgba(212, 175, 55, 0.5);
        padding: 8px;
        background: #000;
    }

    .company-name {
        font-size: 1rem;
        font-weight: bold;
        color: var(--kb-gold);
        margin-top: 1rem;
    }

    .system-name {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .list-group-item {
        background: transparent;
        color: rgba(255, 255, 255, 0.9);
        border: none;
        padding: 1rem 1.5rem;
        transition: all 0.3s;
    }

    .list-group-item:hover {
        background: rgba(212, 175, 55, 0.1);
        color: var(--kb-gold);
        border-left: 3px solid var(--kb-gold);
        padding-left: 2rem;
    }

    .list-group-item.active {
        background: rgba(212, 175, 55, 0.15);
        color: var(--kb-gold);
        border-left: 4px solid var(--kb-gold);
    }

    .list-group-item i {
        color: var(--kb-gold);
        width: 20px;
    }

    .sidebar-divider {
        background: rgba(212, 175, 55, 0.2);
        height: 1px;
        margin: 0.5rem 1rem;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.3);
        border-top: 2px solid rgba(212, 175, 55, 0.3);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
    }

    .user-avatar {
        color: var(--kb-gold);
    }

    .user-name {
        color: white;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .user-role {
        color: var(--kb-gold);
        font-size: 0.75rem;
    }

    /* NAVBAR */
    .navbar {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        border-bottom: 2px solid var(--kb-gold) !important;
    }

    .btn-primary,
    #sidebarToggle {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border: 1px solid var(--kb-gold) !important;
        color: white !important;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .nav-link:hover {
        color: var(--kb-gold) !important;
    }

    .fas.fa-user-circle {
        color: var(--kb-gold) !important;
    }

    /* PAGE HEADER */
    .page-header-modern {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        border: 2px solid var(--kb-gold);
    }

    .page-title-modern {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--kb-gold);
        margin-bottom: 0.5rem;
    }

    .page-title-modern i {
        color: var(--kb-gold);
    }

    .page-subtitle-modern {
        color: rgba(255, 255, 255, 0.9);
        margin: 0;
        font-size: 0.95rem;
    }

    /* ALERTS */
    .modern-alert {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .alert-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .alert-content {
        flex: 1;
    }

    /* PROFILE OVERVIEW CARD */
    .profile-overview-card {
        border: 2px solid var(--kb-gold);
        border-radius: 15px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }

    .profile-avatar-large {
        position: relative;
        display: inline-block;
        margin-bottom: 1.5rem;
    }

    .avatar-circle-large {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: rgba(212, 175, 55, 0.2);
        border: 4px solid var(--kb-gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 700;
        color: var(--kb-gold);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .avatar-badge {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        font-size: 1rem;
    }

    .admin-badge {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
    }

    .admin-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: white;
    }

    .admin-role {
        color: var(--kb-gold);
        margin-bottom: 2rem;
    }

    .admin-role i {
        color: var(--kb-gold);
    }

    .admin-stats {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(212, 175, 55, 0.3);
    }

    .stat-box-inline {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(212, 175, 55, 0.1);
        border-radius: 10px;
        transition: all 0.3s;
        border: 1px solid rgba(212, 175, 55, 0.2);
    }

    .stat-box-inline:hover {
        background: rgba(212, 175, 55, 0.15);
        transform: translateX(5px);
    }

    .stat-icon-inline {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: rgba(212, 175, 55, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--kb-gold);
    }

    .stat-number-inline {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--kb-gold);
    }

    .stat-label-inline {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    /* MODERN CARDS */
    .modern-card-white {
        border: none;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }

    .modern-card-white:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
    }

    .card-header-gradient {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        padding: 1rem 1.5rem;
        border-radius: 15px 15px 0 0;
        border: none;
        border-bottom: 2px solid var(--kb-gold);
    }

    .card-title-white {
        color: white;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .card-title-white i {
        color: var(--kb-gold);
    }

    .card-header-custom {
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        border-bottom: 2px solid var(--kb-gold);
        border-radius: 15px 15px 0 0;
    }

    .card-title-custom {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--kb-gold);
    }

    .card-title-custom i {
        color: var(--kb-gold);
    }

    /* INFO LIST */
    .info-list-modern {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .info-item-modern {
        display: flex;
        gap: 1rem;
        padding: 0.75rem;
        background: #f9fafb;
        border-radius: 10px;
        transition: all 0.3s;
    }

    .info-item-modern:hover {
        background: rgba(212, 175, 55, 0.1);
        transform: translateX(5px);
    }

    .info-icon-modern {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-label-modern {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value-modern {
        font-size: 0.9rem;
        color: #374151;
        font-weight: 600;
        word-break: break-word;
    }

    .badge-status-active {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* MODERN FORM */
    .modern-form .form-label-modern {
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }

    .modern-form .form-label-modern i {
        color: var(--kb-gold);
    }

    .form-control-modern {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 0.625rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .form-control-modern:focus {
        border-color: var(--kb-gold);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        outline: none;
    }

    .password-input-wrapper {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0.5rem;
    }

    .password-toggle:hover {
        color: var(--kb-gold);
    }

    .password-requirements {
        background: rgba(212, 175, 55, 0.1);
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid var(--kb-gold);
    }

    .password-requirements h6 {
        font-size: 0.875rem;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .password-requirements h6 i {
        color: var(--kb-gold);
    }

    .password-requirements ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .password-requirements li {
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }

    .form-actions-modern {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-gradient-primary,
    .btn-gradient-warning {
        padding: 0.625rem 1.5rem;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        color: white;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
    }

    .btn-gradient-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .btn-gradient-primary:hover,
    .btn-gradient-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: white;
    }

    .btn-outline-secondary {
        padding: 0.625rem 1.5rem;
        border-radius: 8px;
        border: 2px solid var(--kb-gold);
        background: white;
        color: var(--kb-gold);
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
    }

    .btn-outline-secondary:hover {
        border-color: var(--kb-dark-gold);
        color: var(--kb-dark-gold);
        background: rgba(212, 175, 55, 0.1);
    }

    /* FOOTER */
    .main-footer {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        padding: 1.5rem 0;
        margin-top: 3rem;
        border-top: 2px solid var(--kb-gold);
        color: rgba(255, 255, 255, 0.8);
    }

    .main-footer strong {
        color: var(--kb-gold);
    }

    .main-footer a {
        color: var(--kb-gold);
        text-decoration: none;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            margin-left: -260px;
            position: fixed;
            z-index: 1000;
            height: 100vh;
            overflow-y: auto;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        #wrapper.toggled::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .page-header-modern {
            padding: 1.5rem;
        }

        .page-title-modern {
            font-size: 1.5rem;
        }

        .avatar-circle-large {
            width: 120px;
            height: 120px;
            font-size: 2.5rem;
        }

        .admin-name {
            font-size: 1.25rem;
        }

        .modern-card-white:hover {
            transform: none;
        }

        .form-actions-modern {
            flex-direction: column;
        }

        .btn-gradient-primary,
        .btn-gradient-warning,
        .btn-outline-secondary {
            width: 100%;
        }
    }

    #sidebar-wrapper::-webkit-scrollbar {
        width: 6px;
    }

    #sidebar-wrapper::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.3);
    }

    #sidebar-wrapper::-webkit-scrollbar-thumb {
        background: rgba(212, 175, 55, 0.5);
        border-radius: 3px;
    }
    </style>
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="border-end" id="sidebar-wrapper">
            <!-- Logo & Company Section -->
            <div class="sidebar-logo">
                <div class="logo-container">
                    <img src="<?php echo $company_logo; ?>" alt="<?php echo $company_name; ?>" class="company-logo">
                </div>
                <div class="company-info">
                    <h5 class="company-name"><?php echo $company_name; ?></h5>
                    <p class="system-name"><?php echo $system_name; ?></p>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'departments') ? 'active' : ''; ?>"
                    href="departments.php">
                    <i class="fas fa-building me-2"></i>Departments
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'employees') ? 'active' : ''; ?>"
                    href="employees.php">
                    <i class="fas fa-users me-2"></i>Employees
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'rotation') ? 'active' : ''; ?>"
                    href="rotation_management.php">
                    <i class="fas fa-sync-alt me-2"></i>Rotation
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'leave') ? 'active' : ''; ?>"
                    href="leave_management.php">
                    <i class="fas fa-calendar-times me-2"></i>Leave
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'reports') ? 'active' : ''; ?>"
                    href="reports.php">
                    <i class="fas fa-file-alt me-2"></i>Reports
                </a>
                <a class="list-group-item list-group-item-action <?php echo ($current_page == 'profile') ? 'active' : ''; ?>"
                    href="profile.php">
                    <i class="fas fa-user-circle me-2"></i>Profile
                </a>

                <div class="sidebar-divider"></div>

                <a class="list-group-item list-group-item-action" href="#" onclick="window.print(); return false;">
                    <i class="fas fa-print me-2"></i>Print
                </a>
                <a class="list-group-item list-group-item-action" href="../logout.php"
                    onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>

            <!-- User Info at Bottom -->
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle fa-2x"></i>
                    </div>
                    <div class="user-details">
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"]); ?></p>
                        <p class="user-role">Administrator</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page content wrapper -->
        <div id="page-content-wrapper" class="flex-fill">
            <!-- Top navigation -->
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                                    data-bs-toggle="dropdown">
                                    <i
                                        class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Page Header -->
                <div class="page-header-modern mb-4">
                    <div class="page-header-content">
                        <h1 class="page-title-modern">
                            <i class="fas fa-user-cog me-2"></i>Profile Settings
                        </h1>
                        <p class="page-subtitle-modern">Manage your administrator account settings and preferences</p>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show modern-alert" role="alert">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error!</strong> <?php echo $error; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show modern-alert" role="alert">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Success!</strong> <?php echo $success; ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Profile Overview Card -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card profile-overview-card">
                            <div class="card-body text-center">
                                <div class="profile-avatar-large">
                                    <div class="avatar-circle-large">
                                        <?php echo strtoupper(substr($admin['name'], 0, 2)); ?>
                                    </div>
                                    <div class="avatar-badge admin-badge">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                </div>
                                <h3 class="admin-name"><?php echo htmlspecialchars($admin['name']); ?></h3>
                                <p class="admin-role">
                                    <i class="fas fa-user-shield me-2"></i>System Administrator
                                </p>
                                <div class="admin-stats">
                                    <div class="stat-box-inline">
                                        <div class="stat-icon-inline">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-details-inline">
                                            <div class="stat-number-inline"><?php echo $total_employees; ?></div>
                                            <div class="stat-label-inline">Employees</div>
                                        </div>
                                    </div>
                                    <div class="stat-box-inline">
                                        <div class="stat-icon-inline">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="stat-details-inline">
                                            <div class="stat-number-inline"><?php echo $total_departments; ?></div>
                                            <div class="stat-label-inline">Departments</div>
                                        </div>
                                    </div>
                                    <div class="stat-box-inline">
                                        <div class="stat-icon-inline">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="stat-details-inline">
                                            <div class="stat-number-inline"><?php echo $today_attendance; ?></div>
                                            <div class="stat-label-inline">Today</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Info Card -->
                        <div class="card modern-card-white mt-4">
                            <div class="card-header-custom">
                                <h5 class="card-title-custom">
                                    <i class="fas fa-info-circle me-2"></i>Account Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="info-list-modern">
                                    <div class="info-item-modern">
                                        <div class="info-icon-modern">
                                            <i class="fas fa-id-badge"></i>
                                        </div>
                                        <div class="info-content-modern">
                                            <div class="info-label-modern">User ID</div>
                                            <div class="info-value-modern">#<?php echo $admin_id; ?></div>
                                        </div>
                                    </div>
                                    <div class="info-item-modern">
                                        <div class="info-icon-modern">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="info-content-modern">
                                            <div class="info-label-modern">Email</div>
                                            <div class="info-value-modern">
                                                <?php echo htmlspecialchars($admin['email']); ?></div>
                                        </div>
                                    </div>
                                    <div class="info-item-modern">
                                        <div class="info-icon-modern">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="info-content-modern">
                                            <div class="info-label-modern">Status</div>
                                            <div class="info-value-modern">
                                                <span class="badge-status-active">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forms Column -->
                    <div class="col-xl-8 col-lg-7">
                        <!-- Update Profile Card -->
                        <div class="card modern-card-white mb-4">
                            <div class="card-header-gradient">
                                <h5 class="card-title-white">
                                    <i class="fas fa-user-edit me-2"></i>Update Profile Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="POST" class="modern-form">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label-modern">
                                                <i class="fas fa-user me-2"></i>Full Name
                                            </label>
                                            <input type="text" class="form-control-modern" id="name" name="name"
                                                value="<?php echo htmlspecialchars($admin["name"]); ?>"
                                                placeholder="Enter your full name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label-modern">
                                                <i class="fas fa-envelope me-2"></i>Email Address
                                            </label>
                                            <input type="email" class="form-control-modern" id="email" name="email"
                                                value="<?php echo htmlspecialchars($admin["email"]); ?>"
                                                placeholder="admin@example.com" required>
                                        </div>
                                    </div>
                                    <div class="form-actions-modern mt-4">
                                        <button type="submit" class="btn-gradient-primary">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                        <button type="reset" class="btn-outline-secondary">
                                            <i class="fas fa-undo me-2"></i>Reset
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password Card -->
                        <div class="card modern-card-white">
                            <div class="card-header-gradient">
                                <h5 class="card-title-white">
                                    <i class="fas fa-lock me-2"></i>Change Password
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="POST" class="modern-form">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label-modern">
                                            <i class="fas fa-key me-2"></i>Current Password
                                        </label>
                                        <div class="password-input-wrapper">
                                            <input type="password" class="form-control-modern" id="current_password"
                                                name="current_password" placeholder="Enter current password" required>
                                            <button type="button" class="password-toggle"
                                                onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label-modern">
                                                <i class="fas fa-lock me-2"></i>New Password
                                            </label>
                                            <div class="password-input-wrapper">
                                                <input type="password" class="form-control-modern" id="new_password"
                                                    name="new_password" placeholder="Enter new password" required>
                                                <button type="button" class="password-toggle"
                                                    onclick="togglePassword('new_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_new_password" class="form-label-modern">
                                                <i class="fas fa-lock me-2"></i>Confirm New Password
                                            </label>
                                            <div class="password-input-wrapper">
                                                <input type="password" class="form-control-modern"
                                                    id="confirm_new_password" name="confirm_new_password"
                                                    placeholder="Confirm new password" required>
                                                <button type="button" class="password-toggle"
                                                    onclick="togglePassword('confirm_new_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="password-requirements mt-3">
                                        <h6><i class="fas fa-shield-alt me-2"></i>Password Requirements:</h6>
                                        <ul>
                                            <li>At least 8 characters long</li>
                                            <li>Include uppercase and lowercase letters</li>
                                            <li>Include at least one number</li>
                                            <li>Use special characters for extra security</li>
                                        </ul>
                                    </div>
                                    <div class="form-actions-modern mt-4">
                                        <button type="submit" class="btn-gradient-warning">
                                            <i class="fas fa-shield-alt me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="main-footer">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="mb-0">
                                <strong><?php echo $company_name; ?></strong> &copy; <?php echo date('Y'); ?>. All
                                rights reserved.
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0">
                                Version 1.0.0 |
                                <a href="#">Help</a> |
                                <a href="#">Contact Support</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.parentElement.querySelector('.password-toggle');
        const icon = button.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    $(document).ready(function() {
        $('#sidebarToggle').on('click', function(e) {
            e.preventDefault();
            $('#wrapper').toggleClass('toggled');
        });

        $(document).on('click', function(event) {
            if ($(window).width() <= 768 && $('#wrapper').hasClass('toggled')) {
                if (!$('#sidebar-wrapper').is(event.target) && $('#sidebar-wrapper').has(event.target)
                    .length === 0 &&
                    !$('#sidebarToggle').is(event.target) && $('#sidebarToggle').has(event.target)
                    .length === 0) {
                    $('#wrapper').removeClass('toggled');
                }
            }
        });

        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $('#wrapper').removeClass('toggled');
            }
        });
    });
    </script>
</body>

</html>