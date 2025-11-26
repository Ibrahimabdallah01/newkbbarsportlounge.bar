<!-- Footer -->
<footer class="main-footer">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    <strong><?php echo $company_name; ?></strong> &copy; <?php echo date('Y'); ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    Version 1.0.0 |
                    <a href="#" class="text-decoration-none">Help</a> |
                    <a href="#" class="text-decoration-none">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button id="scrollToTop" title="Go to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts -->
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables (if needed) -->
<?php if(isset($use_datatables) && $use_datatables): ?>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>

<!-- Custom Scripts -->
<script src="../assets/js/scripts.js"></script>

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const wrapper = document.getElementById('wrapper');
        const sidebar = document.getElementById('sidebar-wrapper');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
            if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                wrapper.classList.remove('toggled');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.getElementById('wrapper').classList.remove('toggled');
        }
    });

    // Scroll to top button
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Hide loading overlay
    setTimeout(function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }, 300);

    // Add fade-in animation to content
    const pageContent = document.querySelector('.container-fluid');
    if (pageContent) {
        pageContent.classList.add('fade-in');
    }
});
</script>

<?php if(isset($extra_js)): ?>
<?php echo $extra_js; ?>
<?php endif; ?>

</body>

</html>

<style>
/* Footer Styles */
.main-footer {
    background: white;
    padding: 1.5rem 0;
    margin-top: 3rem;
    border-top: 1px solid #dee2e6;
    font-size: 0.875rem;
    color: #6c757d;
}

.main-footer a {
    color: #667eea;
    transition: color 0.3s;
}

.main-footer a:hover {
    color: #764ba2;
}

/* Scroll to Top Button */
#scrollToTop {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s, box-shadow 0.3s;
}

#scrollToTop:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
    .main-footer {
        font-size: 0.8rem;
        padding: 1rem 0;
    }

    .main-footer .col-md-6 {
        text-align: center !important;
        margin-bottom: 0.5rem;
    }

    #scrollToTop {
        bottom: 15px;
        right: 15px;
        width: 45px;
        height: 45px;
    }
}

@media print {

    .main-footer,
    #scrollToTop,
    #sidebar-wrapper,
    #sidebarToggle,
    .navbar {
        display: none !important;
    }
}
</style>