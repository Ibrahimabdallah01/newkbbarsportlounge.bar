<?php
// Employee Configuration
$company_name = "Apizu Attendance";
$company_logo = "../assets/img/logo.png";
$system_name = "Employee Portal";

// Get page title from including page
if (!isset($page_title)) {
    $page_title = "Dashboard";
}

// Get current page from including page
if (!isset($current_page)) {
    $current_page = "dashboard";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Employee attendance management system">
    <meta name="author" content="<?php echo $company_name; ?>">

    <title><?php echo $page_title . " - " . $system_name; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS (optional) -->
    <?php if (isset($use_datatables) && $use_datatables): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/employee-style.css">

    <style>
    /* Loading Overlay */
    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s, visibility 0.3s;
    }

    #loading-overlay.hidden {
        opacity: 0;
        visibility: hidden;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Fade In Animation */
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
    // Hide loading overlay when page is loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loading-overlay').classList.add('hidden');
            document.body.classList.add('fade-in');
        }, 300);
    });
    </script>