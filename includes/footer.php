<?php
/**
 * includes/footer.php — ReFindUHO
 * Footer kampus UHO, Bootstrap 5.3.3 JS Bundle, dan script.js.
 */
?>

<footer class="footer-rf py-4 mt-5">
    <div class="container container-rf text-center">
        <p class="mb-1">
            <i class="bi bi-mortarboard me-1"></i>ReFindUHO — Universitas Halu Oleo
        </p>
        <p class="mb-0 small">
            Sistem Informasi Pelaporan Barang Hilang dan Temuan &copy; <?= date('Y') ?>
        </p>
    </div>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= base_url('assets/js/script.js') ?>"></script>

</body>
</html>
