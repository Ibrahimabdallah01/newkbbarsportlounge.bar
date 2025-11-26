<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_employee()) {
    redirect("../index.php");
}

$employee_id = $_SESSION["user_id"];
$error = "";
$success = "";

// Check if employee has already checked in today
$today = date("Y-m-d");
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND DATE(check_in) = ?");
$stmt->execute([$employee_id, $today]);
$current_attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle check-in/check-out
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["check_in"])) {
        if (!$current_attendance) {
            $stmt = $pdo->prepare("INSERT INTO attendances (employee_id, check_in, status) VALUES (?, ?, ?)");
            if ($stmt->execute([$employee_id, date("Y-m-d H:i:s"), "Present"])) {
                $success = "Checked in successfully!";
                $current_attendance = ["check_in" => date("Y-m-d H:i:s"), "check_out" => null]; // Update for display
            } else {
                $error = "Error during check-in.";
            }
        } else {
            $error = "You have already checked in today.";
        }
    } elseif (isset($_POST["check_out"])) {
        if ($current_attendance && empty($current_attendance["check_out"])) {
            $stmt = $pdo->prepare("UPDATE attendances SET check_out = ? WHERE id = ?");
            if ($stmt->execute([date("Y-m-d H:i:s"), $current_attendance["id"]])) {
                $success = "Checked out successfully!";
                $current_attendance["check_out"] = date("Y-m-d H:i:s"); // Update for display
            } else {
                $error = "Error during check-out.";
            }
        } else {
            $error = "You have not checked in yet or already checked out.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar-->
        <div class="border-end bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom bg-light">Employee Panel</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="dashboard.php">Dashboard</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="profile.php">Profile</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="mark_attendance.php">Mark Attendance</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="current_month_attendance.php">Current Month Attendance</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="reports.php">Reports</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="change_password.php">Change Password</a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3" href="../logout.php">Logout</a>
            </div>
        </div>
        <!-- Page content wrapper-->
        <div id="page-content-wrapper">
            <!-- Top navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">Toggle Menu</button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php echo $_SESSION["user_name"]; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="profile.php">Profile</a>
                                    <a class="dropdown-item" href="change_password.php">Change Password</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="../logout.php">Logout</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Page content-->
            <div class="container-fluid">
                <h1 class="mt-4">Mark Attendance</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Today's Attendance
                    </div>
                    <div class="card-body">
                        <p><strong>Date:</strong> <?php echo date("Y-m-d"); ?></p>
                        <?php if ($current_attendance): ?>
                            <p><strong>Check-in Time:</strong> <?php echo $current_attendance["check_in"]; ?></p>
                            <?php if ($current_attendance["check_out"]): ?>
                                <p><strong>Check-out Time:</strong> <?php echo $current_attendance["check_out"]; ?></p>
                                <p class="text-success">You have completed your attendance for today.</p>
                            <?php else: ?>
                                <p class="text-info">You are currently checked in.</p>
                                <form action="mark_attendance.php" method="POST">
                                    <button type="submit" name="check_out" class="btn btn-danger">Check Out</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-warning">You have not checked in yet today.</p>
                            <form action="mark_attendance.php" method="POST">
                                <button type="submit" name="check_in" class="btn btn-success">Check In</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
</body>
</html>


