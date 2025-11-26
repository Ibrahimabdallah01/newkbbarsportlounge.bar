<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Rotation Management";
$current_page = "rotation";
$use_datatables = true;

$error = "";
$success = "";

// Fetch all rotation groups with details
$rotation_groups = $pdo->query("
    SELECT rg.*, sp.name as shift_name, sp.work_days, sp.off_days, d.name as department_name,
    (SELECT COUNT(*) FROM rotation_group_members WHERE rotation_group_id = rg.id AND is_active = 1) as member_count
    FROM rotation_groups rg
    LEFT JOIN shift_patterns sp ON rg.shift_pattern_id = sp.id
    LEFT JOIN departments d ON rg.department_id = d.id
    ORDER BY rg.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch shift patterns for dropdown
$shift_patterns = $pdo->query("SELECT * FROM shift_patterns WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all employees for adding to rotation
$employees = $pdo->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php include 'includes/sidebar.php'; ?>

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
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">
                            <i class="fas fa-sync-alt me-2"></i>Rotation Management
                        </h1>
                        <p class="text-muted mb-0">Manage employee work rotation schedules</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRotationGroupModal">
                            <i class="fas fa-plus me-2"></i>Create Rotation Group
                        </button>
                    </div>
                </div>
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

            <!-- Quick Stats -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo count($rotation_groups); ?></h3>
                            <p class="stat-label">Rotation Groups</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo count($shift_patterns); ?></h3>
                            <p class="stat-label">Shift Patterns</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo count($employees); ?></h3>
                            <p class="stat-label">Available Employees</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">Week <?php echo date('W'); ?></h3>
                            <p class="stat-label">Current Week</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rotation Groups Card -->
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>All Rotation Groups
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rotation_groups)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-sync-alt fa-4x text-muted mb-3"></i>
                        <h5>No Rotation Groups Yet</h5>
                        <p class="text-muted">Create your first rotation group to start managing schedules</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRotationGroupModal">
                            <i class="fas fa-plus me-2"></i>Create Rotation Group
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($rotation_groups as $group): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="rotation-group-card">
                                <div class="rotation-group-header">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h6>
                                    <span
                                        class="badge <?php echo $group['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $group['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="rotation-group-body">
                                    <div class="info-row">
                                        <i class="fas fa-building text-primary"></i>
                                        <span><?php echo $group['department_name'] ?? 'All Departments'; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-clock text-success"></i>
                                        <span><?php echo htmlspecialchars($group['shift_name']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-calendar-alt text-info"></i>
                                        <span><?php echo $group['work_days']; ?> days work,
                                            <?php echo $group['off_days']; ?> day(s) off</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-users text-warning"></i>
                                        <span><?php echo $group['member_count']; ?> members</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-play-circle text-secondary"></i>
                                        <span>Started:
                                            <?php echo date('M d, Y', strtotime($group['start_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="rotation-group-footer">
                                    <a href="rotation_details.php?id=<?php echo $group['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <a href="rotation_schedule.php?id=<?php echo $group['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-calendar me-1"></i>Schedule
                                    </a>
                                    <button class="btn btn-sm btn-warning"
                                        onclick="editGroup(<?php echo $group['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Add Rotation Group Modal -->
<div class="modal fade" id="addRotationGroupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="rotation_create.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Create New Rotation Group
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="group_name" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Group Name
                                </label>
                                <input type="text" class="form-control" id="group_name" name="name"
                                    placeholder="e.g., Bartender Team A" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">
                                    <i class="fas fa-building me-1"></i>Department
                                </label>
                                <select class="form-control" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="shift_pattern_id" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Shift Pattern
                                </label>
                                <select class="form-control" id="shift_pattern_id" name="shift_pattern_id" required>
                                    <option value="">Select Shift Pattern</option>
                                    <?php foreach ($shift_patterns as $pattern): ?>
                                    <option value="<?php echo $pattern['id']; ?>"
                                        data-work="<?php echo $pattern['work_days']; ?>"
                                        data-off="<?php echo $pattern['off_days']; ?>">
                                        <?php echo htmlspecialchars($pattern['name']); ?>
                                        (<?php echo $pattern['work_days']; ?> days work,
                                        <?php echo $pattern['off_days']; ?> off)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                            placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.stat-card-primary .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* Rotation Group Cards */
.rotation-group-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.rotation-group-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.rotation-group-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rotation-group-header h6 {
    margin: 0;
    font-weight: 600;
}

.rotation-group-body {
    padding: 1.25rem;
    flex: 1;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-row i {
    width: 20px;
    font-size: 1rem;
}

.rotation-group-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .stat-card {
        padding: 1.25rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }

    .stat-number {
        font-size: 1.5rem;
    }
}
</style>