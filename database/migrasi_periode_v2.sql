-- =========================================================
-- [ARSIP] Migrasi v2: perbaikan skema periode_karyawan
-- ---------------------------------------------------------
-- SUDAH DIGABUNG ke database/db_slipgaji.sql dengan skema yang
-- benar. Untuk setup baru (PC baru, dsb) file ini TIDAK PERLU
-- dijalankan -- cukup import db_slipgaji.sql saja.
--
-- File ini hanya disimpan untuk referensi/riwayat: dulu dipakai
-- untuk memperbaiki skema periode_karyawan versi lama (yang
-- kolomnya salah mereferensikan tabel users) menjadi versi yang
-- sudah benar (snapshot nama/nik/jabatan/gaji_pokok).
-- =========================================================
USE db_slipgaji;

DROP TABLE IF EXISTS periode_karyawan;

CREATE TABLE periode_karyawan (
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
