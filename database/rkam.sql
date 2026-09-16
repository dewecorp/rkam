-- =============================================
-- SIRKAM v1.0 | MySQL 5.7+ / MariaDB 10.2+
-- Charset utf8mb4 | Engine InnoDB
-- Cara pakai: buat db rkam_db lalu import file ini
-- =============================================
CREATE DATABASE IF NOT EXISTS rkam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rkam_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','kepala_madrasah','bendahara','operator','viewer') NOT NULL DEFAULT 'operator',
  email VARCHAR(100) NULL,
  no_hp VARCHAR(20) NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_role (role),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- madrasah (1 baris id=1) ----------
CREATE TABLE IF NOT EXISTS madrasah (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_madrasah VARCHAR(150) NOT NULL DEFAULT 'MI Salafiyah',
  nsm VARCHAR(30) NULL,
  npsn VARCHAR(30) NULL,
  alamat TEXT NULL,
  desa VARCHAR(100) NULL,
  kecamatan VARCHAR(100) NULL,
  kabupaten VARCHAR(100) NULL,
  provinsi VARCHAR(100) NULL,
  kode_pos VARCHAR(10) NULL,
  email VARCHAR(100) NULL,
  telepon VARCHAR(30) NULL,
  nama_kepala VARCHAR(100) NULL,
  nip_kepala VARCHAR(50) NULL,
  nama_bendahara VARCHAR(100) NULL,
  nip_bendahara VARCHAR(50) NULL,
  logo VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- tahun_anggaran ----------
CREATE TABLE IF NOT EXISTS tahun_anggaran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tahun INT NOT NULL UNIQUE,
  tanggal_mulai DATE NULL,
  tanggal_selesai DATE NULL,
  status ENUM('draft','aktif','selesai','dikunci') NOT NULL DEFAULT 'draft',
  keterangan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tahun (tahun),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- bidang ----------
CREATE TABLE IF NOT EXISTS bidang (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NULL,
  nama_bidang VARCHAR(150) NOT NULL,
  keterangan TEXT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- satuan ----------
CREATE TABLE IF NOT EXISTS satuan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NULL,
  nama_satuan VARCHAR(50) NOT NULL,
  keterangan TEXT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- sumber_dana ----------
CREATE TABLE IF NOT EXISTS sumber_dana (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama_sumber_dana VARCHAR(100) NOT NULL,
  keterangan TEXT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- jenis_belanja ----------
CREATE TABLE IF NOT EXISTS jenis_belanja (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NULL,
  nama VARCHAR(100) NOT NULL,
  keterangan TEXT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rekening ----------
CREATE TABLE IF NOT EXISTS rekening (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(50) NOT NULL UNIQUE,
  nama_rekening VARCHAR(150) NOT NULL,
  kelompok VARCHAR(100) NULL,
  jenis_belanja_id INT UNSIGNED NULL,
  keterangan TEXT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (jenis_belanja_id) REFERENCES jenis_belanja(id) ON DELETE SET NULL,
  INDEX idx_kode (kode),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- kegiatan master ----------
CREATE TABLE IF NOT EXISTS kegiatan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(50) NOT NULL UNIQUE,
  nama_kegiatan VARCHAR(255) NOT NULL,
  bidang_id INT UNSIGNED NULL,
  indikator TEXT NULL,
  tujuan TEXT NULL,
  sasaran VARCHAR(255) NULL,
  volume DECIMAL(18,2) NULL DEFAULT 1,
  satuan_id INT UNSIGNED NULL,
  waktu_pelaksanaan VARCHAR(100) NULL,
  penanggung_jawab VARCHAR(100) NULL,
  prioritas ENUM('rendah','sedang','tinggi','mendesak') NOT NULL DEFAULT 'sedang',
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  keterangan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bidang_id) REFERENCES bidang(id) ON DELETE SET NULL,
  FOREIGN KEY (satuan_id) REFERENCES satuan(id) ON DELETE SET NULL,
  INDEX idx_bidang (bidang_id),
  INDEX idx_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rkam (header) ----------
CREATE TABLE IF NOT EXISTS rkam (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_dokumen VARCHAR(100) NULL UNIQUE,
  tahun_id INT UNSIGNED NOT NULL,
  bidang_id INT UNSIGNED NULL,
  kegiatan_id INT UNSIGNED NULL,
  kode_kegiatan VARCHAR(50) NULL,
  nama_kegiatan VARCHAR(255) NOT NULL,
  tujuan TEXT NULL,
  sasaran VARCHAR(255) NULL,
  indikator TEXT NULL,
  penanggung_jawab VARCHAR(100) NULL,
  waktu_pelaksanaan VARCHAR(100) NULL,
  prioritas ENUM('rendah','sedang','tinggi','mendesak') NOT NULL DEFAULT 'sedang',
  keterangan TEXT NULL,
  total_anggaran DECIMAL(18,2) NOT NULL DEFAULT 0,
  status ENUM('draft','diajukan','diverifikasi','disetujui','ditolak','direvisi','dikunci') NOT NULL DEFAULT 'draft',
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tahun_id) REFERENCES tahun_anggaran(id) ON DELETE RESTRICT,
  FOREIGN KEY (bidang_id) REFERENCES bidang(id) ON DELETE SET NULL,
  FOREIGN KEY (kegiatan_id) REFERENCES kegiatan(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_tahun (tahun_id),
  INDEX idx_bidang (bidang_id),
  INDEX idx_status (status),
  INDEX idx_nama (nama_kegiatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rkam_items ----------
CREATE TABLE IF NOT EXISTS rkam_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rkam_id INT UNSIGNED NOT NULL,
  rekening_id INT UNSIGNED NULL,
  kode_rekening VARCHAR(50) NULL,
  uraian VARCHAR(255) NOT NULL,
  volume DECIMAL(18,2) NOT NULL DEFAULT 1,
  satuan_id INT UNSIGNED NULL,
  satuan_text VARCHAR(50) NULL,
  harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
  jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
  keterangan TEXT NULL,
  FOREIGN KEY (rkam_id) REFERENCES rkam(id) ON DELETE CASCADE,
  FOREIGN KEY (rekening_id) REFERENCES rekening(id) ON DELETE SET NULL,
  FOREIGN KEY (satuan_id) REFERENCES satuan(id) ON DELETE SET NULL,
  INDEX idx_rkam (rkam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rkam_sumber_dana ----------
CREATE TABLE IF NOT EXISTS rkam_sumber_dana (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rkam_id INT UNSIGNED NOT NULL,
  sumber_dana_id INT UNSIGNED NOT NULL,
  jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
  keterangan VARCHAR(255) NULL,
  FOREIGN KEY (rkam_id) REFERENCES rkam(id) ON DELETE CASCADE,
  FOREIGN KEY (sumber_dana_id) REFERENCES sumber_dana(id) ON DELETE RESTRICT,
  UNIQUE KEY uq_rkam_sumber (rkam_id, sumber_dana_id),
  INDEX idx_rkam (rkam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rkam_status_history ----------
CREATE TABLE IF NOT EXISTS rkam_status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rkam_id INT UNSIGNED NOT NULL,
  status_from VARCHAR(20) NULL,
  status_to VARCHAR(20) NOT NULL,
  catatan TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rkam_id) REFERENCES rkam(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_rkam (rkam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- realisasi ----------
CREATE TABLE IF NOT EXISTS realisasi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rkam_id INT UNSIGNED NOT NULL,
  tahun_id INT UNSIGNED NULL,
  tanggal_transaksi DATE NOT NULL,
  nomor_bukti VARCHAR(100) NULL,
  sumber_dana_id INT UNSIGNED NULL,
  rekening_id INT UNSIGNED NULL,
  kode_rekening VARCHAR(50) NULL,
  uraian VARCHAR(255) NOT NULL,
  volume DECIMAL(18,2) NOT NULL DEFAULT 1,
  satuan VARCHAR(50) NULL,
  harga DECIMAL(18,2) NOT NULL DEFAULT 0,
  jumlah DECIMAL(18,2) NOT NULL DEFAULT 0,
  penerima_vendor VARCHAR(150) NULL,
  nomor_nota VARCHAR(100) NULL,
  keterangan TEXT NULL,
  bukti_file VARCHAR(255) NULL,
  allow_overbudget TINYINT(1) NOT NULL DEFAULT 0,
  overbudget_reason TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (rkam_id) REFERENCES rkam(id) ON DELETE RESTRICT,
  FOREIGN KEY (sumber_dana_id) REFERENCES sumber_dana(id) ON DELETE SET NULL,
  FOREIGN KEY (rekening_id) REFERENCES rekening(id) ON DELETE SET NULL,
  INDEX idx_rkam (rkam_id),
  INDEX idx_tanggal (tanggal_transaksi),
  INDEX idx_tahun (tahun_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- activity_logs ----------
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  aktivitas VARCHAR(50) NOT NULL,
  modul VARCHAR(50) NOT NULL,
  record_id INT UNSIGNED NULL,
  data_before MEDIUMTEXT NULL,
  data_after MEDIUMTEXT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_modul (modul),
  INDEX idx_user (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- notifications ----------
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  role_target VARCHAR(30) NULL,
  judul VARCHAR(150) NOT NULL,
  pesan TEXT NULL,
  modul VARCHAR(50) NULL,
  record_id INT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- settings ----------
CREATE TABLE IF NOT EXISTS app_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skey VARCHAR(100) NOT NULL UNIQUE,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- login_attempts ----------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NULL,
  ip VARCHAR(45) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (username),
  INDEX idx_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =============================================
-- SEED
-- password semua: admin123 / kepala123 / bendahara123 / operator123 / viewer123
-- hash bcrypt (password_hash PHP, cost 12)
-- =============================================
INSERT INTO madrasah (id,nama_madrasah,nsm,npsn,alamat,desa,kecamatan,kabupaten,provinsi,kode_pos,email,telepon,nama_kepala,nip_kepala,nama_bendahara,nip_bendahara)
VALUES (1,'MI Salafiyah Falah','111234560001','60700001','Jl. Pendidikan No. 10','Sukamaju','Banyuresmi','Garut','Jawa Barat','44191','mi@example.sch.id','(0262) 000000','H. Ahmad Suryana','196501012000031001','Siti Aminah','198001012010012001')
ON DUPLICATE KEY UPDATE nama_madrasah=VALUES(nama_madrasah);

INSERT INTO tahun_anggaran (id,tahun,tanggal_mulai,tanggal_selesai,status,keterangan) VALUES
(1,2025,'2025-01-01','2025-12-31','selesai','Tahun berjalan lalu'),
(2,2026,'2026-01-01','2026-12-31','aktif','Tahun aktif')
ON DUPLICATE KEY UPDATE status=VALUES(status);

INSERT INTO bidang (kode,nama_bidang,keterangan,status) VALUES
('B01','Manajemen Madrasah','', 'aktif'),
('B02','Pengembangan Kurikulum','', 'aktif'),
('B03','Kesiswaan','', 'aktif'),
('B04','Sarana dan Prasarana','', 'aktif'),
('B05','Pendidik dan Tenaga Kependidikan','', 'aktif'),
('B06','Kegiatan Pembelajaran','', 'aktif'),
('B07','Kegiatan Keagamaan','', 'aktif'),
('B08','Ekstrakurikuler','', 'aktif'),
('B09','Perpustakaan','', 'aktif'),
('B10','UKS','', 'aktif'),
('B11','Pramuka','', 'aktif'),
('B12','Administrasi','', 'aktif'),
('B13','Pemeliharaan','', 'aktif'),
('B99','Lainnya','', 'aktif');

INSERT INTO sumber_dana (kode,nama_sumber_dana,keterangan,status) VALUES
('BOS','BOS','Bantuan Operasional Sekolah','aktif'),
('BOP','BOP','Bantuan Operasional Pendidikan','aktif'),
('KOM','Komite','Iuran Komite','aktif'),
('YSN','Yayasan','Dana Yayasan','aktif'),
('MDR','Dana Mandiri','', 'aktif'),
('SMB','Sumbangan','', 'aktif'),
('HBH','Hibah','', 'aktif'),
('LLN','Lainnya','', 'aktif');

INSERT INTO jenis_belanja (kode,nama,keterangan,status) VALUES
('JB01','Belanja Barang','','aktif'),
('JB02','Belanja Jasa','','aktif'),
('JB03','Belanja Pemeliharaan','','aktif'),
('JB04','Belanja Kegiatan','','aktif'),
('JB05','Belanja Honorarium','','aktif'),
('JB06','Belanja Modal','','aktif'),
('JB07','Belanja ATK','','aktif'),
('JB08','Belanja Transportasi','','aktif'),
('JB09','Belanja Konsumsi','','aktif'),
('JB10','Belanja Lainnya','','aktif');

INSERT INTO satuan (kode,nama_satuan,status) VALUES
('unit','unit','aktif'),('buah','buah','aktif'),('rim','rim','aktif'),
('paket','paket','aktif'),('orang','orang','aktif'),('kegiatan','kegiatan','aktif'),
('bulan','bulan','aktif'),('hari','hari','aktif'),('meter','meter','aktif'),
('liter','liter','aktif'),('set','set','aktif'),('lembar','lembar','aktif'),('lainnya','lainnya','aktif');

INSERT INTO rekening (kode,nama_rekening,kelompok,jenis_belanja_id,keterangan,status) VALUES
('5.1.01','Belanja ATK','Belanja Barang',7,'','aktif'),
('5.1.02','Belanja Konsumsi','Belanja Barang',9,'','aktif'),
('5.1.03','Belanja Fotokopi/Cetak','Belanja Jasa',2,'','aktif'),
('5.2.01','Belanja Honor Narasumber','Belanja Honorarium',5,'','aktif'),
('5.2.02','Belanja Transport','Belanja Transportasi',8,'','aktif'),
('5.3.01','Belanja Modal Peralatan','Belanja Modal',6,'','aktif'),
('5.3.02','Belanja Pemeliharaan Gedung','Belanja Pemeliharaan',3,'','aktif');

-- user seed: hash digenerate via install (lihat README). hash bawah = admin123 dst (cost 10, stabil).
-- Jika import gagal verifikasi, jalankan: UPDATE users SET password='$2y$10$...' dst atau reset via tools/reset-password.php
INSERT INTO users (nama,username,password,role,email,status,must_change_password) VALUES
('Super Admin','superadmin','$2y$12$gf/tVWP6fsQ3DlY6pPDPSunwzLuKA8CHj3IKeYkxwio9Mi6w.4pkW','superadmin','admin@madrasah.sch.id','aktif',1),
('Kepala Madrasah','kepala','$2y$12$SAM4yCr4PjmkDenU4CENieyWwfCQZ0HiG6HU1VLp/2M.D.dOOxI6q','kepala_madrasah','kepala@madrasah.sch.id','aktif',1),
('Bendahara','bendahara','$2y$12$Z/Bi1UPnqPr6dgcjgEp2mOc4uKR8SN2eY9vYHO6Wa5KFF/NOmWqca','bendahara','bendahara@madrasah.sch.id','aktif',1),
('Operator','operator','$2y$12$dka0Qs4pe04OPMLsGP.mvelpaZSVIC2JGKz8ymswWNwuWNDYVhvf6','operator','operator@madrasah.sch.id','aktif',1),
('Viewer','viewer','$2y$12$qRAC96dyOyckqGzRW5mx8eVrMhT04CphYnMsXVssV1iamotk90m6G','viewer','viewer@madrasah.sch.id','aktif',1)
ON DUPLICATE KEY UPDATE role=VALUES(role);

INSERT INTO app_settings (skey,svalue) VALUES
('app_name','SIRKAM'),
('kode_madrasah','MI-SF'),
('format_nomor','{no}/RKAM/{kode}/{romawi}/{tahun}'),
('max_upload_mb','5'),
('session_timeout','7200'),
('last_backup_at','')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);
