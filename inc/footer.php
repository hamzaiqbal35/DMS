<?php
// Define base URL if not already defined
if (!isset($base_url)) {
    $base_url = "http://localhost/DMS/";
}
?>

<!-- Footer Section -->
<footer class="auto-hide-footer" id="autoFooter">
    <p class="mb-0">&copy; <span id="currentYear"></span> Allied Steel Works. All Rights Reserved.</p>
</footer>

<!-- Bootstrap & jQuery Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script src="<?= $base_url ?>assets/js/scripts.js"></script>

<script>
    let lastScrollY = window.scrollY;
const footer = document.getElementById("autoFooter");

window.addEventListener("scroll", () => {
    if (!footer) return;
    if (window.scrollY > lastScrollY) {
        // Scrolling down
        footer.classList.add("footer-hidden");
    } else {
        // Scrolling up
        footer.classList.remove("footer-hidden");
    }
    lastScrollY = window.scrollY;
});
</script>
