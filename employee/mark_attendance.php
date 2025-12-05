<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_employee()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Mark Attendance";
$current_page = "mark_attendance";

$employee_id = $_SESSION["user_id"];
$error = "";
$success = "";

// Check if employee has already checked in today
$today = date("Y-m-d");
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) = ?");
$stmt->execute([$employee_id, $today]);
$current_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle check-in/check-out
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["check_in"])) {
        if (!$current_attendance) {
            $stmt = $pdo->prepare("INSERT INTO attendances (employee_id, check_in, status) VALUES (?, ?, ?)");
            if ($stmt->execute([$employee_id, date("Y-m-d H:i:s"), "Present"])) {
                $success = "Checked in successfully!";
                // Refresh current attendance
                $stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$employee_id, $today]);
                $current_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Error during check-in.";
            }
        } else {
            $error = "You have already checked in today.";
        }
    } elseif (isset($_POST["check_out"])) {
        if ($current_attendance && empty($current_attendance["check_out"])) {
            $stmt = $pdo->prepare("UPDATE attendances SET check_out = ? WHERE id = ?");
            if ($stmt->execute([date("Y-m-d H:i:s"), $current_attendance["id"]])) {
                $success = "Checked out successfully!";
                // Refresh current attendance
                $stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$employee_id, $today]);
                $current_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Error during check-out.";
            }
        } else {
            $error = "You have not checked in yet or already checked out.";
        }
    }
}

// Calculate working hours if both check-in and check-out exist
$working_hours = "0h 0m";
if ($current_attendance && !empty($current_attendance["check_in"]) && !empty($current_attendance["check_out"])) {
    $check_in_time = new DateTime($current_attendance["check_in"]);
    $check_out_time = new DateTime($current_attendance["check_out"]);
    $interval = $check_in_time->diff($check_out_time);
    $working_hours = $interval->format('%hh %im');
}

// Get recent attendance history (last 7 days)
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY check_in DESC LIMIT 7");
$stmt->execute([$employee_id]);
$recent_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h2>Mark Attendance</h2>
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

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-qrcode"></i> Mark Attendance
            </h1>
            <p class="page-subtitle"><?php echo date('l, F j, Y'); ?></p>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Today's Status Card -->
            <div class="col-lg-6">
                <div class="modern-card attendance-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-day"></i> Today's Status
                        </h5>
                        <span class="current-time" id="currentTime"></span>
                    </div>
                    <div class="card-body">
                        <?php if ($current_attendance): ?>
                        <!-- Checked In Status -->
                        <div class="status-display">
                            <div class="status-item checked-in">
                                <div class="status-icon bg-success">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="status-details">
                                    <h6>Check In Time</h6>
                                    <p class="status-time">
                                        <?php echo date('h:i A', strtotime($current_attendance["check_in"])); ?>
                                    </p>
                                    <span class="status-date">
                                        <?php echo date('M d, Y', strtotime($current_attendance["check_in"])); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($current_attendance["check_out"]): ?>
                            <!-- Checked Out -->
                            <div class="status-divider completed">
                                <i class="fas fa-check"></i>
                            </div>

                            <div class="status-item checked-out">
                                <div class="status-icon bg-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="status-details">
                                    <h6>Check Out Time</h6>
                                    <p class="status-time">
                                        <?php echo date('h:i A', strtotime($current_attendance["check_out"])); ?>
                                    </p>
                                    <span class="status-date">
                                        <?php echo date('M d, Y', strtotime($current_attendance["check_out"])); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Working Hours -->
                            <div class="working-hours completed">
                                <div class="hours-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="hours-details">
                                    <div class="hours-label">Total Working Hours</div>
                                    <div class="hours-value"><?php echo $working_hours; ?></div>
                                </div>
                            </div>

                            <!-- Completion Message -->
                            <div class="completion-message">
                                <div class="completion-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="completion-text">
                                    <h6>Attendance Complete</h6>
                                    <p>You have successfully completed your attendance for today. Have a great day!</p>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- Currently Checked In -->
                            <div class="status-divider active">
                                <i class="fas fa-spinner fa-pulse"></i>
                            </div>

                            <div class="status-item pending">
                                <div class="status-icon bg-secondary">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="status-details">
                                    <h6>Check Out Time</h6>
                                    <p class="status-time">Pending</p>
                                    <span class="status-date">Waiting for check-out</span>
                                </div>
                            </div>

                            <!-- Current Session Info -->
                            <div class="current-session">
                                <div class="session-status">
                                    <i class="fas fa-circle text-success blink"></i>
                                    <span>Currently Checked In</span>
                                </div>
                                <p class="session-info">
                                    <i class="fas fa-info-circle"></i>
                                    Remember to check out when you finish your work today.
                                </p>
                            </div>

                            <!-- Check Out Button -->
                            <form action="mark_attendance.php" method="POST" class="mt-3">
                                <button type="submit" name="check_out" class="btn btn-danger w-100 checkout-btn">
                                    <i class="fas fa-sign-out-alt"></i> Check Out Now
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <!-- Not Checked In Yet -->
                        <div class="not-checked-in">
                            <div class="welcome-message">
                                <div class="welcome-icon">
                                    <i class="fas fa-hand-wave"></i>
                                </div>
                                <h4>Good
                                    <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>!
                                </h4>
                                <p>You haven't checked in yet today. Click the button below to mark your attendance.</p>
                            </div>

                            <div class="checkin-info">
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Date: <?php echo date('M d, Y'); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Time: <span id="liveTime"></span></span>
                                </div>
                            </div>

                            <form action="mark_attendance.php" method="POST" class="mt-3">
                                <button type="submit" name="check_in" class="btn btn-success w-100 checkin-btn">
                                    <i class="fas fa-sign-in-alt"></i> Check In Now
                                </button>
                            </form>

                            <div class="checkin-tips">
                                <h6><i class="fas fa-lightbulb"></i> Quick Tips:</h6>
                                <ul>
                                    <li>Check in when you arrive at work</li>
                                    <li>Check out when you leave</li>
                                    <li>Make sure to complete both actions daily</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-lg-6">
                <!-- Quick Info Card -->
                <div class="modern-card mb-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i> Quick Info
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-stats">
                            <div class="stat-box">
                                <div class="stat-icon-small bg-success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Status</h6>
                                    <p>
                                        <?php 
                                        if (!$current_attendance) {
                                            echo '<span class="badge bg-warning">Not Checked In</span>';
                                        } elseif ($current_attendance && !$current_attendance["check_out"]) {
                                            echo '<span class="badge bg-success">Checked In</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Completed</span>';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <div class="stat-box">
                                <div class="stat-icon-small bg-primary">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>This Week</h6>
                                    <p><?php echo count($recent_attendance); ?> days recorded</p>
                                </div>
                            </div>

                            <div class="stat-box">
                                <div class="stat-icon-small bg-info">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h6>Today's Hours</h6>
                                    <p><?php echo $working_hours; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="modern-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-exclamation-triangle"></i> Important Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="notes-list">
                            <div class="note-item">
                                <i class="fas fa-arrow-right text-primary"></i>
                                <span>Check in and out times are recorded automatically</span>
                            </div>
                            <div class="note-item">
                                <i class="fas fa-arrow-right text-primary"></i>
                                <span>You can only check in once per day</span>
                            </div>
                            <div class="note-item">
                                <i class="fas fa-arrow-right text-primary"></i>
                                <span>Remember to check out at the end of your shift</span>
                            </div>
                            <div class="note-item">
                                <i class="fas fa-arrow-right text-primary"></i>
                                <span>Contact HR if you need to make corrections</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Attendance History -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="modern-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-history"></i> Recent Attendance (Last 7 Days)
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
                                    <?php foreach ($recent_attendance as $attendance): ?>
                                    <?php
                                            $hours = "N/A";
                                            if (!empty($attendance["check_in"]) && !empty($attendance["check_out"])) {
                                                $in = new DateTime($attendance["check_in"]);
                                                $out = new DateTime($attendance["check_out"]);
                                                $diff = $in->diff($out);
                                                $hours = $diff->format('%hh %im');
                                            }
                                            ?>
                                    <tr>
                                        <td data-label="Date">
                                            <?php echo date('M d, Y', strtotime($attendance["check_in"])); ?></td>
                                        <td data-label="Check In">
                                            <span class="time-badge">
                                                <i class="fas fa-sign-in-alt"></i>
                                                <?php echo date('h:i A', strtotime($attendance["check_in"])); ?>
                                            </span>
                                        </td>
                                        <td data-label="Check Out">
                                            <?php if ($attendance["check_out"]): ?>
                                            <span class="time-badge">
                                                <i class="fas fa-sign-out-alt"></i>
                                                <?php echo date('h:i A', strtotime($attendance["check_out"])); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">In Progress</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Working Hours"><?php echo $hours; ?></td>
                                        <td data-label="Status">
                                            <?php
                                                    if (!$attendance["check_out"]) {
                                                        echo '<span class="badge bg-warning">In Progress</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">Complete</span>';
                                                    }
                                                    ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No attendance records found for the last 7 days.
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
    </div>
</main>

<style>
/* Reduced Font Sizes */
.attendance-card {
    min-height: 450px;
}

.current-time {
    font-family: monospace;
    font-size: 12px;
    color: #666;
    font-weight: 600;
}

.status-display {
    padding: 15px 0;
}

.status-item {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
}

.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.status-details h6 {
    font-size: 11px;
    color: #999;
    margin-bottom: 4px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-time {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin: 0 0 4px 0;
}

.status-date {
    font-size: 11px;
    color: #999;
}

.status-divider {
    text-align: center;
    margin: 15px 0;
    color: #ddd;
    font-size: 16px;
}

.status-divider.active {
    color: var(--primary-color);
}

.status-divider.completed {
    color: #22c55e;
}

.status-item.pending .status-icon {
    opacity: 0.5;
}

.status-item.pending .status-details {
    opacity: 0.6;
}

.working-hours {
    background: linear-gradient(135deg, #22c55e, #4ade80);
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    color: white;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.hours-icon {
    font-size: 28px;
}

.hours-details {
    text-align: left;
}

.hours-label {
    font-size: 11px;
    opacity: 0.9;
    margin-bottom: 4px;
}

.hours-value {
    font-size: 24px;
    font-weight: 700;
}

.completion-message {
    background: rgba(34, 197, 94, 0.1);
    border: 2px solid rgba(34, 197, 94, 0.3);
    border-radius: 12px;
    padding: 15px;
    margin-top: 15px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.completion-icon {
    width: 40px;
    height: 40px;
    background: #22c55e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.completion-text h6 {
    color: #22c55e;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 13px;
}

.completion-text p {
    color: #666;
    margin: 0;
    font-size: 12px;
}

.current-session {
    background: rgba(124, 58, 237, 0.05);
    border: 1px solid rgba(124, 58, 237, 0.2);
    border-radius: 10px;
    padding: 12px;
    margin-top: 15px;
}

.session-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 13px;
}

.blink {
    animation: blink 2s infinite;
}

@keyframes blink {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.3;
    }
}

.session-info {
    color: #666;
    font-size: 12px;
    margin: 0;
}

.checkout-btn,
.checkin-btn {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.checkout-btn:hover,
.checkin-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Not Checked In Styles */
.not-checked-in {
    text-align: center;
    padding: 20px 15px;
}

.welcome-message {
    margin-bottom: 20px;
}

.welcome-icon {
    font-size: 60px;
    color: var(--primary-color);
    margin-bottom: 15px;
    animation: wave 2s infinite;
}

@keyframes wave {

    0%,
    100% {
        transform: rotate(0deg);
    }

    25% {
        transform: rotate(20deg);
    }

    75% {
        transform: rotate(-20deg);
    }
}

.welcome-message h4 {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.welcome-message p {
    color: #666;
    font-size: 14px;
}

.checkin-info {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #f9fafb;
    border-radius: 8px;
    font-weight: 500;
    color: #666;
    font-size: 13px;
}

.info-item i {
    color: var(--primary-color);
    font-size: 16px;
}

.checkin-tips {
    background: #f9fafb;
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
    text-align: left;
}

.checkin-tips h6 {
    color: #333;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.checkin-tips i {
    color: #f59e0b;
}

.checkin-tips ul {
    margin: 0;
    padding-left: 18px;
}

.checkin-tips li {
    color: #666;
    margin-bottom: 6px;
    font-size: 12px;
}

/* Quick Stats */
.quick-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-box {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.stat-box:hover {
    background: #f3f4f6;
    transform: translateX(5px);
}

.stat-icon-small {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-content h6 {
    font-size: 11px;
    color: #999;
    margin-bottom: 4px;
    font-weight: 500;
}

.stat-content p {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

/* Notes List */
.notes-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.note-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-size: 12px;
    color: #666;
    line-height: 1.5;
}

.note-item i {
    margin-top: 3px;
    flex-shrink: 0;
    font-size: 10px;
}

/* Table Styles */
.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #f9fafb;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 500;
    color: #666;
}

.time-badge i {
    font-size: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 15px;
        padding-bottom: 100px;
    }

    .status-time {
        font-size: 18px;
    }

    .status-icon {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }

    .hours-value {
        font-size: 20px;
    }

    .welcome-icon {
        font-size: 50px;
    }

    .welcome-message h4 {
        font-size: 18px;
    }
}

@media (max-width: 576px) {
    .attendance-card {
        min-height: auto;
    }

    .status-display {
        padding: 10px 0;
    }

    .status-time {
        font-size: 16px;
    }

    .hours-value {
        font-size: 18px;
    }

    .completion-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }

    .completion-text h6 {
        font-size: 12px;
    }

    .completion-text p {
        font-size: 11px;
    }
}
</style>

<script>
// Real-time clock in header
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

    const liveTimeElement = document.getElementById('liveTime');
    if (liveTimeElement) {
        liveTimeElement.textContent = now.toLocaleTimeString('en-US');
    }
}

updateClock();
setInterval(updateClock, 1000);
</script>

<?php include 'includes/footer.php'; ?>