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
                    <!-- Notifications -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger badge-sm">3</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                            <h6 class="dropdown-header">Notifications</h6>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user-check text-success me-2"></i>
                                New employee registered
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-clock text-warning me-2"></i>
                                Late check-in detected
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-calendar text-info me-2"></i>
                                Monthly report ready
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="#">View all notifications</a>
                        </div>
                    </li>

                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i>Settings
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
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="page-title">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview
                    </h1>
                    <p class="text-muted mb-0">Welcome back,
                        <?php echo htmlspecialchars($_SESSION["user_name"]); ?>! Here's what's happening today.</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-primary p-2">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('l, F d, Y'); ?>
                    </span>
                    <span class="badge bg-success p-2 ms-2">
                        <i class="fas fa-clock me-1"></i>
                        <span id="currentTime"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Total Employees -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $employee_count; ?></h3>
                        <p class="stat-label">Total Employees</p>
                        <a href="employees.php" class="stat-link">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Total Departments -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $department_count; ?></h3>
                        <p class="stat-label">Departments</p>
                        <a href="departments.php" class="stat-link">
                            Manage <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Today's Check-ins -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $today_checkins_count; ?></h3>
                        <p class="stat-label">Today's Check-ins</p>
                        <?php 
                            $percentage = $yesterday_checkins_count > 0 
                                ? round((($today_checkins_count - $yesterday_checkins_count) / $yesterday_checkins_count) * 100) 
                                : 0;
                            ?>
                        <span class="stat-trend <?php echo $percentage >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <i class="fas fa-<?php echo $percentage >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs($percentage); ?>% from yesterday
                        </span>
                    </div>
                </div>
            </div>

            <!-- This Week -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $week_checkins_count; ?></h3>
                        <p class="stat-label">This Week</p>
                        <a href="reports.php" class="stat-link">
                            View Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Attendance Trend Chart -->
            <div class="col-12 col-lg-8">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-area me-2"></i>Attendance Trend (Last 7 Days)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Department Distribution -->
            <div class="col-12 col-lg-4">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Today by Department
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Quick Actions -->
        <div class="row g-3 g-md-4">
            <!-- Recent Attendances -->
            <div class="col-12 col-lg-8">
                <div class="card activity-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Check-ins
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Check-in Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_attendances as $att): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-user-circle me-2 text-primary"></i>
                                            <strong><?php echo htmlspecialchars($att['employee_name']); ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($att['check_in'])); ?>
                                        </td>
                                        <td>
                                            <?php
                                                $status = $att['status'];
                                                $badge_class = 'bg-secondary';
                                                if ($status == 'present') $badge_class = 'bg-success';
                                                elseif ($status == 'late') $badge_class = 'bg-warning';
                                                elseif ($status == 'absent') $badge_class = 'bg-danger';
                                                ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="reports.php" class="btn btn-outline-primary btn-sm">
                                View All Attendances <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-12 col-lg-4">
                <div class="card quick-actions-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="employees.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Add New Employee
                            </a>
                            <a href="departments.php" class="btn btn-warning text-white">
                                <i class="fas fa-building me-2"></i>Add Department
                            </a>
                            <a href="reports.php" class="btn btn-success">
                                <i class="fas fa-file-export me-2"></i>Generate Report
                            </a>
                            <a href="#" onclick="window.print(); return false;" class="btn btn-info">
                                <i class="fas fa-print me-2"></i>Print Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Info Card -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>System Info
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled system-info">
                            <li>
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <strong>Month:</strong> <?php echo $month_checkins_count; ?> check-ins
                            </li>
                            <li>
                                <i class="fas fa-server text-success me-2"></i>
                                <strong>Status:</strong> <span class="text-success">Online</span>
                            </li>
                            <li>
                                <i class="fas fa-code-branch text-info me-2"></i>
                                <strong>Version:</strong> 1.0.0
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>