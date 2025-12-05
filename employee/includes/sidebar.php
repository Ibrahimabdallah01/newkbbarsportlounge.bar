<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Company Logo & Info -->
    <div class="sidebar-header">
        <div class="company-logo">
            <?php if (file_exists($company_logo)): ?>
            <img src="<?php echo $company_logo; ?>" alt="<?php echo $company_name; ?>">
            <?php else: ?>
            <div class="logo-placeholder">
                <i class="fas fa-user-tie"></i>
            </div>
            <?php endif; ?>
        </div>
        <h5 class="company-name"><?php echo $company_name; ?></h5>
        <p class="system-name"><?php echo $system_name; ?></p>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-menu">
        <a class="menu-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a class="menu-item <?php echo ($current_page == 'mark_attendance') ? 'active' : ''; ?>"
            href="mark_attendance.php">
            <i class="fas fa-qrcode"></i>
            <span>Mark Attendance</span>
        </a>

        <a class="menu-item <?php echo ($current_page == 'attendance_history') ? 'active' : ''; ?>"
            href="current_month_attendance.php">
            <i class="fas fa-calendar-alt"></i>
            <span>My Attendance</span>
        </a>

        <a class="menu-item <?php echo ($current_page == 'schedule') ? 'active' : ''; ?>" href="my_schedule.php">
            <i class="fas fa-calendar-week"></i>
            <span>My Schedule</span>
        </a>

        <a class="menu-item <?php echo ($current_page == 'leave') ? 'active' : ''; ?>" href="leave_request.php">
            <i class="fas fa-umbrella-beach"></i>
            <span>Leave Request</span>
        </a>

        <a class="menu-item <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" href="profile.php">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>

        <div class="menu-divider"></div>

        <a class="menu-item" href="#" onclick="window.print(); return false;">
            <i class="fas fa-print"></i>
            <span>Print</span>
        </a>

        <a class="menu-item" href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- User Info Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p class="user-role">Employee</p>
            </div>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* KB Bar Gold & Black Sidebar - ADD THIS CSS */
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    border-right: 2px solid #d4af37;
}

.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.3);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.5);
    border-radius: 10px;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    text-align: center;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
    background: rgba(0, 0, 0, 0.3);
}

.company-logo {
    margin-bottom: 1rem;
}

.company-logo img {
    width: 80px;
    height: 80px;
    border-radius: 15px;
    object-fit: contain;
    border: 2px solid #d4af37;
    padding: 5px;
    background: #000;
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
}

.logo-placeholder {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #d4af37;
    border: 3px solid #d4af37;
}

.company-name {
    color: #d4af37;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    line-height: 1.3;
}

.system-name {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.75rem;
    margin: 0;
}

.sidebar-menu {
    flex: 1;
    padding: 1rem 0;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.menu-item:hover {
    background: rgba(212, 175, 55, 0.1);
    color: #d4af37;
    padding-left: 2rem;
    border-left: 3px solid #d4af37;
}

.menu-item.active {
    background: rgba(212, 175, 55, 0.15);
    color: #d4af37;
    border-left: 4px solid #d4af37;
    font-weight: 600;
}

.menu-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
    color: #d4af37;
}

.menu-item span {
    font-size: 0.95rem;
}

.menu-divider {
    height: 1px;
    background: rgba(212, 175, 55, 0.2);
    margin: 1rem 1.5rem;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 2px solid rgba(212, 175, 55, 0.3);
    margin-top: auto;
    background: rgba(0, 0, 0, 0.3);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    color: #d4af37;
    font-size: 0.75rem;
    margin: 0;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

@media (max-width: 768px) {
    .sidebar {
        left: -260px;
    }

    .sidebar.active {
        left: 0;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }
}
</style>