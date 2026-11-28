<?php
/**
 * includes/navbar.php — ReFindUHO
 * Navigasi responsif, menu dinamis berbasis hak akses,
 * tombol login/logout, status koneksi database, dan alert notifikasi get_flash().
 */

$user       = current_user();
$isLoggedIn = is_logged_in();
$role       = $user['role'] ?? '';
$dbErr      = db_connection_error();

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isLandingPage = $currentScript === 'index.php';
$isCatalogPage = in_array($currentScript, ['daftar_barang.php', 'detail_barang.php'], true);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-rf-primary sticky-top shadow-sm<?= $isLandingPage ? ' landing-nav' : '' ?><?= $isCatalogPage ? ' catalog-nav' : '' ?>">
    <div class="container container-rf">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= base_url('index.php') ?>">
            <i class="bi bi-search-heart text-warning fs-5"></i>
            <span>ReFind<span class="text-warning">UHO</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentScript === 'index.php') ? 'active fw-semibold' : '' ?>" href="<?= base_url('index.php') ?>">
                        <i class="bi bi-house-door me-1"></i>Beranda
                    </a>
                </li>

                <!-- Terbuka untuk umum: semua civitas akademika dapat melihat daftar barang -->
                <li class="nav-item">
                    <a class="nav-link <?= ($currentScript === 'daftar_barang.php') ? 'active fw-semibold' : '' ?>" href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" data-page-transition>
                        <i class="bi bi-grid me-1"></i>Daftar Barang
                    </a>
                </li>

                <?php if ($isLoggedIn && $role === 'mahasiswa'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentScript === 'form_laporan.php') ? 'active fw-semibold' : '' ?>" href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>">
                            <i class="bi bi-plus-circle me-1"></i>Buat Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active fw-semibold' : '' ?>" href="<?= base_url('pages/mahasiswa/dashboard.php') ?>">
                            <i class="bi bi-layout-text-sidebar me-1"></i>Laporan Saya
                        </a>
                    </li>
                <?php elseif ($isLoggedIn && $role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active fw-semibold' : '' ?>" href="<?= base_url('pages/admin/dashboard.php') ?>">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard Admin
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span><?= escape($user['nama_lengkap'] ?? '') ?></span>
                            <?php if ($role === 'admin'): ?>
                                <span class="badge bg-warning text-dark font-mono ms-1" style="font-size: 0.65rem;">ADMIN</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <div class="px-3 py-2">
                                    <div class="fw-semibold small"><?= escape($user['nama_lengkap'] ?? '') ?></div>
                                    <div class="text-muted small"><?= escape($user['email'] ?? '') ?></div>
                                    <?php if (!empty($user['nim'])): ?>
                                        <div class="text-muted font-mono small">NIM: <?= escape($user['nim']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php if ($role === 'mahasiswa'): ?>
                                <li>
                                    <a class="dropdown-item small" href="<?= base_url('pages/mahasiswa/dashboard.php') ?>">
                                        <i class="bi bi-journal-text me-2"></i>Laporan Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item small" href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>">
                                        <i class="bi bi-plus-square me-2"></i>Buat Laporan Baru
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item small" href="<?= base_url('pages/admin/dashboard.php') ?>">
                                        <i class="bi bi-shield-check me-2"></i>Kelola Laporan
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item text-danger small" href="<?= base_url('pages/auth/logout.php') ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link" href="<?= base_url('pages/auth/login.php') ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-light text-rf-primary fw-semibold ms-lg-1 mt-2 mt-lg-0" href="<?= base_url('pages/auth/register.php') ?>">
                            <i class="bi bi-person-plus me-1"></i>Daftar Akun
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($dbErr !== null): ?>
<div class="container container-rf mt-3">
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0"></i>
        <div>
            <strong>Koneksi Database Gagal:</strong> Pastikan layanan MySQL/MariaDB aktif dan database <code>refinduho</code> telah diimpor dari <code>database/schema.sql</code>.
            <div class="small text-muted mt-1 font-mono"><?= escape($dbErr) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Flash messages
$flashMessages = get_flash();
if (!empty($flashMessages)): ?>
<div class="container container-rf mt-3">
    <?php foreach ($flashMessages as $flash): ?>
        <div class="alert alert-<?= escape($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= escape($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
