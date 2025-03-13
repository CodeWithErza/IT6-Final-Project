            </main>
        </div>
    </div>
    
    <!-- Footer Credits -->
    <footer class="footer mt-auto py-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0 text-muted">
                        <small>&copy; <?php echo date('Y'); ?> ERC-POS System. All rights reserved.</small>
                    </p>
                    <p class="mb-0 text-muted">
                        <small>Developed by: Dumangcas, Era G. | Diog, Casandra Ella V. | Dolino, Ryle Joshua E.</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Custom JavaScript -->
    <script src="/ERC-POS/assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Fix dropdown behavior in sidebar
        document.querySelectorAll('.sidebar button[data-bs-toggle="collapse"]').forEach(function(button) {
            button.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) {
                    // Use Bootstrap's API to toggle the collapse
                    const bsCollapse = bootstrap.Collapse.getInstance(target);
                    if (bsCollapse) {
                        bsCollapse.toggle();
                    } else {
                        new bootstrap.Collapse(target, {
                            toggle: true
                        });
                    }
                }
            });
        });
        
        // Fix user dropdown in header
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) {
            // Initialize dropdown
            new bootstrap.Dropdown(userDropdown);
        }
    });
    </script>

    <style>
    .footer {
        background-color: #ffffff;
        border-top: 2px solid var(--secondary-color);
        position: fixed;
        bottom: 0;
        width: 100%;
        padding: 10px 0;
        font-size: 0.85rem;
        z-index: 1000;
        margin-left: 220px;
        width: calc(100% - 220px);
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .footer p {
        color: #6c757d;
        transition: color 0.3s ease;
    }
    
    .footer p:hover {
        color: var(--primary-color);
    }
    
    @media (max-width: 768px) {
        .footer {
            margin-left: 0;
            width: 100%;
        }
    }
    
    /* Add some bottom padding to main content to prevent overlap with footer */
    main {
        padding-bottom: 80px;
    }
    </style>
</body>
</html> 