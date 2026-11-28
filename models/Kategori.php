<?php
/**
 * Model Kategori — ReFindUHO
 * Mengelola data kategori barang (read-only, seed via SQL).
 */

class Kategori
{
    private ?PDO $pdo;

    public int    $id_kategori   = 0;
    public string $nama_kategori = '';

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
        $this->id_kategori   = (int) ($row['id_kategori'] ?? 0);
        $this->nama_kategori = $row['nama_kategori'] ?? '';
    }

    // =================================================================
    // METHOD: semua
    // Mengambil seluruh kategori, diurutkan berdasarkan id_kategori.
    // =================================================================
    public function semua(): array
    {
        if ($this->pdo === null) {
            return [];
        }

        $sql = "SELECT * FROM kategori ORDER BY id_kategori ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    // =================================================================
    // METHOD: cariById
    // Mencari kategori berdasarkan id_kategori.
    // =================================================================
    public function cariById(int $id_kategori): ?self
    {
        if ($this->pdo === null) {
            return null;
        }

        $sql = "SELECT * FROM kategori WHERE id_kategori = :id_kategori LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_kategori' => $id_kategori]);
        $row = $stmt->fetch();

        if ($row) {
            $obj = new self($this->pdo);
            $obj->isiDariArray($row);
            return $obj;
        }

        return null;
    }
}
