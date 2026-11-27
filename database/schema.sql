-- ==============================================================
-- DATABASE SCHEMA: ReFindUHO
-- Sistem Informasi Pelaporan & Penelusuran Barang Hilang & Temuan
-- Universitas Halu Oleo (UHO)
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `refinduho` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `refinduho`;

-- --------------------------------------------------------
-- 1. Tabel: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `nim` VARCHAR(20) DEFAULT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('mahasiswa', 'admin') NOT NULL DEFAULT 'mahasiswa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabel: kategori
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel: barang
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `barang` (
    `id_barang` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT NOT NULL,
    `id_kategori` INT NOT NULL,
    `jenis_laporan` ENUM('hilang', 'temuan') NOT NULL,
    `nama_barang` VARCHAR(100) NOT NULL,
    `deskripsi` TEXT NOT NULL,
    `lokasi` VARCHAR(150) NOT NULL,
    `tanggal_kejadian` DATE NOT NULL,
    `kontak` VARCHAR(100) NOT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai') NOT NULL DEFAULT 'Menunggu Verifikasi',
    `alasan_penolakan` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_barang_status_created` (`status`, `created_at`),
    INDEX `idx_barang_user` (`id_user`),
    INDEX `idx_barang_kategori` (`id_kategori`),
    CONSTRAINT `fk_barang_users` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SEED DATA: Kategori Barang Tetap
-- --------------------------------------------------------
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Dokumen dan kartu' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Dokumen dan kartu');
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Elektronik' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Elektronik');
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Tas dan dompet' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Tas dan dompet');
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Pakaian dan aksesori' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Pakaian dan aksesori');
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Kunci' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Kunci');
INSERT INTO `kategori` (`nama_kategori`)
SELECT 'Lainnya' WHERE NOT EXISTS (SELECT 1 FROM `kategori` WHERE `nama_kategori` = 'Lainnya');

-- --------------------------------------------------------
-- SEED DATA: Akun Default Administrator
-- Email   : admin@uho.ac.id
-- Password: admin123 (bcrypt hash)
-- Role    : admin (NIM = NULL)
-- --------------------------------------------------------
INSERT INTO `users` (`nama_lengkap`, `nim`, `email`, `password`, `role`)
SELECT 'Administrator ReFindUHO', NULL, 'admin@uho.ac.id',
       '$2y$10$9A8bcUAIqwxQs506Md2UFO0sJaQjDXLDAIrzjITK/keqGEu1Sg68W', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@uho.ac.id');
