<?php
/**
 * pages/admin/dashboard.php — ReFindUHO
 * Dashboard admin: ringkasan status & daftar laporan masuk (FR-09).
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';

$authController = new AuthController($pdo);
$authController->pastikanAdmin();

$laporanController = new LaporanController($pdo);

// Filter status dari query string (default: Menunggu Verifikasi)
$filterStatus = $_GET['status'] ?? 'Menunggu Verifikasi';
$validStatuses = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai', 'Semua'];
if (!in_array($filterStatus, $validStatuses, true)) {
    $filterStatus = 'Menunggu Verifikasi';
}

// Ambil data untuk tabel
$laporanList = $laporanController->laporanMasuk($filterStatus === 'Semua' ? null : $filterStatus);

// Hitung ringkasan status secara efisien
$countByStatus = $laporanController->hitungRingkasanAdmin();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container container-rf py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h4 fw-bold mb-1">Dashboard Verifikasi Admin</h1>
            <p class="text-muted small mb-0">Kelola dan verifikasi laporan barang hilang & temuan civitas UHO</p>
        </div>
        <span class="badge bg-primary px-3 py-2">
            <i class="bi bi-shield-lock-fill me-1"></i> Mode Administrator
        </span>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <?php
        $summaryCards = [
            ['status' => 'Menunggu Verifikasi', 'icon' => 'bi-hourglass-split', 'label' => 'Menunggu Verifikasi', 'color' => 'text-warning'],
            ['status' => 'Disetujui',           'icon' => 'bi-check-circle',    'label' => 'Disetujui',           'color' => 'text-success'],
            ['status' => 'Ditolak',             'icon' => 'bi-x-circle',        'label' => 'Ditolak',             'color' => 'text-danger'],
            ['status' => 'Selesai',             'icon' => 'bi-archive',         'label' => 'Selesai',             'color' => 'text-secondary'],
        ];
        foreach ($summaryCards as $card):
            $isActive = ($filterStatus === $card['status']);
        ?>
            <div class="col-6 col-lg-3">
                <a href="?status=<?= urlencode($card['status']) ?>" class="summary-card-link">
                    <div class="card-interactive p-3 text-center h-100 <?= $isActive ? 'border-primary shadow-sm' : '' ?>"
                         style="<?= $isActive ? 'border-color: var(--rf-primary); background-color: var(--rf-primary-soft);' : '' ?>">
                        <i class="bi <?= $card['icon'] ?> <?= $card['color'] ?> d-block mb-1 fs-3"></i>
                        <div class="h3 fw-bold mb-0 font-mono"><?= (int) ($countByStatus[$card['status']] ?? 0) ?></div>
                        <div class="small text-muted fw-medium"><?= $card['label'] ?></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter info -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="mb-0 fw-medium">
            Status: <span class="badge-rf <?= badge_status_class($filterStatus) ?>"><?= escape($filterStatus) ?></span>
            <span class="text-muted small ms-1">(<?= count($laporanList) ?> laporan ditemukan)</span>
        </p>
        <?php if ($filterStatus !== 'Semua'): ?>
            <a href="?status=Semua" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul"></i> Tampilkan Semua Status
            </a>
        <?php endif; ?>
    </div>

    <!-- Reports Table -->
    <?php if (empty($laporanList)): ?>
        <div class="card-surface p-5 text-center shadow-sm">
            <i class="bi bi-inbox d-block mb-2" style="font-size: 2.5rem; color: var(--rf-text-muted);"></i>
            <p class="mb-1 fw-semibold">Tidak ada laporan dengan status "<?= escape($filterStatus) ?>"</p>
            <p class="text-muted small mb-0">Laporan baru yang diajukan oleh mahasiswa akan muncul di sini.</p>
        </div>
    <?php else: ?>
        <div class="card-surface shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="small fw-semibold text-muted">Pelapor</th>
                            <th class="small fw-semibold text-muted">Jenis</th>
                            <th class="small fw-semibold text-muted">Nama Barang</th>
                            <th class="small fw-semibold text-muted">Kategori</th>
                            <th class="small fw-semibold text-muted">Tanggal Lapor</th>
                            <th class="small fw-semibold text-muted">Status</th>
                            <th class="small fw-semibold text-muted text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporanList as $item): ?>
                            <tr>
                                <td class="fw-medium"><?= escape($item['nama_lengkap']) ?></td>
                                <td>
                                    <span class="badge-rf <?= badge_jenis_class($item['jenis_laporan']) ?>">
                                        <?= ($item['jenis_laporan'] === 'hilang') ? 'Hilang' : 'Temuan' ?>
                                    </span>
                                </td>
                                <td class="fw-semibold"><?= escape($item['nama_barang']) ?></td>
                                <td class="small text-muted"><?= escape($item['nama_kategori']) ?></td>
                                <td class="font-mono small"><?= escape($item['created_at']) ?></td>
                                <td>
                                    <span class="badge-rf <?= badge_status_class($item['status']) ?>">
                                        <?= escape($item['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('pages/admin/detail_laporan.php?id=' . (int) $item['id_barang']) ?>"
                                       class="btn btn-sm btn-outline-rf-primary">
                                        <i class="bi bi-search"></i> Periksa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
