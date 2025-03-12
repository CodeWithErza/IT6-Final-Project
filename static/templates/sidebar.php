<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Debug output
echo "<!-- Debug: current_page = " . htmlspecialchars($current_page) . " -->\n";
echo "<!-- Debug: current_dir = " . htmlspecialchars($current_dir) . " -->\n";
?>

<nav id="sidebar" class="col-md-2 col-lg-2 d-md-block sidebar">
    <!-- Logo Section -->
    <div class="sidebar-logo text-center py-2">
        <img src="/ERC-POS/assets/images/erc logo.png" alt="ERC Logo" class="img-fluid mb-1" style="max-width: 120px; height: auto;">
        <h5 class="text-white">ERC POS</h5> 
    </div>

    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page === 'index' && $current_dir === 'htdocs') || $current_dir === 'sales_order' ? 'active' : ''; ?>" 
                   href="/ERC-POS/index.php">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Sales Order
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'index' && $current_dir === 'dashboard' ? 'active' : ''; ?>" 
                   href="/ERC-POS/views/dashboard/index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'menu' ? 'active' : ''; ?>" 
                   href="/ERC-POS/views/menu/index.php">
                    <i class="fas fa-utensils me-2"></i>
                    Menu Items
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'orders' ? 'active' : ''; ?>" 
                   href="/ERC-POS/views/orders/index.php">
                    <i class="fas fa-receipt me-2"></i>
                    Order History
                </a>
            </li>
            <li class="nav-item">
                <button class="nav-link w-100 text-start border-0 <?php echo $current_dir === 'inventory' ? 'active' : ''; ?>" 
                   type="button"
                   data-bs-toggle="collapse" 
                   data-bs-target="#inventorySubmenu"
                   aria-expanded="<?php echo $current_dir === 'inventory' ? 'true' : 'false'; ?>">
                    <i class="fas fa-boxes me-2"></i>
                    Inventory
                    <i class="fas fa-chevron-down float-end"></i>
                </button>
                <div class="collapse <?php echo $current_dir === 'inventory' ? 'show' : ''; ?>" id="inventorySubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'index' && $current_dir === 'inventory' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/inventory/index.php">
                                <i class="fas fa-box me-2"></i>
                                Stock Levels
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'stock_in' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/inventory/stock_in.php">
                                <i class="fas fa-arrow-circle-down me-2"></i>
                                Stock In
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'stock_adjustment' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/inventory/stock_adjustment.php">
                                <i class="fas fa-balance-scale me-2"></i>
                                Stock Adjustment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'history' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/inventory/history.php">
                                <i class="fas fa-history me-2"></i>
                                All Transactions
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'expenses' ? 'active' : ''; ?>" 
                   href="/ERC-POS/views/expenses/index.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Expenses
                </a>
            </li>
            <li class="nav-item">
                <button class="nav-link w-100 text-start border-0 <?php echo $current_dir === 'reports' ? 'active' : ''; ?>" 
                   type="button"
                   data-bs-toggle="collapse" 
                   data-bs-target="#reportsSubmenu"
                   aria-expanded="<?php echo $current_dir === 'reports' ? 'true' : 'false'; ?>">
                    <i class="fas fa-chart-bar me-2"></i>
                    Reports
                    <i class="fas fa-chevron-down float-end"></i>
                </button>
                <div class="collapse <?php echo $current_dir === 'reports' ? 'show' : ''; ?>" id="reportsSubmenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'sales' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/reports/sales.php">
                                <i class="fas fa-chart-line me-2"></i>
                                Sales Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'expenses' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/reports/expenses.php">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                Expenses Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'summary' ? 'active' : ''; ?>" 
                               href="/ERC-POS/views/reports/summary.php">
                                <i class="fas fa-chart-pie me-2"></i>
                                Summary Report
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'users' ? 'active' : ''; ?>" href="/ERC-POS/views/users/index.php">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_dir === 'settings' ? 'active' : ''; ?>" href="/ERC-POS/views/settings/index.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-3">
                <a href="/ERC-POS/handlers/auth/logout.php" class="nav-link text-white">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 220px;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    z-index: 1040; /* Higher than header */
    padding-top: 0;
}

.sidebar-logo {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-logo img {
    max-width: 120px;
    height: auto;
    transition: transform 0.3s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.sidebar-logo img:hover {
    transform: scale(1.05);
}

.sidebar-logo h5 {
    font-size: 1rem;
    margin-top: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.nav-link {
    color: rgba(255, 255, 255, 0.85);
    padding: 0.7rem 1rem;
    transition: all 0.3s ease;
    background: transparent;
}

.nav-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
}

.nav-link.active {
    color: #fff;
    background: rgba(255, 255, 255, 0.2);
    border-left: 3px solid #fff;
}

.collapse {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    margin: 0 0.5rem;
}

.collapse .nav-link {
    padding-left: 1rem;
}

/* Adjust main content margin */
@media (min-width: 768px) {
    main {
        margin-left: 220px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug: Log current page and directory
    console.log('Current Page:', '<?php echo $current_page; ?>');
    console.log('Current Directory:', '<?php echo $current_dir; ?>');
    
    // Make dropdown headers clickable
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault(); // Always prevent default to handle navigation manually
            
            // If the link has a real URL (not #)
            const href = this.getAttribute('href');
            const targetId = this.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);
            
            // Check if we're clicking on a dropdown that's already in the current directory
            const isCurrentDir = this.classList.contains('active');
            
            if (isCurrentDir) {
                // Toggle the dropdown
                if (targetElement) {
                    const bsCollapse = bootstrap.Collapse.getInstance(targetElement);
                    if (bsCollapse) {
                        bsCollapse.toggle();
                    } else {
                        new bootstrap.Collapse(targetElement, {
                            toggle: true
                        });
                    }
                }
            } else if (href && href !== '#') {
                // Navigate to the main page of that section
                window.location.href = href;
            }
        });
    });
});
</script> 