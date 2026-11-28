<?php
/**
 * pages/mahasiswa/daftar_barang.php — ReFindUHO
 * Halaman daftar & pencarian barang berstatus Disetujui (FR-05, FR-07).
 * Dapat diakses oleh publik (semua civitas akademika).
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';

$laporanController = new LaporanController($pdo);

// Ambil parameter pencarian & filter
$keyword      = trim($_GET['keyword'] ?? '');
$id_kategori  = !empty($_GET['id_kategori']) ? (int) $_GET['id_kategori'] : null;
$jenis        = !empty($_GET['jenis']) ? $_GET['jenis'] : null;
$halaman      = max(1, (int) ($_GET['halaman'] ?? 1));

// Ambil data dari controller
$hasil         = $laporanController->daftarBarang($keyword, $id_kategori, $jenis, $halaman);
$daftarBarang  = $hasil['data'];
$total         = $hasil['total'];
$totalHalaman  = $hasil['totalHalaman'];
$halamanAktif  = $hasil['halaman'];

// Ambil kategori untuk dropdown filter
$daftarKategori = $laporanController->daftarKategori();

$pageTitle = 'Daftar Barang';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="catalog-page">
    <div class="container container-rf catalog-container">
        <header class="catalog-heading catalog-reveal">
            <div>
                <span class="catalog-eyebrow">Universitas Halu Oleo · ReFindUHO</span>
                <h1>Barang hilang &amp; temuan</h1>
                <p>Telusuri laporan yang telah diverifikasi oleh tim admin kampus.</p>
            </div>
            <?php if (is_logged_in() && (current_user()['role'] ?? '') === 'mahasiswa'): ?>
                <a href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>" class="btn catalog-primary-btn">
                    <i class="bi bi-plus-lg"></i> Buat laporan
                </a>
            <?php endif; ?>
        </header>

        <div class="catalog-layout">
            <aside class="catalog-filter-panel catalog-reveal" style="--reveal-delay: 80ms;" aria-label="Filter daftar barang">
                <div class="catalog-filter-heading">
                    <h2>Filter</h2>
                    <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>">Reset</a>
                </div>
                <form id="form-pencarian-barang" method="GET" action="">
                    <div class="catalog-filter-group">
                        <label for="keyword">Cari barang</label>
                        <div class="catalog-search-input">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input type="search" id="keyword" name="keyword"
                                   value="<?= escape($keyword) ?>" placeholder="Nama atau ciri barang">
                        </div>
                    </div>
                    <div class="catalog-filter-group">
                        <label for="id_kategori">Kategori</label>
                        <select class="form-select" id="id_kategori" name="id_kategori">
                            <option value="">Semua kategori</option>
                            <?php foreach ($daftarKategori as $kat): ?>
                                <option value="<?= (int) $kat['id_kategori'] ?>"
                                    <?= ($id_kategori === (int) $kat['id_kategori']) ? 'selected' : '' ?>>
                                    <?= escape($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <fieldset class="catalog-filter-group catalog-kind-filter">
                        <legend>Jenis laporan</legend>
                        <label class="catalog-radio-option">
                            <input type="radio" name="jenis" value="" <?= empty($jenis) ? 'checked' : '' ?>>
                            <span>Semua laporan</span>
                        </label>
                        <label class="catalog-radio-option">
                            <input type="radio" name="jenis" value="hilang" <?= ($jenis === 'hilang') ? 'checked' : '' ?>>
                            <span>Barang hilang</span>
                        </label>
                        <label class="catalog-radio-option">
                            <input type="radio" name="jenis" value="temuan" <?= ($jenis === 'temuan') ? 'checked' : '' ?>>
                            <span>Barang temuan</span>
                        </label>
                    </fieldset>
                    <button type="submit" class="btn catalog-primary-btn w-100">
                        <i class="bi bi-sliders"></i> Terapkan filter
                    </button>
                </form>
            </aside>

            <section class="catalog-results" aria-labelledby="catalog-results-title">
                <div class="catalog-results-heading catalog-reveal" style="--reveal-delay: 120ms;">
                    <div>
                        <span class="catalog-eyebrow">Laporan terverifikasi</span>
                        <h2 id="catalog-results-title">Semua barang</h2>
                    </div>
                    <span class="catalog-result-count"><?= (int) $total ?> laporan</span>
                </div>

                <div class="catalog-grid" id="grid-daftar-barang">
                    <?php if (empty($daftarBarang)): ?>
                        <div class="catalog-empty-state catalog-reveal">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <h3>Belum ada barang yang cocok</h3>
                            <p>Coba ubah kata kunci atau filter untuk menemukan laporan yang dicari.</p>
                            <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="catalog-text-link">
                                Hapus semua filter <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftarBarang as $index => $barang): ?>
                            <?php
                            $fotoAda = !empty($barang['foto']) && file_exists(UPLOAD_DIR . $barang['foto']);
                            $fotoSrc = $fotoAda ? base_url('uploads/' . $barang['foto']) : base_url('assets/images/no-image.svg');
                            $detailUrl = base_url('pages/mahasiswa/detail_barang.php?id=' . (int) $barang['id_barang']);
                            ?>
                            <article class="listing-card catalog-reveal" style="--reveal-delay: <?= min($index, 5) * 55 ?>ms;">
                                <a class="listing-photo" href="<?= $detailUrl ?>" aria-label="Lihat <?= escape($barang['nama_barang']) ?>" data-page-transition>
                                    <div class="img-shimmer-wrap skeleton-shimmer">
                                        <img src="<?= escape($fotoSrc) ?>" alt="<?= escape($barang['nama_barang']) ?>"
                                             onerror="this.src='<?= base_url('assets/images/no-image.svg') ?>'">
                                    </div>
                                    <span class="listing-type <?= $barang['jenis_laporan'] === 'hilang' ? 'listing-type-lost' : 'listing-type-found' ?>">
                                        <i class="bi <?= $barang['jenis_laporan'] === 'hilang' ? 'bi-search' : 'bi-hand-index' ?>"></i>
                                        <?= $barang['jenis_laporan'] === 'hilang' ? 'Hilang' : 'Ditemukan' ?>
                                    </span>
                                    <span class="listing-open" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                                </a>
                                <div class="listing-card-body">
                                    <span class="listing-category"><?= escape($barang['nama_kategori']) ?></span>
                                    <h3><a href="<?= $detailUrl ?>" data-page-transition><?= escape($barang['nama_barang']) ?></a></h3>
                                    <p class="listing-location">
                                        <i class="bi bi-geo-alt"></i> <?= escape($barang['lokasi']) ?>
                                    </p>
                                    <div class="listing-card-footer">
                                        <span><i class="bi bi-calendar3"></i> <?= escape($barang['tanggal_kejadian']) ?></span>
                                        <a href="<?= $detailUrl ?>" aria-label="Detail <?= escape($barang['nama_barang']) ?>" data-page-transition>
                                            Detail <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($totalHalaman > 1): ?>
                    <nav class="catalog-pagination" aria-label="Navigasi halaman">
                        <ul class="pagination justify-content-center mb-0">
                            <?php
                            $queryParams = $_GET;
                            unset($queryParams['halaman']);
                            $baseQuery = http_build_query($queryParams);
                            $buildUrl = function (int $targetPage) use ($baseQuery): string {
                                return '?' . ($baseQuery !== '' ? $baseQuery . '&' : '') . 'halaman=' . $targetPage;
                            };
                            ?>
                            <li class="page-item <?= ($halamanAktif <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $buildUrl($halamanAktif - 1) ?>" aria-label="Sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                                <li class="page-item <?= ($i === $halamanAktif) ? 'active' : '' ?>">
                                    <a class="page-link font-mono" href="<?= $buildUrl($i) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($halamanAktif >= $totalHalaman) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $buildUrl($halamanAktif + 1) ?>" aria-label="Berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
