<?php
session_start();
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/includes/functions.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST["email"]);

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email exists in admins or employees table
        $user_found = false;
        $user_role = "";
        $user_id = 0;

        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $user_found = true;
            $user_role = "admin";
            $user_id = $stmt->fetchColumn();
        }

        if (!$user_found) {
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $user_found = true;
                $user_role = "employee";
                $user_id = $stmt->fetchColumn();
            }
        }

        if ($user_found) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Store token in a temporary table or add remember_token column to users table
            // For simplicity, we'll update the remember_token in the respective table
            if ($user_role === "admin") {
                $stmt = $pdo->prepare("UPDATE admins SET remember_token = ? WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE employees SET remember_token = ? WHERE id = ?");
            }
            $stmt->execute([$token, $user_id]);

            // In a real application, you would send an email with a link like:
            // $reset_link = "http://yourdomain.com/reset_password.php?token=" . $token;
            // mail($email, "Password Reset Link", "Click here to reset your password: " . $reset_link);
            $success = "A password reset link has been sent to your email address (simulated). Token: " . $token;
        } else {
            $error = "No account found with that email address.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-sm" style="width: 400px;">
            <h3 class="card-title text-center mb-4">Forgot Password</h3>
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
            <form action="forgot_password.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <p class="text-center mt-3"><a href="index.php">Back to Login</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


