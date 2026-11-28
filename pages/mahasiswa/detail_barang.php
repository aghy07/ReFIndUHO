<?php
/**
 * pages/mahasiswa/detail_barang.php — ReFindUHO
 * Halaman detail barang (FR-06, BR-08).
 * Dapat diakses publik; informasi kontak dibatasi untuk pengguna yang telah login (BR-08).
 * Pemilik laporan dapat melihat status laporannya dan menandai selesai jika sudah kembali.
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';

$authController    = new AuthController($pdo);
$laporanController = new LaporanController($pdo);

$user         = current_user();
$userId       = $user['id_user'] ?? null;
$isAdmin      = ($user['role'] ?? '') === 'admin';
$id           = (int) ($_GET['id'] ?? 0);

// Cari barang (publik: hanya Disetujui; pemilik / admin: semua status)
$barang = $laporanController->detailBarang($id, $userId, $isAdmin);

if ($barang === null) {
    set_flash('warning', 'Laporan barang tidak ditemukan atau belum disetujui oleh admin.');
    header('Location: ' . base_url('pages/mahasiswa/daftar_barang.php'));
    exit;
}

$isOwner = ($userId !== null && $barang->dimilikiOleh($userId));

// Handle penyelesaian mandiri oleh pemilik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
    } else {
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'selesaikan_mandiri' && $isOwner) {
            $hasil = $laporanController->selesaikanLaporanMahasiswa($id, $userId);
            set_flash($hasil['success'] ? 'success' : 'danger', $hasil['message']);
            header('Location: ' . base_url('pages/mahasiswa/detail_barang.php?id=' . $id));
            exit;
        }
    }
}

$pageTitle = $barang->nama_barang;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="catalog-page item-detail-page">
    <div class="container container-rf catalog-container">
    <div class="item-detail-top catalog-reveal">
        <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="btn catalog-back-btn" data-page-transition>
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
        <?php if ($isOwner): ?>
            <a href="<?= base_url('pages/mahasiswa/dashboard.php') ?>" class="btn catalog-back-btn">
                <i class="bi bi-layout-text-sidebar"></i> Laporan Saya
            </a>
        <?php endif; ?>
    </div>

    <!-- Banner kepemilikan laporan -->
    <?php if ($isOwner): ?>
        <div class="alert item-owner-alert catalog-reveal d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <i class="bi bi-person-check-fill me-1"></i>
                <strong>Ini adalah laporan Anda.</strong> Status saat ini:
                <span class="badge-rf <?= badge_status_class($barang->status) ?>"><?= escape($barang->status) ?></span>
            </div>
            <?php if ($barang->status === 'Disetujui'): ?>
                <form method="POST" action="" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="selesaikan_mandiri">
                    <button type="submit" class="btn btn-sm btn-success"
                            data-confirm="Tandai laporan ini sebagai selesai karena barang sudah ditemukan atau urusan telah selesai?">
                        <i class="bi bi-check2-circle"></i> Tandai Barang Selesai
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="item-detail-layout">
        <div class="item-gallery catalog-reveal" style="--reveal-delay: 80ms;">
            <?php
            $fotoAda = !empty($barang->foto) && file_exists(UPLOAD_DIR . $barang->foto);
            $fotoSrc = $fotoAda
                ? base_url('uploads/' . $barang->foto)
                : base_url('assets/images/no-image.svg');
            ?>
            <div class="item-photo-frame">
                <div class="img-shimmer-wrap skeleton-shimmer">
                    <img src="<?= escape($fotoSrc) ?>" alt="<?= escape($barang->nama_barang) ?>"
                         onerror="this.src='<?= base_url('assets/images/no-image.svg') ?>'">
                </div>
                <span class="item-photo-kind">
                    <i class="bi <?= $barang->jenis_laporan === 'hilang' ? 'bi-search' : 'bi-hand-index' ?>"></i>
                    <?= $barang->jenis_laporan === 'hilang' ? 'Barang hilang' : 'Barang temuan' ?>
                </span>
            </div>
            <p class="item-photo-caption"><i class="bi bi-shield-check"></i> Foto dari laporan civitas akademika UHO</p>
        </div>

        <section class="item-info-panel catalog-reveal" style="--reveal-delay: 150ms;" aria-labelledby="item-title">
                <div class="item-info-label"><span></span> Detail laporan</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php if ($barang->jenis_laporan === 'hilang'): ?>
                        <span class="item-detail-badge item-detail-badge-lost">
                            <i class="bi bi-search"></i> Barang hilang
                        </span>
                    <?php else: ?>
                        <span class="item-detail-badge item-detail-badge-found">
                            <i class="bi bi-hand-index"></i> Barang temuan
                        </span>
                    <?php endif; ?>

                    <span class="item-detail-badge item-detail-badge-status">
                        <i class="bi bi-patch-check"></i> <?= escape($barang->status) ?>
                    </span>
                </div>

                <h1 id="item-title"><?= escape($barang->nama_barang) ?></h1>
                <p class="item-detail-lead">Informasi barang yang dilaporkan oleh civitas akademika Universitas Halu Oleo.</p>

                <div class="item-meta-grid">
                    <div class="item-meta-entry">
                        <i class="bi bi-tag"></i>
                        <span>Kategori<strong><?= escape($barang->nama_kategori) ?></strong></span>
                    </div>
                    <div class="item-meta-entry">
                        <i class="bi bi-geo-alt"></i>
                        <span>Lokasi<strong><?= escape($barang->lokasi) ?></strong></span>
                    </div>
                    <div class="item-meta-entry">
                        <i class="bi bi-calendar3"></i>
                        <span>Tanggal<strong><?= escape($barang->tanggal_kejadian) ?></strong></span>
                    </div>
                    <div class="item-meta-entry">
                        <i class="bi bi-person"></i>
                        <span>Pelapor<strong><?= escape($barang->nama_pelapor) ?></strong></span>
                    </div>
                </div>

                <div class="item-description">
                    <h2>Deskripsi</h2>
                    <p><?= escape($barang->deskripsi) ?></p>
                </div>

                <!-- Alasan Penolakan (khusus pemilik / admin jika ditolak) -->
                <?php if ($barang->status === 'Ditolak' && !empty($barang->alasan_penolakan)): ?>
                    <div class="rejection-block mb-4">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Alasan Penolakan dari Admin:</strong> <?= escape($barang->alasan_penolakan) ?>
                    </div>
                <?php endif; ?>

                <!-- Contact Card (BR-08: Kontak hanya boleh dilihat pengguna yang telah login) -->
                <?php if (is_logged_in()): ?>
                    <div class="item-contact-card">
                        <h3><i class="bi bi-telephone"></i> Kontak pelapor</h3>
                        <p class="font-mono"><?= escape($barang->kontak) ?></p>
                        <button type="button" class="btn catalog-primary-btn"
                                data-copy-text="<?= escape($barang->kontak) ?>">
                            <i class="bi bi-clipboard"></i> Salin Kontak
                        </button>
                    </div>
                <?php else: ?>
                    <div class="item-contact-card item-contact-private">
                        <i class="bi bi-shield-lock"></i>
                        <h3>Kontak pelapor dilindungi</h3>
                        <p>
                            Sesuai aturan privasi kampus (BR-08), silakan masuk dengan akun Anda untuk melihat kontak pelapor ini.
                        </p>
                        <a href="<?= base_url('pages/auth/login.php') ?>" class="btn catalog-primary-btn">
                            <i class="bi bi-box-arrow-in-right"></i> Masuk untuk Melihat Kontak
                        </a>
                    </div>
                <?php endif; ?>
        </section>
    </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
