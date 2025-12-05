<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_employee()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Dashboard";
$current_page = "dashboard";

$employee_id = $_SESSION["user_id"];

// Get today's attendance
$today = date("Y-m-d");
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) = ?");
$stmt->execute([$employee_id, $today]);
$today_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get this month's attendance count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendances WHERE employee_id = ? AND MONTH(check_in) = MONTH(CURDATE()) AND YEAR(check_in) = YEAR(CURDATE())");
$stmt->execute([$employee_id]);
$month_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get this week's attendance
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendances WHERE employee_id = ? AND YEARWEEK(check_in, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$employee_id]);
$week_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total working hours this month
$stmt = $pdo->prepare("
    SELECT 
        SUM(TIMESTAMPDIFF(SECOND, check_in, check_out)) as total_seconds
    FROM attendances 
    WHERE employee_id = ? 
    AND MONTH(check_in) = MONTH(CURDATE()) 
    AND YEAR(check_in) = YEAR(CURDATE())
    AND check_out IS NOT NULL
");
$stmt->execute([$employee_id]);
$hours_result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_hours = 0;
if ($hours_result['total_seconds']) {
    $total_hours = floor($hours_result['total_seconds'] / 3600);
}

// Get recent attendance (last 5 days)
$stmt = $pdo->prepare("
    SELECT * FROM attendances 
    WHERE employee_id = ? 
    AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
    ORDER BY check_in DESC 
    LIMIT 5
");
$stmt->execute([$employee_id]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employee details
$stmt = $pdo->prepare("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if employee is working today (checked in but not checked out)
$is_working = $today_attendance && empty($today_attendance['check_out']);

// Include header
include 'includes/header.php';
?>

<!-- Include Sidebar -->
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="header-left">
            <button id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-title">
                <h2>Dashboard</h2>
            </div>
        </div>
        <div class="header-right">
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
            <div class="user-dropdown">
                <div class="user-avatar-small">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div class="user-info-small">
                    <span class="name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <span class="role">Employee</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-home"></i>
            Welcome back, <?php echo htmlspecialchars($employee['name']); ?>!
        </h1>
        <p class="page-subtitle">
            <?php 
            $hour = date('H');
            if ($hour < 12) echo "Good Morning";
            elseif ($hour < 17) echo "Good Afternoon";
            else echo "Good Evening";
            ?>
            - <?php echo date('l, F j, Y'); ?>
        </p>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $month_attendance['total']; ?></h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $week_attendance['total']; ?></h3>
                    <p>This Week</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_hours; ?>h</h3>
                    <p>Working Hours</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-<?php echo $is_working ? 'user-check' : 'user-clock'; ?>"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $is_working ? 'Working' : 'Off Duty'; ?></h3>
                    <p>Current Status</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">
        <!-- Today's Attendance Card -->
        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-calendar-day"></i> Today's Attendance
                    </h5>
                    <span style="font-size: 0.9rem;" id="currentTime"></span>
                </div>
                <div class="card-body">
                    <?php if ($today_attendance): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon success" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Check In</h6>
                            <h4 class="mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></h4>
                        </div>
                    </div>

                    <?php if ($today_attendance['check_out']): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon"
                            style="width: 60px; height: 60px; font-size: 1.5rem; background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Check Out</h6>
                            <h4 class="mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_out'])); ?>
                            </h4>
                        </div>
                    </div>

                    <?php
                        $check_in_time = new DateTime($today_attendance['check_in']);
                        $check_out_time = new DateTime($today_attendance['check_out']);
                        $interval = $check_in_time->diff($check_out_time);
                        $working_hours = $interval->format('%hh %im');
                        ?>

                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i>
                        <strong>Completed!</strong> You worked <?php echo $working_hours; ?> today.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        <strong>Checked In!</strong> Remember to check out when you leave.
                    </div>
                    <a href="mark_attendance.php" class="btn btn-danger w-100 mt-3">
                        <i class="fas fa-sign-out-alt"></i> Check Out Now
                    </a>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>Not Checked In</h5>
                        <p class="text-muted">You haven't checked in today yet.</p>
                        <a href="mark_attendance.php" class="btn btn-success btn-lg mt-2">
                            <i class="fas fa-sign-in-alt"></i> Check In Now
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="mark_attendance.php"
                                class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3">
                                <i class="fas fa-qrcode fa-2x mb-2"></i>
                                <span>Mark Attendance</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="current_month_attendance.php"
                                class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                                <span>My Records</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="my_schedule.php"
                                class="btn btn-outline-info w-100 d-flex flex-column align-items-center py-3">
                                <i class="fas fa-calendar-week fa-2x mb-2"></i>
                                <span>My Schedule</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="leave_request.php"
                                class="btn btn-outline-warning w-100 d-flex flex-column align-items-center py-3">
                                <i class="fas fa-umbrella-beach fa-2x mb-2"></i>
                                <span>Request Leave</span>
                            </a>
                        </div>
                    </div>

                    <!-- Employee Info -->
                    <div class="mt-4 p-3" style="background: #f9fafb; border-radius: 10px;">
                        <h6 class="mb-3"><i class="fas fa-user-circle me-2"></i>Your Information</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Department:</span>
                            <strong><?php echo htmlspecialchars($employee['department_name'] ?? 'N/A'); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Email:</span>
                            <strong><?php echo htmlspecialchars($employee['email']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Phone:</span>
                            <strong><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-history"></i> Recent Attendance
                    </h5>
                    <a href="current_month_attendance.php" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Working Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_attendance)): ?>
                                <?php foreach ($recent_attendance as $record): ?>
                                <?php
                                    $hours = "N/A";
                                    if (!empty($record['check_in']) && !empty($record['check_out'])) {
                                        $in = new DateTime($record['check_in']);
                                        $out = new DateTime($record['check_out']);
                                        $diff = $in->diff($out);
                                        $hours = $diff->format('%hh %im');
                                    }
                                    ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['check_in'])); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <?php echo date('h:i A', strtotime($record['check_in'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($record['check_out']): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <?php echo date('h:i A', strtotime($record['check_out'])); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $hours; ?></strong></td>
                                    <td>
                                        <?php if (!$record['check_out']): ?>
                                        <span class="badge bg-warning">In Progress</span>
                                        <?php else: ?>
                                        <span class="badge bg-success">Complete</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No recent attendance records.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Real-time clock
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds}`;

    const clockElement = document.getElementById('currentTime');
    if (clockElement) {
        clockElement.textContent = timeString;
    }
}

updateClock();
setInterval(updateClock, 1000);
</script>

<?php include 'includes/footer.php'; ?>