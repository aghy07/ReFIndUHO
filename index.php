<?php
/**
 * index.php — ReFindUHO
 * Halaman beranda (Semua aktor & publik).
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/controllers/LaporanController.php';

$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$user       = current_user();
$isLoggedIn = is_logged_in();
$role       = $user['role'] ?? '';

// Ambil laporan terbaru yang telah disetujui untuk ditampilkan di beranda
$laporanController = new LaporanController($pdo);
$laporanTerbaru = $laporanController->laporanTerbaru(6);
?>

<main>
    <section class="hero-section landing-hero">
        <div class="container container-rf landing-hero-container">
            <div class="hero-layout">
                <div class="landing-hero-copy landing-reveal">
                    <span class="hero-kicker">Layanan barang hilang &amp; temuan UHO</span>
                    <h1 class="hero-title">Temukan barang.<br>Kembalikan ke pemiliknya.</h1>
                    <p class="hero-copy">
                        Satu tempat untuk melaporkan kehilangan, mengumumkan temuan,
                        dan menelusuri barang terverifikasi di lingkungan kampus.
                    </p>
                    <div class="hero-actions landing-hero-actions">
                        <?php if ($isLoggedIn && $role === 'mahasiswa'): ?>
                            <a href="<?= base_url('pages/mahasiswa/form_laporan.php') ?>" class="btn btn-rf-primary">
                                Buat laporan <i class="bi bi-arrow-right"></i>
                            </a>
                            <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="btn btn-outline-rf-primary landing-secondary-action" data-page-transition>
                                Jelajahi laporan
                            </a>
                        <?php elseif ($isLoggedIn && $role === 'admin'): ?>
                            <a href="<?= base_url('pages/admin/dashboard.php') ?>" class="btn btn-rf-primary">
                                Buka dashboard <i class="bi bi-arrow-right"></i>
                            </a>
                            <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="btn btn-outline-rf-primary landing-secondary-action" data-page-transition>
                                Jelajahi laporan
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('pages/auth/register.php') ?>" class="btn btn-rf-primary">
                                Laporkan barang <i class="bi bi-arrow-right"></i>
                            </a>
                            <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="btn btn-outline-rf-primary landing-secondary-action" data-page-transition>
                                Jelajahi laporan
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="hero-note">
                        <i class="bi bi-shield-check"></i> Laporan ditinjau admin sebelum tampil ke publik.
                    </p>
                </div>

                <aside class="hero-panel landing-search-card landing-reveal" aria-label="Cari laporan barang" style="--reveal-delay: 120ms;">
                    <div class="hero-panel-label">
                        <span class="hero-panel-mark"><i class="bi bi-search"></i></span>
                        Pencarian barang
                    </div>
                    <h2>Ada yang sedang dicari?</h2>
                    <p class="landing-search-intro">Telusuri barang yang telah diverifikasi di kampus.</p>
                    <form action="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" method="get" class="landing-search-form">
                        <div>
                            <label for="landing-keyword">Nama atau ciri barang</label>
                            <input id="landing-keyword" name="keyword" type="search" placeholder="Contoh: dompet hitam">
                        </div>
                        <div>
                            <label for="landing-kind">Jenis laporan</label>
                            <select id="landing-kind" name="jenis">
                                <option value="">Semua laporan</option>
                                <option value="hilang">Barang hilang</option>
                                <option value="temuan">Barang temuan</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-rf-primary">
                            Cari barang <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </aside>
            </div>
            <div class="landing-trust-strip" aria-label="Keunggulan ReFindUHO">
                <div class="landing-trust-item landing-reveal" style="--reveal-delay: 180ms;">
                    <i class="bi bi-patch-check"></i>
                    <span><strong>Ditinjau admin</strong><small>Laporan diperiksa sebelum dipublikasi</small></span>
                </div>
                <div class="landing-trust-item landing-reveal" style="--reveal-delay: 260ms;">
                    <i class="bi bi-mortarboard"></i>
                    <span><strong>Untuk civitas UHO</strong><small>Terhubung dalam satu kampus</small></span>
                </div>
                <div class="landing-trust-item landing-reveal" style="--reveal-delay: 340ms;">
                    <i class="bi bi-person-lock"></i>
                    <span><strong>Akses kontak terbatas</strong><small>Kontak pelapor hanya untuk pengguna masuk</small></span>
                </div>
            </div>
        </div>
    </section>

<!-- Latest Approved Items Preview (Jika ada) -->
<?php if (!empty($laporanTerbaru)): ?>
<section class="section-wrap bg-white border-bottom">
    <div class="container container-rf">
        <div class="section-heading landing-reveal">
            <div>
                <span class="section-kicker">Pembaruan kampus</span>
                <h2>Barang yang baru diverifikasi</h2>
                <p>Laporan terbaru yang siap ditelusuri oleh civitas akademika.</p>
            </div>
            <a href="<?= base_url('pages/mahasiswa/daftar_barang.php') ?>" class="btn btn-outline-rf-primary">
                Lihat semua <i class="bi bi-arrow-up-right"></i>
            </a>
        </div>
        <div class="row g-3">
            <?php foreach ($laporanTerbaru as $index => $item): ?>
                <div class="col-12 col-sm-6 col-lg-4 landing-reveal" style="--reveal-delay: <?= $index * 80 ?>ms;">
                    <article class="card-interactive h-100 d-flex flex-column">
                        <div class="img-shimmer-wrap skeleton-shimmer" style="border-radius: 8px 8px 0 0;">
                            <?php
                            $fotoAda = !empty($item['foto']) && file_exists(UPLOAD_DIR . $item['foto']);
                            $fotoSrc = $fotoAda ? base_url('uploads/' . $item['foto']) : base_url('assets/images/no-image.svg');
                            ?>
                            <img src="<?= escape($fotoSrc) ?>" alt="<?= escape($item['nama_barang']) ?>"
                                onerror="this.src='<?= base_url('assets/images/no-image.svg') ?>'">
                        </div>
                        <div class="p-3 p-lg-4 d-flex flex-column flex-grow-1">
                            <?php if ($item['jenis_laporan'] === 'hilang'): ?>
                                <span class="badge-rf badge-jenis-hilang mb-2 align-self-start">
                                    <i class="bi bi-exclamation-circle"></i> Barang Hilang
                                </span>
                            <?php else: ?>
                                <span class="badge-rf badge-jenis-temuan mb-2 align-self-start">
                                    <i class="bi bi-hand-index"></i> Barang Temuan
                                </span>
                            <?php endif; ?>

                            <h3 class="h6 fw-bold mb-1 text-clamp-2"><?= escape($item['nama_barang']) ?></h3>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-tag"></i> <?= escape($item['nama_kategori']) ?>
                            </p>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-geo-alt"></i> <?= escape($item['lokasi']) ?>
                            </p>
                            <p class="small text-muted mb-3 font-mono">
                                <i class="bi bi-calendar3"></i> <?= escape($item['tanggal_kejadian']) ?>
                            </p>

                            <div class="mt-auto">
                                <a href="<?= base_url('pages/mahasiswa/detail_barang.php?id=' . (int) $item['id_barang']) ?>"
                                   class="btn btn-sm btn-outline-rf-primary w-100">
                                    <i class="bi bi-eye"></i> Lihat Detail
                                </a>
                            </article>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section-wrap process-section" id="cara-kerja">
    <div class="process-parallax-bg" aria-hidden="true">
        <span class="process-orb process-orb-1" data-parallax-speed="0.06"></span>
        <span class="process-orb process-orb-2" data-parallax-speed="-0.05"></span>
    </div>
    <div class="container container-rf position-relative">
        <div class="section-heading process-reveal">
            <div>
                <span class="section-kicker">Cara kerja</span>
                <h2>Dari laporan hingga barang kembali</h2>
                <p>Alur sederhana yang menjaga informasi tetap jelas bagi pelapor dan pencari.</p>
            </div>
        </div>
        <div class="row g-3">
            <?php
            $steps = [
                ['title' => 'Ajukan laporan', 'desc' => 'Isi informasi barang, waktu, lokasi, dan ciri yang membantu proses pencarian.'],
                ['title' => 'Admin meninjau', 'desc' => 'Laporan diperiksa sebelum dipublikasikan agar informasi tetap terjaga.'],
                ['title' => 'Koordinasikan pengambilan', 'desc' => 'Masuk untuk melihat kontak pelapor dan menyelesaikan serah terima.'],
            ];
            foreach ($steps as $i => $step):
                $parallaxSpeed = $i === 1 ? '-0.035' : '0.025';
            ?>
                <div class="col-12 col-md-4 process-reveal" style="--reveal-delay: <?= $i * 110 ?>ms;">
                    <div class="process-parallax-item" data-parallax-speed="<?= $parallaxSpeed ?>">
                        <div class="process-float-wrap" style="--float-delay: <?= $i * 0.7 ?>s;">
                            <article class="process-card">
                                <span class="process-card-index">0<?= $i + 1 ?></span>
                                <h3><?= $step['title'] ?></h3>
                                <p><?= $step['desc'] ?></p>
                            </article>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pb-5 landing-reveal">
    <div class="container container-rf">
        <div class="contact-panel">
            <div>
                <span class="section-kicker">Butuh bantuan?</span>
                <h2 class="mt-2">Layanan administrasi kampus</h2>
                <p>Hubungi administrator jika mengalami kendala saat menggunakan ReFindUHO.</p>
            </div>
            <a href="mailto:admin@uho.ac.id">
                <i class="bi bi-envelope me-2"></i>admin@uho.ac.id
            </a>
        </div>
    </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
