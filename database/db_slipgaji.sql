-- =========================================================
-- SIGAP Payroll — Skema Database
-- Import file ini lewat phpMyAdmin (XAMPP/LAMPP) sebelum
-- menjalankan aplikasi.
-- =========================================================

CREATE DATABASE IF NOT EXISTS db_slipgaji
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE db_slipgaji;

-- ---------------------------------------------------------
-- Tabel: users
-- Menyimpan akun karyawan yang bisa login.
-- Password disimpan dalam bentuk HASH (bcrypt), bukan
-- plaintext, sesuai praktik keamanan yang benar.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  email     VARCHAR(100) NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  nama      VARCHAR(100) NOT NULL,
  nik       VARCHAR(30)  NOT NULL,
  jabatan   VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun demo: email admin@gmail.com, password admin123
-- (password di bawah adalah hasil password_hash('admin123'))
INSERT INTO users (email, password, nama, nik, jabatan) VALUES
('admin@gmail.com', '$2y$10$BlGpO8yQCtvoiY/.mSkH/OiELAVTR9JKIcJUb6HDWwkPPXJoHNxZq',
 'Andi Prasetyo', '3204010101010001', 'Staff Administrasi');

-- ---------------------------------------------------------
-- Tabel: riwayat_gaji
-- Menyimpan setiap slip gaji yang pernah dihitung/dicetak,
-- terhubung ke users lewat foreign key user_id.
-- Berguna sebagai riwayat/histori slip gaji karyawan.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS riwayat_gaji (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  user_id            INT NOT NULL,
  nama               VARCHAR(100) NOT NULL,
  nik                VARCHAR(30)  NOT NULL,
  jabatan            VARCHAR(100) NOT NULL,
  gaji_pokok         DECIMAL(12,2) NOT NULL DEFAULT 0,
  lembur             DECIMAL(12,2) NOT NULL DEFAULT 0,
  pinjaman_karyawan  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_penghasilan  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_potongan     DECIMAL(12,2) NOT NULL DEFAULT 0,
  gaji_bersih        DECIMAL(12,2) NOT NULL DEFAULT 0,
  periode_awal       DATE NOT NULL,
  periode_akhir      DATE NOT NULL,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_riwayat_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: periode_gaji
-- Menyimpan rentang periode (awal-akhir) yang dibuat lewat
-- fitur "Kelola Periode Gaji" di dashboard.php.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS periode_gaji (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  periode_awal  DATE NOT NULL,
  periode_akhir DATE NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_periode_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel: periode_karyawan
-- Karyawan di aplikasi ini TIDAK punya akun/tabel sendiri --
-- datanya berupa data yang sudah pernah diinput lewat slip
-- gaji (tabel riwayat_gaji). Karena itu tabel ini menyimpan
-- SNAPSHOT data karyawan (nama/nik/jabatan/gaji_pokok) yang
-- dipilih untuk suatu periode, bukan referensi ke tabel users.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS periode_karyawan (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  periode_id  INT NOT NULL,
  nama        VARCHAR(100) NOT NULL,
  nik         VARCHAR(30)  NOT NULL,
  jabatan     VARCHAR(100) NOT NULL,
  gaji_pokok  DECIMAL(12,2) NOT NULL DEFAULT 0,
  riwayat_id  INT NULL,
  CONSTRAINT fk_pk_periode
    FOREIGN KEY (periode_id) REFERENCES periode_gaji(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_pk_riwayat
    FOREIGN KEY (riwayat_id) REFERENCES riwayat_gaji(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;
