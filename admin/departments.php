<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Department Management";
$current_page = "departments";
$use_datatables = true;

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

$error = "";
$success = "";

// Handle Add/Edit Department
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"]);
    $description = sanitize_input($_POST["description"]);
    $department_id = isset($_POST["department_id"]) ? sanitize_input($_POST["department_id"]) : null;

    if (empty($name)) {
        $error = "Department name is required.";
    } else {
        if ($department_id) {
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $department_id])) {
                $success = "Department updated successfully.";
            } else {
                $error = "Error updating department.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = "Department added successfully.";
            } else {
                $error = "Error adding department. Department name might already exist.";
            }
        }
    }
}

// Handle Delete Department
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $department_id = sanitize_input($_GET["id"]);
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    if ($stmt->execute([$department_id])) {
        $success = "Department deleted successfully.";
    } else {
        $error = "Error deleting department.";
    }
}

// Fetch all departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . $company_name; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

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

    .fas.fa-user-circle {
        color: var(--kb-gold) !important;
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

    /* ==================== MODERN CARD ==================== */
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
        margin-bottom: 0;
    }

    .modern-card .card-header .card-title i {
        color: var(--kb-gold);
    }

    .modern-card .card-header .btn-light {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%);
        color: white;
        border: none;
        font-weight: 600;
    }

    .modern-card .card-header .btn-light:hover {
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
    }

    /* ==================== TABLES ==================== */
    table.dataTable thead {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
    }

    table.dataTable thead th {
        color: var(--kb-gold) !important;
        border: none;
        font-weight: 600;
    }

    table.dataTable tbody tr:hover {
        background: rgba(212, 175, 55, 0.05);
    }

    .badge.bg-secondary {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        color: var(--kb-gold) !important;
        border: 1px solid var(--kb-gold);
    }

    /* ==================== BUTTONS ==================== */
    .btn-warning {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border: none !important;
        color: white !important;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%) !important;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        border: none !important;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%) !important;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
    }

    /* ==================== MODAL ==================== */
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

    /* ==================== ALERTS ==================== */
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

    /* ==================== DROPDOWN ==================== */
    .dropdown-menu {
        border: 1px solid var(--kb-gold);
    }

    .dropdown-item:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .dropdown-item i {
        color: var(--kb-gold);
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

    /* ==================== DATATABLES CUSTOM ==================== */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 0.375rem 0.75rem;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: var(--kb-gold);
        outline: none;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border-color: var(--kb-gold) !important;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: rgba(212, 175, 55, 0.1) !important;
        border-color: var(--kb-gold) !important;
        color: var(--kb-dark) !important;
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

        .page-title {
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
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-building me-2"></i>Department Management
                    </h1>
                    <p class="text-muted mb-0">Manage all departments in your organization</p>
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

                <!-- Departments Card -->
                <div class="card modern-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>All Departments
                            </h5>
                            <button type="button" class="btn btn-light btn-sm mt-2 mt-md-0" data-bs-toggle="modal"
                                data-bs-target="#addEditDepartmentModal">
                                <i class="fas fa-plus me-2"></i>Add Department
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="departmentsTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $dept): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $dept["id"]; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($dept["name"]); ?></strong></td>
                                        <td><?php echo htmlspecialchars($dept["description"]); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning"
                                                data-id="<?php echo $dept["id"]; ?>"
                                                data-name="<?php echo htmlspecialchars($dept["name"]); ?>"
                                                data-description="<?php echo htmlspecialchars($dept["description"]); ?>"
                                                data-bs-toggle="modal" data-bs-target="#addEditDepartmentModal">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <a href="departments.php?action=delete&id=<?php echo $dept["id"]; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this department?');">
                                                <i class="fas fa-trash me-1"></i>Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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

    <!-- Add/Edit Department Modal -->
    <div class="modal fade" id="addEditDepartmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="departments.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-building me-2"></i>Add/Edit Department
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="department_id" id="department_id">
                        <div class="mb-3">
                            <label for="department_name" class="form-label">
                                <i class="fas fa-tag me-1"></i>Department Name
                            </label>
                            <input type="text" class="form-control" id="department_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="department_description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control" id="department_description" name="description"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" title="Go to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#departmentsTable').DataTable({
            responsive: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ departments"
            }
        });

        // Modal handling
        $('#addEditDepartmentModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var description = button.data('description');

            var modal = $(this);
            if (id) {
                modal.find('.modal-title').html('<i class="fas fa-edit me-2"></i>Edit Department');
                modal.find('#department_id').val(id);
                modal.find('#department_name').val(name);
                modal.find('#department_description').val(description);
            } else {
                modal.find('.modal-title').html('<i class="fas fa-plus me-2"></i>Add New Department');
                modal.find('#department_id').val('');
                modal.find('#department_name').val('');
                modal.find('#department_description').val('');
            }
        });

        // Sidebar toggle
        $('#sidebarToggle').on('click', function(e) {
            e.preventDefault();
            $('#wrapper').toggleClass('toggled');
        });

        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(event) {
            var wrapper = $('#wrapper');
            var sidebar = $('#sidebar-wrapper');
            var toggleBtn = $('#sidebarToggle');

            if ($(window).width() <= 768 && wrapper.hasClass('toggled')) {
                if (!sidebar.is(event.target) && sidebar.has(event.target).length === 0 &&
                    !toggleBtn.is(event.target) && toggleBtn.has(event.target).length === 0) {
                    wrapper.removeClass('toggled');
                }
            }
        });

        // Handle window resize
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $('#wrapper').removeClass('toggled');
            }
        });

        // Scroll to top button
        var scrollToTopBtn = $('#scrollToTop');
        $(window).scroll(function() {
            if ($(window).scrollTop() > 300) {
                scrollToTopBtn.fadeIn();
            } else {
                scrollToTopBtn.fadeOut();
            }
        });

        scrollToTopBtn.click(function() {
            $('html, body').animate({
                scrollTop: 0
            }, 'smooth');
        });
    });
    </script>
</body>

</html>