            </main>
        </div>
    </div>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="/ERC-POS/assets/js/main.js"></script>
    <script>
    // Debug: Check if Bootstrap is loaded
    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    console.log('jQuery loaded:', typeof jQuery !== 'undefined');
    
    // Initialize all Bootstrap components
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        var dropdownList = dropdownElementList.map(function(element) {
            return new bootstrap.Dropdown(element);
        });
        
        // Initialize all collapses
        var collapseElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="collapse"]'));
        var collapseList = collapseElementList.map(function(element) {
            return new bootstrap.Collapse(element, {toggle: false});
        });
    });
    </script>
</body>
</html> 