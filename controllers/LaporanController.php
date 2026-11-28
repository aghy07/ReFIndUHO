<?php
/**
 * LaporanController — ReFindUHO
 * Mengelola pembuatan laporan, daftar barang, detail, pencarian,
 * verifikasi, dan perubahan status laporan.
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/Barang.php';
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../models/User.php';

class LaporanController
{
    private ?PDO      $pdo;
    private ?Barang   $barangModel   = null;
    private ?Kategori $kategoriModel = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
        if ($pdo !== null) {
            $this->barangModel   = new Barang($pdo);
            $this->kategoriModel = new Kategori($pdo);
        }
    }

    // =================================================================
    // METHOD: validasiLaporan
    // Memvalidasi seluruh input form laporan (server-side) termasuk
    // validasi foto unggah (opsional, JPG/PNG, max 2 MB, rename unik).
    // =================================================================
    public function validasiLaporan(array $data, array $file = []): array
    {
        $errors   = [];
        $fotoPath = null;

        // Validasi field wajib
        $requiredFields = [
            'nama_barang'      => 'Nama barang',
            'id_kategori'      => 'Kategori',
            'deskripsi'        => 'Deskripsi',
            'lokasi'           => 'Lokasi',
            'tanggal_kejadian' => 'Tanggal kejadian',
            'kontak'           => 'Kontak pelapor',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[] = "$label wajib diisi.";
            }
        }

        // Validasi jenis laporan
        $jenis = $data['jenis_laporan'] ?? '';
        if (!in_array($jenis, ['hilang', 'temuan'], true)) {
            $errors[] = 'Jenis laporan tidak valid.';
        }

        // Validasi kategori valid di database
        $id_kategori = (int) ($data['id_kategori'] ?? 0);
        if ($id_kategori <= 0) {
            $errors[] = 'Kategori wajib dipilih.';
        } elseif ($this->kategoriModel !== null) {
            $kat = $this->kategoriModel->cariById($id_kategori);
            if ($kat === null) {
                $errors[] = 'Kategori yang dipilih tidak valid.';
            }
        }

        // Validasi tanggal tidak di masa depan (menggunakan timezone lokal Asia/Makassar)
        $tanggal = $data['tanggal_kejadian'] ?? '';
        if (!empty($tanggal)) {
            $tglInput = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
            $formatValid = $tglInput !== false && $tglInput->format('Y-m-d') === $tanggal;
            if (!$formatValid) {
                $errors[] = 'Format tanggal kejadian tidak valid.';
            } elseif ($tglInput > new DateTimeImmutable('today')) {
                $errors[] = 'Tanggal kejadian tidak boleh di masa depan.';
            }
        }

        // Validasi batas panjang karakter
        $maxLengths = [
            'nama_barang' => ['label' => 'Nama barang', 'max' => 100],
            'lokasi'      => ['label' => 'Lokasi kejadian', 'max' => 150],
            'kontak'      => ['label' => 'Kontak pelapor', 'max' => 100],
        ];

        foreach ($maxLengths as $field => $info) {
            $nilai = trim((string) ($data[$field] ?? ''));
            if (strlen($nilai) > $info['max']) {
                $errors[] = $info['label'] . ' maksimal ' . $info['max'] . ' karakter.';
            }
        }

        // Validasi foto (opsional)
        if (!empty($file) && isset($file['tmp_name']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = 'Ukuran foto melebihi batas maksimal server (2 MB).';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Terjadi kesalahan saat mengunggah foto. Kode error: ' . $file['error'];
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = 'File foto tidak valid.';
            } else {
                $allowedMime = ['image/jpeg', 'image/png'];
                $allowedExt  = ['jpg', 'jpeg', 'png'];

                $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

                // Deteksi MIME type dengan fallback aman jika fileinfo belum aktif
                $mimeType = null;
                if (class_exists('finfo')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);
                } elseif (function_exists('mime_content_type')) {
                    $mimeType = mime_content_type($file['tmp_name']);
                } elseif (function_exists('getimagesize')) {
                    $imgInfo = @getimagesize($file['tmp_name']);
                    $mimeType = $imgInfo['mime'] ?? null;
                }

                $isValidMime = ($mimeType !== null && in_array($mimeType, $allowedMime, true));
                $isValidExt  = in_array($ext, $allowedExt, true);

                if (!$isValidMime || !$isValidExt) {
                    $errors[] = 'Format foto harus berupa JPG, JPEG, atau PNG.';
                }

                // Validasi ukuran file (max 2 MB)
                $maxSize = 2 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    $errors[] = 'Ukuran foto maksimal 2 MB.';
                }

                // Jika lolos validasi, simpan foto
                if (empty($errors)) {
                    $namaUnik = uniqid('rfuho_', true) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);

                    if (!is_dir(UPLOAD_DIR)) {
                        @mkdir(UPLOAD_DIR, 0755, true);
                    }

                    $tujuan = UPLOAD_DIR . $namaUnik;

                    if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                        $fotoPath = $namaUnik;
                    } else {
                        $errors[] = 'Gagal menyimpan foto ke server. Periksa izin folder uploads/.';
                    }
                }
            }
        }

        return [
            'valid'     => empty($errors),
            'errors'    => $errors,
            'foto_path' => $fotoPath,
        ];
    }

    // =================================================================
    // METHOD: buatLaporanHilang
    // =================================================================
    public function buatLaporanHilang(array $data, array $file, int $id_user): array
    {
        $data['jenis_laporan'] = 'hilang';
        return $this->prosesLaporan($data, $file, $id_user);
    }

    // =================================================================
    // METHOD: buatLaporanTemuan
    // =================================================================
    public function buatLaporanTemuan(array $data, array $file, int $id_user): array
    {
        $data['jenis_laporan'] = 'temuan';
        return $this->prosesLaporan($data, $file, $id_user);
    }

    // -----------------------------------------------------------------
    // INTERNAL: Proses laporan
    // -----------------------------------------------------------------
    private function prosesLaporan(array $data, array $file, int $id_user): array
    {
        if ($this->pdo === null) {
            return [
                'success' => false,
                'errors'  => ['Koneksi database tidak tersedia.'],
            ];
        }

        $validasi = $this->validasiLaporan($data, $file);

        if (!$validasi['valid']) {
            return ['success' => false, 'errors' => $validasi['errors']];
        }

        $barang = new Barang($this->pdo);
        $barang->id_user          = $id_user;
        $barang->id_kategori      = (int) $data['id_kategori'];
        $barang->jenis_laporan    = $data['jenis_laporan'];
        $barang->nama_barang      = trim($data['nama_barang']);
        $barang->deskripsi        = trim($data['deskripsi']);
        $barang->lokasi           = trim($data['lokasi']);
        $barang->tanggal_kejadian = $data['tanggal_kejadian'];
        $barang->kontak           = trim($data['kontak']);
        $barang->foto             = $validasi['foto_path'];

        try {
            $berhasil = $barang->simpan();
        } catch (PDOException $exception) {
            $berhasil = false;
            error_log('ReFindUHO failed to save report: ' . $exception->getMessage());
        }

        if ($berhasil) {
            return [
                'success' => true,
                'message' => 'Laporan barang berhasil dikirim dan sedang menunggu verifikasi admin kampus.',
            ];
        }

        if ($validasi['foto_path'] !== null) {
            $path = UPLOAD_DIR . $validasi['foto_path'];
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return ['success' => false, 'errors' => ['Gagal menyimpan laporan ke database. Silakan coba lagi.']];
    }

    // =================================================================
    // METHOD: daftarBarang (FR-05, FR-07)
    // =================================================================
    public function daftarBarang(
        string $keyword = '',
        ?int $id_kategori = null,
        ?string $jenis = null,
        int $halaman = 1
    ): array {
        if ($this->barangModel === null) {
            return [
                'data'         => [],
                'total'        => 0,
                'halaman'      => 1,
                'totalHalaman' => 1,
                'perHalaman'   => 10,
            ];
        }
        return $this->barangModel->cariDisetujui($keyword, $id_kategori, $jenis, $halaman, 10);
    }

    public function cariBarang(
        string $keyword = '',
        ?int $id_kategori = null,
        ?string $jenis = null,
        int $halaman = 1
    ): array {
        return $this->daftarBarang($keyword, $id_kategori, $jenis, $halaman);
    }

    // =================================================================
    // METHOD: laporanTerbaru (Homepage preview)
    // =================================================================
    public function laporanTerbaru(int $limit = 6): array
    {
        if ($this->barangModel === null) {
            return [];
        }
        return $this->barangModel->cariTerbaruDisetujui($limit);
    }

    // =================================================================
    // METHOD: detailBarang (FR-06)
    // Publik hanya melihat 'Disetujui'. Pemilik laporan & admin dapat
    // melihat laporan mereka dalam status apa pun.
    // =================================================================
    public function detailBarang(int $id_barang, ?int $id_user = null, bool $isAdmin = false): ?Barang
    {
        if ($this->barangModel === null) {
            return null;
        }

        $barang = $this->barangModel->cariById($id_barang);

        if ($barang === null) {
            return null;
        }

        if ($barang->tampilDiDaftar() || ($id_user !== null && $barang->dimilikiOleh($id_user)) || $isAdmin) {
            return $barang;
        }

        return null;
    }

    // =================================================================
    // METHOD: laporanSaya (FR-08, BR-07)
    // =================================================================
    public function laporanSaya(int $id_user): array
    {
        if ($this->barangModel === null) {
            return [];
        }
        return $this->barangModel->cariByPemilik($id_user);
    }

    // =================================================================
    // METHOD: laporanMasuk (FR-09)
    // =================================================================
    public function laporanMasuk(?string $status = null): array
    {
        if ($this->barangModel === null) {
            return [];
        }
        return $this->barangModel->cariByStatus($status);
    }

    public function hitungRingkasanAdmin(): array
    {
        if ($this->barangModel === null) {
            return [
                'Menunggu Verifikasi' => 0,
                'Disetujui'           => 0,
                'Ditolak'             => 0,
                'Selesai'             => 0,
            ];
        }
        return $this->barangModel->hitungStatusAdmin();
    }

    public function detailLaporanAdmin(int $id_barang): ?Barang
    {
        if ($this->barangModel === null) {
            return null;
        }
        return $this->barangModel->cariById($id_barang);
    }

    // =================================================================
    // METHOD: setujuiLaporan (FR-10)
    // =================================================================
    public function setujuiLaporan(int $id_barang): array
    {
        if ($this->barangModel === null) {
            return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
        }

        $barang = $this->barangModel->cariById($id_barang);

        if ($barang === null) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan.'];
        }

        $berhasil = $barang->setujui();

        if ($berhasil) {
            return ['success' => true, 'message' => 'Laporan berhasil disetujui dan kini tampil di daftar barang.'];
        }

        return ['success' => false, 'message' => 'Gagal menyetujui laporan. Pastikan status saat ini adalah "Menunggu Verifikasi".'];
    }

    // =================================================================
    // METHOD: tolakLaporan (FR-10)
    // =================================================================
    public function tolakLaporan(int $id_barang, string $alasan): array
    {
        if (empty(trim($alasan))) {
            return ['success' => false, 'message' => 'Alasan penolakan wajib diisi.'];
        }

        if ($this->barangModel === null) {
            return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
        }

        $barang = $this->barangModel->cariById($id_barang);

        if ($barang === null) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan.'];
        }

        $berhasil = $barang->tolak($alasan);

        if ($berhasil) {
            return ['success' => true, 'message' => 'Laporan telah ditolak.'];
        }

        return ['success' => false, 'message' => 'Gagal menolak laporan. Pastikan status saat ini adalah "Menunggu Verifikasi".'];
    }

    // =================================================================
    // METHOD: selesaikanLaporan (FR-11 - Admin)
    // =================================================================
    public function selesaikanLaporan(int $id_barang): array
    {
        if ($this->barangModel === null) {
            return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
        }

        $barang = $this->barangModel->cariById($id_barang);

        if ($barang === null) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan.'];
        }

        $berhasil = $barang->selesaikan();

        if ($berhasil) {
            return ['success' => true, 'message' => 'Laporan telah ditandai selesai.'];
        }

        return ['success' => false, 'message' => 'Gagal menandai selesai. Pastikan status saat ini adalah "Disetujui".'];
    }

    // =================================================================
    // METHOD: selesaikanLaporanMahasiswa (Penyelesaian mandiri oleh pemilik)
    // =================================================================
    public function selesaikanLaporanMahasiswa(int $id_barang, int $id_user): array
    {
        if ($this->barangModel === null) {
            return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
        }

        $barang = $this->barangModel->cariById($id_barang);

        if ($barang === null) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan.'];
        }

        if (!$barang->dimilikiOleh($id_user)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak untuk mengubah laporan ini.'];
        }

        if ($barang->status !== 'Disetujui') {
            return ['success' => false, 'message' => 'Hanya laporan berstatus "Disetujui" yang dapat ditandai selesai.'];
        }

        $berhasil = $barang->selesaikan();

        if ($berhasil) {
            return ['success' => true, 'message' => 'Alhamdulillah! Laporan Anda telah berhasil ditandai selesai.'];
        }

        return ['success' => false, 'message' => 'Gagal mengubah status laporan.'];
    }

    // =================================================================
    // METHOD: daftarKategori
    // =================================================================
    public function daftarKategori(): array
    {
        if ($this->kategoriModel === null) {
            return [];
        }
        return $this->kategoriModel->semua();
    }
}
