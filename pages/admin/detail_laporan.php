<?php
/**
 * pages/admin/detail_laporan.php — ReFindUHO
 * Detail & verifikasi laporan oleh admin (FR-10, FR-11).
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';

$authController = new AuthController($pdo);
$authController->pastikanAdmin();

$laporanController = new LaporanController($pdo);

$id = (int) ($_GET['id'] ?? 0);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
    } else {
        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'setujui') {
            $result = $laporanController->setujuiLaporan($id);
            set_flash($result['success'] ? 'success' : 'danger', $result['message']);
        } elseif ($aksi === 'tolak') {
            $alasan = $_POST['alasan_penolakan'] ?? '';
            $result = $laporanController->tolakLaporan($id, $alasan);
            set_flash($result['success'] ? 'success' : 'danger', $result['message']);
        } elseif ($aksi === 'selesai') {
            $result = $laporanController->selesaikanLaporan($id);
            set_flash($result['success'] ? 'success' : 'danger', $result['message']);
        }
    }

    header('Location: ' . base_url('pages/admin/detail_laporan.php?id=' . $id));
    exit;
}

$barang = $laporanController->detailLaporanAdmin($id);

if ($barang === null) {
    set_flash('warning', 'Laporan tidak ditemukan.');
    header('Location: ' . base_url('pages/admin/dashboard.php'));
    exit;
}

$pageTitle = 'Verifikasi Laporan — ' . $barang->nama_barang;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container container-rf py-4">
    <div class="mb-3 d-flex align-items-center gap-2">
        <a href="<?= base_url('pages/admin/dashboard.php') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <div class="row g-4">
        <!-- Photo Column -->
        <div class="col-12 col-lg-5">
            <div class="card-surface overflow-hidden shadow-sm">
                <div class="img-shimmer-wrap skeleton-shimmer">
                    <?php
                    $fotoAda = !empty($barang->foto) && file_exists(UPLOAD_DIR . $barang->foto);
                    $fotoSrc = $fotoAda
                        ? base_url('uploads/' . $barang->foto)
                        : base_url('assets/images/no-image.svg');
                    ?>
                    <img src="<?= escape($fotoSrc) ?>" alt="<?= escape($barang->nama_barang) ?>"
                         onerror="this.src='<?= base_url('assets/images/no-image.svg') ?>'">
                </div>
            </div>
        </div>

        <!-- Detail & Action Column -->
        <div class="col-12 col-lg-7">
            <div class="card-surface p-4 shadow-sm">
                <!-- Status & Jenis -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge-rf <?= badge_status_class($barang->status) ?>">
                        <i class="bi bi-info-circle"></i> <?= escape($barang->status) ?>
                    </span>
                    <?php if ($barang->jenis_laporan === 'hilang'): ?>
                        <span class="badge-rf badge-jenis-hilang">
                            <i class="bi bi-exclamation-circle"></i> Barang Hilang
                        </span>
                    <?php else: ?>
                        <span class="badge-rf badge-jenis-temuan">
                            <i class="bi bi-hand-index"></i> Barang Temuan
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="h4 fw-bold mb-3"><?= escape($barang->nama_barang) ?></h1>

                <table class="table table-borderless detail-meta mb-3">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 170px;"><i class="bi bi-tag me-1"></i>Kategori</td>
                            <td class="fw-semibold"><?= escape($barang->nama_kategori) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-geo-alt me-1"></i>Lokasi</td>
                            <td class="fw-medium"><?= escape($barang->lokasi) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-calendar3 me-1"></i>Tanggal Kejadian</td>
                            <td class="fw-medium font-mono"><?= escape($barang->tanggal_kejadian) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-person-badge me-1"></i>Nama Pelapor</td>
                            <td class="fw-medium">
                                <?= escape($barang->nama_pelapor) ?>
                                <?php if (!empty($barang->nim_pelapor)): ?>
                                    <span class="badge bg-light text-dark font-mono ms-1 border">NIM: <?= escape($barang->nim_pelapor) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($barang->email_pelapor)): ?>
                            <tr>
                                <td class="text-muted"><i class="bi bi-envelope me-1"></i>Email Pelapor</td>
                                <td class="fw-medium font-mono small"><?= escape($barang->email_pelapor) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="text-muted"><i class="bi bi-telephone me-1"></i>Kontak Pelapor</td>
                            <td class="fw-medium font-mono"><?= escape($barang->kontak) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="bi bi-clock-history me-1"></i>Waktu Lapor</td>
                            <td class="fw-medium font-mono small"><?= escape($barang->created_at) ?> WITA</td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="h6 fw-bold mb-2">Deskripsi Laporan</h2>
                <p class="mb-4 text-secondary" style="white-space: pre-line;"><?= escape($barang->deskripsi) ?></p>

                <!-- Alasan Penolakan (jika ditolak) -->
                <?php if ($barang->status === 'Ditolak' && !empty($barang->alasan_penolakan)): ?>
                    <div class="rejection-block mb-4">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Alasan Penolakan yang Diberikan:</strong> <?= escape($barang->alasan_penolakan) ?>
                    </div>
                <?php endif; ?>

                <!-- Admin Action Panel -->
                <hr>

                <?php if ($barang->status === 'Menunggu Verifikasi'): ?>
                    <!-- FR-10: Setujui / Tolak -->
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-shield-check me-1"></i>Tindakan Verifikasi</h3>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <form method="POST" action="" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="setujui">
                            <button type="submit" class="btn btn-success"
                                    data-confirm="Apakah Anda yakin ingin menyetujui laporan ini? Laporan akan segera ditampilkan di daftar barang publik.">
                                <i class="bi bi-check-circle me-1"></i> Setujui Laporan
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="" data-loading-submit>
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="tolak">
                        <div class="mb-2">
                            <label for="alasan_penolakan" class="form-label fw-medium">Tolak Laporan dengan Alasan:</label>
                            <textarea class="form-control" id="alasan_penolakan" name="alasan_penolakan" rows="3"
                                      placeholder="Tulis alasan penolakan secara jelas agar pelapor memahami kendalanya..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger" data-loading-text="Menolak laporan...">
                            <i class="bi bi-x-circle me-1"></i> Tolak Laporan Ini
                        </button>
                    </form>

                <?php elseif ($barang->status === 'Disetujui'): ?>
                    <!-- FR-11: Tandai Selesai -->
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-check2-all me-1"></i>Tindakan Penyelesaian</h3>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="selesai">
                        <button type="submit" class="btn btn-rf-primary"
                                data-confirm="Tandai laporan ini sebagai selesai? Tindakan ini bersifat final.">
                            <i class="bi bi-check2-all me-1"></i> Tandai Selesai
                        </button>
                    </form>
                    <p class="small text-muted mt-2 mb-0">
                        Gunakan tombol di atas jika barang sudah berhasil dikembalikan ke pemiliknya.
                    </p>

                <?php else: ?>
                    <!-- Status Ditolak / Selesai — final, tanpa aksi -->
                    <div class="card-surface p-3" style="background-color: var(--rf-status-done-bg);">
                        <i class="bi bi-lock me-1"></i>
                        <span class="fw-medium">Status laporan ini adalah "<?= escape($barang->status) ?>" (bersifat final). Tidak ada tindakan lebih lanjut yang diperlukan.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
