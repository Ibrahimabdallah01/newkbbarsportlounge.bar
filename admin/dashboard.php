<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Dashboard";
$current_page = "dashboard";
$use_charts = true; // Enable Chart.js

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

// Fetch statistics
$employee_count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$department_count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));
$this_week_start = date("Y-m-d", strtotime("monday this week"));
$this_month_start = date("Y-m-01");

// Today's check-ins
$today_checkins = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) = ?");
$today_checkins->execute([$today]);
$today_checkins_count = $today_checkins->fetchColumn();

// Yesterday's check-ins
$yesterday_checkins = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) = ?");
$yesterday_checkins->execute([$yesterday]);
$yesterday_checkins_count = $yesterday_checkins->fetchColumn();

// This week's check-ins
$week_checkins = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) >= ?");
$week_checkins->execute([$this_week_start]);
$week_checkins_count = $week_checkins->fetchColumn();

// This month's check-ins
$month_checkins = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) >= ?");
$month_checkins->execute([$this_month_start]);
$month_checkins_count = $month_checkins->fetchColumn();

// Get recent attendances
$recent_attendances = $pdo->query("SELECT a.*, e.name as employee_name FROM attendances a JOIN employees e ON a.employee_id = e.id ORDER BY a.check_in DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get attendance data for the last 7 days (for chart)
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date("Y-m-d", strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE DATE(check_in) = ?");
    $stmt->execute([$date]);
    $count = $stmt->fetchColumn();
    $chart_data[] = [
        'date' => date("M d", strtotime($date)),
        'count' => $count
    ];
}

// Department-wise attendance (for pie chart)
$dept_attendance = $pdo->query("SELECT d.name, COUNT(a.id) as count FROM departments d LEFT JOIN employees e ON d.id = e.department_id LEFT JOIN attendances a ON e.id = a.employee_id AND DATE(a.check_in) = '$today' GROUP BY d.id, d.name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $company_name; ?> - Professional Attendance Management System">
    <meta name="author" content="<?php echo $company_name; ?>">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $company_name : $company_name; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
    /* ==================== KB BAR THEME COLORS ==================== */
    :root {
        --kb-gold: #d4af37;
        --kb-dark-gold: #b8860b;
        --kb-dark: #1a1a1a;
        --kb-dark-alt: #2d2d2d;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        overflow-x: hidden;
        background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    #wrapper {
        display: flex;
        min-height: 100vh;
    }

    #page-content-wrapper {
        flex: 1;
        min-width: 0;
        background: transparent;
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(26, 26, 26, 0.95) 0%, rgba(45, 45, 45, 0.95) 100%);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-overlay.active {
        display: flex;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(212, 175, 55, 0.2);
        border-top: 5px solid var(--kb-gold);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ==================== SIDEBAR ==================== */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        transition: margin 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
        border-right: 2px solid var(--kb-gold);
    }

    .sidebar-logo {
        padding: 1.5rem 1rem;
        text-align: center;
        background: rgba(0, 0, 0, 0.3);
        border-bottom: 2px solid rgba(212, 175, 55, 0.3);
    }

    .logo-container {
        margin-bottom: 1rem;
    }

    .company-logo {
        max-width: 120px;
        height: auto;
        border-radius: 15px;
        border: 2px solid rgba(212, 175, 55, 0.5);
        padding: 8px;
        background: #000;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        transition: all 0.3s ease;
    }

    .company-logo:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
    }

    .company-info {
        color: white;
    }

    .company-name {
        font-size: 1rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
        color: var(--kb-gold);
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        line-height: 1.3;
    }

    .system-name {
        font-size: 0.75rem;
        opacity: 0.9;
        margin-bottom: 0;
        color: rgba(255, 255, 255, 0.8);
    }

    .list-group-item {
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.9);
        padding: 1rem 1.5rem;
        transition: all 0.3s;
        font-size: 0.95rem;
    }

    .list-group-item:hover {
        background: rgba(212, 175, 55, 0.1);
        color: var(--kb-gold);
        padding-left: 2rem;
        border-left: 3px solid var(--kb-gold);
    }

    .list-group-item.active {
        background: rgba(212, 175, 55, 0.15);
        color: var(--kb-gold);
        border-left: 4px solid var(--kb-gold);
        font-weight: 600;
    }

    .list-group-item i {
        width: 20px;
        text-align: center;
        color: var(--kb-gold);
    }

    .sidebar-divider {
        height: 1px;
        background: rgba(212, 175, 55, 0.2);
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

    .user-details {
        flex: 1;
    }

    .user-name {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: white;
    }

    .user-role {
        font-size: 0.75rem;
        opacity: 0.8;
        margin-bottom: 0;
        color: var(--kb-gold);
    }

    /* ==================== NAVBAR ==================== */
    .navbar {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        border-bottom: 2px solid var(--kb-gold) !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .btn-primary,
    #sidebarToggle {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border: 1px solid var(--kb-gold) !important;
        color: white !important;
    }

    .btn-primary:hover,
    #sidebarToggle:hover {
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%) !important;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4) !important;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .nav-link:hover {
        color: var(--kb-gold) !important;
    }

    .badge.bg-danger {
        background: var(--kb-gold) !important;
    }

    /* ==================== PAGE HEADER ==================== */
    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        border-left: 4px solid var(--kb-gold);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .page-title {
        color: var(--kb-dark);
        font-weight: 700;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }

    .page-title i {
        color: var(--kb-gold);
    }

    .badge.bg-primary {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
    }

    .badge.bg-success {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        border: 1px solid var(--kb-gold);
        color: var(--kb-gold) !important;
    }

    /* ==================== STAT CARDS ==================== */
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.3s;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .stat-card-primary {
        border-left: 4px solid var(--kb-gold);
    }

    .stat-card-primary .stat-icon {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%);
        color: white;
    }

    .stat-card-warning {
        border-left: 4px solid var(--kb-dark-gold);
    }

    .stat-card-warning .stat-icon {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: var(--kb-gold);
    }

    .stat-card-success {
        border-left: 4px solid #28a745;
    }

    .stat-card-success .stat-icon {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .stat-card-info {
        border-left: 4px solid #17a2b8;
    }

    .stat-card-info .stat-icon {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--kb-dark);
    }

    .stat-label {
        color: #6c757d;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .stat-link {
        color: var(--kb-gold);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .stat-link:hover {
        color: var(--kb-dark-gold);
    }

    .stat-trend {
        font-size: 0.875rem;
        font-weight: 600;
    }

    .trend-up {
        color: #28a745;
    }

    .trend-down {
        color: #dc3545;
    }

    /* ==================== CARDS ==================== */
    .chart-card,
    .activity-card,
    .quick-actions-card,
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        color: white !important;
        border-bottom: 2px solid var(--kb-gold) !important;
        border-radius: 15px 15px 0 0 !important;
        padding: 1rem 1.5rem;
    }

    .card-header .card-title {
        color: white !important;
        margin-bottom: 0;
    }

    .card-header i {
        color: var(--kb-gold) !important;
    }

    /* ==================== TABLES ==================== */
    table thead {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
    }

    table thead th {
        color: var(--kb-gold) !important;
        border: none;
    }

    table tbody tr:hover {
        background: rgba(212, 175, 55, 0.05);
    }

    .text-primary {
        color: var(--kb-gold) !important;
    }

    /* ==================== BUTTONS ==================== */
    .btn-warning {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        border: 1px solid var(--kb-gold) !important;
        color: var(--kb-gold) !important;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--kb-dark-alt) 0%, var(--kb-dark) 100%) !important;
        color: white !important;
    }

    .btn-outline-primary {
        border: 2px solid var(--kb-gold) !important;
        color: var(--kb-gold) !important;
    }

    .btn-outline-primary:hover {
        background: var(--kb-gold) !important;
        color: white !important;
    }

    /* ==================== DROPDOWN ==================== */
    .dropdown-menu {
        border: 1px solid var(--kb-gold);
    }

    .dropdown-header {
        color: var(--kb-gold);
        font-weight: 700;
    }

    .dropdown-item:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .notification-dropdown {
        min-width: 300px;
    }

    .badge-sm {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.7rem;
    }

    /* ==================== FOOTER ==================== */
    .main-footer {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        padding: 1.5rem 0;
        margin-top: 3rem;
        border-top: 2px solid var(--kb-gold);
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.2);
    }

    .main-footer strong {
        color: var(--kb-gold);
    }

    .main-footer a {
        color: var(--kb-gold);
        text-decoration: none;
    }

    .main-footer a:hover {
        color: var(--kb-dark-gold);
    }

    /* ==================== SCROLL TO TOP ==================== */
    #scrollToTop {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%);
        color: white;
        border: 2px solid rgba(212, 175, 55, 0.3);
        cursor: pointer;
        display: none;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        transition: all 0.3s;
    }

    #scrollToTop:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6);
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%);
    }

    .system-info li {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .system-info li:last-child {
        border-bottom: none;
    }

    /* ==================== MOBILE RESPONSIVE ==================== */
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

        #page-content-wrapper {
            width: 100%;
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

        .company-logo {
            max-width: 90px;
        }

        .company-name {
            font-size: 0.9rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .stat-card {
            padding: 1.25rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            font-size: 1.75rem;
        }

        .stat-number {
            font-size: 1.75rem;
        }
    }

    @media (max-width: 576px) {
        .stat-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }
    }

    /* Custom Scrollbar */
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

    #sidebar-wrapper::-webkit-scrollbar-thumb:hover {
        background: rgba(212, 175, 55, 0.7);
    }

    @media print {

        .navbar,
        .btn,
        #scrollToTop,
        #sidebar-wrapper,
        .main-footer {
            display: none !important;
        }
    }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

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
        <div id="page-content-wrapper">
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
                            <!-- Notifications -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge bg-danger badge-sm">3</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                                    <h6 class="dropdown-header">Notifications</h6>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-user-check text-success me-2"></i>
                                        New employee registered
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        Late check-in detected
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-calendar text-info me-2"></i>
                                        Monthly report ready
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-center" style="color: var(--kb-gold);" href="#">View
                                        all notifications</a>
                                </div>
                            </li>

                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1" style="color: var(--kb-gold);"></i>
                                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user me-2" style="color: var(--kb-gold);"></i>Profile
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-cog me-2" style="color: var(--kb-gold);"></i>Settings
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php">
                                        <i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout
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
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="page-title">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                            </h1>
                            <p class="text-muted mb-0">Welcome back,
                                <?php echo htmlspecialchars($_SESSION["user_name"]); ?>! Here's what's happening today.
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-primary p-2">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('l, F d, Y'); ?>
                            </span>
                            <span class="badge bg-success p-2 ms-2">
                                <i class="fas fa-clock me-1"></i>
                                <span id="currentTime"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-3 g-md-4 mb-4">
                    <!-- Total Employees -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $employee_count; ?></h3>
                                <p class="stat-label">Total Employees</p>
                                <a href="employees.php" class="stat-link">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Total Departments -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $department_count; ?></h3>
                                <p class="stat-label">Departments</p>
                                <a href="departments.php" class="stat-link">
                                    Manage <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Check-ins -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-success">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $today_checkins_count; ?></h3>
                                <p class="stat-label">Today's Check-ins</p>
                                <?php 
                                $percentage = $yesterday_checkins_count > 0 
                                    ? round((($today_checkins_count - $yesterday_checkins_count) / $yesterday_checkins_count) * 100) 
                                    : 0;
                                ?>
                                <span class="stat-trend <?php echo $percentage >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                    <i class="fas fa-<?php echo $percentage >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs($percentage); ?>% from yesterday
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- This Week -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-info">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo $week_checkins_count; ?></h3>
                                <p class="stat-label">This Week</p>
                                <a href="reports.php" class="stat-link">
                                    View Report <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-3 g-md-4 mb-4">
                    <!-- Attendance Trend Chart -->
                    <div class="col-12 col-lg-8">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-area me-2"></i>Attendance Trend (Last 7 Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="attendanceChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Department Distribution -->
                    <div class="col-12 col-lg-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-pie me-2"></i>Today by Department
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Quick Actions -->
                <div class="row g-3 g-md-4">
                    <!-- Recent Attendances -->
                    <div class="col-12 col-lg-8">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>Recent Check-ins
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Check-in Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recent_attendances) > 0): ?>
                                            <?php foreach($recent_attendances as $att): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                                    <strong><?php echo htmlspecialchars($att['employee_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($att['check_in'])); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $status = $att['status'];
                                                        $badge_class = 'bg-secondary';
                                                        if ($status == 'present') $badge_class = 'bg-success';
                                                        elseif ($status == 'late') $badge_class = 'bg-warning';
                                                        elseif ($status == 'absent') $badge_class = 'bg-danger';
                                                        ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">
                                                    <i class="fas fa-info-circle me-2"></i>No check-ins recorded today
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="reports.php" class="btn btn-outline-primary btn-sm">
                                        View All Attendances <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-12 col-lg-4">
                        <div class="card quick-actions-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="employees.php" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Add New Employee
                                    </a>
                                    <a href="departments.php" class="btn btn-warning">
                                        <i class="fas fa-building me-2"></i>Add Department
                                    </a>
                                    <a href="reports.php" class="btn btn-success">
                                        <i class="fas fa-file-export me-2"></i>Generate Report
                                    </a>
                                    <a href="#" onclick="window.print(); return false;" class="btn btn-info">
                                        <i class="fas fa-print me-2"></i>Print Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System Info Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle me-2"></i>System Info
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled system-info">
                                    <li>
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        <strong>Month:</strong> <?php echo $month_checkins_count; ?> check-ins
                                    </li>
                                    <li>
                                        <i class="fas fa-server text-success me-2"></i>
                                        <strong>Status:</strong> <span class="text-success">Online</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-code-branch me-2"></i>
                                        <strong>Version:</strong> 1.0.0
                                    </li>
                                </ul>
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

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('wrapper').classList.toggle('toggled');
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const wrapper = document.getElementById('wrapper');
            const sidebar = document.getElementById('sidebar-wrapper');
            const toggleBtn = document.getElementById('sidebarToggle');

            if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    wrapper.classList.remove('toggled');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('wrapper').classList.remove('toggled');
            }
        });

        // Current Time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Scroll to top button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        if (scrollToTopBtn) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    scrollToTopBtn.style.display = 'block';
                } else {
                    scrollToTopBtn.style.display = 'none';
                }
            });

            scrollToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Hide loading overlay
        setTimeout(function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
            }
        }, 300);

        // Add fade-in animation to content
        const pageContent = document.querySelector('.container-fluid');
        if (pageContent) {
            pageContent.classList.add('fade-in');
        }

        // Attendance Chart
        const attCtx = document.getElementById('attendanceChart');
        if (attCtx) {
            const attData = <?php echo json_encode($chart_data); ?>;
            new Chart(attCtx, {
                type: 'line',
                data: {
                    labels: attData.map(d => d.date),
                    datasets: [{
                        label: 'Check-ins',
                        data: attData.map(d => d.count),
                        backgroundColor: 'rgba(212, 175, 55, 0.1)',
                        borderColor: '#d4af37',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#d4af37',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Department Chart
        const deptCtx = document.getElementById('departmentChart');
        if (deptCtx) {
            const deptData = <?php echo json_encode($dept_attendance); ?>;
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: deptData.map(d => d.name),
                    datasets: [{
                        data: deptData.map(d => d.count),
                        backgroundColor: [
                            '#d4af37',
                            '#b8860b',
                            '#28a745',
                            '#17a2b8',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
    </script>
</body>

</html>