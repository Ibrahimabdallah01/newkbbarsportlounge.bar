<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Department Management";
$current_page = "departments";
$use_datatables = true;

$error = "";
$success = "";

// Handle Add/Edit Department
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"]);
    $description = sanitize_input($_POST["description"]);
    $department_id = isset($_POST["department_id"]) ? sanitize_input($_POST["department_id"]) : null;

    if (empty($name)) {
        $error = "Department name is required.";
    } else {
        if ($department_id) {
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $department_id])) {
                $success = "Department updated successfully.";
            } else {
                $error = "Error updating department.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = "Department added successfully.";
            } else {
                $error = "Error adding department. Department name might already exist.";
            }
        }
    }
}

// Handle Delete Department
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $department_id = sanitize_input($_GET["id"]);
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    if ($stmt->execute([$department_id])) {
        $success = "Department deleted successfully.";
    } else {
        $error = "Error deleting department.";
    }
}

// Fetch all departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
                    <i class="fas fa-building me-2"></i>Department Management
                </h1>
                <p class="text-muted mb-0">Manage all departments in your organization</p>
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

            <!-- Departments Card -->
            <div class="card modern-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>All Departments
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addEditDepartmentModal">
                            <i class="fas fa-plus me-2"></i>Add Department
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="departmentsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $dept["id"]; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($dept["name"]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($dept["description"]); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            data-id="<?php echo $dept["id"]; ?>"
                                            data-name="<?php echo htmlspecialchars($dept["name"]); ?>"
                                            data-description="<?php echo htmlspecialchars($dept["description"]); ?>"
                                            data-bs-toggle="modal" data-bs-target="#addEditDepartmentModal">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <a href="departments.php?action=delete&id=<?php echo $dept["id"]; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this department?');">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </a>
                                    </td>
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

<!-- Add/Edit Department Modal -->
<div class="modal fade" id="addEditDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="departments.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>Add/Edit Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="department_id" id="department_id">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">
                            <i class="fas fa-tag me-1"></i>Department Name
                        </label>
                        <input type="text" class="form-control" id="department_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="department_description" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea class="form-control" id="department_description" name="description"
                            rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#departmentsTable').DataTable({
        responsive: true,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ departments"
        }
    });

    $('#addEditDepartmentModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var description = button.data('description');

        var modal = $(this);
        if (id) {
            modal.find('.modal-title').html('<i class="fas fa-edit me-2"></i>Edit Department');
            modal.find('#department_id').val(id);
            modal.find('#department_name').val(name);
            modal.find('#department_description').val(description);
        } else {
            modal.find('.modal-title').html('<i class="fas fa-plus me-2"></i>Add New Department');
            modal.find('#department_id').val('');
            modal.find('#department_name').val('');
            modal.find('#department_description').val('');
        }
    });
});
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

.modern-card .card-header .btn-light {
    background: white;
    color: #667eea;
    border: none;
    font-weight: 600;
}

.modern-card .card-header .btn-light:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

.page-header .page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}
</style>