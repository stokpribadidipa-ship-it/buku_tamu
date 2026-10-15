-- =====================================================
-- Database: db_buku_tamu
-- Sistem Buku Tamu & Registrasi Pengunjung Kantor
-- Role: Resepsionis, Keamanan (Satpam), Admin
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_buku_tamu;
USE db_buku_tamu;

-- =====================================================
-- Tabel: tb_users
-- =====================================================
CREATE TABLE tb_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'resepsionis', 'keamanan') NOT NULL DEFAULT 'resepsionis',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Tabel: tb_staf
-- =====================================================
CREATE TABLE tb_staf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_staf VARCHAR(100) NOT NULL,
    departemen VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Tabel: tb_tamu
-- =====================================================
CREATE TABLE tb_tamu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tamu VARCHAR(100) NOT NULL,
    no_identitas VARCHAR(50) NOT NULL,
    no_telepon VARCHAR(20) NOT NULL,
    instansi VARCHAR(100) DEFAULT NULL,
    staf_dituju_id INT NOT NULL,
    no_badge VARCHAR(20) NOT NULL,
    keperluan VARCHAR(255) DEFAULT NULL,
    waktu_checkin DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    waktu_checkout DATETIME DEFAULT NULL,
    status ENUM('di_dalam', 'sudah_keluar') NOT NULL DEFAULT 'di_dalam',
    dicatat_oleh INT NOT NULL,
    diproses_keluar_oleh INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_tamu_staf
        FOREIGN KEY (staf_dituju_id) REFERENCES tb_staf(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_tamu_dicatat_oleh
        FOREIGN KEY (dicatat_oleh) REFERENCES tb_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_tamu_diproses_keluar_oleh
        FOREIGN KEY (diproses_keluar_oleh) REFERENCES tb_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nomor identitas harus unik agar satu NIK/SIM tidak tercatat lebih dari sekali.
CREATE UNIQUE INDEX uq_tamu_no_identitas ON tb_tamu(no_identitas);

CREATE INDEX idx_tamu_status ON tb_tamu(status);
CREATE INDEX idx_tamu_waktu_checkin ON tb_tamu(waktu_checkin);

-- =====================================================
-- Data awal (sample/seed)
-- Password: admin123 / resepsionis123 / satpam123
-- (hash di bawah dibuat dengan password_hash() PHP — ganti sebelum produksi)
-- =====================================================
INSERT INTO tb_users (nama, email, password, role) VALUES
('Admin Kantor', 'admin@kantor.com', '$2b$10$6hM4dCNa68k0CrrVD08xsOBlwDDUuz0eBS4gPAIrQRvvn6XYIjVu.', 'admin'),
('Siti Resepsionis', 'resepsionis@kantor.com', '$2b$10$0gaxYq9rsttZys9LHxCrj.ACWIfOoY5eEJ/Bry/mSvXnSv7v1a5ZS', 'resepsionis'),
('Budi Satpam', 'satpam@kantor.com', '$2b$10$rR6xB97nHdv2MOuEZSER.e/5/ucXctHmYZtmtWf/beWcyWibxIZOq', 'keamanan');

INSERT INTO tb_staf (nama_staf, departemen) VALUES
('Andi Wijaya', 'Keuangan'),
('Rina Pratiwi', 'Human Resources'),
('Dedi Kurniawan', 'IT Support'),
('Lusi Marlina', 'Marketing');

-- id 1=Admin Kantor, 2=Siti Resepsionis (dicatat_oleh), 3=Budi Satpam (diproses_keluar_oleh)
INSERT INTO tb_tamu
(nama_tamu, no_identitas, no_telepon, instansi, staf_dituju_id, no_badge, keperluan, waktu_checkin, waktu_checkout, status, dicatat_oleh, diproses_keluar_oleh)
VALUES
('Hendra Saputra', '3521010101900001', '081234567890', 'PT Maju Jaya', 1, 'B-01', 'Meeting kontrak kerja sama', NOW() - INTERVAL 3 HOUR, NULL, 'di_dalam', 2, NULL),
('Dewi Anggraini', '3521010202900002', '081298765432', 'CV Sentosa', 3, 'B-02', 'Instalasi perangkat', NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 3 HOUR, 'sudah_keluar', 2, 3);

-- =====================================================
-- Query referensi yang dipakai aplikasi (sudah diimplementasikan di PHP)
-- =====================================================

-- 1. Dashboard Keamanan: daftar tamu yang masih di dalam gedung
-- SELECT t.id, t.nama_tamu, t.no_badge, s.nama_staf, t.waktu_checkin
-- FROM tb_tamu t
-- JOIN tb_staf s ON t.staf_dituju_id = s.id
-- WHERE t.status = 'di_dalam'
-- ORDER BY t.waktu_checkin ASC;

-- 2. Proses Check-out (dilakukan oleh Keamanan)
-- UPDATE tb_tamu
-- SET waktu_checkout = NOW(), status = 'sudah_keluar', diproses_keluar_oleh = :user_id_satpam
-- WHERE id = :id_tamu AND status = 'di_dalam';

-- 3. Log harian tamu (filter per tanggal, dipakai di admin/laporan.php)
-- SELECT t.*, s.nama_staf
-- FROM tb_tamu t
-- JOIN tb_staf s ON t.staf_dituju_id = s.id
-- WHERE DATE(t.waktu_checkin) = :tanggal
-- ORDER BY t.waktu_checkin DESC;


-- SELECT id, nama, email, role
-- FROM tb_users
-- ORDER BY id ASC;
