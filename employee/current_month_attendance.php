<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_employee()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "My Attendance History";
$current_page = "attendance_history";
$use_datatables = true;

$employee_id = $_SESSION["user_id"];
$error = "";

$current_month_start = date("Y-m-01 00:00:00");
$current_month_end = date("Y-m-t 23:59:59");

// Fetch attendance records for the current month
try {
    $stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND check_in BETWEEN ? AND ? ORDER BY check_in DESC");
    $stmt->execute([$employee_id, $current_month_start, $current_month_end]);
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching attendance records: " . $e->getMessage();
}

// Calculate statistics
$total_days = count($attendances);
$present_days = count(array_filter($attendances, function($a) { 
    return $a['status'] == 'Present'; 
}));
$late_days = count(array_filter($attendances, function($a) { 
    return $a['status'] == 'Late'; 
}));
$absent_days = count(array_filter($attendances, function($a) { 
    return $a['status'] == 'Absent'; 
}));

// Calculate total working hours
$total_seconds = 0;
foreach ($attendances as $att) {
    if (!empty($att['check_in']) && !empty($att['check_out'])) {
        $in = new DateTime($att['check_in']);
        $out = new DateTime($att['check_out']);
        $diff = $in->diff($out);
        $total_seconds += ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
    }
}
$total_hours = floor($total_seconds / 3600);
$total_minutes = floor(($total_seconds % 3600) / 60);

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
                <h2>My Attendance</h2>
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

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-calendar-alt"></i>
            My Attendance History
        </h1>
        <p class="page-subtitle">
            Attendance records for <?php echo date("F Y"); ?>
        </p>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_days; ?></h3>
                    <p>Total Days</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $present_days; ?></h3>
                    <p>Present</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $late_days; ?></h3>
                    <p>Late</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_hours; ?>h <?php echo $total_minutes; ?>m</h3>
                    <p>Total Hours</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Records Card -->
    <div class="modern-card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-table me-2"></i>
                Attendance Records - <?php echo date("F Y"); ?>
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light" onclick="printTable()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-sm btn-light" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($attendances)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h5>No Attendance Records</h5>
                <p class="text-muted">You don't have any attendance records for this month yet.</p>
                <a href="mark_attendance.php" class="btn btn-primary mt-2">
                    <i class="fas fa-qrcode me-2"></i>Mark Attendance Now
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="attendanceTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Working Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendances as $attendance): ?>
                        <?php
                            $hours = "N/A";
                            if (!empty($attendance['check_in']) && !empty($attendance['check_out'])) {
                                $in = new DateTime($attendance['check_in']);
                                $out = new DateTime($attendance['check_out']);
                                $diff = $in->diff($out);
                                $hours = $diff->format('%hh %im');
                            }
                            
                            // Status badge colors
                            $status_class = 'secondary';
                            if ($attendance['status'] == 'Present') $status_class = 'success';
                            elseif ($attendance['status'] == 'Late') $status_class = 'warning';
                            elseif ($attendance['status'] == 'Absent') $status_class = 'danger';
                            ?>
                        <tr>
                            <td>
                                <strong><?php echo date("M d, Y", strtotime($attendance['check_in'])); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    <?php echo date("l", strtotime($attendance['check_in'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($attendance['check_in']): ?>
                                <span class="time-badge check-in">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <?php echo date("h:i A", strtotime($attendance['check_in'])); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($attendance['check_out']): ?>
                                <span class="time-badge check-out">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <?php echo date("h:i A", strtotime($attendance['check_out'])); ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-warning">In Progress</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: #667eea;"><?php echo $hours; ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($attendance['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($attendance['notes']): ?>
                                <span class="text-muted">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    <?php echo htmlspecialchars($attendance['notes']); ?>
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

            <!-- Summary Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="summary-box">
                        <h6><i class="fas fa-chart-pie me-2"></i>Attendance Summary</h6>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-check-circle text-success"></i> Present:
                            </span>
                            <span class="summary-value"><?php echo $present_days; ?> days</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-clock text-warning"></i> Late:
                            </span>
                            <span class="summary-value"><?php echo $late_days; ?> days</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-times-circle text-danger"></i> Absent:
                            </span>
                            <span class="summary-value"><?php echo $absent_days; ?> days</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="summary-box">
                        <h6><i class="fas fa-clock me-2"></i>Working Hours</h6>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-hourglass-half text-info"></i> Total Hours:
                            </span>
                            <span class="summary-value"><?php echo $total_hours; ?>h
                                <?php echo $total_minutes; ?>m</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-calendar-day text-primary"></i> Average/Day:
                            </span>
                            <span class="summary-value">
                                <?php 
                                    if ($total_days > 0) {
                                        $avg_hours = floor($total_seconds / $total_days / 3600);
                                        $avg_minutes = floor(($total_seconds / $total_days % 3600) / 60);
                                        echo $avg_hours . "h " . $avg_minutes . "m";
                                    } else {
                                        echo "0h 0m";
                                    }
                                    ?>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-percentage text-success"></i> Attendance Rate:
                            </span>
                            <span class="summary-value">
                                <?php 
                                    $working_days = date('t'); // Total days in month
                                    $rate = $total_days > 0 ? round(($present_days / $working_days) * 100, 1) : 0;
                                    echo $rate . "%";
                                    ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Time Badges */
.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
}

.time-badge.check-in {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.time-badge.check-out {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.time-badge i {
    font-size: 0.8rem;
}

/* Summary Boxes */
.summary-box {
    background: #f9fafb;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
}

.summary-box h6 {
    color: #374151;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6b7280;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-value {
    color: #374151;
    font-weight: 700;
    font-size: 1rem;
}

/* Table Enhancements */
.table thead th {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    border-bottom: 2px solid #e5e7eb;
    padding: 1rem;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table tbody tr:hover {
    background: #f9fafb;
    transition: background 0.2s ease;
}

/* Card Header Buttons */
.card-header .btn-sm {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.card-header .btn-light {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.card-header .btn-light:hover {
    background: white;
    transform: translateY(-2px);
}

/* Responsive Table */
@media (max-width: 768px) {
    .table-responsive {
        border-radius: 12px;
    }

    .table thead {
        display: none;
    }

    .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem;
        background: white;
    }

    .table tbody td {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border: none;
        border-bottom: 1px solid #f3f4f6;
    }

    .table tbody td:last-child {
        border-bottom: none;
    }

    .table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6b7280;
        margin-right: 1rem;
    }

    .summary-box {
        margin-bottom: 1rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#attendanceTable').DataTable({
        responsive: true,
        order: [
            [0, 'desc']
        ],
        pageLength: 25,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            infoEmpty: "No records available",
            infoFiltered: "(filtered from _TOTAL_ total records)",
            zeroRecords: "No matching records found"
        },
        dom: 'lBfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

// Print function
function printTable() {
    window.print();
}

// Export to CSV function
function exportToCSV() {
    const table = document.getElementById('attendanceTable');
    let csv = [];

    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));

    // Get data
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            // Clean the text content
            let text = td.textContent.trim().replace(/\n/g, ' ').replace(/,/g, ';');
            row.push(text);
        });
        csv.push(row.join(','));
    });

    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], {
        type: 'text/csv'
    });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'attendance_<?php echo date("F_Y"); ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>