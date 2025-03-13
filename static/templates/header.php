<?php
require_once __DIR__ . '/../../helpers/functions.php';
check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERC POS System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ERC-POS/assets/css/style.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <!-- Fix for Bootstrap Dropdowns -->
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            // Ensure Bootstrap is properly loaded
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap is not loaded properly!');
                // Try to load it again
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js';
                document.head.appendChild(script);
            } else {
                console.log('Bootstrap loaded successfully:', bootstrap.Collapse.VERSION);
            }
            
            // Initialize all dropdowns and collapses
            setTimeout(function() {
                document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(element) {
                    new bootstrap.Collapse(element, {
                        toggle: false
                    });
                });
            }, 500);
        });
    </script>
</head>
<body>
    <!-- Debug output -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Bootstrap version:', bootstrap.Collapse.VERSION);
        console.log('jQuery version:', jQuery.fn.jquery);
    });
    </script>
    <!-- Top Header Bar -->
    <header class="navbar navbar-light fixed-top header-bar">
        <div class="d-flex justify-content-end align-items-center w-100 header-content">
            <!-- Right Side - Date, Time, and User -->
            <div class="d-flex align-items-center gap-4">
                <!-- Date and Time -->
                <div class="d-flex align-items-center datetime-container">
                    <div class="date-box me-4">
                        <i class="far fa-calendar-alt me-2"></i>
                        <span id="currentDate" class="fw-bold"></span>
                    </div>
                    <div class="time-box">
                        <i class="far fa-clock me-2"></i>
                        <span id="currentTime" class="fw-bold"></span>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="dropdown user-profile">
                    <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </div>
                        <span class="fw-bold"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/ERC-POS/views/profile/index.php">
                            <i class="fas fa-user me-2"></i>Profile
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/ERC-POS/handlers/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-2">
                <!-- Content will be injected here -->

<style>
/* Header styles */
.header-bar {
    background: #ffffff;
    height: 60px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 0 1.5rem;
    width: 100%;
    z-index: 1030; /* Lower than sidebar */
    border-bottom: 3px solid var(--primary-color);
}

.header-content {
    padding: 0 2rem;
    margin-right: 1rem;
}

/* Date and Time styles */
.datetime-container {
    font-size: 0.95rem;
    color: #2c3e50;
    margin-right: 1rem;
}

.date-box, .time-box {
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border-left: 3px solid var(--primary-color);
}

.date-box:hover, .time-box:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.date-box i, .time-box i {
    color: var(--primary-color);
    font-size: 1.1rem;
}

/* User Profile styles */
.user-profile {
    position: relative;
}

.user-profile .dropdown-toggle {
    padding: 0.5rem 1rem;
    color: #2c3e50;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border-left: 3px solid var(--accent-color);
    font-weight: 600;
}

.user-profile .dropdown-toggle:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.user-avatar i {
    color: var(--primary-color);
    font-size: 1.2rem;
}

.dropdown-menu {
    border: none;
    border-radius: 10px;
    margin-top: 10px;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    z-index: 1031; /* Higher than header but lower than sidebar */
    overflow: hidden;
}

.dropdown-item {
    padding: 0.7rem 1.2rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

.dropdown-item:hover {
    background: var(--secondary-color);
    transform: translateX(5px);
    color: #333;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    margin-right: 8px;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

/* Adjust main content */
main {
    padding-top: 65px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .datetime-container {
        display: none;
    }
    
    .header-content {
        padding: 0 1rem;
    }
}
</style>

<script>
// Function to update date and time
function updateDateTime() {
    const now = new Date();
    
    // Update date
    const dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', dateOptions);
    
    // Update time
    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: true };
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', timeOptions);
}

// Update immediately and then every second
updateDateTime();
setInterval(updateDateTime, 1000);
</script> 