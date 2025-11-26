/*!
 * Responsive Admin Dashboard Scripts
 * Enhanced sidebar with mobile support
 */

// Wait for DOM to be fully loaded
window.addEventListener('DOMContentLoaded', event => {

    // ===== SIDEBAR TOGGLE FUNCTIONALITY =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar-wrapper');

    if (sidebarToggle && wrapper) {

        // Load saved sidebar state from localStorage (optional)
        // Uncomment below to persist sidebar state across page refreshes
        /*
        if (localStorage.getItem('sidebar-toggled') === 'true') {
            wrapper.classList.add('toggled');
        }
        */

        // Toggle sidebar when button is clicked
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            wrapper.classList.toggle('toggled');

            // Save state to localStorage (optional)
            // Uncomment below to persist sidebar state
            /*
            localStorage.setItem('sidebar-toggled', wrapper.classList.contains('toggled'));
            */
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
                // Check if click is outside sidebar and toggle button
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    wrapper.classList.remove('toggled');

                    // Update localStorage if enabled
                    // localStorage.setItem('sidebar-toggled', 'false');
                }
            }
        });

        // Handle window resize - close mobile sidebar on desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                wrapper.classList.remove('toggled');

                // Update localStorage if enabled
                // localStorage.setItem('sidebar-toggled', 'false');
            }
        });
    }

    // ===== AUTO-DISMISS ALERTS =====
    // Automatically dismiss success/error alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    if (alerts.length > 0) {
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000); // 5 seconds
        });
    }

    // ===== SMOOTH SCROLL TO TOP =====
    // Add a "scroll to top" button functionality if needed
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ===== CONFIRM DELETE ACTIONS =====
    // Add confirmation to all delete buttons/links
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // ===== FORM VALIDATION ENHANCEMENT =====
    // Add Bootstrap validation styling to forms
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ===== TOOLTIP INITIALIZATION =====
    // Initialize Bootstrap tooltips if present
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ===== LOADING INDICATOR =====
    // Show loading indicator on form submissions
    const submitButtons = document.querySelectorAll('form button[type="submit"]');
    submitButtons.forEach(button => {
        button.closest('form').addEventListener('submit', function (e) {
            // Only show loading if form is valid
            if (this.checkValidity()) {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                button.disabled = true;

                // Re-enable after 10 seconds (safety measure)
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 10000);
            }
        });
    });

    // ===== ACTIVE MENU HIGHLIGHTING =====
    // Highlight current page in sidebar menu
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item');

    menuLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        if (currentPath.includes(linkPath.split('/').pop())) {
            // Remove active class from all links
            menuLinks.forEach(l => l.classList.remove('active'));
            // Add active class to current link
            link.classList.add('active');
        }
    });

    // ===== PASSWORD VISIBILITY TOGGLE =====
    // Add show/hide password functionality
    const passwordToggles = document.querySelectorAll('.toggle-password');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const input = document.querySelector(this.getAttribute('data-target'));
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            }
        });
    });

    // ===== TABLE ROW CLICK =====
    // Make table rows clickable if they have data-href attribute
    const clickableRows = document.querySelectorAll('tr[data-href]');
    clickableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            // Don't trigger if clicking on a button or link
            if (!e.target.closest('button') && !e.target.closest('a')) {
                window.location.href = this.getAttribute('data-href');
            }
        });
    });

    // ===== RESPONSIVE TABLES =====
    // Add horizontal scroll indicator for tables on mobile
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(table => {
        if (table.scrollWidth > table.clientWidth) {
            table.classList.add('has-scroll');

            // Add scroll indicator
            const indicator = document.createElement('div');
            indicator.className = 'table-scroll-indicator';
            indicator.innerHTML = '<i class="fas fa-arrow-right"></i> Scroll to see more';
            table.parentNode.insertBefore(indicator, table);

            // Hide indicator when scrolled to end
            table.addEventListener('scroll', function () {
                if (this.scrollLeft + this.clientWidth >= this.scrollWidth - 10) {
                    indicator.style.display = 'none';
                } else {
                    indicator.style.display = 'block';
                }
            });
        }
    });

    // ===== PRINT FUNCTIONALITY =====
    // Add print button functionality
    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    });

    // ===== DATE INPUT DEFAULTS =====
    // Set today's date as max for date inputs (optional)
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (input.hasAttribute('data-max-today')) {
            input.max = today;
        }
        if (input.hasAttribute('data-default-today')) {
            input.value = today;
        }
    });

    console.log('🚀 Admin Dashboard Scripts Loaded Successfully!');
});

// ===== UTILITY FUNCTIONS =====

/**
 * Show a toast notification (requires Bootstrap 5 toast markup)
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();

    // Remove from DOM after hidden
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy to clipboard', 'danger');
    });
}

// Make utility functions globally available
window.showToast = showToast;
window.formatNumber = formatNumber;
window.debounce = debounce;
window.copyToClipboard = copyToClipboard;