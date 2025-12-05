<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Rotation Details";
$current_page = "rotation";
$use_datatables = true;

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

$rotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$rotation_id) {
    redirect("rotation_management.php");
}

$error = $_SESSION['error'] ?? "";
$success = $_SESSION['success'] ?? "";
unset($_SESSION['error'], $_SESSION['success']);

// Handle Add Employee to Rotation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $employee_id = sanitize_input($_POST['employee_id']);
    $rotation_order = sanitize_input($_POST['rotation_order']);
    
    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM rotation_group_members WHERE rotation_group_id = ? AND employee_id = ? AND is_active = 1");
        $check->execute([$rotation_id, $employee_id]);
        
        if ($check->fetchColumn() > 0) {
            $error = "Employee is already in this rotation group.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rotation_group_members (rotation_group_id, employee_id, rotation_order, join_date, is_active) 
                VALUES (?, ?, ?, CURDATE(), 1)
            ");
            
            if ($stmt->execute([$rotation_id, $employee_id, $rotation_order])) {
                $success = "Employee added to rotation group successfully!";
            } else {
                $error = "Failed to add employee to rotation.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Remove Employee from Rotation
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['member_id'])) {
    $member_id = (int)$_GET['member_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE rotation_group_members SET is_active = 0, leave_date = CURDATE() WHERE id = ?");
        if ($stmt->execute([$member_id])) {
            $success = "Employee removed from rotation group.";
        } else {
            $error = "Failed to remove employee.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch rotation group details
$rotation = $pdo->prepare("
    SELECT rg.*, sp.name as shift_name, sp.work_days, sp.off_days, sp.start_time, sp.end_time,
           d.name as department_name
    FROM rotation_groups rg
    LEFT JOIN shift_patterns sp ON rg.shift_pattern_id = sp.id
    LEFT JOIN departments d ON rg.department_id = d.id
    WHERE rg.id = ?
");
$rotation->execute([$rotation_id]);
$rotation_group = $rotation->fetch(PDO::FETCH_ASSOC);

if (!$rotation_group) {
    redirect("rotation_management.php");
}

// Fetch rotation members
$members = $pdo->prepare("
    SELECT rgm.*, e.name as employee_name, e.email as employee_email, 
           d.name as department_name
    FROM rotation_group_members rgm
    JOIN employees e ON rgm.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE rgm.rotation_group_id = ? AND rgm.is_active = 1
    ORDER BY rgm.rotation_order ASC
");
$members->execute([$rotation_id]);
$rotation_members = $members->fetchAll(PDO::FETCH_ASSOC);

// Fetch available employees
$available_employees = $pdo->prepare("
    SELECT e.*, d.name as department_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE e.id NOT IN (
        SELECT employee_id FROM rotation_group_members 
        WHERE rotation_group_id = ? AND is_active = 1
    )
    ORDER BY e.name ASC
");
$available_employees->execute([$rotation_id]);
$available_emps = $available_employees->fetchAll(PDO::FETCH_ASSOC);

$next_order = count($rotation_members) + 1;
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

    /* SIDEBAR - Same as other pages */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        border-right: 2px solid var(--kb-gold);
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
    }

    .list-group-item.active {
        background: rgba(212, 175, 55, 0.15);
        color: var(--kb-gold);
        border-left: 4px solid var(--kb-gold);
    }

    .list-group-item i {
        color: var(--kb-gold);
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

    /* NAVBAR */
    .navbar {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%) !important;
        border-bottom: 2px solid var(--kb-gold) !important;
    }

    .btn-primary,
    #sidebarToggle {
        background: linear-gradient(135deg, var(--kb-dark-gold) 0%, var(--kb-gold) 100%) !important;
        border: 1px solid var(--kb-gold) !important;
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268) !important;
        border: none !important;
    }

    .btn-info {
        background: linear-gradient(135deg, #3b82f6, #60a5fa) !important;
        border: none !important;
    }

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .fas.fa-user-circle {
        color: var(--kb-gold) !important;
    }

    /* ROTATION DETAILS HEADER */
    .rotation-details-header {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        border: 2px solid var(--kb-gold);
    }

    .rotation-details-header h1 {
        color: var(--kb-gold);
    }

    .details-meta .badge {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }

    /* INFO CARDS */
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .info-icon {
        width: 65px;
        height: 65px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
    }

    .info-icon.bg-primary {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
    }

    .info-content h3 {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--kb-dark);
        margin-bottom: 0.25rem;
    }

    .info-content p {
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

    .modern-card .card-header .card-title i {
        color: var(--kb-gold);
    }

    /* TABLE */
    table thead {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
    }

    table thead th {
        color: var(--kb-gold) !important;
        border: none;
        font-weight: 600;
    }

    .table-success {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }

    /* STATUS BADGES */
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-active {
        background: rgba(34, 197, 94, 0.15);
        color: #16a34a;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .status-rest {
        background: rgba(239, 68, 68, 0.15);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    /* BADGES */
    .badge.bg-success {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    .badge.bg-primary {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold)) !important;
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

    /* RESPONSIVE */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            margin-left: -260px;
            position: fixed;
            z-index: 1000;
            height: 100vh;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
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

        <div id="page-content-wrapper" class="flex-fill">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="collapse navbar-collapse">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i
                                        class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="../logout.php">Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-3 p-md-4">
                <!-- Back Buttons -->
                <div class="mb-3">
                    <a href="rotation_management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Rotations
                    </a>
                    <a href="rotation_schedule.php?id=<?php echo $rotation_id; ?>" class="btn btn-info">
                        <i class="fas fa-calendar me-2"></i>View Schedule
                    </a>
                </div>

                <!-- Page Header -->
                <div class="rotation-details-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2"><?php echo htmlspecialchars($rotation_group['name']); ?></h1>
                            <div class="details-meta">
                                <span class="badge bg-info me-2">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo htmlspecialchars($rotation_group['shift_name']); ?>
                                </span>
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo $rotation_group['work_days']; ?> days work,
                                    <?php echo $rotation_group['off_days']; ?> off
                                </span>
                                <?php if ($rotation_group['department_name']): ?>
                                <span class="badge bg-primary me-2">
                                    <i class="fas fa-building me-1"></i>
                                    <?php echo htmlspecialchars($rotation_group['department_name']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="fas fa-user-plus me-2"></i>Add Employee
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

                <!-- Info Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="info-card">
                            <div class="info-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="info-content">
                                <h3><?php echo count($rotation_members); ?></h3>
                                <p>Team Members</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="info-card">
                            <div class="info-icon bg-success">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h3><?php echo $rotation_group['start_time']; ?></h3>
                                <p>Start Time</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="info-card">
                            <div class="info-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h3><?php echo $rotation_group['end_time']; ?></h3>
                                <p>End Time</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="info-card">
                            <div class="info-icon bg-info">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <h3>Week <?php echo date('W'); ?></h3>
                                <p>Current Week</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members Card -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Rotation Members (<?php echo count($rotation_members); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rotation_members)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5>No Members Yet</h5>
                            <p class="text-muted">Add employees to this rotation group to get started</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="fas fa-user-plus me-2"></i>Add First Member
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Employee</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Joined</th>
                                        <th>Current Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $total_members = count($rotation_members);
                                        $weeks_since_start = floor((time() - strtotime($rotation_group['start_date'])) / (7 * 24 * 60 * 60));
                                        $current_rotation = ($weeks_since_start % $total_members) + 1;
                                        
                                        foreach ($rotation_members as $member): 
                                            $is_working = ($member['rotation_order'] == $current_rotation);
                                        ?>
                                    <tr class="<?php echo $is_working ? 'table-success' : ''; ?>">
                                        <td>
                                            <span class="badge bg-primary" style="font-size: 1rem;">
                                                #<?php echo $member['rotation_order']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['employee_name']); ?></strong>
                                            <?php if ($is_working): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="fas fa-check-circle me-1"></i>Working This Week
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['employee_email']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($member['department_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                        <td>
                                            <?php if ($is_working): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-briefcase me-1"></i>On Duty
                                            </span>
                                            <?php else: ?>
                                            <span class="status-badge status-rest">
                                                <i class="fas fa-couch me-1"></i>Rest Period
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="employee_attendance.php?id=<?php echo $member['employee_id']; ?>"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="rotation_details.php?id=<?php echo $rotation_id; ?>&action=remove&member_id=<?php echo $member['id']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Remove this employee from rotation?');">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Rotation Explanation -->
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle me-2"></i>How Rotation Works:</h6>
                            <p class="mb-2">
                                <strong>Total Members:</strong> <?php echo $total_members; ?> employees<br>
                                <strong>Cycle Length:</strong> <?php echo $total_members; ?> weeks<br>
                                <strong>Working Pattern:</strong> <?php echo $rotation_group['work_days']; ?> days on,
                                <?php echo $rotation_group['off_days']; ?> day(s) off
                            </p>
                            <p class="mb-0">
                                Each employee works for <strong><?php echo $rotation_group['work_days']; ?>
                                    days</strong>,
                                then the next employee in order takes over. After all <?php echo $total_members; ?>
                                employees
                                have completed their rotation, the cycle repeats automatically.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="main-footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0">
                                <strong><?php echo $company_name; ?></strong> &copy; <?php echo date('Y'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="rotation_details.php?id=<?php echo $rotation_id; ?>" method="POST">
                    <input type="hidden" name="add_member" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Add Employee to Rotation
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($available_emps)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            All employees are already in this rotation group.
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">
                                <i class="fas fa-user me-1"></i>Select Employee
                            </label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">Choose employee...</option>
                                <?php foreach ($available_emps as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['name']); ?>
                                    (<?php echo htmlspecialchars($emp['department_name'] ?? 'No Dept'); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="rotation_order" class="form-label">
                                <i class="fas fa-sort-numeric-up me-1"></i>Rotation Order
                            </label>
                            <input type="number" class="form-control" id="rotation_order" name="rotation_order"
                                value="<?php echo $next_order; ?>" min="1" required>
                            <div class="form-text">
                                Order <?php echo $next_order; ?> is next in line. Lower numbers work first.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <?php if (!empty($available_emps)): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Member
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#sidebarToggle').on('click', function() {
            $('#wrapper').toggleClass('toggled');
        });
    });
    </script>
</body>

</html>