<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-custom">
    <div class="container-fluid">
        <!-- Menu Toggle Button -->
        <button class="btn btn-link" id="sidebarToggle">
            <i class="fas fa-bars text-gold"></i>
        </button>

        <!-- Page Title -->
        <span class="navbar-brand mb-0 h1 ms-3">
            <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
        </span>

        <!-- Right Side Menu -->
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Notifications -->
            <li class="nav-item dropdown me-3">
                <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell text-gold fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3
                        <span class="visually-hidden">unread notifications</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown"
                    aria-labelledby="notificationDropdown">
                    <li class="dropdown-header">Notifications</li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-user-check text-success me-2"></i>
                            New employee registered
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-clock text-warning me-2"></i>
                            Late arrival detected
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fas fa-calendar-times text-info me-2"></i>
                            Leave request pending
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-center text-gold" href="#">View all notifications</a></li>
                </ul>
            </li>

            <!-- User Profile -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar me-2">
                        <i class="fas fa-user-circle text-gold fs-4"></i>
                    </div>
                    <span class="d-none d-md-inline text-white">
                        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li class="dropdown-header">
                        <div class="text-center">
                            <i class="fas fa-user-circle text-gold fs-3"></i>
                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($_SESSION["user_name"]); ?></p>
                            <small class="text-muted">Administrator</small>
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="../logout.php"
                            onclick="return confirm('Are you sure you want to logout?');">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<style>
/* Navbar Styles */
.bg-dark-custom {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
    border-bottom: 2px solid #d4af37;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.text-gold {
    color: #d4af37 !important;
}

.navbar-brand {
    color: #d4af37 !important;
    font-weight: 700;
    font-size: 1.3rem;
}

#sidebarToggle {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    padding: 0.5rem;
    transition: all 0.3s;
}

#sidebarToggle:hover {
    transform: scale(1.1);
}

.nav-link {
    color: rgba(255, 255, 255, 0.8) !important;
    transition: all 0.3s;
}

.nav-link:hover {
    color: #d4af37 !important;
}

/* Notification Dropdown */
.notification-dropdown {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-dropdown .dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
}

.notification-dropdown .dropdown-item:hover {
    background-color: rgba(212, 175, 55, 0.1);
}

/* User Dropdown */
.dropdown-menu {
    border: 1px solid rgba(212, 175, 55, 0.3);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.dropdown-header {
    font-weight: 700;
    color: #d4af37;
}

.dropdown-item {
    transition: all 0.3s;
}

.dropdown-item:hover {
    background-color: rgba(212, 175, 55, 0.1);
    color: #d4af37;
}

/* Responsive */
@media (max-width: 768px) {
    .navbar-brand {
        font-size: 1rem;
    }
}
</style>