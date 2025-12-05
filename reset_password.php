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
    <title>Reset Password - NEW KB BAR & SPORT LOUNGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
        min-height: 100vh;
        position: relative;
    }

    .login-background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
    }

    .login-background::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('assets/img/login1.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.08;
        animation: kenBurns 30s infinite alternate;
    }

    .login-background::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
    }

    @keyframes kenBurns {
        0% {
            transform: scale(1) translateX(0);
        }

        100% {
            transform: scale(1.1) translateX(-5%);
        }
    }

    .particles {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        width: 8px;
        height: 8px;
        background: rgba(212, 175, 55, 0.6);
        border-radius: 50%;
        animation: float 20s infinite;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    }

    .particle:nth-child(1) {
        left: 10%;
        animation-delay: 0s;
        animation-duration: 15s;
    }

    .particle:nth-child(2) {
        left: 20%;
        animation-delay: 2s;
        animation-duration: 18s;
    }

    .particle:nth-child(3) {
        left: 30%;
        animation-delay: 4s;
        animation-duration: 22s;
    }

    .particle:nth-child(4) {
        left: 40%;
        animation-delay: 6s;
        animation-duration: 20s;
    }

    .particle:nth-child(5) {
        left: 50%;
        animation-delay: 8s;
        animation-duration: 16s;
    }

    .particle:nth-child(6) {
        left: 60%;
        animation-delay: 10s;
        animation-duration: 19s;
    }

    .particle:nth-child(7) {
        left: 70%;
        animation-delay: 12s;
        animation-duration: 21s;
    }

    .particle:nth-child(8) {
        left: 80%;
        animation-delay: 14s;
        animation-duration: 17s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(100vh) scale(0);
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            transform: translateY(-100vh) scale(1);
        }
    }

    .login-container {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .glass-card {
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 2px solid rgba(212, 175, 55, 0.4);
        border-radius: 30px;
        padding: 2.5rem;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 60px rgba(212, 175, 55, 0.1);
        animation: slideUp 0.8s ease;
        margin: 1rem auto;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-logo {
        text-align: center;
        margin-bottom: 2rem;
    }

    .logo-icon {
        width: 120px;
        height: 120px;
        background: rgba(0, 0, 0, 0.5);
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
        border: 3px solid rgba(212, 175, 55, 0.5);
        box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
        animation: pulse 3s infinite;
        padding: 12px;
    }

    .logo-icon i {
        font-size: 4rem;
        color: #d4af37;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
            transform: scale(1);
        }

        50% {
            box-shadow: 0 0 50px rgba(212, 175, 55, 0.5);
            transform: scale(1.02);
        }
    }

    .login-title {
        color: #d4af37;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
        letter-spacing: 1px;
        line-height: 1.3;
    }

    .login-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.9rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        color: #d4af37;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    .form-control-glass {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 15px;
        padding: 0.875rem 1rem;
        color: white;
        font-size: 1rem;
        transition: all 0.3s;
        width: 100%;
    }

    .form-control-glass::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }

    .form-control-glass:focus {
        background: rgba(255, 255, 255, 0.1);
        border-color: #d4af37;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        outline: none;
        color: white;
    }

    .password-wrapper {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: rgba(212, 175, 55, 0.7);
        transition: all 0.3s;
        z-index: 10;
    }

    .password-toggle:hover {
        color: #d4af37;
    }

    .btn-glass {
        width: 100%;
        padding: 0.95rem;
        background: linear-gradient(135deg, #b8860b 0%, #d4af37 100%);
        border: 2px solid rgba(212, 175, 55, 0.5);
        border-radius: 15px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    }

    .btn-glass:hover {
        background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
        border-color: #d4af37;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
    }

    .btn-glass:active {
        transform: translateY(0);
    }

    .btn-glass.loading {
        position: relative;
        color: transparent;
    }

    .btn-glass.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .link-glass {
        color: #d4af37;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
    }

    .link-glass:hover {
        color: #b8860b;
        text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    }

    .alert-glass {
        background: rgba(220, 53, 69, 0.15);
        border: 1px solid rgba(220, 53, 69, 0.5);
        border-radius: 15px;
        padding: 0.875rem;
        margin-bottom: 1.25rem;
        color: #ff6b6b;
        backdrop-filter: blur(10px);
        animation: shake 0.5s;
        font-size: 0.9rem;
    }

    .alert-glass.alert-success {
        background: rgba(40, 167, 69, 0.15);
        border-color: rgba(40, 167, 69, 0.5);
        color: #51cf66;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-10px);
        }

        75% {
            transform: translateX(10px);
        }
    }

    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 1.25rem 0;
    }

    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid rgba(212, 175, 55, 0.3);
    }

    .divider span {
        padding: 0 1rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
    }

    .back-link {
        text-align: center;
        margin-top: 1.5rem;
    }

    .password-requirements {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 12px;
        padding: 0.875rem;
        margin-bottom: 1.25rem;
    }

    .password-requirements p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .password-requirements ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .password-requirements li {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.75rem;
        padding: 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .password-requirements li i {
        color: #d4af37;
        font-size: 0.7rem;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .glass-card {
            padding: 1.75rem 1.25rem;
            border-radius: 20px;
        }

        .logo-icon {
            width: 90px;
            height: 90px;
            padding: 10px;
        }

        .logo-icon i {
            font-size: 3rem;
        }

        .login-title {
            font-size: 1.15rem;
        }

        .login-subtitle {
            font-size: 0.8rem;
        }

        .password-toggle {
            right: 12px;
            font-size: 0.9rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="login-background">
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="login-container">
        <div class="glass-card">
            <!-- Logo -->
            <div class="login-logo">
                <div class="logo-icon">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h2 class="login-title">Reset Password</h2>
                <p class="login-subtitle">Create a new secure password</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
            <div class="alert-glass" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert-glass alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($error) && empty($success)): ?>
            <!-- Password Requirements -->
            <div class="password-requirements">
                <p><i class="fas fa-shield-alt me-1"></i> Password Requirements:</p>
                <ul>
                    <li><i class="fas fa-circle"></i> At least 8 characters long</li>
                    <li><i class="fas fa-circle"></i> Include uppercase and lowercase letters</li>
                    <li><i class="fas fa-circle"></i> Include at least one number</li>
                </ul>
            </div>

            <!-- Reset Password Form -->
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST"
                id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-key"></i>
                        New Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control-glass" id="new_password" name="new_password"
                            placeholder="Enter new password" required minlength="8">
                        <i class="fas fa-eye password-toggle" id="toggleNewPassword"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-key"></i>
                        Confirm New Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control-glass" id="confirm_password" name="confirm_password"
                            placeholder="Confirm new password" required minlength="8">
                        <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-glass" id="submitBtn">
                    <i class="fas fa-check-circle me-2"></i>
                    Reset Password
                </button>
            </form>
            <?php endif; ?>

            <div class="divider">
                <span>OR</span>
            </div>

            <div class="back-link">
                <a href="index.php" class="link-glass">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Password Toggle - New Password
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const newPasswordField = document.getElementById('new_password');

    if (toggleNewPassword) {
        toggleNewPassword.addEventListener('click', function() {
            const type = newPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            newPasswordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Password Toggle - Confirm Password
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordField = document.getElementById('confirm_password');

    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Form Submit Animation
    const resetForm = document.getElementById('resetForm');
    const submitBtn = document.getElementById('submitBtn');

    if (resetForm) {
        resetForm.addEventListener('submit', function() {
            submitBtn.classList.add('loading');
        });
    }

    // Password Match Validation
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                submitBtn.classList.remove('loading');
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-glass');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Add fadeOut animation
    const style = document.createElement('style');
    style.textContent = `
            @keyframes fadeOut {
                to { opacity: 0; transform: translateY(-20px); }
            }
        `;
    document.head.appendChild(style);
    </script>
</body>

</html>