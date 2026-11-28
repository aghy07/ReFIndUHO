<?php
/**
 * Model User — ReFindUHO
 * Mengelola data pengguna (mahasiswa & admin).
 */

class User
{
    private ?PDO $pdo;

    // Properti publik (diisi dari data row database)
    public int     $id_user      = 0;
    public string  $nama_lengkap = '';
    public ?string $nim          = null;
    public string  $email        = '';
    public string  $password     = '';
    public string  $role         = 'mahasiswa';
    public string  $created_at   = '';

    /**
     * @param ?PDO $pdo Koneksi database
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    // -----------------------------------------------------------------
    // HELPER: Isi properti dari array asosiatif hasil fetch
    // -----------------------------------------------------------------
    private function isiDariArray(array $row): void
    {
        $this->id_user      = (int) ($row['id_user'] ?? 0);
        $this->nama_lengkap = $row['nama_lengkap'] ?? '';
        $this->nim          = !empty($row['nim']) ? strtoupper(trim($row['nim'])) : null;
        $this->email        = strtolower(trim($row['email'] ?? ''));
        $this->password     = $row['password'] ?? '';
        $this->role         = $row['role'] ?? 'mahasiswa';
        $this->created_at   = $row['created_at'] ?? '';
    }

    // =================================================================
    // METHOD: isAdmin
    // Mengembalikan true jika role pengguna adalah 'admin'.
    // =================================================================
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // =================================================================
    // METHOD: cekPassword
    // Memverifikasi kata sandi plain terhadap hash yang tersimpan.
    // =================================================================
    public function cekPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }

    // =================================================================
    // METHOD: simpan
    // Menyimpan pengguna baru ke database.
    // Kata sandi di-hash dengan password_hash() sebelum disimpan.
    // =================================================================
    public function simpan(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        $this->nama_lengkap = trim($this->nama_lengkap);
        $this->nim          = ($this->nim === null || trim($this->nim) === '') ? null : strtoupper(trim($this->nim));
        $this->email        = strtolower(trim($this->email));
        $this->password     = trim($this->password);

        if (
            $this->nama_lengkap === ''
            || $this->email === ''
            || $this->password === ''
            || !in_array($this->role, ['mahasiswa', 'admin'], true)
        ) {
            return false;
        }

        $sql = "INSERT INTO users (nama_lengkap, nim, email, password, role)
                VALUES (:nama_lengkap, :nim, :email, :password, :role)";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':nama_lengkap' => $this->nama_lengkap,
            ':nim'          => $this->nim,
            ':email'        => $this->email,
            ':password'     => password_hash($this->password, PASSWORD_BCRYPT),
            ':role'         => $this->role,
        ]);

        if ($result) {
            $this->id_user = (int) $this->pdo->lastInsertId();
        }

        return $result;
    }

    // =================================================================
    // METHOD: cariByIdentitas
    // Mencari pengguna berdasarkan NIM atau Email (untuk login).
    // Mahasiswa bisa login dengan NIM atau email; admin dengan email.
    // =================================================================
    public function cariByIdentitas(string $identitas): ?self
    {
        if ($this->pdo === null) {
            return null;
        }

        $identitas = trim($identitas);
        if ($identitas === '') {
            return null;
        }

        $sql = "SELECT * FROM users WHERE nim = :identitas OR email = :identitas2 LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':identitas'  => strtoupper($identitas),
            ':identitas2' => strtolower($identitas),
        ]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new self($this->pdo);
            $user->isiDariArray($row);
            return $user;
        }

        return null;
    }

    // =================================================================
    // METHOD: cariById
    // Mencari pengguna berdasarkan id_user (Primary Key).
    // =================================================================
    public function cariById(int $id_user): ?self
    {
        if ($this->pdo === null) {
            return null;
        }

        $sql = "SELECT * FROM users WHERE id_user = :id_user LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_user' => $id_user]);
        $row = $stmt->fetch();

        if ($row) {
            $user = new self($this->pdo);
            $user->isiDariArray($row);
            return $user;
        }

        return null;
    }

    // =================================================================
    // METHOD: nimAtauEmailTerdaftar
    // Memeriksa apakah NIM atau email sudah terdaftar di database.
    // Mengembalikan array berisi field mana yang duplikat.
    // =================================================================
    public function nimAtauEmailTerdaftar(string $nim, string $email): array
    {
        $duplikat = [];
        if ($this->pdo === null) {
            return $duplikat;
        }

        // Cek NIM
        $nim = trim($nim);
        if (!empty($nim)) {
            $sql = "SELECT COUNT(*) FROM users WHERE nim = :nim";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':nim' => strtoupper($nim)]);
            if ((int) $stmt->fetchColumn() > 0) {
                $duplikat[] = 'nim';
            }
        }

        // Cek Email
        $email = strtolower(trim($email));
        if (!empty($email)) {
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            if ((int) $stmt->fetchColumn() > 0) {
                $duplikat[] = 'email';
            }
        }

        return $duplikat;
    }
}
