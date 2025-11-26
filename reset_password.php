<?php
session_start();
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth.php";

$error = "";
$success = "";
$token = $_GET["token"] ?? "";

if (empty($token)) {
    $error = "Invalid or missing token.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $new_password = sanitize_input($_POST["new_password"]);
    $confirm_password = sanitize_input($_POST["confirm_password"]);
    $token_from_form = sanitize_input($_POST["token"]);

    if ($token_from_form !== $token) {
        $error = "Token mismatch.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check token in admins table
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user_id = $stmt->fetchColumn();
        $user_role = "admin";

        if (!$user_id) {
            // Check token in employees table
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user_id = $stmt->fetchColumn();
            $user_role = "employee";
        }

        if ($user_id) {
            $hashed_password = hash_password($new_password);
            if ($user_role === "admin") {
                $stmt = $pdo->prepare("UPDATE admins SET password = ?, remember_token = NULL WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE employees SET password = ?, remember_token = NULL WHERE id = ?");
            }
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = "Your password has been reset successfully. You can now login.";
            } else {
                $error = "Error resetting password.";
            }
        } else {
            $error = "Invalid or expired token.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-sm" style="width: 400px;">
            <h3 class="card-title text-center mb-4">Reset Password</h3>
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
            <?php if (empty($error) || $success): // Only show form if token is valid or password was reset ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>
            <p class="text-center mt-3"><a href="index.php">Back to Login</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


