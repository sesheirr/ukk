-- =========================================================
-- [ARSIP] Migrasi: fitur Periode Gaji
-- ---------------------------------------------------------
-- SUDAH DIGABUNG ke database/db_slipgaji.sql. Untuk setup baru
-- (PC baru, dsb) CUKUP import db_slipgaji.sql saja -- file ini
-- TIDAK PERLU dijalankan lagi.
--
-- File ini hanya disimpan untuk referensi/riwayat, dan untuk
-- kasus lama: kalau kamu punya database db_slipgaji yang
-- di-import SEBELUM tabel periode_gaji & periode_karyawan
-- digabung ke db_slipgaji.sql (datanya mau tetap dipakai, tidak
-- mau import ulang dari nol), baru jalankan file ini lewat tab
-- SQL di phpMyAdmin untuk menambah 2 tabel itu.
-- =========================================================
USE db_slipgaji;

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

-- Catatan: karyawan di aplikasi ini TIDAK punya akun/tabel sendiri --
-- datanya berupa data yang sudah pernah diinput lewat slip gaji
-- (tabel riwayat_gaji). Karena itu periode_karyawan menyimpan
-- SNAPSHOT data karyawan (nama/nik/jabatan/gaji_pokok) yang dipilih
-- untuk periode tsb, bukan referensi ke tabel users.
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
