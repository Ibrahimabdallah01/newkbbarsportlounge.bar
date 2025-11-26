<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Attendance Reports";
$current_page = "reports";
$use_datatables = true;

$error = "";
$attendances = [];
$start_date = $_GET["start_date"] ?? "";
$end_date = $_GET["end_date"] ?? "";

if (!empty($start_date) && !empty($end_date)) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, e.name as employee_name, e.email as employee_email, d.name as department_name FROM attendances a JOIN employees e ON a.employee_id = e.id JOIN departments d ON e.department_id = d.id WHERE DATE(a.check_in) BETWEEN ? AND ? ORDER BY a.check_in DESC");
        $stmt->execute([$start_date, $end_date]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching reports: " . $e->getMessage();
    }
}

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
            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1 class="page-title">
                    <i class="fas fa-chart-bar me-2"></i>Attendance Reports
                </h1>
                <p class="text-muted mb-0">Generate and view attendance reports by date range</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="card modern-card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Reports
                    </h5>
                </div>
                <div class="card-body">
                    <form action="reports.php" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="<?php echo htmlspecialchars($start_date); ?>" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>End Date
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="<?php echo htmlspecialchars($end_date); ?>" required>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Generate
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Quick Date Filters -->
                    <div class="mt-3">
                        <p class="text-muted mb-2">Quick Filters:</p>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary"
                                onclick="setDateRange('today')">Today</button>
                            <button type="button" class="btn btn-outline-primary"
                                onclick="setDateRange('yesterday')">Yesterday</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDateRange('week')">This
                                Week</button>
                            <button type="button" class="btn btn-outline-primary" onclick="setDateRange('month')">This
                                Month</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Card -->
            <?php if (!empty($attendances)): ?>
            <div class="card modern-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-table me-2"></i>Report Results
                        </h5>
                        <span class="badge bg-light text-dark"><?php echo count($attendances); ?> records</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="reportsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendances as $att): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($att["employee_name"]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($att["employee_email"]); ?></td>
                                    <td><span
                                            class="badge bg-info"><?php echo htmlspecialchars($att["department_name"]); ?></span>
                                    </td>
                                    <td>
                                        <i class="fas fa-sign-in-alt text-success me-1"></i>
                                        <?php echo date('M d, h:i A', strtotime($att["check_in"])); ?>
                                    </td>
                                    <td>
                                        <?php if($att["check_out"]): ?>
                                        <i class="fas fa-sign-out-alt text-danger me-1"></i>
                                        <?php echo date('M d, h:i A', strtotime($att["check_out"])); ?>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $att["status"];
                                        $badge_class = 'bg-secondary';
                                        if ($status == 'present') $badge_class = 'bg-success';
                                        elseif ($status == 'late') $badge_class = 'bg-warning';
                                        elseif ($status == 'absent') $badge_class = 'bg-danger';
                                        ?>
                                        <span
                                            class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($att["notes"]); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif (!empty($start_date) && !empty($end_date)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>No attendance records found for the selected date range.
            </div>
            <?php endif; ?>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($("#reportsTable").length) {
        $("#reportsTable").DataTable({
            responsive: true,
            order: [
                [3, 'desc']
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ records"
            }
        });
    }
});

function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;

    switch (range) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = yesterday.toISOString().split('T')[0];
            break;
        case 'week':
            const weekStart = new Date(today);
            weekStart.setDate(today.getDate() - today.getDay());
            startDate = weekStart.toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
    }

    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
}
</script>

<style>
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

.page-header .page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.btn-group-sm .btn {
    padding: 0.35rem 0.75rem;
}
</style>