<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/auth.php";

if (!is_logged_in()) redirect("../index.php");
if (!is_admin()) redirect("../employee/dashboard.php");

$current_page = 'reports';
$page_title = 'Reports';
$use_charts = true;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get daily attendance stats
$stmt = $pdo->prepare("
    SELECT 
        DATE(check_in) as date,
        COUNT(DISTINCT employee_id) as present_count
    FROM attendances
    WHERE DATE(check_in) BETWEEN ? AND ?
    GROUP BY DATE(check_in)
    ORDER BY date
");
$stmt->execute([$start_date, $end_date]);
$daily_attendance = $stmt->fetchAll();

// Get department-wise attendance
$stmt = $pdo->prepare("
    SELECT 
        d.name as department,
        COUNT(DISTINCT a.id) as attendance_count
    FROM attendances a
    INNER JOIN employees e ON a.employee_id = e.id
    INNER JOIN departments d ON e.department_id = d.id
    WHERE DATE(a.check_in) BETWEEN ? AND ?
    GROUP BY d.id, d.name
    ORDER BY attendance_count DESC
");
$stmt->execute([$start_date, $end_date]);
$dept_attendance = $stmt->fetchAll();

// Get late arrivals
$stmt = $pdo->prepare("
    SELECT 
        e.name as employee_name,
        d.name as department_name,
        COUNT(*) as late_count
    FROM attendances a
    INNER JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE DATE(a.check_in) BETWEEN ? AND ?
    AND TIME(a.check_in) > '09:00:00'
    GROUP BY e.id, e.name, d.name
    ORDER BY late_count DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$late_arrivals = $stmt->fetchAll();

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
                        <h2 class="page-title"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                    </div>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-body-custom">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date"
                                        value="<?php echo $start_date; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date"
                                        value="<?php echo $end_date; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-gold w-100">
                                        <i class="fas fa-chart-line me-2"></i>Generate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <?php if (count($daily_attendance) > 0 || count($dept_attendance) > 0): ?>
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5 class="card-title-custom"><i class="fas fa-chart-line me-2"></i>Daily Attendance Trend
                            </h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($daily_attendance) > 0): ?>
                            <canvas id="attendanceChart"></canvas>
                            <?php else: ?>
                            <p class="text-center text-muted py-5">No data available for the selected period</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5 class="card-title-custom"><i class="fas fa-chart-pie me-2"></i>By Department</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($dept_attendance) > 0): ?>
                            <canvas id="departmentChart"></canvas>
                            <?php else: ?>
                            <p class="text-center text-muted py-5">No data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Late Arrivals Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5 class="card-title-custom"><i class="fas fa-clock me-2"></i>Top 10 Late Arrivals</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($late_arrivals) > 0): ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Late Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($late_arrivals as $i => $rec): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td><strong
                                                class="text-gold"><?php echo htmlspecialchars($rec['employee_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($rec['department_name'] ?: 'N/A'); ?></td>
                                        <td><span class="badge bg-warning"><?php echo $rec['late_count']; ?>
                                                times</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <p class="text-center text-muted py-5">No late arrivals in the selected period</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php
$dates = json_encode(array_column($daily_attendance, 'date'));
$counts = json_encode(array_column($daily_attendance, 'present_count'));
$dept_names = json_encode(array_column($dept_attendance, 'department'));
$dept_counts = json_encode(array_column($dept_attendance, 'attendance_count'));

$extra_js = "
<script>
" . (count($daily_attendance) > 0 ? "
// Daily Attendance Chart
const ctx1 = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: $dates,
        datasets: [{
            label: 'Attendance',
            data: $counts,
            borderColor: '#d4af37',
            backgroundColor: 'rgba(212, 175, 55, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});
" : "") . "

" . (count($dept_attendance) > 0 ? "
// Department Chart
const ctx2 = document.getElementById('departmentChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: $dept_names,
        datasets: [{
            data: $dept_counts,
            backgroundColor: ['#d4af37', '#b8860b', '#ffd700', '#daa520', '#cd7f32']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
" : "") . "
</script>
";

include_once __DIR__ . '/layouts/footer.php';
?>

    <style>
    .page-header {
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