<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> <?php echo $company_name; ?>. All rights reserved.</p>
        <div class="footer-links">
            <a href="#">Help</a>
            <span>|</span>
            <a href="#">Contact Support</a>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS (optional) -->
<?php if (isset($use_datatables) && $use_datatables): ?>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>

<!-- Custom Scripts -->
<script>
$(document).ready(function() {
    // Sidebar Toggle
    $('#sidebarToggle, #mobileSidebarToggle').click(function() {
        $('.sidebar').toggleClass('active');
        $('.sidebar-overlay').toggleClass('active');
    });

    // Close sidebar when clicking overlay
    $('.sidebar-overlay').click(function() {
        $('.sidebar').removeClass('active');
        $(this).removeClass('active');
    });

    // Close sidebar when clicking a menu item on mobile
    $('.menu-item').click(function() {
        if ($(window).width() <= 768) {
            $('.sidebar').removeClass('active');
            $('.sidebar-overlay').removeClass('active');
        }
    });

    // Scroll to Top Button
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            $('#scrollToTop').addClass('show');
        } else {
            $('#scrollToTop').removeClass('show');
        }
    });

    $('#scrollToTop').click(function() {
        $('html, body').animate({
            scrollTop: 0
        }, 600);
        return false;
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
});
</script>

<?php if (isset($extra_js)): ?>
<?php echo $extra_js; ?>
<?php endif; ?>

</body>

</html>