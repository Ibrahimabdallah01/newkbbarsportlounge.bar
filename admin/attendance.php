<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/auth.php";

if (!is_logged_in()) redirect("../index.php");
if (!is_admin()) redirect("../employee/dashboard.php");

$current_page = 'attendance';
$page_title = 'Attendance Records';
$use_datatables = true;

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get attendance from database
$stmt = $pdo->prepare("
    SELECT a.*, e.name as employee_name, e.email, d.name as department_name
    FROM attendances a
    INNER JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE DATE(a.check_in) = ?
    ORDER BY a.check_in DESC
");
$stmt->execute([$filter_date]);
$attendance = $stmt->fetchAll();

include_once __DIR__ . '/layouts/head.php';
?>

<?php include_once __DIR__ . '/layouts/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include_once __DIR__ . '/layouts/navbar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-header">
                        <h2 class="page-title"><i class="fas fa-clipboard-check me-2"></i>Attendance Records</h2>
                        <form method="GET" class="d-flex gap-2">
                            <input type="date" class="form-control" name="date" value="<?php echo $filter_date; ?>"
                                style="max-width: 200px;">
                            <button type="submit" class="btn btn-gold">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5 class="card-title-custom">
                                <i class="fas fa-list me-2"></i>
                                <?php echo date('F d, Y', strtotime($filter_date)); ?>
                                (<?php echo count($attendance); ?> records)
                            </h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($attendance) > 0): ?>
                            <table id="attendanceTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendances as $i => $rec): 
                                        $check_in_time = strtotime($rec['check_in']);
                                        $expected_time = strtotime($filter_date . ' 09:00:00');
                                        $is_late = $check_in_time > $expected_time;
                                        
                                        $hours = 'N/A';
                                        if ($rec['check_out']) {
                                            $diff = strtotime($rec['check_out']) - $check_in_time;
                                            $hours = round($diff / 3600, 2) . ' hrs';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle text-gold fs-4 me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($rec['employee_name']); ?></strong>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars($rec['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($rec['department_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="<?php echo $is_late ? 'text-warning' : 'text-success'; ?>">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('h:i A', $check_in_time); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($rec['check_out']): ?>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($rec['check_out'])); ?>
                                            <?php else: ?>
                                            <span class="badge bg-info">Working</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $hours; ?></td>
                                        <td>
                                            <?php if ($is_late): ?>
                                            <span class="badge bg-warning">Late</span>
                                            <?php else: ?>
                                            <span class="badge bg-success">On Time</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No attendance records for this date</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
$extra_js = "<script>$('#attendanceTable').DataTable({order: [[3, 'asc']]});</script>";
include_once __DIR__ . '/layouts/footer.php';
?>

    <style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        padding: 1.5rem 2rem;
        border-radius: 15px;
        border: 2px solid #d4af37;
    }

    .page-title {
        color: #d4af37;
        margin: 0;
        font-weight: 700;
    }

    .btn-gold {
        background: linear-gradient(135deg, #b8860b 0%, #d4af37 100%);
        border: none;
        color: white;
        font-weight: 600;
    }

    .btn-gold:hover {
        background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
        color: white;
    }

    .card-custom {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 2px solid rgba(212, 175, 55, 0.2);
    }

    .card-header-custom {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        padding: 1.25rem 1.5rem;
        border-bottom: 2px solid #d4af37;
    }

    .card-title-custom {
        color: #d4af37;
        font-weight: 700;
        margin: 0;
    }

    .card-body-custom {
        padding: 1.5rem;
    }

    .table thead th {
        background: rgba(212, 175, 55, 0.1);
        border-bottom: 2px solid #d4af37;
        font-weight: 700;
    }

    .table tbody tr:hover {
        background-color: rgba(212, 175, 55, 0.05);
    }

    .text-gold {
        color: #d4af37 !important;
    }
    </style>