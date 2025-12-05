<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

$employee_id = isset($_GET["id"]) ? sanitize_input($_GET["id"]) : null;

if (!$employee_id) {
    redirect("employees.php");
}

// Fetch employee details with department
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect("employees.php");
}

// Page configuration
$page_title = "Attendance - " . $employee["name"];
$current_page = "employees";
$use_datatables = true;

// Company Configuration
$company_name = "NEW KB BAR & SPORT LOUNGE";
$company_logo = "../assets/img/logo_org.jpg";
$system_name = "Attendance Management System";

// Fetch attendance records for the employee
$attendances = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? ORDER BY check_in DESC");
$attendances->execute([$employee_id]);
$employee_attendances = $attendances->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_days = count($employee_attendances);
$present_count = 0;
$late_count = 0;
$absent_count = 0;
$total_seconds = 0;

// Working hours definition (East Africa Time) - BARTENDER SHIFT
$work_start_time = '20:00:00'; // 8:00 PM (Bartender shift start)
$late_threshold = '20:00:00';  // After 8:00 PM is late

foreach ($employee_attendances as &$attendance) {
    $status = strtolower($attendance['status']);
    
    if ($status == 'absent') {
        $absent_count++;
        $attendance['is_late'] = false;
        $attendance['working_hours'] = 'Absent';
    } else {
        if (!empty($attendance['check_in'])) {
            $check_in_time = date('H:i:s', strtotime($attendance['check_in']));
            
            if ($check_in_time > $late_threshold) {
                $attendance['is_late'] = true;
                $late_count++;
            } else {
                $attendance['is_late'] = false;
                $present_count++;
            }
        }
        
        if (!empty($attendance['check_in']) && !empty($attendance['check_out'])) {
            $check_in = new DateTime($attendance['check_in']);
            $check_out = new DateTime($attendance['check_out']);
            $diff = $check_in->diff($check_out);
            $seconds = ($diff->days * 24 * 3600) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
            $total_seconds += $seconds;
            
            $attendance['working_hours'] = $diff->format('%hh %im');
        } else {
            $attendance['working_hours'] = 'In Progress';
        }
    }
}

// Convert total seconds to hours and minutes
$total_hours = floor($total_seconds / 3600);
$total_minutes = floor(($total_seconds % 3600) / 60);

// Calculate average hours per day
$days_with_checkout = 0;
foreach ($employee_attendances as $att) {
    if (!empty($att['check_out']) && $att['status'] != 'absent') {
        $days_with_checkout++;
    }
}

$avg_hours = 0;
$avg_minutes = 0;
if ($days_with_checkout > 0) {
    $avg_seconds = $total_seconds / $days_with_checkout;
    $avg_hours = floor($avg_seconds / 3600);
    $avg_minutes = floor(($avg_seconds % 3600) / 60);
}
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

    /* Employee Info Card */
    .employee-info-card {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        border: 2px solid var(--kb-gold);
    }

    .employee-avatar {
        width: 90px;
        height: 90px;
        background: rgba(212, 175, 55, 0.2);
        border: 4px solid var(--kb-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: var(--kb-gold);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .employee-meta {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        font-size: 0.95rem;
        opacity: 0.95;
    }

    .meta-item {
        display: flex;
        align-items: center;
    }

    .meta-item i {
        color: var(--kb-gold);
    }

    /* Statistics Cards */
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        height: 100%;
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
        color: white;
        flex-shrink: 0;
    }

    .stat-present {
        border-left: 4px solid #22c55e;
    }

    .stat-present .stat-icon {
        background: linear-gradient(135deg, #22c55e, #4ade80);
    }

    .stat-late {
        border-left: 4px solid #f59e0b;
    }

    .stat-late .stat-icon {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
    }

    .stat-absent {
        border-left: 4px solid #ef4444;
    }

    .stat-absent .stat-icon {
        background: linear-gradient(135deg, #ef4444, #f87171);
    }

    .stat-total {
        border-left: 4px solid var(--kb-gold);
    }

    .stat-total .stat-icon {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
    }

    .stat-number {
        font-size: 2.25rem;
        font-weight: 700;
        color: #374151;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.95rem;
        color: #6b7280;
        font-weight: 600;
        margin: 0.25rem 0;
    }

    .stat-desc {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    /* Working Hours Cards */
    .hours-card {
        background: linear-gradient(135deg, var(--kb-dark), var(--kb-dark-alt));
        color: white;
        padding: 1.75rem;
        border-radius: 15px;
        display: flex;
        gap: 1.25rem;
        align-items: center;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        height: 100%;
        border: 2px solid var(--kb-gold);
    }

    .hours-icon {
        width: 75px;
        height: 75px;
        background: rgba(212, 175, 55, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.25rem;
        flex-shrink: 0;
        color: var(--kb-gold);
    }

    .hours-details h5 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
        opacity: 0.95;
    }

    .hours-big {
        font-size: 2.25rem;
        font-weight: 700;
        margin: 0;
        line-height: 1;
        color: var(--kb-gold);
    }

    .hours-details small {
        opacity: 0.85;
        font-size: 0.85rem;
    }

    /* Info Alert */
    .alert-info-modern {
        background: #eff6ff;
        border: 2px solid var(--kb-gold);
        border-radius: 15px;
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
    }

    .alert-icon-left {
        font-size: 2.25rem;
        color: var(--kb-gold);
        flex-shrink: 0;
    }

    .alert-body h6 {
        color: var(--kb-dark);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .alert-body h6 i {
        color: var(--kb-gold);
    }

    .policy-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .policy-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #374151;
        font-size: 0.9rem;
    }

    .policy-item i {
        font-size: 1.25rem;
    }

    /* Modern Card */
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

    .modern-card .card-header .btn-light {
        background: rgba(212, 175, 55, 0.2);
        border: 1px solid var(--kb-gold);
        color: var(--kb-gold);
        font-weight: 600;
    }

    .modern-card .card-header .btn-light:hover {
        background: var(--kb-gold);
        color: white;
    }

    /* Table Styles */
    .table-modern thead th {
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: var(--kb-gold);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--kb-gold);
        padding: 1rem;
    }

    .table-modern tbody tr {
        border-bottom: 1px solid #f3f4f6;
    }

    .table-modern tbody tr:hover {
        background: rgba(212, 175, 55, 0.05);
    }

    .table-modern tbody td {
        padding: 1rem;
        vertical-align: middle;
    }

    .date-cell {
        display: flex;
        flex-direction: column;
    }

    .date-cell strong {
        color: #374151;
    }

    .date-cell small {
        color: #9ca3af;
        font-size: 0.75rem;
    }

    /* Time Badges */
    .time-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .time-badge.ontime {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }

    .time-badge.late {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .time-badge.checkout {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Status Badges */
    .badge-custom {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .badge-present {
        background: #22c55e;
        color: white;
    }

    .badge-late {
        background: #f59e0b;
        color: white;
    }

    .badge-working {
        background: #3b82f6;
        color: white;
    }

    .badge-absent {
        background: #ef4444;
        color: white;
    }

    /* Back Button */
    .btn-back {
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
    }

    .btn-back:hover {
        background: linear-gradient(135deg, var(--kb-gold), var(--kb-dark-gold));
        color: white;
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

    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .nav-link:hover {
        color: var(--kb-gold) !important;
    }

    .fas.fa-user-circle {
        color: var(--kb-gold) !important;
    }

    /* SIDEBAR - Same as other pages */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 260px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
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
        border-radius: 15px;
        border: 2px solid rgba(212, 175, 55, 0.5);
        padding: 8px;
        background: #000;
    }

    .company-name {
        font-size: 1rem;
        font-weight: bold;
        color: var(--kb-gold);
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

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
    }

    .user-avatar-sidebar {
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

    /* Responsive */
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

        .employee-info-card {
            text-align: center;
        }

        .employee-avatar {
            margin: 0 auto 1rem;
        }

        .employee-meta {
            flex-direction: column;
            align-items: center;
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

        <!-- Page content -->
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
                <!-- Back Button -->
                <div class="mb-3">
                    <a href="employees.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back to Employees
                    </a>
                </div>

                <!-- Employee Info Card -->
                <div class="employee-info-card mb-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($employee['name'], 0, 2)); ?>
                            </div>
                        </div>
                        <div class="col">
                            <h2 class="mb-2"><?php echo htmlspecialchars($employee["name"]); ?></h2>
                            <div class="employee-meta">
                                <span class="meta-item">
                                    <i class="fas fa-envelope me-2"></i>
                                    <?php echo htmlspecialchars($employee["email"]); ?>
                                </span>
                                <?php if (!empty($employee['department_name'])): ?>
                                <span class="meta-item">
                                    <i class="fas fa-building me-2"></i>
                                    <?php echo htmlspecialchars($employee['department_name']); ?>
                                </span>
                                <?php endif; ?>
                                <span class="meta-item">
                                    <i class="fas fa-id-badge me-2"></i>
                                    ID: <?php echo $employee_id; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-present">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $present_count; ?></div>
                                <div class="stat-label">On Time</div>
                                <div class="stat-desc">Before 8:00 PM</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-late">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $late_count; ?></div>
                                <div class="stat-label">Late Arrivals</div>
                                <div class="stat-desc">After 8:00 PM</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-absent">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $absent_count; ?></div>
                                <div class="stat-label">Absent Days</div>
                                <div class="stat-desc">No attendance</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card stat-total">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?php echo $total_days; ?></div>
                                <div class="stat-label">Total Days</div>
                                <div class="stat-desc">All records</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Working Hours Summary -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="hours-card">
                            <div class="hours-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="hours-details">
                                <h5>Total Working Hours</h5>
                                <p class="hours-big"><?php echo $total_hours; ?>h <?php echo $total_minutes; ?>m</p>
                                <small>Across <?php echo $days_with_checkout; ?> completed days</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="hours-card">
                            <div class="hours-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="hours-details">
                                <h5>Average Hours/Day</h5>
                                <p class="hours-big"><?php echo $avg_hours; ?>h <?php echo $avg_minutes; ?>m</p>
                                <small>Daily working time average</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Working Hours Policy -->
                <div class="alert-info-modern mb-4">
                    <div class="alert-icon-left">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="alert-body">
                        <h6><i class="fas fa-cocktail me-2"></i>Bartender Working Hours Policy (East Africa Time)</h6>
                        <div class="policy-grid">
                            <div class="policy-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>On Time:</strong> Check-in before or at 8:00 PM (Saa 2 usiku)
                            </div>
                            <div class="policy-item">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <strong>Late:</strong> Check-in after 8:00 PM (Saa 2 usiku)
                            </div>
                            <div class="policy-item">
                                <i class="fas fa-sunrise text-info"></i>
                                <strong>Check-out:</strong> Expected around 2:00 AM (Saa 8 usiku) onwards
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="card modern-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Complete Attendance History
                            </h5>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>Print
                                </button>
                                <button class="btn btn-sm btn-light" onclick="exportToCSV()">
                                    <i class="fas fa-file-csv me-1"></i>Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($employee_attendances)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5>No Attendance Records</h5>
                            <p class="text-muted">This employee hasn't marked any attendance yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="attendanceTable" class="table table-hover table-modern">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Working Hours</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employee_attendances as $attendance): ?>
                                    <tr>
                                        <td>
                                            <div class="date-cell">
                                                <strong><?php echo date('M d, Y', strtotime($attendance["check_in"])); ?></strong>
                                                <small><?php echo date('l', strtotime($attendance["check_in"])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($attendance['status'] != 'absent'): ?>
                                            <span
                                                class="time-badge <?php echo $attendance['is_late'] ? 'late' : 'ontime'; ?>">
                                                <i class="fas fa-sign-in-alt"></i>
                                                <?php echo date('h:i A', strtotime($attendance["check_in"])); ?>
                                            </span>
                                            <br>
                                            <?php if ($attendance['is_late']): ?>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Late
                                            </small>
                                            <?php else: ?>
                                            <small class="text-success">
                                                <i class="fas fa-check"></i> On Time
                                            </small>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($attendance["check_out"]): ?>
                                            <span class="time-badge checkout">
                                                <i class="fas fa-sign-out-alt"></i>
                                                <?php echo date('h:i A', strtotime($attendance["check_out"])); ?>
                                            </span>
                                            <?php elseif ($attendance['status'] == 'absent'): ?>
                                            <span class="text-muted">-</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">In Progress</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong
                                                class="text-primary"><?php echo $attendance['working_hours']; ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $status_lower = strtolower($attendance['status']);
                                            if ($status_lower == 'absent') {
                                                echo '<span class="badge-custom badge-absent">Absent</span>';
                                            } elseif ($attendance['is_late']) {
                                                echo '<span class="badge-custom badge-late">Late</span>';
                                            } elseif (!$attendance['check_out']) {
                                                echo '<span class="badge-custom badge-working">Working</span>';
                                            } else {
                                                echo '<span class="badge-custom badge-present">Present</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($attendance["notes"])): ?>
                                            <span class="text-muted">
                                                <i class="fas fa-sticky-note me-1"></i>
                                                <?php echo htmlspecialchars($attendance["notes"]); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0">
                                <a href="#">Help</a> | <a href="#">Support</a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $("#attendanceTable").DataTable({
            responsive: true,
            order: [
                [0, 'desc']
            ],
            pageLength: 25
        });

        $('#sidebarToggle').on('click', function() {
            $('#wrapper').toggleClass('toggled');
        });
    });

    function exportToCSV() {
        const table = document.getElementById('attendanceTable');
        let csv = [];

        const headers = [];
        table.querySelectorAll('thead th').forEach(th => {
            headers.push(th.textContent.trim());
        });
        csv.push(headers.join(','));

        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            tr.querySelectorAll('td').forEach(td => {
                let text = td.textContent.trim().replace(/\n/g, ' ').replace(/,/g, ';');
                row.push(text);
            });
            csv.push(row.join(','));
        });

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], {
            type: 'text/csv'
        });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download =
            'attendance_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $employee["name"]); ?>_<?php echo date("Y-m-d"); ?>.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    </script>
</body>

</html>