<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/auth.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Employee Management";
$current_page = "employees";
$use_datatables = true;

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

$error = "";
$success = "";

// Handle Add/Edit Employee
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"]);
    $email = sanitize_input($_POST["email"]);
    $phone = sanitize_input($_POST["phone"]);
    $department_id = sanitize_input($_POST["department_id"]);
    $address = sanitize_input($_POST["address"]);
    $password = sanitize_input($_POST["password"]);
    $employee_id = isset($_POST["employee_id"]) ? sanitize_input($_POST["employee_id"]) : null;

    if (empty($name) || empty($email) || empty($department_id)) {
        $error = "Name, email, and department are required.";
    } else {
        if ($employee_id) {
            if (!empty($password)) {
                $hashed_password = hash_password($password);
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, department_id = ?, address = ?, password = ? WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $department_id, $address, $hashed_password, $employee_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, department_id = ?, address = ? WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $department_id, $address, $employee_id]);
            }
            if ($result) {
                $success = "Employee updated successfully.";
            } else {
                $error = "Error updating employee.";
            }
        } else {
            if (empty($password)) {
                $error = "Password is required for new employee.";
            } else {
                $hashed_password = hash_password($password);
                $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, department_id, address, password) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $phone, $department_id, $address, $hashed_password])) {
                    $success = "Employee added successfully.";
                } else {
                    $error = "Error adding employee. Email might already exist.";
                }
            }
        }
    }
}

// Handle Delete Employee
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $employee_id = sanitize_input($_GET["id"]);
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$employee_id])) {
        $success = "Employee deleted successfully.";
    } else {
        $error = "Error deleting employee.";
    }
}

// Fetch all employees with department names
$employees = $pdo->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . $company_name; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

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

    /* TABLES */
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

    .badge.bg-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    }

    /* BUTTONS */
    .btn-warning {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border: none !important;
        color: white !important;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, var(--kb-gold) 0%, var(--kb-dark-gold) 100%) !important;
    }

    .btn-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
        border: none !important;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        border: none !important;
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

    /* DROPDOWN */
    .dropdown-menu {
        border: 1px solid var(--kb-gold);
    }

    .dropdown-item:hover {
        background: rgba(212, 175, 55, 0.1);
    }

    .dropdown-item i {
        color: var(--kb-gold);
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

    .main-footer a:hover {
        color: var(--kb-dark-gold);
    }

    /* DATATABLES CUSTOM */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: var(--kb-gold);
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
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users me-2"></i>Employee Management
                    </h1>
                    <p class="text-muted mb-0">Manage all employees in your organization</p>
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

                <!-- Employees Card -->
                <div class="card modern-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>All Employees
                            </h5>
                            <button type="button" class="btn btn-light btn-sm mt-2 mt-md-0" data-bs-toggle="modal"
                                data-bs-target="#addEditEmployeeModal">
                                <i class="fas fa-user-plus me-2"></i>Add Employee
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="employeesTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Department</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo $emp["id"]; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($emp["name"]); ?></strong></td>
                                        <td><?php echo htmlspecialchars($emp["email"]); ?></td>
                                        <td><?php echo htmlspecialchars($emp["phone"]); ?></td>
                                        <td><span
                                                class="badge bg-info"><?php echo htmlspecialchars($emp["department_name"]); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning"
                                                data-id="<?php echo $emp["id"]; ?>"
                                                data-name="<?php echo htmlspecialchars($emp["name"]); ?>"
                                                data-email="<?php echo htmlspecialchars($emp["email"]); ?>"
                                                data-phone="<?php echo htmlspecialchars($emp["phone"]); ?>"
                                                data-department="<?php echo $emp["department_id"]; ?>"
                                                data-address="<?php echo htmlspecialchars($emp["address"]); ?>"
                                                data-bs-toggle="modal" data-bs-target="#addEditEmployeeModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="employee_attendance.php?id=<?php echo $emp["id"]; ?>"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-calendar-check"></i>
                                            </a>
                                            <a href="employees.php?action=delete&id=<?php echo $emp["id"]; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i>
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

    <!-- Add/Edit Employee Modal -->
    <div class="modal fade" id="addEditEmployeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="employees.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user me-2"></i>Add/Edit Employee
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="employee_id" id="employee_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Name
                                    </label>
                                    <input type="text" class="form-control" id="employee_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="employee_email" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Phone
                                    </label>
                                    <input type="text" class="form-control" id="employee_phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_department" class="form-label">
                                        <i class="fas fa-building me-1"></i>Department
                                    </label>
                                    <select class="form-control" id="employee_department" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept["id"]; ?>">
                                            <?php echo htmlspecialchars($dept["name"]); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="employee_address" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Address
                            </label>
                            <textarea class="form-control" id="employee_address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="employee_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password
                            </label>
                            <input type="password" class="form-control" id="employee_password" name="password">
                            <div class="form-text">Leave blank to keep current password (for edit).</div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#employeesTable').DataTable({
            responsive: true,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ employees"
            }
        });

        $('#addEditEmployeeModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var email = button.data('email');
            var phone = button.data('phone');
            var department = button.data('department');
            var address = button.data('address');

            var modal = $(this);
            if (id) {
                modal.find('.modal-title').html('<i class="fas fa-edit me-2"></i>Edit Employee');
                modal.find('#employee_id').val(id);
                modal.find('#employee_name').val(name);
                modal.find('#employee_email').val(email);
                modal.find('#employee_phone').val(phone);
                modal.find('#employee_department').val(department);
                modal.find('#employee_address').val(address);
                modal.find('#employee_password').removeAttr('required');
            } else {
                modal.find('.modal-title').html('<i class="fas fa-plus me-2"></i>Add New Employee');
                modal.find('#employee_id').val('');
                modal.find('#employee_name').val('');
                modal.find('#employee_email').val('');
                modal.find('#employee_phone').val('');
                modal.find('#employee_department').val('');
                modal.find('#employee_address').val('');
                modal.find('#employee_password').attr('required', 'required');
            }
        });

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