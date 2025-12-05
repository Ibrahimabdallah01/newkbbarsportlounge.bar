<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_employee()) {
    redirect("../index.php");
}

// Page configuration
$page_title = "My Profile";
$current_page = "profile";

$employee_id = $_SESSION["user_id"];
$error = "";
$success = "";

// Fetch employee details with department name
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect("../logout.php");
}

// Fetch all departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $name = sanitize_input($_POST["name"]);
    $email = sanitize_input($_POST["email"]);
    $phone = sanitize_input($_POST["phone"]);
    $address = sanitize_input($_POST["address"]);
    $department_id = sanitize_input($_POST["department_id"]);

    if (empty($name) || empty($email) || empty($department_id)) {
        $error = "Name, email, and department are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists for another employee
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$email, $employee_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "This email is already taken by another employee.";
        } else {
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, phone = ?, address = ?, department_id = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $phone, $address, $department_id, $employee_id])) {
                $_SESSION["user_name"] = $name; // Update session name
                $success = "Profile updated successfully!";
                
                // Refresh employee data
                $stmt = $pdo->prepare("
                    SELECT e.*, d.name as department_name 
                    FROM employees e 
                    LEFT JOIN departments d ON e.department_id = d.id 
                    WHERE e.id = ?
                ");
                $stmt->execute([$employee_id]);
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Error updating profile.";
            }
        }
    }
}

// Get employee statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendances WHERE employee_id = ?");
$stmt->execute([$employee_id]);
$total_attendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM attendances 
    WHERE employee_id = ? 
    AND MONTH(check_in) = MONTH(CURDATE()) 
    AND YEAR(check_in) = YEAR(CURDATE())
");
$stmt->execute([$employee_id]);
$month_attendance = $stmt->fetchColumn();

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
                <h2>My Profile</h2>
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

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-circle"></i>
                My Profile
            </h1>
            <p class="page-subtitle">
                Manage your personal information and account settings
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

        <div class="row g-4">
            <!-- Profile Card -->
            <div class="col-lg-4">
                <!-- Profile Overview Card -->
                <div class="modern-card profile-card">
                    <div class="card-body text-center">
                        <div class="profile-avatar">
                            <div class="avatar-circle">
                                <?php echo strtoupper(substr($employee['name'], 0, 2)); ?>
                            </div>
                            <div class="avatar-status online">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                        <h4 class="profile-name"><?php echo htmlspecialchars($employee['name']); ?></h4>
                        <p class="profile-role">
                            <i class="fas fa-briefcase me-2"></i>
                            <?php echo htmlspecialchars($employee['department_name'] ?? 'No Department'); ?>
                        </p>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $total_attendance; ?></div>
                                <div class="stat-label">Total Days</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $month_attendance; ?></div>
                                <div class="stat-label">This Month</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info Card -->
                <div class="modern-card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i> Quick Info
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($employee['email']); ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Phone</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Address</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($employee['address'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-lg-8">
                <div class="modern-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-edit"></i> Edit Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="profile.php" method="POST" class="profile-form">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user me-2"></i>Full Name
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?php echo htmlspecialchars($employee['name']); ?>" required
                                        placeholder="Enter your full name">
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email Address
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($employee['email']); ?>" required
                                        placeholder="your.email@example.com">
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Phone Number
                                    </label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>"
                                        placeholder="+255 XXX XXX XXX">
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label">
                                        <i class="fas fa-building me-2"></i>Department
                                    </label>
                                    <select class="form-select" id="department" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"
                                            <?php echo ($employee['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="3"
                                    placeholder="Enter your full address"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <a href="change_password.php" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Settings Card -->
                <div class="modern-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-cog"></i> Account Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="settings-list">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h6><i class="fas fa-key me-2"></i>Password</h6>
                                    <p>Change your account password</p>
                                </div>
                                <a href="change_password.php" class="btn btn-sm btn-outline-primary">
                                    Change Password
                                </a>
                            </div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h6><i class="fas fa-calendar-check me-2"></i>Attendance History</h6>
                                    <p>View your attendance records</p>
                                </div>
                                <a href="current_month_attendance.php" class="btn btn-sm btn-outline-primary">
                                    View History
                                </a>
                            </div>
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h6><i class="fas fa-qrcode me-2"></i>Mark Attendance</h6>
                                    <p>Check in or check out</p>
                                </div>
                                <a href="mark_attendance.php" class="btn btn-sm btn-outline-primary">
                                    Mark Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Profile Card Styles */
.profile-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.profile-avatar {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.avatar-status {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    background: #22c55e;
    border-radius: 50%;
    border: 3px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-status.online {
    background: #22c55e;
    animation: pulse 2s infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.avatar-status i {
    font-size: 8px;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: white;
}

.profile-role {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
}

.stat-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
}

/* Info List */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: #f3f4f6;
    transform: translateX(5px);
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.9rem;
    color: #374151;
    font-weight: 600;
    word-break: break-word;
}

/* Profile Form */
.profile-form .form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.profile-form .form-control,
.profile-form .form-select {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 0.625rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.profile-form .form-control:focus,
.profile-form .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.form-actions .btn {
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Settings List */
.settings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.setting-item:hover {
    background: #f3f4f6;
    border-color: #667eea;
}

.setting-info h6 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.setting-info p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .avatar-circle {
        width: 100px;
        height: 100px;
        font-size: 2rem;
    }

    .profile-name {
        font-size: 1.25rem;
    }

    .stat-number {
        font-size: 1.5rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }

    .setting-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .setting-item .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .profile-stats {
        flex-direction: column;
        gap: 1rem;
    }

    .info-item {
        padding: 0.5rem;
    }

    .info-icon {
        width: 35px;
        height: 35px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>