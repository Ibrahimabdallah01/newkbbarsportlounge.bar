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

// Fetch employee details
$stmt = $pdo->prepare("SELECT name, email FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect("employees.php");
}

// Page configuration
$page_title = "Attendance - " . $employee["name"];
$current_page = "employees";
$use_datatables = true;

// Fetch attendance records for the employee
$attendances = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? ORDER BY check_in DESC");
$attendances->execute([$employee_id]);
$employee_attendances = $attendances->fetchAll(PDO::FETCH_ASSOC);

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
            <!-- Back Button -->
            <div class="mb-3">
                <a href="employees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Employees
                </a>
            </div>

            <!-- Employee Info Card -->
            <div class="employee-info-card mb-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="employee-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2"><?php echo htmlspecialchars($employee["name"]); ?></h2>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($employee["email"]); ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-success p-2">
                            <i class="fas fa-check-circle me-1"></i>
                            <?php echo count($employee_attendances); ?> Total Records
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attendance History Card -->
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check me-2"></i>Attendance History
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attendanceTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employee_attendances as $attendance): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $attendance["id"]; ?></span></td>
                                    <td>
                                        <i class="fas fa-sign-in-alt text-success me-1"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($attendance["check_in"])); ?>
                                    </td>
                                    <td>
                                        <?php if($attendance["check_out"]): ?>
                                        <i class="fas fa-sign-out-alt text-danger me-1"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($attendance["check_out"])); ?>
                                        <?php else: ?>
                                        <span class="text-muted">Not checked out</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $attendance["status"];
                                        $badge_class = 'bg-secondary';
                                        if ($status == 'present') $badge_class = 'bg-success';
                                        elseif ($status == 'late') $badge_class = 'bg-warning';
                                        elseif ($status == 'absent') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($attendance["notes"]); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#attendanceTable").DataTable({
        responsive: true,
        order: [
            [1, 'desc']
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ records"
        }
    });
});
</script>

<style>
.employee-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.employee-avatar {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
}

.modern-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.modern-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.5rem;
    border: none;
}

@media (max-width: 768px) {
    .employee-info-card {
        padding: 1.5rem;
        text-align: center;
    }

    .employee-avatar {
        width: 60px;
        height: 60px;
        font-size: 2rem;
        margin: 0 auto 1rem;
    }
}
</style>