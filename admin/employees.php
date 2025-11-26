<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/auth.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "Employee Management";
$current_page = "employees";
$use_datatables = true;

$error = "";
$success = "";

// Handle Add/Edit Employee
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"]);
    $email = sanitize_input($_POST["email"]);
    $phone = sanitize_input($_POST["phone"]);
    $department_id = sanitize_input($_POST["department_id"]);
    $address = sanitize_input($_POST["address"]);
    $password = sanitize_input($_POST["password"]);
    $employee_id = isset($_POST["employee_id"]) ? sanitize_input($_POST["employee_id"]) : null;

    if (empty($name) || empty($email) || empty($department_id)) {
        $error = "Name, email, and department are required.";
    } else {
        if ($employee_id) {
            if (!empty($password)) {
                $hashed_password = hash_password($password);
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, department_id = ?, address = ?, password = ? WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $department_id, $address, $hashed_password, $employee_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, department_id = ?, address = ? WHERE id = ?");
                $result = $stmt->execute([$name, $email, $phone, $department_id, $address, $employee_id]);
            }
            if ($result) {
                $success = "Employee updated successfully.";
            } else {
                $error = "Error updating employee.";
            }
        } else {
            if (empty($password)) {
                $error = "Password is required for new employee.";
            } else {
                $hashed_password = hash_password($password);
                $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, department_id, address, password) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $phone, $department_id, $address, $hashed_password])) {
                    $success = "Employee added successfully.";
                } else {
                    $error = "Error adding employee. Email might already exist.";
                }
            }
        }
    }
}

// Handle Delete Employee
if (isset($_GET["action"]) && $_GET["action"] == "delete" && isset($_GET["id"])) {
    $employee_id = sanitize_input($_GET["id"]);
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$employee_id])) {
        $success = "Employee deleted successfully.";
    } else {
        $error = "Error deleting employee.";
    }
}

// Fetch all employees with department names
$employees = $pdo->query("SELECT e.*, d.name as department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id ORDER BY e.name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all departments for dropdown
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
                    <i class="fas fa-users me-2"></i>Employee Management
                </h1>
                <p class="text-muted mb-0">Manage all employees in your organization</p>
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

            <!-- Employees Card -->
            <div class="card modern-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>All Employees
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal"
                            data-bs-target="#addEditEmployeeModal">
                            <i class="fas fa-user-plus me-2"></i>Add Employee
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="employeesTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $emp["id"]; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($emp["name"]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($emp["email"]); ?></td>
                                    <td><?php echo htmlspecialchars($emp["phone"]); ?></td>
                                    <td><span
                                            class="badge bg-info"><?php echo htmlspecialchars($emp["department_name"]); ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            data-id="<?php echo $emp["id"]; ?>"
                                            data-name="<?php echo htmlspecialchars($emp["name"]); ?>"
                                            data-email="<?php echo htmlspecialchars($emp["email"]); ?>"
                                            data-phone="<?php echo htmlspecialchars($emp["phone"]); ?>"
                                            data-department="<?php echo $emp["department_id"]; ?>"
                                            data-address="<?php echo htmlspecialchars($emp["address"]); ?>"
                                            data-bs-toggle="modal" data-bs-target="#addEditEmployeeModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="employee_attendance.php?id=<?php echo $emp["id"]; ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-calendar-check"></i>
                                        </a>
                                        <a href="employees.php?action=delete&id=<?php echo $emp["id"]; ?>"
                                            class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash"></i>
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

<!-- Add/Edit Employee Modal -->
<div class="modal fade" id="addEditEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="employees.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i>Add/Edit Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="employee_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Name
                                </label>
                                <input type="text" class="form-control" id="employee_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="employee_email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Phone
                                </label>
                                <input type="text" class="form-control" id="employee_phone" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_department" class="form-label">
                                    <i class="fas fa-building me-1"></i>Department
                                </label>
                                <select class="form-control" id="employee_department" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept["id"]; ?>">
                                        <?php echo htmlspecialchars($dept["name"]); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="employee_address" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Address
                        </label>
                        <textarea class="form-control" id="employee_address" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="employee_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>Password
                        </label>
                        <input type="password" class="form-control" id="employee_password" name="password">
                        <div class="form-text">Leave blank to keep current password (for edit).</div>
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
    $('#employeesTable').DataTable({
        responsive: true,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ employees"
        }
    });

    $('#addEditEmployeeModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var email = button.data('email');
        var phone = button.data('phone');
        var department = button.data('department');
        var address = button.data('address');

        var modal = $(this);
        if (id) {
            modal.find('.modal-title').html('<i class="fas fa-edit me-2"></i>Edit Employee');
            modal.find('#employee_id').val(id);
            modal.find('#employee_name').val(name);
            modal.find('#employee_email').val(email);
            modal.find('#employee_phone').val(phone);
            modal.find('#employee_department').val(department);
            modal.find('#employee_address').val(address);
            modal.find('#employee_password').removeAttr('required');
        } else {
            modal.find('.modal-title').html('<i class="fas fa-plus me-2"></i>Add New Employee');
            modal.find('#employee_id').val('');
            modal.find('#employee_name').val('');
            modal.find('#employee_email').val('');
            modal.find('#employee_phone').val('');
            modal.find('#employee_department').val('');
            modal.find('#employee_address').val('');
            modal.find('#employee_password').attr('required', 'required');
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