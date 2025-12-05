<?php
session_start();
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth.php";

if (is_logged_in()) {
    if (is_admin()) {
        redirect("admin/dashboard.php");
    } else if (is_employee()) {
        redirect("employee/dashboard.php");
    }
}

$error = "";
$success = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = sanitize_input($_POST["email"]);
    $password = sanitize_input($_POST["password"]);
    
    if (authenticate_admin($email, $password)) {
        redirect("admin/dashboard.php");
    } else if (authenticate_employee($email, $password)) {
        redirect("employee/dashboard.php");
    } else {
        $error = "Invalid email or password.";
    }
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = sanitize_input($_POST["reg_name"]);
    $email = sanitize_input($_POST["reg_email"]);
    $password = sanitize_input($_POST["reg_password"]);
    $confirm_password = sanitize_input($_POST["reg_confirm_password"]);
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already registered.";
        } else {
            // Register new admin
            $hashed_password = hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NEW KB BAR & SPORT LOUNGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* ============================================
   Responsive Login CSS - All Devices
   ============================================ */

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
        background-image: url('../img/login1.jpg');
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

    .logo-container {
        margin-bottom: 1rem;
        position: relative;
    }

    .logo-icon {
        width: 140px;
        height: 140px;
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

    .logo-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 15px;
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

    .form-check-input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .form-check-input:checked {
        background: #d4af37;
        border-color: #d4af37;
    }

    .form-check-label {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.85rem;
    }

    .link-glass {
        color: #d4af37;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .link-glass:hover {
        color: #b8860b;
        text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
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

    .register-link {
        text-align: center;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
    }

    .register-link a {
        color: #d4af37;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .register-link a:hover {
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

    .modal-glass {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s;
        padding: 1rem;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .modal-glass.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content-glass {
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(20px);
        border: 2px solid rgba(212, 175, 55, 0.4);
        border-radius: 30px;
        padding: 2rem;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 60px rgba(212, 175, 55, 0.1);
        animation: modalSlideUp 0.4s ease;
        position: relative;
        margin: auto;
    }

    @keyframes modalSlideUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header-glass {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .modal-title-glass {
        color: #d4af37;
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .modal-subtitle-glass {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
    }

    .modal-close {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        background: rgba(212, 175, 55, 0.2);
        border: none;
        color: #d4af37;
        font-size: 1.25rem;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        background: rgba(212, 175, 55, 0.3);
        transform: rotate(90deg);
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

    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }

    .mb-0 {
        margin-bottom: 0;
    }

    /* ============================================
   RESPONSIVE BREAKPOINTS
   ============================================ */

    /* Extra Small Devices (Phones, 320px to 480px) */
    @media (max-width: 480px) {
        .login-container {
            padding: 0.5rem;
        }

        .glass-card {
            padding: 1.75rem 1.25rem;
            border-radius: 20px;
        }

        .logo-icon {
            width: 100px;
            height: 100px;
            padding: 10px;
        }

        .login-title {
            font-size: 1.15rem;
            letter-spacing: 0.5px;
        }

        .login-subtitle {
            font-size: 0.8rem;
        }

        .form-label {
            font-size: 0.85rem;
        }

        .form-control-glass {
            padding: 0.75rem 0.875rem;
            font-size: 0.95rem;
        }

        .password-toggle {
            right: 12px;
            font-size: 0.9rem;
        }

        .btn-glass {
            padding: 0.85rem;
            font-size: 0.95rem;
        }

        .form-check-label,
        .link-glass {
            font-size: 0.8rem;
        }

        .divider span {
            font-size: 0.8rem;
            padding: 0 0.5rem;
        }

        .register-link {
            font-size: 0.85rem;
        }

        .alert-glass {
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        /* Modal Adjustments */
        .modal-glass {
            padding: 0.5rem;
        }

        .modal-content-glass {
            padding: 1.5rem 1rem;
            border-radius: 20px;
        }

        .modal-title-glass {
            font-size: 1.25rem;
        }

        .modal-subtitle-glass {
            font-size: 0.85rem;
        }

        .modal-close {
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            font-size: 1.1rem;
        }

        /* Adjust flex layout for small screens */
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .d-flex.justify-content-between .form-check {
            order: 2;
        }

        .d-flex.justify-content-between .link-glass {
            order: 1;
            align-self: flex-end;
        }
    }

    /* Small Devices (Small Tablets, 481px to 767px) */
    @media (min-width: 481px) and (max-width: 767px) {
        .login-container {
            padding: 1rem;
        }

        .glass-card {
            padding: 2rem 1.5rem;
            max-width: 420px;
        }

        .logo-icon {
            width: 120px;
            height: 120px;
        }

        .login-title {
            font-size: 1.35rem;
        }

        .login-subtitle {
            font-size: 0.85rem;
        }

        .modal-content-glass {
            padding: 1.75rem 1.5rem;
            max-width: 440px;
        }

        .modal-title-glass {
            font-size: 1.4rem;
        }
    }

    /* Medium Devices (Tablets, 768px to 991px) */
    @media (min-width: 768px) and (max-width: 991px) {
        .glass-card {
            max-width: 460px;
            padding: 2.5rem 2rem;
        }

        .logo-icon {
            width: 130px;
            height: 130px;
        }

        .login-title {
            font-size: 1.45rem;
        }

        .modal-content-glass {
            max-width: 480px;
            padding: 2rem 1.75rem;
        }
    }

    /* Large Devices (Desktops, 992px and up) */
    @media (min-width: 992px) {
        .glass-card {
            padding: 3rem 2.5rem;
        }

        .login-title {
            font-size: 1.65rem;
        }

        .modal-content-glass {
            padding: 2.5rem 2rem;
        }

        .modal-title-glass {
            font-size: 1.65rem;
        }
    }

    /* Extra Large Devices (Large Desktops, 1200px and up) */
    @media (min-width: 1200px) {
        .glass-card {
            padding: 3rem;
        }

        .logo-icon {
            width: 150px;
            height: 150px;
        }

        .login-title {
            font-size: 1.75rem;
        }
    }

    /* Landscape Orientation for Mobile */
    @media (max-height: 600px) and (orientation: landscape) {
        .login-container {
            padding: 0.5rem;
            align-items: flex-start;
        }

        .glass-card {
            margin: 0.5rem auto;
            padding: 1.5rem 1.25rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 0.5rem;
        }

        .login-logo {
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.1rem;
        }

        .login-subtitle {
            font-size: 0.75rem;
        }

        .form-group {
            margin-bottom: 0.875rem;
        }

        .modal-content-glass {
            padding: 1.25rem 1rem;
            margin: 0.5rem auto;
        }

        .modal-header-glass {
            margin-bottom: 1rem;
        }
    }

    /* Accessibility - Reduce Motion */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* High DPI Displays */
    @media (-webkit-min-device-pixel-ratio: 2),
    (min-resolution: 192dpi) {
        .logo-icon img {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
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

    <!-- Main Login Container -->
    <div class="login-container">
        <div class="glass-card">
            <!-- Logo -->
            <div class="login-logo">
                <div class="logo-container">
                    <div class="logo-icon">
                        <img src="assets/img/logo_org.jpg" alt="KB Bar Logo">
                    </div>
                </div>
                <h2 class="login-title">NEW KB BAR & SPORT LOUNGE</h2>
                <p class="login-subtitle">Employee Attendance Management System</p>
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

            <!-- Login Form -->
            <form action="index.php" method="POST" id="loginForm">
                <input type="hidden" name="login" value="1">

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control-glass" id="email" name="email"
                        placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control-glass" id="password" name="password"
                            placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="link-glass">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-glass" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In
                </button>
            </form>

            <div class="divider">
                <span>OR</span>
            </div>

            <div class="register-link">
                <p class="mb-0">Don't have an account? <a id="registerBtn">Register Now</a></p>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="registerModal" class="modal-glass">
        <div class="modal-content-glass">
            <button class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>

            <div class="modal-header-glass">
                <h3 class="modal-title-glass">Create Account</h3>
                <p class="modal-subtitle-glass">Register as a new administrator</p>
            </div>

            <form action="index.php" method="POST" id="registerForm">
                <input type="hidden" name="register" value="1">

                <div class="form-group">
                    <label for="reg_name" class="form-label">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input type="text" class="form-control-glass" id="reg_name" name="reg_name"
                        placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="reg_email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control-glass" id="reg_email" name="reg_email"
                        placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="reg_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control-glass" id="reg_password" name="reg_password"
                            placeholder="Create a password" required>
                        <i class="fas fa-eye password-toggle" id="toggleRegPassword"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg_confirm_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control-glass" id="reg_confirm_password"
                            name="reg_confirm_password" placeholder="Confirm your password" required>
                        <i class="fas fa-eye password-toggle" id="toggleRegConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="btn-glass" id="registerSubmitBtn">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                </button>
            </form>

            <div class="divider">
                <span>OR</span>
            </div>

            <div class="register-link">
                <p class="mb-0">Already have an account? <a id="backToLogin">Sign In</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Password Toggle - Login
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Password Toggle - Register
    const toggleRegPassword = document.getElementById('toggleRegPassword');
    const regPasswordField = document.getElementById('reg_password');

    if (toggleRegPassword) {
        toggleRegPassword.addEventListener('click', function() {
            const type = regPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            regPasswordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Password Toggle - Register Confirm
    const toggleRegConfirmPassword = document.getElementById('toggleRegConfirmPassword');
    const regConfirmPasswordField = document.getElementById('reg_confirm_password');

    if (toggleRegConfirmPassword) {
        toggleRegConfirmPassword.addEventListener('click', function() {
            const type = regConfirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            regConfirmPasswordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Modal Controls
    const registerBtn = document.getElementById('registerBtn');
    const registerModal = document.getElementById('registerModal');
    const closeModal = document.getElementById('closeModal');
    const backToLogin = document.getElementById('backToLogin');

    registerBtn.addEventListener('click', function(e) {
        e.preventDefault();
        registerModal.classList.add('show');
    });

    closeModal.addEventListener('click', function() {
        registerModal.classList.remove('show');
    });

    backToLogin.addEventListener('click', function(e) {
        e.preventDefault();
        registerModal.classList.remove('show');
    });

    // Close modal when clicking outside
    registerModal.addEventListener('click', function(e) {
        if (e.target === registerModal) {
            registerModal.classList.remove('show');
        }
    });

    // Form Submit Animation - Login
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    loginForm.addEventListener('submit', function() {
        loginBtn.classList.add('loading');
    });

    // Form Submit Animation - Register
    const registerForm = document.getElementById('registerForm');
    const registerSubmitBtn = document.getElementById('registerSubmitBtn');

    registerForm.addEventListener('submit', function() {
        registerSubmitBtn.classList.add('loading');
    });

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

    // Open register modal if there's an error from registration
    <?php if ($error && isset($_POST['register'])): ?>
    window.addEventListener('DOMContentLoaded', function() {
        registerModal.classList.add('show');
    });
    <?php endif; ?>
    </script>
</body>

</html>