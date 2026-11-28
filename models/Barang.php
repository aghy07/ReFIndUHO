<?php
/**
 * Model Barang — ReFindUHO
 * Mengelola data laporan barang hilang & temuan.
 */

class Barang
{
    private ?PDO $pdo;

    // Properti publik
    public int     $id_barang        = 0;
    public int     $id_user          = 0;
    public int     $id_kategori      = 0;
    public string  $jenis_laporan    = 'hilang';
    public string  $nama_barang      = '';
    public string  $deskripsi        = '';
    public string  $lokasi           = '';
    public string  $tanggal_kejadian = '';
    public string  $kontak           = '';
    public ?string $foto             = null;
    public string  $status           = 'Menunggu Verifikasi';
    public ?string $alasan_penolakan = null;
    public string  $created_at       = '';

    // Properti relasi (dari JOIN)
    public string  $nama_pelapor     = '';
    public ?string $nim_pelapor      = null;
    public ?string $email_pelapor    = null;
    public string  $nama_kategori    = '';

    /**
     * @param ?PDO $pdo Koneksi database
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    // -----------------------------------------------------------------
    // HELPER: Isi properti dari array asosiatif
    // -----------------------------------------------------------------
    private function isiDariArray(array $row): void
    {
        $this->id_barang        = (int) ($row['id_barang'] ?? 0);
        $this->id_user          = (int) ($row['id_user'] ?? 0);
        $this->id_kategori      = (int) ($row['id_kategori'] ?? 0);
        $this->jenis_laporan    = $row['jenis_laporan'] ?? 'hilang';
        $this->nama_barang      = $row['nama_barang'] ?? '';
        $this->deskripsi        = $row['deskripsi'] ?? '';
        $this->lokasi           = $row['lokasi'] ?? '';
        $this->tanggal_kejadian = $row['tanggal_kejadian'] ?? '';
        $this->kontak           = $row['kontak'] ?? '';
        $this->foto             = $row['foto'] ?? null;
        $this->status           = $row['status'] ?? 'Menunggu Verifikasi';
        $this->alasan_penolakan = $row['alasan_penolakan'] ?? null;
        $this->created_at       = $row['created_at'] ?? '';

        // Kolom relasi (dari JOIN)
        $this->nama_pelapor     = $row['nama_lengkap'] ?? '';
        $this->nim_pelapor      = $row['nim_pelapor'] ?? ($row['nim'] ?? null);
        $this->email_pelapor    = $row['email_pelapor'] ?? ($row['email'] ?? null);
        $this->nama_kategori    = $row['nama_kategori'] ?? '';
    }

    // =================================================================
    // METHOD: simpan
    // Menyimpan laporan barang baru ke database.
    // Status awal otomatis 'Menunggu Verifikasi' (BR-04).
    // =================================================================
    public function simpan(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        $this->nama_barang = trim($this->nama_barang);
        $this->deskripsi   = trim($this->deskripsi);
        $this->lokasi      = trim($this->lokasi);
        $this->kontak      = trim($this->kontak);

        if (
            $this->id_user <= 0
            || $this->id_kategori <= 0
            || !in_array($this->jenis_laporan, ['hilang', 'temuan'], true)
            || $this->nama_barang === ''
            || $this->deskripsi === ''
            || $this->lokasi === ''
            || $this->tanggal_kejadian === ''
            || $this->kontak === ''
        ) {
            return false;
        }

        $sql = "INSERT INTO barang
                    (id_user, id_kategori, jenis_laporan, nama_barang, deskripsi,
                     lokasi, tanggal_kejadian, kontak, foto, status)
                VALUES
                    (:id_user, :id_kategori, :jenis_laporan, :nama_barang, :deskripsi,
                     :lokasi, :tanggal_kejadian, :kontak, :foto, 'Menunggu Verifikasi')";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':id_user'          => $this->id_user,
            ':id_kategori'      => $this->id_kategori,
            ':jenis_laporan'    => $this->jenis_laporan,
            ':nama_barang'      => $this->nama_barang,
            ':deskripsi'        => $this->deskripsi,
            ':lokasi'           => $this->lokasi,
            ':tanggal_kejadian' => $this->tanggal_kejadian,
            ':kontak'           => $this->kontak,
            ':foto'             => $this->foto,
        ]);

        if ($result) {
            $this->id_barang = (int) $this->pdo->lastInsertId();
        }

        return $result;
    }

    // =================================================================
    // METHOD: setujui
    // Mengubah status laporan menjadi 'Disetujui'.
    // Hanya berlaku jika status saat ini 'Menunggu Verifikasi'.
    // =================================================================
    public function setujui(): bool
    {
        if ($this->pdo === null || $this->status !== 'Menunggu Verifikasi') {
            return false;
        }

        $sql = "UPDATE barang
                SET status = 'Disetujui', alasan_penolakan = NULL
                WHERE id_barang = :id_barang
                  AND status = 'Menunggu Verifikasi'";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id_barang' => $this->id_barang]);

        if ($result && $stmt->rowCount() === 1) {
            $this->status           = 'Disetujui';
            $this->alasan_penolakan = null;
            return true;
        }

        return false;
    }

    // =================================================================
    // METHOD: tolak
    // Mengubah status laporan menjadi 'Ditolak' dengan alasan wajib.
    // Hanya berlaku jika status saat ini 'Menunggu Verifikasi'.
    // Status 'Ditolak' bersifat akhir (final).
    // =================================================================
    public function tolak(string $alasan): bool
    {
        if ($this->pdo === null || $this->status !== 'Menunggu Verifikasi') {
            return false;
        }

        if (empty(trim($alasan))) {
            return false;
        }

        $sql = "UPDATE barang
                SET status = 'Ditolak', alasan_penolakan = :alasan
                WHERE id_barang = :id_barang
                  AND status = 'Menunggu Verifikasi'";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':alasan'    => trim($alasan),
            ':id_barang' => $this->id_barang,
        ]);

        if ($result && $stmt->rowCount() === 1) {
            $this->status           = 'Ditolak';
            $this->alasan_penolakan = trim($alasan);
            return true;
        }

        return false;
    }

    // =================================================================
    // METHOD: selesaikan
    // Mengubah status laporan menjadi 'Selesai'.
    // Hanya berlaku jika status saat ini 'Disetujui'.
    // =================================================================
    public function selesaikan(): bool
    {
        if ($this->pdo === null || $this->status !== 'Disetujui') {
            return false;
        }

        $sql = "UPDATE barang
                SET status = 'Selesai'
                WHERE id_barang = :id_barang
                  AND status = 'Disetujui'";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':id_barang' => $this->id_barang]);

        if ($result && $stmt->rowCount() === 1) {
            $this->status = 'Selesai';
            return true;
        }

        return false;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function tampilDiDaftar(): bool
    {
        return $this->status === 'Disetujui';
    }

    public function dimilikiOleh(int $id_user): bool
    {
        return $this->id_user === $id_user;
    }

    // =================================================================
    // METHOD: cariById
    // Mencari satu laporan barang berdasarkan id_barang lengkap dengan
    // detail relasi pengguna pelapor dan kategori.
    // =================================================================
    public function cariById(int $id_barang): ?self
    {
        if ($this->pdo === null) {
            return null;
        }

        $sql = "SELECT b.*, u.nama_lengkap, u.nim AS nim_pelapor, u.email AS email_pelapor, k.nama_kategori
                FROM barang b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                WHERE b.id_barang = :id_barang
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_barang' => $id_barang]);
        $row = $stmt->fetch();

        if ($row) {
            $obj = new self($this->pdo);
            $obj->isiDariArray($row);
            return $obj;
        }

        return null;
    }

    // =================================================================
    // METHOD: cariDisetujui
    // Mengambil laporan berstatus 'Disetujui' dengan pagination dan filter.
    // =================================================================
    public function cariDisetujui(
        string $keyword = '',
        ?int $id_kategori = null,
        ?string $jenis = null,
        int $halaman = 1,
        int $perHalaman = 10
    ): array {
        if ($this->pdo === null) {
            return [
                'data'         => [],
                'total'        => 0,
                'halaman'      => 1,
                'totalHalaman' => 1,
                'perHalaman'   => $perHalaman,
            ];
        }

        $halaman = max(1, $halaman);
        $perHalaman = max(1, min($perHalaman, 100));
        $where  = "WHERE b.status = 'Disetujui'";
        $params = [];

        // Filter kata kunci
        if (!empty(trim($keyword))) {
            $where .= " AND b.nama_barang LIKE :keyword";
            $params[':keyword'] = '%' . trim($keyword) . '%';
        }

        // Filter kategori
        if ($id_kategori !== null && $id_kategori > 0) {
            $where .= " AND b.id_kategori = :id_kategori";
            $params[':id_kategori'] = $id_kategori;
        }

        // Filter jenis laporan
        if ($jenis !== null && in_array($jenis, ['hilang', 'temuan'], true)) {
            $where .= " AND b.jenis_laporan = :jenis";
            $params[':jenis'] = $jenis;
        }

        // Hitung total data
        $sqlCount = "SELECT COUNT(*) FROM barang b $where";
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        // Hitung pagination
        $totalHalaman = ($total > 0) ? (int) ceil($total / $perHalaman) : 1;
        $halaman = max(1, min($halaman, $totalHalaman));
        $offset  = ($halaman - 1) * $perHalaman;

        // Ambil data dengan JOIN
        $sql = "SELECT b.*, u.nama_lengkap, k.nama_kategori
                FROM barang b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                $where
                ORDER BY b.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll() ?: [],
            'total'        => $total,
            'halaman'      => $halaman,
            'totalHalaman' => $totalHalaman,
            'perHalaman'   => $perHalaman,
        ];
    }

    // =================================================================
    // METHOD: cariTerbaruDisetujui
    // Mengambil N laporan barang terbaru yang berstatus Disetujui
    // untuk ditampilkan di Beranda / Homepage.
    // =================================================================
    public function cariTerbaruDisetujui(int $limit = 6): array
    {
        if ($this->pdo === null) {
            return [];
        }

        $sql = "SELECT b.*, u.nama_lengkap, k.nama_kategori
                FROM barang b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                WHERE b.status = 'Disetujui'
                ORDER BY b.created_at DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    // =================================================================
    // METHOD: cariByPemilik
    // Mengambil seluruh laporan milik seorang pengguna (BR-07).
    // =================================================================
    public function cariByPemilik(int $id_user): array
    {
        if ($this->pdo === null) {
            return [];
        }

        $sql = "SELECT b.*, u.nama_lengkap, k.nama_kategori
                FROM barang b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori
                WHERE b.id_user = :id_user
                ORDER BY b.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        return $stmt->fetchAll() ?: [];
    }

    // =================================================================
    // METHOD: cariByStatus
    // Mengambil laporan berdasarkan status tertentu (untuk admin).
    // =================================================================
    public function cariByStatus(?string $status = null): array
    {
        if ($this->pdo === null) {
            return [];
        }

        $allowedStatus = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai'];
        if ($status !== null && $status !== '' && $status !== 'Semua' && !in_array($status, $allowedStatus, true)) {
            return [];
        }

        $sql = "SELECT b.*, u.nama_lengkap, k.nama_kategori
                FROM barang b
                JOIN users u ON b.id_user = u.id_user
                JOIN kategori k ON b.id_kategori = k.id_kategori";

        $params = [];

        if (!empty($status) && $status !== 'Semua') {
            $sql .= " WHERE b.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    // =================================================================
    // METHOD: hitungStatusAdmin
    // Mengambil jumlah agregat tiap status untuk ringkasan kartu admin.
    // =================================================================
    public function hitungStatusAdmin(): array
    {
        $hasil = [
            'Menunggu Verifikasi' => 0,
            'Disetujui'           => 0,
            'Ditolak'             => 0,
            'Selesai'             => 0,
        ];

        if ($this->pdo === null) {
            return $hasil;
        }

        $sql = "SELECT status, COUNT(*) as total FROM barang GROUP BY status";
        $stmt = $this->pdo->query($sql);
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                if (isset($hasil[$row['status']])) {
                    $hasil[$row['status']] = (int) $row['total'];
                }
            }
        }

        return $hasil;
    }
}
