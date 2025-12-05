<!-- Sidebar-->
<div class="border-end" id="sidebar-wrapper">
    <!-- Logo & Company Section -->
    <div class="sidebar-logo">
        <div class="logo-container">
            <img src="../assets/img/logo_org.jpg" alt="NEW KB BAR & SPORT LOUNGE" class="company-logo">
        </div>
        <div class="company-info">
            <h5 class="company-name">NEW KB BAR & SPORT LOUNGE</h5>
            <p class="system-name">Attendance Management System</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>"
            href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'departments') ? 'active' : ''; ?>"
            href="departments.php">
            <i class="fas fa-building me-2"></i>Departments
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'employees') ? 'active' : ''; ?>"
            href="employees.php">
            <i class="fas fa-users me-2"></i>Employees
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'rotation') ? 'active' : ''; ?>"
            href="rotation_management.php">
            <i class="fas fa-sync-alt me-2"></i>Rotation
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'leave') ? 'active' : ''; ?>"
            href="leave_management.php">
            <i class="fas fa-calendar-times me-2"></i>Leave
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'reports') ? 'active' : ''; ?>"
            href="reports.php">
            <i class="fas fa-file-alt me-2"></i>Reports
        </a>
        <a class="list-group-item list-group-item-action <?php echo ($current_page == 'profile') ? 'active' : ''; ?>"
            href="profile.php">
            <i class="fas fa-user-circle me-2"></i>Profile
        </a>

        <!-- Divider -->
        <div class="sidebar-divider"></div>

        <!-- System Links -->
        <a class="list-group-item list-group-item-action" href="#" onclick="window.print(); return false;">
            <i class="fas fa-print me-2"></i>Print
        </a>
        <a class="list-group-item list-group-item-action" href="../logout.php"
            onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>

    <!-- User Info at Bottom -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle fa-2x"></i>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"]); ?></p>
                <p class="user-role">Administrator</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Sidebar Styles */
#sidebar-wrapper {
    min-height: 100vh;
    width: 260px;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    transition: margin 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
    border-right: 2px solid #d4af37;
}

/* Logo Section */
.sidebar-logo {
    padding: 1.5rem 1rem;
    text-align: center;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
}

.logo-container {
    margin-bottom: 1rem;
}

.company-logo {
    max-width: 120px;
    height: auto;
    border-radius: 15px;
    border: 2px solid rgba(212, 175, 55, 0.5);
    padding: 8px;
    background: #000;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
    transition: all 0.3s ease;
}

.company-logo:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.5);
}

.logo-placeholder {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    background: rgba(212, 175, 55, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
}

.company-info {
    color: white;
}

.company-name {
    font-size: 1rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
    color: #d4af37;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    line-height: 1.3;
}

.system-name {
    font-size: 0.75rem;
    opacity: 0.9;
    margin-bottom: 0;
    color: rgba(255, 255, 255, 0.8);
}

/* Navigation Items */
.list-group-item {
    border: none;
    background: transparent;
    color: rgba(255, 255, 255, 0.9);
    padding: 1rem 1.5rem;
    transition: all 0.3s;
    font-size: 0.95rem;
}

.list-group-item:hover {
    background: rgba(212, 175, 55, 0.1);
    color: #d4af37;
    padding-left: 2rem;
    border-left: 3px solid #d4af37;
}

.list-group-item.active {
    background: rgba(212, 175, 55, 0.15);
    color: #d4af37;
    border-left: 4px solid #d4af37;
    font-weight: 600;
}

.list-group-item i {
    width: 20px;
    text-align: center;
    color: #d4af37;
}

/* Sidebar Divider */
.sidebar-divider {
    height: 1px;
    background: rgba(212, 175, 55, 0.2);
    margin: 0.5rem 1rem;
}

/* Sidebar Footer */
.sidebar-footer {
    margin-top: auto;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.3);
    border-top: 2px solid rgba(212, 175, 55, 0.3);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
}

.user-avatar {
    color: #d4af37;
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: white;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-bottom: 0;
    color: #d4af37;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    #sidebar-wrapper {
        margin-left: -260px;
        position: fixed;
        z-index: 1000;
        height: 100vh;
        overflow-y: auto;
    }

    #wrapper.toggled #sidebar-wrapper {
        margin-left: 0;
    }

    #page-content-wrapper {
        width: 100%;
    }

    /* Overlay when sidebar is open */
    #wrapper.toggled::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .company-logo {
        max-width: 90px;
    }

    .company-name {
        font-size: 0.9rem;
    }

    .system-name {
        font-size: 0.7rem;
    }
}

/* Custom Scrollbar for Sidebar */
#sidebar-wrapper::-webkit-scrollbar {
    width: 6px;
}

#sidebar-wrapper::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.3);
}

#sidebar-wrapper::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.5);
    border-radius: 3px;
}

#sidebar-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(212, 175, 55, 0.7);
}
</style>