<?php
/**
 * pages/mahasiswa/dashboard.php — ReFindUHO
 * Dashboard riwayat laporan mahasiswa (FR-08, BR-07).
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';

$authController = new AuthController($pdo);
$authController->pastikanLogin();

// Pastikan hanya mahasiswa
if ((current_user()['role'] ?? '') !== 'mahasiswa') {
    header('Location: ' . base_url('pages/admin/dashboard.php'));
    exit;
}

$laporanController = new LaporanController($pdo);
$user    = current_user();
$laporan = $laporanController->laporanSaya((int) $user['id_user']);

$pageTitle = 'Laporan Saya';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container container-rf py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Laporan Saya</h1>
            <p class="text-muted mb-0">Total <strong><?= count($laporan) ?></strong> laporan yang Anda ajukan</p>
        </div>
        <a href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>" class="btn btn-rf-primary">
            <i class="bi bi-plus-circle"></i> Buat Laporan Baru
        </a>
    </div>

    <?php if (empty($laporan)): ?>
        <div class="card-surface p-5 text-center">
            <i class="bi bi-clipboard-x d-block mb-2" style="font-size: 2.5rem; color: var(--rf-text-muted);"></i>
            <p class="mb-2 fw-medium">Anda belum memiliki riwayat laporan</p>
            <p class="text-muted small mb-3">Pernah kehilangan atau menemukan barang di lingkungan UHO? Laporkan sekarang agar dapat ditelusuri.</p>
            <a href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>" class="btn btn-sm btn-rf-primary">
                <i class="bi bi-plus-circle"></i> Buat Laporan Pertama
            </a>
        </div>
    <?php else: ?>
        <div class="card-surface">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small fw-semibold text-muted">Jenis</th>
                            <th class="small fw-semibold text-muted">Nama Barang</th>
                            <th class="small fw-semibold text-muted">Tanggal Kejadian</th>
                            <th class="small fw-semibold text-muted">Status</th>
                            <th class="small fw-semibold text-muted text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge-rf <?= badge_jenis_class($item['jenis_laporan']) ?>">
                                        <?= ($item['jenis_laporan'] === 'hilang') ? 'Hilang' : 'Temuan' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('pages/mahasiswa/detail_barang.php?id=' . (int) $item['id_barang']) ?>" class="fw-semibold text-decoration-none text-dark">
                                        <?= escape($item['nama_barang']) ?>
                                    </a>
                                </td>
                                <td class="font-mono small"><?= escape($item['tanggal_kejadian']) ?></td>
                                <td>
                                    <span class="badge-rf <?= badge_status_class($item['status']) ?>">
                                        <?= escape($item['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('pages/mahasiswa/detail_barang.php?id=' . (int) $item['id_barang']) ?>"
                                       class="btn btn-sm btn-outline-rf-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            <?php if ($item['status'] === 'Ditolak' && !empty($item['alasan_penolakan'])): ?>
                                <tr>
                                    <td colspan="5" class="py-2 px-3 bg-light">
                                        <div class="rejection-block">
                                            <i class="bi bi-info-circle me-1"></i>
                                            <strong>Alasan Penolakan:</strong> <?= escape($item['alasan_penolakan']) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
