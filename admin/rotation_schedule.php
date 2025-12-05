<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Rotation Schedule";
$current_page = "rotation";

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

// Handle Generate Schedule
if (isset($_POST['generate_schedule'])) {
    $weeks_ahead = (int)$_POST['weeks_ahead'];
    
    try {
        $rotation = $pdo->prepare("SELECT * FROM rotation_groups WHERE id = ?");
        $rotation->execute([$rotation_id]);
        $rotation_group = $rotation->fetch(PDO::FETCH_ASSOC);
        
        $members = $pdo->prepare("
            SELECT * FROM rotation_group_members 
            WHERE rotation_group_id = ? AND is_active = 1 
            ORDER BY rotation_order ASC
        ");
        $members->execute([$rotation_id]);
        $rotation_members = $members->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rotation_members)) {
            $error = "Add employees to the rotation first!";
        } else {
            $total_members = count($rotation_members);
            $start_date = new DateTime($rotation_group['start_date']);
            $current_date = new DateTime();
            
            $weeks_since_start = floor(($current_date->getTimestamp() - $start_date->getTimestamp()) / (7 * 24 * 60 * 60));
            
            $pdo->prepare("DELETE FROM rotation_schedule WHERE rotation_group_id = ? AND start_date >= CURDATE()")->execute([$rotation_id]);
            
            $generated_count = 0;
            for ($week = 0; $week < $weeks_ahead; $week++) {
                $rotation_index = ($weeks_since_start + $week) % $total_members;
                $current_member = $rotation_members[$rotation_index];
                
                $week_start = clone $current_date;
                $week_start->modify("+$week weeks");
                $week_start->modify('this week monday');
                
                $week_end = clone $week_start;
                $week_end->modify('+6 days');
                
                $week_number = (int)$week_start->format('W');
                
                $stmt = $pdo->prepare("
                    INSERT INTO rotation_schedule 
                    (rotation_group_id, employee_id, rotation_order, week_number, start_date, end_date, is_rest_week, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, 0, 'scheduled', ?)
                ");
                
                $notes = "Auto-generated schedule for week " . $week_number;
                $stmt->execute([
                    $rotation_id,
                    $current_member['employee_id'],
                    $current_member['rotation_order'],
                    $week_number,
                    $week_start->format('Y-m-d'),
                    $week_end->format('Y-m-d'),
                    $notes
                ]);
                
                $generated_count++;
            }
            
            $success = "Successfully generated $generated_count weeks of schedule!";
        }
    } catch (Exception $e) {
        $error = "Error generating schedule: " . $e->getMessage();
    }
}

// Fetch rotation group details
$rotation = $pdo->prepare("
    SELECT rg.*, sp.name as shift_name, sp.work_days, sp.off_days
    FROM rotation_groups rg
    LEFT JOIN shift_patterns sp ON rg.shift_pattern_id = sp.id
    WHERE rg.id = ?
");
$rotation->execute([$rotation_id]);
$rotation_group = $rotation->fetch(PDO::FETCH_ASSOC);

if (!$rotation_group) {
    redirect("rotation_management.php");
}

// Fetch rotation members
$members = $pdo->prepare("
    SELECT rgm.*, e.name as employee_name
    FROM rotation_group_members rgm
    JOIN employees e ON rgm.employee_id = e.id
    WHERE rgm.rotation_group_id = ? AND rgm.is_active = 1
    ORDER BY rgm.rotation_order ASC
");
$members->execute([$rotation_id]);
$rotation_members = $members->fetchAll(PDO::FETCH_ASSOC);

// Fetch schedules
$schedules = $pdo->prepare("
    SELECT rs.*, e.name as employee_name
    FROM rotation_schedule rs
    JOIN employees e ON rs.employee_id = e.id
    WHERE rs.rotation_group_id = ? 
    AND rs.start_date >= CURDATE()
    ORDER BY rs.start_date ASC
    LIMIT 12
");
$schedules->execute([$rotation_id]);
$rotation_schedules = $schedules->fetchAll(PDO::FETCH_ASSOC);
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

    /* SIDEBAR */
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
    }

    .btn-success {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    /* PAGE HEADER */
    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        border-left: 4px solid var(--kb-gold);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--kb-dark);
    }

    .page-title i {
        color: var(--kb-gold);
    }

    /* CURRENT WEEK CARD */
    .current-week-card {
        border: 2px solid var(--kb-gold);
        border-radius: 15px;
        background: linear-gradient(135deg, var(--kb-dark) 0%, var(--kb-dark-alt) 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }

    .current-week-card .card-title {
        color: var(--kb-gold);
    }

    .current-week-card h3 {
        color: var(--kb-gold);
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
        border-bottom: 2px solid var(--kb-gold);
    }

    .modern-card .card-header .card-title i {
        color: var(--kb-gold);
    }

    /* TIMELINE */
    .timeline {
        position: relative;
        padding: 1rem 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--kb-gold);
    }

    .timeline-item {
        position: relative;
        padding-left: 60px;
        margin-bottom: 2rem;
    }

    .timeline-marker {
        position: absolute;
        left: 10px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--kb-dark-gold), var(--kb-gold));
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .schedule-card {
        background: white;
        border-radius: 15px;
        padding: 1.25rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
        border-left: 4px solid var(--kb-gold);
    }

    .schedule-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .schedule-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    }

    .schedule-header h6 {
        margin: 0;
        color: var(--kb-dark);
        font-weight: 600;
    }

    .schedule-header h6 i {
        color: var(--kb-gold);
    }

    .employee-info {
        display: flex;
        align-items: center;
    }

    .employee-info i {
        color: var(--kb-gold);
    }

    .employee-info h5 {
        color: var(--kb-dark);
    }

    /* MODAL */
    .modal-header.bg-success {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    .modal-header {
        border-bottom: 2px solid var(--kb-gold);
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

        .timeline::before {
            left: 10px;
        }

        .timeline-item {
            padding-left: 40px;
        }
    }
    </style>
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-logo">
                <img src="<?php echo $company_logo; ?>" alt="<?php echo $company_name; ?>" class="company-logo">
                <h5 class="company-name"><?php echo $company_name; ?></h5>
                <p class="mb-0" style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.8);"><?php echo $system_name; ?>
                </p>
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

            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-2" style="color: white;">
                    <i class="fas fa-user-circle fa-2x" style="color: var(--kb-gold);"></i>
                    <div>
                        <p class="mb-0" style="font-size: 0.9rem; font-weight: 600;">
                            <?php echo htmlspecialchars($_SESSION["user_name"]); ?></p>
                        <p class="mb-0" style="font-size: 0.75rem; color: var(--kb-gold);">Administrator</p>
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
                </div>
            </nav>

            <div class="container-fluid p-3 p-md-4">
                <!-- Back Buttons -->
                <div class="mb-3">
                    <a href="rotation_details.php?id=<?php echo $rotation_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Details
                    </a>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateScheduleModal">
                        <i class="fas fa-calendar-plus me-2"></i>Generate Schedule
                    </button>
                </div>

                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt me-2"></i>Rotation Schedule
                    </h1>
                    <p class="text-muted mb-0">
                        <strong><?php echo htmlspecialchars($rotation_group['name']); ?></strong> -
                        <?php echo count($rotation_members); ?> members rotating every
                        <?php echo $rotation_group['work_days']; ?> days
                    </p>
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

                <?php if (empty($rotation_members)): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>No Members in Rotation</h5>
                    <p>Please add employees to this rotation group before generating schedules.</p>
                    <a href="rotation_details.php?id=<?php echo $rotation_id; ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Add Members
                    </a>
                </div>
                <?php else: ?>

                <!-- Current Week Card -->
                <div class="card current-week-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-calendar-day me-2"></i>Current Week (Week <?php echo date('W'); ?>)
                        </h5>
                        <?php
                        $start_date = new DateTime($rotation_group['start_date']);
                        $current_date = new DateTime();
                        $weeks_since_start = floor(($current_date->getTimestamp() - $start_date->getTimestamp()) / (7 * 24 * 60 * 60));
                        $current_rotation = $weeks_since_start % count($rotation_members);
                        $current_worker = $rotation_members[$current_rotation];
                        
                        $monday = new DateTime();
                        $monday->modify('this week monday');
                        $sunday = new DateTime();
                        $sunday->modify('this week sunday');
                        ?>
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h3 class="mb-2">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <?php echo htmlspecialchars($current_worker['employee_name']); ?>
                                </h3>
                                <p class="text-muted mb-0" style="color: rgba(255, 255, 255, 0.8) !important;">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo $monday->format('M d'); ?> - <?php echo $sunday->format('M d, Y'); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <span class="badge bg-success p-3" style="font-size: 1rem;">
                                    <i class="fas fa-briefcase me-2"></i>Currently On Duty
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Timeline -->
                <div class="card modern-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-week me-2"></i>Upcoming Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rotation_schedules)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5>No Schedule Generated</h5>
                            <p class="text-muted">Click "Generate Schedule" to create rotation schedules</p>
                            <button class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#generateScheduleModal">
                                <i class="fas fa-calendar-plus me-2"></i>Generate Now
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($rotation_schedules as $schedule): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="schedule-card">
                                        <div class="schedule-header">
                                            <h6>
                                                <i class="fas fa-calendar-week me-2"></i>
                                                Week <?php echo $schedule['week_number']; ?>
                                            </h6>
                                            <span class="badge bg-info">
                                                <?php echo date('M d', strtotime($schedule['start_date'])); ?> -
                                                <?php echo date('M d, Y', strtotime($schedule['end_date'])); ?>
                                            </span>
                                        </div>
                                        <div class="schedule-body">
                                            <div class="employee-info">
                                                <i class="fas fa-user-circle fa-2x me-3"></i>
                                                <div>
                                                    <h5 class="mb-1">
                                                        <?php echo htmlspecialchars($schedule['employee_name']); ?></h5>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-sort-numeric-up me-1"></i>
                                                        Rotation Order: #<?php echo $schedule['rotation_order']; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <?php if ($schedule['notes']): ?>
                                            <p class="text-muted small mb-0 mt-2">
                                                <i class="fas fa-sticky-note me-1"></i>
                                                <?php echo htmlspecialchars($schedule['notes']); ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php endif; ?>
            </div>

            <!-- Footer -->
            <footer class="main-footer">
                <div class="container-fluid">
                    <p class="mb-0 text-center">
                        <strong><?php echo $company_name; ?></strong> &copy; <?php echo date('Y'); ?>
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Generate Schedule Modal -->
    <div class="modal fade" id="generateScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="rotation_schedule.php?id=<?php echo $rotation_id; ?>" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-calendar-plus me-2"></i>Generate Rotation Schedule
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This will automatically generate weekly rotation schedules based on your rotation group
                            settings.
                        </div>
                        <div class="mb-3">
                            <label for="weeks_ahead" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Generate for how many weeks?
                            </label>
                            <select class="form-control" id="weeks_ahead" name="weeks_ahead" required>
                                <option value="4">4 weeks (1 month)</option>
                                <option value="8" selected>8 weeks (2 months)</option>
                                <option value="12">12 weeks (3 months)</option>
                                <option value="16">16 weeks (4 months)</option>
                                <option value="24">24 weeks (6 months)</option>
                                <option value="52">52 weeks (1 year)</option>
                            </select>
                            <div class="form-text">Recommended: 8-12 weeks for easy planning</div>
                        </div>
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>Note:</strong>
                            This will replace any existing future schedules.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" name="generate_schedule" class="btn btn-success">
                            <i class="fas fa-magic me-1"></i>Generate Schedule
                        </button>
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