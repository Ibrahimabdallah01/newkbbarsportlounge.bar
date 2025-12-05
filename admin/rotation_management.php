<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Rotation Management";
$current_page = "rotation";
$use_datatables = true;

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

$error = "";
$success = "";

// Fetch all rotation groups with details
$rotation_groups = $pdo->query("
    SELECT rg.*, sp.name as shift_name, sp.work_days, sp.off_days, d.name as department_name,
    (SELECT COUNT(*) FROM rotation_group_members WHERE rotation_group_id = rg.id AND is_active = 1) as member_count
    FROM rotation_groups rg
    LEFT JOIN shift_patterns sp ON rg.shift_pattern_id = sp.id
    LEFT JOIN departments d ON rg.department_id = d.id
    ORDER BY rg.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch shift patterns for dropdown
$shift_patterns = $pdo->query("SELECT * FROM shift_patterns WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all employees for adding to rotation
$employees = $pdo->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.name ASC")->fetchAll(PDO::FETCH_ASSOC);
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

    .btn-primary:hover,
    #sidebarToggle:hover {
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%) !important;
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
    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        border-left: 4px solid var(--kb-gold);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--kb-dark);
        margin-bottom: 0.5rem;
    }

    .page-title i {
        color: var(--kb-gold);
    }

    /* STAT CARDS */
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        width: 65px;
        height: 65px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        flex-shrink: 0;
    }

    .stat-card-primary {
        border-left: 4px solid var(--kb-gold);
    }

    .stat-card-primary .stat-icon {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
        color: white;
    }

    .stat-card-success {
        border-left: 4px solid #22c55e;
    }

    .stat-card-success .stat-icon {
        background: linear-gradient(135deg, #22c55e, #4ade80);
        color: white;
    }

    .stat-card-info {
        border-left: 4px solid #3b82f6;
    }

    .stat-card-info .stat-icon {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: white;
    }

    .stat-card-warning {
        border-left: 4px solid #f59e0b;
    }

    .stat-card-warning .stat-icon {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: white;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--kb-dark);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    /* MODERN CARD */
    .modern-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .modern-card .card-header {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 1rem 1.5rem;
        border: none;
        border-bottom: 2px solid var(--kb-gold);
    }

    .modern-card .card-header .card-title {
        color: white;
    }

    .modern-card .card-header .card-title i {
        color: var(--kb-gold);
    }

    /* ROTATION GROUP CARDS */
    .rotation-group-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .rotation-group-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .rotation-group-header {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid var(--kb-gold);
    }

    .rotation-group-header h6 {
        margin: 0;
        font-weight: 600;
        color: var(--kb-gold);
    }

    .rotation-group-body {
        padding: 1.25rem;
        flex: 1;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }

    .info-row:last-child {
        margin-bottom: 0;
    }

    .info-row i {
        width: 20px;
        font-size: 1rem;
    }

    .rotation-group-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid rgba(212, 175, 55, 0.2);
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* BUTTONS */
    .btn-info {
        background: linear-gradient(135deg, #3b82f6, #60a5fa) !important;
        border: none !important;
    }

    .btn-warning {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold)) !important;
        border: none !important;
        color: white !important;
    }

    /* MODAL */
    .modal-header {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        border-bottom: 2px solid var(--kb-gold);
    }

    .modal-header .modal-title i {
        color: var(--kb-gold);
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .form-label i {
        color: var(--kb-gold);
    }

    /* ALERTS */
    .alert {
        border-radius: 12px;
        border-left: 4px solid;
    }

    .alert-danger {
        border-left-color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
    }

    .alert-success {
        border-left-color: #28a745;
        background: rgba(40, 167, 69, 0.1);
    }

    /* BADGE */
    .badge.bg-success {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    .badge.bg-secondary {
        background: linear-gradient(135deg, var(--kb-dark), var(--kb-dark-alt)) !important;
        border: 1px solid var(--kb-gold);
        color: var(--kb-gold) !important;
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

        .stat-card {
            padding: 1.25rem;
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 1.75rem;
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
                <div class="page-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="page-title">
                                <i class="fas fa-sync-alt me-2"></i>Rotation Management
                            </h1>
                            <p class="text-muted mb-0">Manage employee work rotation schedules</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <button class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addRotationGroupModal">
                                <i class="fas fa-plus me-2"></i>Create Rotation Group
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Quick Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo count($rotation_groups); ?></h3>
                                <p class="stat-label">Rotation Groups</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-success">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo count($shift_patterns); ?></h3>
                                <p class="stat-label">Shift Patterns</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-info">
                            <div class="stat-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo count($employees); ?></h3>
                                <p class="stat-label">Available Employees</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number">Week <?php echo date('W'); ?></h3>
                                <p class="stat-label">Current Week</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rotation Groups Card -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>All Rotation Groups
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rotation_groups)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-sync-alt fa-4x text-muted mb-3"></i>
                            <h5>No Rotation Groups Yet</h5>
                            <p class="text-muted">Create your first rotation group to start managing schedules</p>
                            <button class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addRotationGroupModal">
                                <i class="fas fa-plus me-2"></i>Create Rotation Group
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($rotation_groups as $group): ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="rotation-group-card">
                                    <div class="rotation-group-header">
                                        <h6><?php echo htmlspecialchars($group['name']); ?></h6>
                                        <span
                                            class="badge <?php echo $group['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $group['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="rotation-group-body">
                                        <div class="info-row">
                                            <i class="fas fa-building text-primary"></i>
                                            <span><?php echo $group['department_name'] ?? 'All Departments'; ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-clock text-success"></i>
                                            <span><?php echo htmlspecialchars($group['shift_name']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-calendar-alt text-info"></i>
                                            <span><?php echo $group['work_days']; ?> days work,
                                                <?php echo $group['off_days']; ?> day(s) off</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-users text-warning"></i>
                                            <span><?php echo $group['member_count']; ?> members</span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-play-circle text-secondary"></i>
                                            <span>Started:
                                                <?php echo date('M d, Y', strtotime($group['start_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="rotation-group-footer">
                                        <a href="rotation_details.php?id=<?php echo $group['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                        <a href="rotation_schedule.php?id=<?php echo $group['id']; ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-calendar me-1"></i>Schedule
                                        </a>
                                        <button class="btn btn-sm btn-warning"
                                            onclick="editGroup(<?php echo $group['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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

    <!-- Add Rotation Group Modal -->
    <div class="modal fade" id="addRotationGroupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="rotation_create.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Create New Rotation Group
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="group_name" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Group Name
                                    </label>
                                    <input type="text" class="form-control" id="group_name" name="name"
                                        placeholder="e.g., Bartender Team A" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department_id" class="form-label">
                                        <i class="fas fa-building me-1"></i>Department
                                    </label>
                                    <select class="form-control" id="department_id" name="department_id">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shift_pattern_id" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Shift Pattern
                                    </label>
                                    <select class="form-control" id="shift_pattern_id" name="shift_pattern_id" required>
                                        <option value="">Select Shift Pattern</option>
                                        <?php foreach ($shift_patterns as $pattern): ?>
                                        <option value="<?php echo $pattern['id']; ?>"
                                            data-work="<?php echo $pattern['work_days']; ?>"
                                            data-off="<?php echo $pattern['off_days']; ?>">
                                            <?php echo htmlspecialchars($pattern['name']); ?>
                                            (<?php echo $pattern['work_days']; ?> days work,
                                            <?php echo $pattern['off_days']; ?> off)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">
                                        <i class="fas fa-calendar-alt me-1"></i>Start Date
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                placeholder="Optional description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Create Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
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

    function editGroup(id) {
        alert('Edit functionality for group ' + id + ' - implement in rotation_edit.php');
    }
    </script>
</body>

</html>