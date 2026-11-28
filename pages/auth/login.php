<?php
/**
 * Halaman masuk untuk mahasiswa dan administrator.
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

if (is_logged_in()) {
    $role = current_user()['role'] ?? '';
    $redirect = ($role === 'admin')
        ? base_url('pages/admin/dashboard.php')
        : base_url('pages/mahasiswa/dashboard.php');
    header('Location: ' . $redirect);
    exit;
}

$authController = new AuthController($pdo);
$errors = [];
$oldIdentitas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form login telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $identitas = $_POST['identitas'] ?? '';
        $password = $_POST['password'] ?? '';
        $oldIdentitas = $identitas;
        $result = $authController->login($identitas, $password);

        if ($result['success']) {
            set_flash('success', $result['message']);
            header('Location: ' . $result['redirect']);
            exit;
        }

        $errors = $result['errors'];
    }
}

$pageTitle = 'Masuk';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="auth-page container container-rf">
    <div class="auth-layout">
        <section class="auth-copy">
            <span class="auth-kicker">Portal civitas UHO</span>
            <h1>Temukan kembali barang yang hilang.</h1>
            <p>
                Masuk ke ReFindUHO untuk mengelola laporan Anda, melihat informasi
                kontak, dan mengikuti perkembangan barang hilang maupun temuan.
            </p>
            <div class="auth-assurance">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>Informasi kontak pelapor hanya dapat dilihat oleh pengguna yang sudah masuk.</span>
            </div>
        </section>

        <section class="auth-card card-surface" aria-labelledby="login-title">
            <div class="auth-card-heading">
                <span class="section-kicker">Akses akun</span>
                <h2 id="login-title">Masuk ke akun</h2>
                <p>Gunakan NIM atau email yang terdaftar.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= escape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-loading-submit>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="identitas" class="form-label">NIM atau email</label>
                    <input type="text"
                           class="form-control"
                           id="identitas"
                           name="identitas"
                           value="<?= escape($oldIdentitas) ?>"
                           placeholder="Masukkan NIM atau email"
                           autocomplete="username"
                           required
                           autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Kata sandi</label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               placeholder="Masukkan kata sandi"
                               autocomplete="current-password"
                               required>
                        <button class="btn btn-outline-secondary"
                                type="button"
                                data-toggle-password="password"
                                aria-label="Lihat atau sembunyikan kata sandi">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-rf-primary w-100"
                        data-loading-text="Memeriksa akun...">
                    Masuk <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <hr class="auth-divider">
            <p class="auth-card-foot">
                Belum memiliki akun?
                <a href="<?= base_url('pages/auth/register.php') ?>">Daftar sebagai mahasiswa</a>
            </p>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
