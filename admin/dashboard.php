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

// Include header
include 'includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Page content wrapper -->
    <div id="page-content-wrapper">
        <!-- Top navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
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
                                <a class="dropdown-item text-center" href="#">View all notifications</a>
                            </div>
                        </li>

                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cog me-2"></i>Settings
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
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                        </h1>
                        <p class="text-muted mb-0">Welcome back,
                            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>! Here's what's happening today.</p>
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
                            <h5 class="card-title mb-0">
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
                            <h5 class="card-title mb-0">
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
                            <h5 class="card-title mb-0">
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
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="employees.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Add New Employee
                                </a>
                                <a href="departments.php" class="btn btn-warning text-white">
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
                            <h5 class="card-title mb-0">
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
                                    <i class="fas fa-code-branch text-info me-2"></i>
                                    <strong>Version:</strong> 1.0.0
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Chart.js Configuration -->
<script>
// Update time
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString();
}
updateTime();
setInterval(updateTime, 1000);

// Attendance Trend Chart
const attCtx = document.getElementById('attendanceChart').getContext('2d');
const attData = <?php echo json_encode($chart_data); ?>;
new Chart(attCtx, {
    type: 'line',
    data: {
        labels: attData.map(d => d.date),
        datasets: [{
            label: 'Check-ins',
            data: attData.map(d => d.count),
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderColor: 'rgba(102, 126, 234, 1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
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

// Department Distribution Chart
const deptCtx = document.getElementById('departmentChart').getContext('2d');
const deptData = <?php echo json_encode($dept_attendance); ?>;
new Chart(deptCtx, {
    type: 'doughnut',
    data: {
        labels: deptData.map(d => d.name),
        datasets: [{
            data: deptData.map(d => d.count),
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)'
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
</script>

<style>
/* Dashboard Specific Styles */
.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

/* Modern Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
}

.stat-card-primary .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card-success .stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card-info .stat-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: color 0.3s;
}

.stat-link:hover {
    color: #764ba2;
}

.stat-trend {
    font-size: 0.8rem;
    font-weight: 600;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

/* Chart Cards */
.chart-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    height: 100%;
}

.chart-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem;
    border: none;
}

.card-title {
    font-weight: 600;
}

/* Activity Card */
.activity-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.activity-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem;
    border: none;
}

.activity-card table {
    margin-bottom: 0;
}

.activity-card table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

/* Quick Actions */
.quick-actions-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quick-actions-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem;
    border: none;
}

.quick-actions-card .btn {
    font-weight: 600;
    padding: 0.75rem;
}

/* System Info */
.system-info li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.system-info li:last-child {
    border-bottom: none;
}

/* Notification Dropdown */
.notification-dropdown {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-dropdown .dropdown-item {
    padding: 0.75rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.notification-dropdown .dropdown-item:last-child {
    border-bottom: none;
}

.badge-sm {
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 0.7rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }

    .stat-card {
        flex-direction: row;
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

    .page-header .badge {
        font-size: 0.75rem;
        padding: 0.5rem !important;
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

    .chart-card {
        margin-bottom: 1rem;
    }
}
</style>