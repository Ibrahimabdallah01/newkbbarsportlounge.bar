<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Leave Management";
$current_page = "leave";
$use_datatables = true;

$error = "";
$success = "";

// Handle approve/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = sanitize_input($_POST['request_id']);
    $action = sanitize_input($_POST['action']);
    
    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id'], $request_id])) {
            $success = "Leave request approved successfully!";
        } else {
            $error = "Failed to approve leave request.";
        }
    } elseif ($action == 'reject') {
        $rejection_reason = sanitize_input($_POST['rejection_reason']);
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id'], $rejection_reason, $request_id])) {
            $success = "Leave request rejected.";
        } else {
            $error = "Failed to reject leave request.";
        }
    }
}

// Fetch all leave requests with employee and leave type details
$leave_requests = $pdo->query("
    SELECT lr.*, e.name as employee_name, e.email as employee_email, 
           lt.name as leave_type_name, lt.color as leave_type_color, lt.icon as leave_type_icon,
           a.name as approved_by_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    LEFT JOIN admins a ON lr.approved_by = a.id
    ORDER BY lr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch leave types for stats
$leave_types = $pdo->query("SELECT * FROM leave_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$pending_count = count(array_filter($leave_requests, function($req) { return $req['status'] == 'pending'; }));
$approved_count = count(array_filter($leave_requests, function($req) { return $req['status'] == 'approved'; }));
$rejected_count = count(array_filter($leave_requests, function($req) { return $req['status'] == 'rejected'; }));

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
                <h1 class="page-title">
                    <i class="fas fa-calendar-times me-2"></i>Leave Management
                </h1>
                <p class="text-muted mb-0">Approve or reject employee leave requests</p>
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
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $pending_count; ?></h3>
                            <p class="stat-label">Pending Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $approved_count; ?></h3>
                            <p class="stat-label">Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo $rejected_count; ?></h3>
                            <p class="stat-label">Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo count($leave_requests); ?></h3>
                            <p class="stat-label">Total Requests</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Card -->
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>All Leave Requests
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($leave_requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5>No Leave Requests Yet</h5>
                        <p class="text-muted">Employees haven't submitted any leave requests</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table id="leaveTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_requests as $request): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $request['id']; ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['employee_name']); ?></strong><br>
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($request['employee_email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge"
                                            style="background-color: <?php echo $request['leave_type_color']; ?>">
                                            <i class="fas <?php echo $request['leave_type_icon']; ?> me-1"></i>
                                            <?php echo htmlspecialchars($request['leave_type_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['end_date'])); ?></td>
                                    <td><strong><?php echo $request['total_days']; ?></strong> day(s)</td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($request['reason'], 0, 50)); ?>
                                            <?php echo strlen($request['reason']) > 50 ? '...' : ''; ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $status = $request['status'];
                                            $badge_class = 'bg-secondary';
                                            if ($status == 'pending') $badge_class = 'bg-warning';
                                            elseif ($status == 'approved') $badge_class = 'bg-success';
                                            elseif ($status == 'rejected') $badge_class = 'bg-danger';
                                            ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success"
                                            onclick="approveRequest(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger"
                                            onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info"
                                            onclick="viewDetails(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Reject Leave Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="leave_management.php" method="POST">
                <input type="hidden" name="request_id" id="reject_request_id">
                <input type="hidden" name="action" value="reject">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Reject Leave Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required
                            placeholder="Explain why this leave is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Reject Leave
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#leaveTable').DataTable({
        responsive: true,
        order: [
            [0, 'desc']
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ requests"
        }
    });
});

function approveRequest(id) {
    if (confirm('Are you sure you want to approve this leave request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'leave_management.php';

        const requestIdInput = document.createElement('input');
        requestIdInput.type = 'hidden';
        requestIdInput.name = 'request_id';
        requestIdInput.value = id;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'approve';

        form.appendChild(requestIdInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequest(id) {
    document.getElementById('reject_request_id').value = id;
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

function viewDetails(id) {
    // TODO: Implement view details modal
    alert('View details coming soon!');
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

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card-success .stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card-danger .stat-icon {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.stat-card-info .stat-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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
</style>