CREATE DATABASE IF NOT EXISTS web_tpoa
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_tpoa

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ketua','penata','kasubdit','admin') NOT NULL DEFAULT 'penata',
  aktif BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE organisasi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_registrasi VARCHAR(40) NOT NULL UNIQUE,
  nama_resmi VARCHAR(200) NOT NULL,
  jenis ENUM('INGO','YAYASAN_ASING') NOT NULL,
  negara_asal VARCHAR(100) NOT NULL,
  nama_perwakilan VARCHAR(150) NULL,
  email_resmi VARCHAR(190) NULL,
  alamat TEXT NULL,
  bidang_kegiatan VARCHAR(120) NULL,
  tanggal_pengajuan DATE NOT NULL,
  status ENUM('DRAFT','DIAJUKAN','DALAM_REVIEW','PERLU_PERBAIKAN','DIVERIFIKASI','DISETUJUI','DITOLAK') NOT NULL DEFAULT 'DIAJUKAN',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_org_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_org_jenis_status (jenis,status),
  INDEX idx_org_nama (nama_resmi),
  INDEX idx_org_negara (negara_asal)
) ENGINE=InnoDB;

CREATE TABLE dokumen (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organisasi_id BIGINT UNSIGNED NOT NULL,
  nama_dokumen VARCHAR(200) NOT NULL,
  path_tersimpan VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NULL,
  ukuran_bytes BIGINT UNSIGNED NULL,
  diunggah_oleh BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_org FOREIGN KEY (organisasi_id) REFERENCES organisasi(id) ON DELETE CASCADE,
  CONSTRAINT fk_doc_user FOREIGN KEY (diunggah_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE alur_persetujuan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organisasi_id BIGINT UNSIGNED NOT NULL,
  tahap ENUM('PENATA','KASUBDIT','KETUA') NOT NULL,
  keputusan ENUM('MENUNGGU','DIPROSES','DISETUJUI','PERLU_PERBAIKAN','DITOLAK') NOT NULL DEFAULT 'MENUNGGU',
  catatan TEXT NULL,
  diproses_oleh BIGINT UNSIGNED NULL,
  diproses_pada DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_alur_org FOREIGN KEY (organisasi_id) REFERENCES organisasi(id) ON DELETE CASCADE,
  CONSTRAINT fk_alur_user FOREIGN KEY (diproses_oleh) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_alur_tahap_keputusan (tahap,keputusan)
) ENGINE=InnoDB;

CREATE TABLE riwayat_administrasi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organisasi_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  aksi VARCHAR(100) NOT NULL,
  status_sebelum VARCHAR(40) NULL,
  status_sesudah VARCHAR(40) NULL,
  catatan TEXT NULL,
  ip_hash VARCHAR(128) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_org FOREIGN KEY (organisasi_id) REFERENCES organisasi(id) ON DELETE CASCADE,
  CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_history_org_date (organisasi_id,created_at)
) ENGINE=InnoDB;

-- Data demo opsional. Jangan gunakan nama/status contoh sebagai data resmi.
INSERT INTO organisasi
(nomor_registrasi,nama_resmi,jenis,negara_asal,nama_perwakilan,email_resmi,bidang_kegiatan,tanggal_pengajuan,status)
VALUES
('INGO-DEMO-001','Save the Children','INGO','Inggris','Demo Representative','demo@example.org','Kemanusiaan','2025-10-10','DISETUJUI'),
('INGO-DEMO-002','Global Green Growth','INGO','Jerman','Demo Representative','demo2@example.org','Lingkungan','2025-10-08','DALAM_REVIEW'),
('YAS-DEMO-003','Ford Foundation','YAYASAN_ASING','Amerika Serikat','Demo Representative','demo3@example.org','Pendidikan','2025-10-06','PERLU_PERBAIKAN');
