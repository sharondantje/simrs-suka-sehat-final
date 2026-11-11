-- =====================================================================
--  DATABASE SIMRS RS SUKA SEHAT
--  Cara pakai (XAMPP):
--   1. Start Apache + MySQL di XAMPP Control Panel
--   2. Buka http://localhost/phpmyadmin
--   3. Tab "Import" -> pilih file ini -> klik "Import" (Go)
--  File ini otomatis membuat database `simrs_suka_sehat` beserta semua
--  tabel (16 modul) dan data contoh. Aman di-import ulang (tabel lama dihapus).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `simrs_suka_sehat`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `simrs_suka_sehat`;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `audit_logs`, `role_permissions`, `permissions`, `users`, `roles`,
  `settings`, `insurers`, `poli`, `doctors`, `rooms`, `beds`, `tariffs`, `icd10`, `drugs`,
  `lab_tests`, `radiology_exams`, `patients`, `visits`, `doctor_schedules`, `appointments`,
  `medical_records`, `diagnoses`, `vital_signs`, `patient_documents`, `orders`, `order_items`,
  `referrals`, `admissions`, `bed_transfers`, `triages`, `lab_results`, `radiology_results`,
  `prescriptions`, `prescription_items`, `drug_returns`, `invoices`, `invoice_items`, `payments`,
  `insurance_claims`, `nursing_notes`, `medication_administrations`, `inventory_items`,
  `stock_movements`, `purchase_requests`, `purchase_request_items`, `stock_opnames`,
  `lab_specimens`, `lab_communications`, `drug_stock_movements`, `inventory_batches`,
  `goods_receipts`, `goods_receipt_items`, `stock_requisitions`, `stock_requisition_items`,
  `stock_opname_items`;

-- =====================================================================
-- MODUL 1 : AUTHENTICATION & USER MANAGEMENT
-- =====================================================================
CREATE TABLE `roles` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(40)  NOT NULL UNIQUE,
  `name`        VARCHAR(80)  NOT NULL,
  `description` VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE `permissions` (
  `id`     INT AUTO_INCREMENT PRIMARY KEY,
  `code`   VARCHAR(60)  NOT NULL UNIQUE,
  `name`   VARCHAR(120) NOT NULL,
  `module` VARCHAR(60)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `role_permissions` (
  `role_id`       INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(120) NOT NULL,
  `email`         VARCHAR(120) NULL,
  `phone`         VARCHAR(20)  NULL,
  `role_id`       INT NOT NULL,
  `unit`          VARCHAR(80)  NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `last_login`    DATETIME NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `audit_logs` (
  `id`          BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT NULL,
  `username`    VARCHAR(50) NULL,
  `action`      VARCHAR(40) NOT NULL,
  `module`      VARCHAR(60) NOT NULL,
  `description` VARCHAR(255) NULL,
  `ip_address`  VARCHAR(45) NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 16 : MASTER DATA & CONFIGURATION
-- =====================================================================
CREATE TABLE `settings` (
  `setting_key`   VARCHAR(60) PRIMARY KEY,
  `setting_value` VARCHAR(255) NOT NULL,
  `label`         VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `insurers` (
  `id`   INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('umum','bpjs','asuransi','perusahaan') NOT NULL DEFAULT 'umum'
) ENGINE=InnoDB;

CREATE TABLE `poli` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `code`      VARCHAR(20) NOT NULL UNIQUE,
  `name`      VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `doctors` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT NULL,
  `nip`            VARCHAR(30) NOT NULL UNIQUE,
  `name`           VARCHAR(120) NOT NULL,
  `specialization` VARCHAR(100) NOT NULL,
  `poli_id`        INT NULL,
  `sip_number`     VARCHAR(50) NULL,
  `phone`          VARCHAR(20) NULL,
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`poli_id`) REFERENCES `poli`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `rooms` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `code`           VARCHAR(20) NOT NULL UNIQUE,
  `name`           VARCHAR(100) NOT NULL,
  `ward`           VARCHAR(80) NOT NULL,
  `class`          ENUM('VIP','I','II','III','ICU','IGD') NOT NULL,
  `tariff_per_day` DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `beds` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `room_id`    INT NOT NULL,
  `bed_number` VARCHAR(10) NOT NULL,
  `status`     ENUM('tersedia','terisi','dibersihkan','maintenance') NOT NULL DEFAULT 'tersedia',
  UNIQUE (`room_id`, `bed_number`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `tariffs` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `code`     VARCHAR(20) NOT NULL UNIQUE,
  `name`     VARCHAR(120) NOT NULL,
  `category` ENUM('administrasi','konsultasi','tindakan','laboratorium','radiologi','kamar','lainnya') NOT NULL,
  `price`    DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `icd10` (
  `code`        VARCHAR(10) PRIMARY KEY,
  `description` VARCHAR(200) NOT NULL,
  `category`    VARCHAR(80) NULL
) ENGINE=InnoDB;

CREATE TABLE `drugs` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(20) NOT NULL UNIQUE,
  `name`        VARCHAR(120) NOT NULL,
  `form`        VARCHAR(40) NOT NULL,
  `unit`        VARCHAR(20) NOT NULL,
  `category`    VARCHAR(60) NULL,
  `price`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `stock`       INT NOT NULL DEFAULT 0,
  `min_stock`   INT NOT NULL DEFAULT 0,
  `expiry_date` DATE NULL
) ENGINE=InnoDB;

CREATE TABLE `lab_tests` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `code`         VARCHAR(20) NOT NULL UNIQUE,
  `name`         VARCHAR(120) NOT NULL,
  `category`     VARCHAR(60) NOT NULL,
  `unit`         VARCHAR(20) NULL,
  `normal_range` VARCHAR(60) NULL,
  `price`        DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `radiology_exams` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `code`     VARCHAR(20) NOT NULL UNIQUE,
  `name`     VARCHAR(120) NOT NULL,
  `modality` ENUM('X-Ray','CT Scan','MRI','USG','Mammografi') NOT NULL,
  `price`    DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 2 : PATIENT REGISTRATION
-- =====================================================================
CREATE TABLE `patients` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `mrn`              VARCHAR(20) NOT NULL UNIQUE COMMENT 'Nomor rekam medis',
  `nik`              VARCHAR(16) NULL UNIQUE,
  `name`             VARCHAR(120) NOT NULL,
  `gender`           ENUM('L','P') NOT NULL,
  `birth_place`      VARCHAR(60) NULL,
  `birth_date`       DATE NOT NULL,
  `blood_type`       VARCHAR(3) NULL,
  `address`          VARCHAR(255) NULL,
  `phone`            VARCHAR(20) NULL,
  `allergies`        VARCHAR(255) NULL,
  `insurer_id`       INT NULL,
  `insurance_number` VARCHAR(30) NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`insurer_id`) REFERENCES `insurers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `visits` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `visit_no`   VARCHAR(30) NOT NULL UNIQUE,
  `patient_id` INT NOT NULL,
  `visit_type` ENUM('rawat_jalan','rawat_inap','igd') NOT NULL,
  `poli_id`    INT NULL,
  `doctor_id`  INT NULL,
  `insurer_id` INT NULL,
  `queue_no`   VARCHAR(10) NULL,
  `visit_date` DATETIME NOT NULL,
  `complaint`  VARCHAR(255) NULL,
  `status`     ENUM('menunggu','dipanggil','diperiksa','selesai','dirawat','batal') NOT NULL DEFAULT 'menunggu',
  `created_by` INT NULL,
  INDEX (`visit_date`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`poli_id`) REFERENCES `poli`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`insurer_id`) REFERENCES `insurers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 12 : SCHEDULING & APPOINTMENT
-- =====================================================================
CREATE TABLE `doctor_schedules` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id`   INT NOT NULL,
  `poli_id`     INT NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '1=Senin ... 7=Minggu',
  `start_time`  TIME NOT NULL,
  `end_time`    TIME NOT NULL,
  `quota`       INT NOT NULL DEFAULT 20,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`poli_id`) REFERENCES `poli`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `appointments` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_no`   VARCHAR(30) NOT NULL UNIQUE,
  `patient_id`       INT NOT NULL,
  `doctor_id`        INT NOT NULL,
  `poli_id`          INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `queue_no`         VARCHAR(10) NULL,
  `status`           ENUM('terjadwal','check_in','selesai','reschedule','dibatalkan') NOT NULL DEFAULT 'terjadwal',
  `notes`            VARCHAR(255) NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`),
  FOREIGN KEY (`poli_id`) REFERENCES `poli`(`id`)
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 3 : ELECTRONIC MEDICAL RECORD (EMR)
-- =====================================================================
CREATE TABLE `medical_records` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `visit_id`   INT NOT NULL,
  `patient_id` INT NOT NULL,
  `doctor_id`  INT NULL,
  `subjective` TEXT NULL,
  `objective`  TEXT NULL,
  `assessment` TEXT NULL,
  `plan`       TEXT NULL,
  `exam_result` TEXT NULL COMMENT 'Hasil pemeriksaan fisik/penunjang',
  `status`     ENUM('draft','final') NOT NULL DEFAULT 'final',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `diagnoses` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `record_id`  INT NOT NULL,
  `icd10_code` VARCHAR(10) NOT NULL,
  `type`       ENUM('primer','sekunder') NOT NULL DEFAULT 'primer',
  FOREIGN KEY (`record_id`) REFERENCES `medical_records`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`icd10_code`) REFERENCES `icd10`(`code`)
) ENGINE=InnoDB;

CREATE TABLE `vital_signs` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `visit_id`    INT NOT NULL,
  `patient_id`  INT NOT NULL,
  `recorded_by` INT NULL,
  `systolic`    SMALLINT NULL,
  `diastolic`   SMALLINT NULL,
  `pulse`       SMALLINT NULL,
  `respiration` SMALLINT NULL,
  `temperature` DECIMAL(4,1) NULL,
  `spo2`        SMALLINT NULL,
  `weight`      DECIMAL(5,1) NULL,
  `height`      DECIMAL(5,1) NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `patient_documents` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`  INT NOT NULL,
  `visit_id`    INT NULL,
  `title`       VARCHAR(120) NOT NULL,
  `file_path`   VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 4 : CPOE (ORDER MANAGEMENT)
-- =====================================================================
CREATE TABLE `orders` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `order_no`   VARCHAR(30) NOT NULL UNIQUE,
  `visit_id`   INT NOT NULL,
  `patient_id` INT NOT NULL,
  `doctor_id`  INT NULL,
  `order_type` ENUM('laboratorium','radiologi','obat','tindakan','konsultasi','rawat_inap') NOT NULL,
  `priority`   ENUM('normal','cito') NOT NULL DEFAULT 'normal',
  `status`     ENUM('baru','diproses','selesai','dibatalkan') NOT NULL DEFAULT 'baru',
  `notes`      VARCHAR(255) NULL,
  `cancel_reason` VARCHAR(255) NULL,
  `ordered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cancelled_at` DATETIME NULL,
  INDEX (`ordered_at`),
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`  INT NOT NULL,
  `item_code` VARCHAR(20) NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `qty`       INT NOT NULL DEFAULT 1,
  `notes`     VARCHAR(255) NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 5 : OUTPATIENT (RAWAT JALAN) — memakai tabel visits + referrals
-- =====================================================================
CREATE TABLE `referrals` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `visit_id`      INT NOT NULL,
  `referral_type` ENUM('internal','eksternal','kontrol_ulang') NOT NULL,
  `destination`   VARCHAR(150) NOT NULL,
  `reason`        VARCHAR(255) NULL,
  `control_date`  DATE NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 6 : INPATIENT (RAWAT INAP)
-- =====================================================================
CREATE TABLE `admissions` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `admission_no`      VARCHAR(30) NOT NULL UNIQUE,
  `visit_id`          INT NOT NULL,
  `patient_id`        INT NOT NULL,
  `bed_id`            INT NULL,
  `doctor_id`         INT NULL,
  `admit_date`        DATETIME NOT NULL,
  `discharge_date`    DATETIME NULL,
  `diagnosis`         VARCHAR(200) NULL,
  `status`            ENUM('dirawat','pulang','dirujuk','meninggal') NOT NULL DEFAULT 'dirawat',
  `discharge_summary` TEXT NULL,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `bed_transfers` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `admission_id`   INT NOT NULL,
  `from_bed_id`    INT NULL,
  `to_bed_id`      INT NULL,
  `reason`         VARCHAR(255) NULL,
  `transferred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admission_id`) REFERENCES `admissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_bed_id`) REFERENCES `beds`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`to_bed_id`) REFERENCES `beds`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 7 : EMERGENCY DEPARTMENT (IGD)
-- =====================================================================
CREATE TABLE `triages` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `visit_id`        INT NOT NULL,
  `patient_id`      INT NOT NULL,
  `triage_level`    ENUM('merah','kuning','hijau','hitam') NOT NULL,
  `chief_complaint` VARCHAR(255) NOT NULL,
  `gcs`             TINYINT NULL,
  `arrival_mode`    ENUM('sendiri','ambulans','rujukan','polisi') NOT NULL DEFAULT 'sendiri',
  `disposition`     ENUM('observasi','rawat_inap','pulang','rujuk') NULL,
  `notes`           VARCHAR(255) NULL COMMENT 'Catatan triase',
  `actions`         TEXT NULL COMMENT 'Tindakan darurat yang dilakukan',
  `observation`     TEXT NULL COMMENT 'Hasil observasi',
  `patient_condition` VARCHAR(60) NULL,
  `triaged_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`)
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 8 : LABORATORY
-- =====================================================================
CREATE TABLE `lab_results` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`     INT NOT NULL,
  `lab_test_id`  INT NOT NULL,
  `result_value` VARCHAR(60) NULL,
  `flag`         ENUM('normal','tinggi','rendah','kritis') NULL,
  `status`       ENUM('menunggu_sampel','diproses','menunggu_validasi','tervalidasi') NOT NULL DEFAULT 'menunggu_sampel',
  `validated_by` INT NULL,
  `validated_at` DATETIME NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests`(`id`),
  FOREIGN KEY (`validated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `lab_specimens` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`      INT NOT NULL UNIQUE,
  `specimen_type` VARCHAR(60) NOT NULL,
  `barcode`       VARCHAR(30) NULL,
  `received_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `checklist`     VARCHAR(255) NULL COMMENT 'Checklist kelayakan yang terpenuhi',
  `status`        ENUM('diterima','ditolak') NOT NULL,
  `notes`         VARCHAR(255) NULL COMMENT 'Catatan / alasan penolakan',
  `verified_by`   INT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `lab_communications` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`        INT NOT NULL,
  `recipient`       VARCHAR(120) NOT NULL COMMENT 'Dokter / unit penerima hasil kritis',
  `communicated_at` DATETIME NOT NULL,
  `note`            VARCHAR(255) NULL,
  `created_by`      INT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 9 : RADIOLOGY
-- =====================================================================
CREATE TABLE `radiology_results` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`       INT NOT NULL,
  `exam_id`        INT NOT NULL,
  `scheduled_at`   DATETIME NULL,
  `findings`       TEXT NULL,
  `impression`     TEXT NULL,
  `image_path`     VARCHAR(255) NULL,
  `radiologist_id` INT NULL,
  `status`         ENUM('terjadwal','pemeriksaan','menunggu_bacaan','selesai') NOT NULL DEFAULT 'terjadwal',
  `reported_at`    DATETIME NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`exam_id`) REFERENCES `radiology_exams`(`id`),
  FOREIGN KEY (`radiologist_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 10 : PHARMACY
-- =====================================================================
CREATE TABLE `prescriptions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_no` VARCHAR(30) NOT NULL UNIQUE,
  `order_id`        INT NULL COMMENT 'Order obat CPOE asal resep',
  `visit_id`        INT NOT NULL,
  `patient_id`      INT NOT NULL,
  `doctor_id`       INT NULL,
  `status`          ENUM('baru','diverifikasi','disiapkan','diserahkan','ditolak') NOT NULL DEFAULT 'baru',
  `verified_by`     INT NULL,
  `notes`           VARCHAR(255) NULL COMMENT 'Catatan farmasi / edukasi',
  `reject_reason`   VARCHAR(255) NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `handed_at`       DATETIME NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `prescription_items` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_id` INT NOT NULL,
  `drug_id`         INT NOT NULL,
  `qty`             INT NOT NULL,
  `dosage`          VARCHAR(60) NOT NULL,
  `instructions`    VARCHAR(150) NULL,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`drug_id`) REFERENCES `drugs`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `drug_returns` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `return_no`       VARCHAR(30) NOT NULL UNIQUE,
  `prescription_id` INT NULL,
  `source`          VARCHAR(120) NULL COMMENT 'Pasien / unit asal retur',
  `drug_id`         INT NOT NULL,
  `qty`             INT NOT NULL,
  `drug_condition`  VARCHAR(60) NULL,
  `reason`          VARCHAR(255) NULL,
  `status`          ENUM('menunggu','diterima','ditolak') NOT NULL DEFAULT 'menunggu',
  `verified_by`     INT NULL,
  `returned_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`drug_id`) REFERENCES `drugs`(`id`),
  FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `drug_stock_movements` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `drug_id`       INT NOT NULL,
  `movement_type` ENUM('masuk','keluar','retur','penyesuaian') NOT NULL,
  `qty`           INT NOT NULL COMMENT 'Positif = menambah stok, negatif = mengurangi',
  `reference`     VARCHAR(60) NULL,
  `notes`         VARCHAR(255) NULL,
  `created_by`    INT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`created_at`),
  FOREIGN KEY (`drug_id`) REFERENCES `drugs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 11 : BILLING & INSURANCE
-- =====================================================================
CREATE TABLE `invoices` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no`      VARCHAR(30) NOT NULL UNIQUE,
  `visit_id`        INT NOT NULL,
  `patient_id`      INT NOT NULL,
  `insurer_id`      INT NULL,
  `total`           DECIMAL(14,2) NOT NULL DEFAULT 0,
  `discount`        DECIMAL(14,2) NOT NULL DEFAULT 0,
  `insurance_cover` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `patient_pay`     DECIMAL(14,2) NOT NULL DEFAULT 0,
  `status`          ENUM('belum_bayar','cicilan','lunas','klaim') NOT NULL DEFAULT 'belum_bayar',
  `insurance_number` VARCHAR(30) NULL COMMENT 'No. peserta / member',
  `policy_no`       VARCHAR(30) NULL,
  `valid_until`     DATE NULL,
  `verified`        TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Penjamin sudah diverifikasi',
  `notes`           VARCHAR(255) NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at`         DATETIME NULL,
  INDEX (`created_at`),
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`insurer_id`) REFERENCES `insurers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `invoice_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id`  INT NOT NULL,
  `description` VARCHAR(150) NOT NULL,
  `category`    VARCHAR(40) NOT NULL,
  `qty`         INT NOT NULL DEFAULT 1,
  `price`       DECIMAL(12,2) NOT NULL,
  `subtotal`    DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `payments` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `amount`     DECIMAL(14,2) NOT NULL,
  `method`     ENUM('tunai','debit','kredit','transfer','qris') NOT NULL,
  `cashier_id` INT NULL,
  `paid_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `insurance_claims` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `claim_no`     VARCHAR(30) NOT NULL UNIQUE,
  `invoice_id`   INT NOT NULL,
  `insurer_id`   INT NOT NULL,
  `amount`       DECIMAL(14,2) NOT NULL,
  `status`       ENUM('diajukan','diverifikasi','disetujui','ditolak','dibayar') NOT NULL DEFAULT 'diajukan',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`insurer_id`) REFERENCES `insurers`(`id`)
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 13 : NURSING
-- =====================================================================
CREATE TABLE `nursing_notes` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `note_type`         ENUM('identifikasi','assessment','catatan','obat','monitoring','finalisasi') NOT NULL DEFAULT 'catatan',
  `visit_id`          INT NOT NULL,
  `patient_id`        INT NOT NULL,
  `nurse_id`          INT NULL,
  `assessment`        TEXT NULL,
  `nursing_diagnosis` VARCHAR(200) NULL,
  `intervention`      TEXT NULL,
  `evaluation`        TEXT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`nurse_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `medication_administrations` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `admission_id` INT NOT NULL,
  `drug_id`      INT NOT NULL,
  `dose`         VARCHAR(60) NOT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `given_at`     DATETIME NULL,
  `nurse_id`     INT NULL,
  `route`        VARCHAR(10) NULL COMMENT 'Oral / IV / IM / SC',
  `response`     VARCHAR(255) NULL COMMENT 'Respons pasien / observasi',
  `status`       ENUM('terjadwal','diberikan','ditunda','ditolak') NOT NULL DEFAULT 'terjadwal',
  FOREIGN KEY (`admission_id`) REFERENCES `admissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`drug_id`) REFERENCES `drugs`(`id`),
  FOREIGN KEY (`nurse_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- MODUL 14 : INVENTORY & WAREHOUSE
-- =====================================================================
CREATE TABLE `inventory_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `code`        VARCHAR(20) NOT NULL UNIQUE,
  `name`        VARCHAR(120) NOT NULL,
  `category`    ENUM('medis','nonmedis','obat','alkes') NOT NULL,
  `unit`        VARCHAR(20) NOT NULL,
  `stock`       INT NOT NULL DEFAULT 0,
  `min_stock`   INT NOT NULL DEFAULT 0,
  `location`    VARCHAR(80) NOT NULL DEFAULT 'Gudang Utama',
  `price`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `expiry_date` DATE NULL
) ENGINE=InnoDB;

CREATE TABLE `stock_movements` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `item_id`       INT NOT NULL,
  `movement_type` ENUM('masuk','keluar','mutasi','penyesuaian') NOT NULL,
  `qty`           INT NOT NULL,
  `from_location` VARCHAR(80) NULL,
  `to_location`   VARCHAR(80) NULL,
  `reference`     VARCHAR(60) NULL,
  `created_by`    INT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `purchase_requests` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `pr_no`        VARCHAR(30) NOT NULL UNIQUE,
  `requested_by` INT NULL,
  `status`       ENUM('draft','diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan',
  `notes`        VARCHAR(255) NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `purchase_request_items` (
  `id`      INT AUTO_INCREMENT PRIMARY KEY,
  `pr_id`   INT NOT NULL,
  `item_id` INT NOT NULL,
  `qty`     INT NOT NULL,
  FOREIGN KEY (`pr_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `stock_opnames` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `opname_no`   VARCHAR(30) NOT NULL UNIQUE,
  `location`    VARCHAR(80) NOT NULL,
  `period`      VARCHAR(20) NULL,
  `count_date`  DATE NULL,
  `reason`      VARCHAR(120) NULL COMMENT 'Alasan selisih',
  `notes`       VARCHAR(255) NULL,
  `status`      ENUM('draft','menunggu_approval','disetujui','ditolak') NOT NULL DEFAULT 'draft',
  `created_by`  INT NULL,
  `approved_by` INT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `inventory_batches` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `item_id`     INT NOT NULL,
  `batch_no`    VARCHAR(30) NOT NULL,
  `expiry_date` DATE NULL,
  `location`    VARCHAR(80) NOT NULL,
  `qty`         INT NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_batch_loc` (`item_id`,`batch_no`,`location`),
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `goods_receipts` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_no`    VARCHAR(30) NOT NULL UNIQUE,
  `delivery_note` VARCHAR(40) NOT NULL COMMENT 'No. surat jalan',
  `po_number`     VARCHAR(40) NOT NULL,
  `supplier`      VARCHAR(120) NOT NULL,
  `received_date` DATE NOT NULL,
  `checklist`     VARCHAR(255) NULL COMMENT 'Verifikasi fisik',
  `created_by`    INT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `goods_receipt_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_id`  INT NOT NULL,
  `item_id`     INT NOT NULL,
  `batch_no`    VARCHAR(30) NOT NULL,
  `expiry_date` DATE NULL,
  `qty`         INT NOT NULL,
  FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `stock_requisitions` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `req_no`       VARCHAR(30) NOT NULL UNIQUE,
  `destination`  VARCHAR(80) NOT NULL COMMENT 'Depo / unit tujuan',
  `request_date` DATE NOT NULL,
  `status`       ENUM('menunggu','dikirim','ditolak') NOT NULL DEFAULT 'menunggu',
  `notes`        VARCHAR(255) NULL,
  `requested_by` INT NULL,
  `processed_by` INT NULL,
  `processed_at` DATETIME NULL,
  FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `stock_requisition_items` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `req_id`     INT NOT NULL,
  `item_id`    INT NOT NULL,
  `qty`        INT NOT NULL,
  `batch_id`   INT NULL COMMENT 'Batch yang dikeluarkan (FEFO)',
  `issued_qty` INT NULL,
  FOREIGN KEY (`req_id`) REFERENCES `stock_requisitions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`),
  FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `stock_opname_items` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `opname_id`    INT NOT NULL,
  `item_id`      INT NOT NULL,
  `batch_id`     INT NULL,
  `system_qty`   INT NOT NULL,
  `physical_qty` INT NOT NULL,
  FOREIGN KEY (`opname_id`) REFERENCES `stock_opnames`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`),
  FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  DATA AWAL / CONTOH
-- =====================================================================

INSERT INTO `roles` (`id`,`code`,`name`,`description`) VALUES
(1,'admin','Administrator','Mengelola akun, hak akses, master data dan konfigurasi'),
(2,'petugas_registrasi','Petugas Registrasi','Registrasi pasien, data pasien dan appointment'),
(3,'radiologist','Dokter Radiologi','Order dan hasil pemeriksaan radiologi'),
(4,'dokter','Dokter','EMR, CPOE, rawat jalan, rawat inap dan IGD'),
(5,'perawat','Perawat / Bidan','EMR, asuhan keperawatan dan pemberian obat'),
(6,'kasir','Kasir / Billing','Tagihan, pembayaran dan klaim penjamin'),
(7,'farmasi','Farmasi / Apoteker','Verifikasi resep, dispensing, stok dan retur obat'),
(8,'laboratorium','Laboratorium','Order, input dan validasi hasil laboratorium'),
(9,'gudang','Petugas Gudang','Inventory, penerimaan dan mutasi barang'),
(10,'kepala_gudang','Kepala Gudang','Approval stok opname dan audit gudang'),
(11,'executive','Management Executive','Dashboard dan laporan manajemen'),
(12,'analyst','Data Analyst','Laporan, rekap dan statistik');

INSERT INTO `permissions` (`id`,`code`,`name`,`module`) VALUES
(1,'user.manage','Kelola akun pengguna','Authentication & User Management'),
(2,'role.manage','Kelola role & hak akses','Authentication & User Management'),
(3,'audit.view','Lihat log aktivitas','Authentication & User Management'),
(4,'patient.register','Registrasi pasien','Patient Registration'),
(5,'patient.view','Lihat data pasien','Patient Registration'),
(6,'emr.write','Isi rekam medis (SOAP)','Electronic Medical Record'),
(7,'emr.view','Lihat rekam medis','Electronic Medical Record'),
(8,'cpoe.order','Membuat order elektronik','CPOE'),
(9,'outpatient.manage','Pelayanan rawat jalan','Outpatient'),
(10,'inpatient.manage','Pelayanan rawat inap','Inpatient'),
(11,'igd.manage','Pelayanan IGD','Emergency Department'),
(12,'lab.process','Proses & validasi hasil lab','Laboratory'),
(13,'radiology.process','Proses & laporan radiologi','Radiology'),
(14,'pharmacy.dispense','Verifikasi & dispensing resep','Pharmacy'),
(15,'billing.manage','Kelola tagihan & pembayaran','Billing & Insurance'),
(16,'schedule.manage','Kelola jadwal & appointment','Scheduling & Appointment'),
(17,'nursing.write','Catatan keperawatan','Nursing'),
(18,'inventory.manage','Kelola stok gudang','Inventory & Warehouse'),
(19,'inventory.approve','Approval stok opname','Inventory & Warehouse'),
(20,'report.view','Lihat laporan & dashboard','Reporting & Dashboard'),
(21,'master.manage','Kelola master data & konfigurasi','Master Data & Configuration');

INSERT INTO `role_permissions` (`role_id`,`permission_id`) VALUES
(1,1),(1,2),(1,3),(1,5),(1,20),(1,21),
(2,4),(2,5),(2,16),
(3,5),(3,7),(3,13),
(4,5),(4,6),(4,7),(4,8),(4,9),(4,10),(4,11),
(5,5),(5,7),(5,10),(5,11),(5,17),
(6,5),(6,15),
(7,5),(7,14),
(8,5),(8,12),
(9,18),
(10,18),(10,19),(10,3),
(11,20),
(12,20),(12,3);

INSERT INTO `users` (`id`,`username`,`password_hash`,`full_name`,`email`,`phone`,`role_id`,`unit`,`is_active`,`last_login`) VALUES
(1,'admin','$2y$10$6leparLOifvnZeL/MU36vOQJ9.YIbHOX1QNSEE0ejb.GnjtyJhsby','Administrator','admin@sukasehat.id','081200000001',1,'IT / SIMRS',1,NOW() - INTERVAL 1 DAY),
(2,'petugas','$2y$10$YWRdssFFHMZYbsnY29mpQuIglOb0TMk3qG.iiWQ.SFJR41Ws.BWWu','Andi Putra','andi.putra@sukasehat.id','081200000002',2,'Admisi',1,NOW() - INTERVAL 3 HOUR),
(3,'radiolog','$2y$10$HU4ZN60CP3Z5Rk2Coyd2vOpPt.tj7.CapeU2GpSqqYMRO3L9CqCH2','dr. Andi Pratama, Sp.Rad','andi.pratama@sukasehat.id','081200000003',3,'Radiologi',1,NOW() - INTERVAL 5 HOUR),
(4,'dokter','$2y$10$oxL4HnXzpMg6EMEqb7S.EejlTc79jypJnuL4LFnUlhw3ZKwG7/UMC','dr. Ahmad Fauzi, Sp.PD','ahmad.fauzi@sukasehat.id','081200000004',4,'Poli Penyakit Dalam',1,NOW() - INTERVAL 2 HOUR),
(5,'perawat','$2y$10$SwtCH4sTW0EjkxzbY1/D/unbt.qyZV8x7pDRaFmJHUNqMcAL0hD3K','Ns. Rina Wulandari, S.Kep','rina.w@sukasehat.id','081200000005',5,'Bangsal Melati',1,NOW() - INTERVAL 1 HOUR),
(6,'kasir','$2y$10$3TtRkB/XeuVFCFRZcNj/SeoF.18MnWcVSoHmtwQg//oIEhY6tMGFy','Dewi Rahayu, S.E.','dewi.rahayu@sukasehat.id','081200000006',6,'Keuangan',1,NOW() - INTERVAL 4 HOUR),
(7,'farmasi','$2y$10$IKk8neeFWXFdyEBdyY52xeAg3fHxUpOEPxmNnCKxcuUnNW3OChUHS','apt. Annisa Putri, S.Farm','annisa.putri@sukasehat.id','081200000007',7,'Instalasi Farmasi',1,NOW() - INTERVAL 6 HOUR),
(8,'laboran','$2y$10$mHxqMeVV3aeCjh8vpo.xHulIl/wlltKcbFoKGqGZYVv70rokw8paa','Agus Laboran, A.Md.AK','agus.lab@sukasehat.id','081200000008',8,'Laboratorium',1,NOW() - INTERVAL 7 HOUR),
(9,'gudang','$2y$10$dSr8XN8bdYye8LWh9MK2TOmQOboCuy4ZFOaNaQeAEW9Omvw7nNIx6','Bambang Setiawan','bambang.s@sukasehat.id','081200000009',9,'Gudang',1,NOW() - INTERVAL 1 DAY),
(10,'kepala_gudang','$2y$10$NwkR4cl0kO2dz7waWSp3yOUtjYam0A.y6vfUkckeqoGES3ytd0nru','Hendra Wijaya, S.T.','hendra.w@sukasehat.id','081200000010',10,'Gudang',1,NOW() - INTERVAL 2 DAY),
(11,'executive','$2y$10$cP/kcCb1V3KSEyU.G23Pr.I3nqjzNomI/0DWldINhHFyYRqGFhOyK','dr. Maria Santoso, MARS','maria.s@sukasehat.id','081200000011',11,'Direksi',1,NOW() - INTERVAL 1 DAY),
(12,'analyst','$2y$10$pFReeIP7/jV8VfARAaBrOeIf.NYSsCgp2EGFminPGN6S/6kdvH12.','Kevin Halim, S.Kom','kevin.h@sukasehat.id','081200000012',12,'Rekam Medis & Data',1,NOW() - INTERVAL 8 HOUR);

INSERT INTO `settings` (`setting_key`,`setting_value`,`label`) VALUES
('hospital_name','RS Suka Sehat','Nama Rumah Sakit'),
('hospital_address','Jl. Kesehatan No. 1, Jakarta','Alamat'),
('hospital_phone','(021) 555-0101','Telepon'),
('hospital_type','Rumah Sakit Umum Tipe C','Tipe Rumah Sakit'),
('mrn_prefix','RM','Prefix Nomor Rekam Medis'),
('session_timeout','30','Timeout Sesi (menit)'),
('password_min_length','8','Panjang Minimal Password'),
('total_beds','40','Jumlah Tempat Tidur (untuk BOR)');

INSERT INTO `insurers` (`id`,`code`,`name`,`type`) VALUES
(1,'UMUM','Umum / Pribadi','umum'),
(2,'BPJS','BPJS Kesehatan','bpjs'),
(3,'PRU','Prudential','asuransi'),
(4,'ALZ','Allianz','asuransi'),
(5,'MDK','Mandiri Inhealth','asuransi');

INSERT INTO `poli` (`id`,`code`,`name`) VALUES
(1,'PDL','Poli Penyakit Dalam'),(2,'ANK','Poli Anak'),(3,'OBG','Poli Kandungan'),
(4,'BDH','Poli Bedah'),(5,'SRF','Poli Saraf'),(6,'JTG','Poli Jantung'),
(7,'UMM','Poli Umum'),(8,'GIG','Poli Gigi'),(9,'IGD','Instalasi Gawat Darurat');

INSERT INTO `doctors` (`id`,`user_id`,`nip`,`name`,`specialization`,`poli_id`,`sip_number`,`phone`) VALUES
(1,4,'198203122010011001','dr. Ahmad Fauzi, Sp.PD','Penyakit Dalam',1,'SIP/PD/2021/001','081311110001'),
(2,NULL,'198507202012012002','dr. Sari Dewi, Sp.A','Anak',2,'SIP/A/2021/014','081311110002'),
(3,NULL,'197911052008011003','dr. Budi Hartono, Sp.OG','Obstetri & Ginekologi',3,'SIP/OG/2020/007','081311110003'),
(4,NULL,'198001252009011004','dr. Rudi Hermawan, Sp.B','Bedah Umum',4,'SIP/B/2019/021','081311110004'),
(5,NULL,'198612082014012005','dr. Lina Marlina, Sp.N','Saraf',5,'SIP/N/2022/003','081311110005'),
(6,NULL,'197808172006011006','dr. Hendra Gunawan, Sp.JP','Jantung & Pembuluh Darah',6,'SIP/JP/2018/011','081311110006'),
(7,NULL,'199003142016012007','dr. Fitri Handayani','Dokter Umum',7,'SIP/U/2023/045','081311110007'),
(8,NULL,'199105222017011008','drg. Yoga Pratama','Gigi',8,'SIP/G/2023/009','081311110008'),
(9,NULL,'198809302015011009','dr. Kevin Wijaya','Dokter Umum (IGD)',9,'SIP/U/2022/031','081311110009'),
(10,3,'198104112009011010','dr. Andi Pratama, Sp.Rad','Radiologi',NULL,'SIP/RAD/2020/002','081311110010');

INSERT INTO `rooms` (`id`,`code`,`name`,`ward`,`class`,`tariff_per_day`) VALUES
(1,'MLT-VIP','Melati VIP','Melati','VIP',1500000),
(2,'MLT-1','Melati Kelas I','Melati','I',750000),
(3,'MWR-2','Mawar Kelas II','Mawar','II',450000),
(4,'ANG-3','Anggrek Kelas III','Anggrek','III',250000),
(5,'ICU','Intensive Care Unit','ICU','ICU',2500000),
(6,'IGD-OBS','Observasi IGD','IGD','IGD',300000);

INSERT INTO `beds` (`room_id`,`bed_number`,`status`) VALUES
(1,'01','terisi'),(1,'02','tersedia'),
(2,'01','terisi'),(2,'02','terisi'),(2,'03','tersedia'),(2,'04','dibersihkan'),
(3,'01','terisi'),(3,'02','terisi'),(3,'03','terisi'),(3,'04','tersedia'),(3,'05','terisi'),(3,'06','tersedia'),
(4,'01','terisi'),(4,'02','terisi'),(4,'03','terisi'),(4,'04','terisi'),(4,'05','tersedia'),(4,'06','terisi'),(4,'07','terisi'),(4,'08','maintenance'),
(5,'01','terisi'),(5,'02','terisi'),(5,'03','tersedia'),(5,'04','terisi'),
(6,'01','terisi'),(6,'02','tersedia'),(6,'03','tersedia'),(6,'04','terisi');

INSERT INTO `tariffs` (`code`,`name`,`category`,`price`) VALUES
('ADM-01','Administrasi Rawat Jalan','administrasi',25000),
('ADM-02','Administrasi Rawat Inap','administrasi',75000),
('KNS-UM','Konsultasi Dokter Umum','konsultasi',75000),
('KNS-SP','Konsultasi Dokter Spesialis','konsultasi',150000),
('TND-01','Pemasangan Infus','tindakan',85000),
('TND-02','Hecting / Jahit Luka (<5 jahitan)','tindakan',150000),
('TND-03','Nebulizer','tindakan',95000),
('TND-04','EKG','tindakan',120000),
('IGD-01','Pelayanan IGD','tindakan',200000);

INSERT INTO `icd10` (`code`,`description`,`category`) VALUES
('A09','Diare dan gastroenteritis','Penyakit infeksi'),
('A01.0','Demam tifoid','Penyakit infeksi'),
('A91','Demam berdarah dengue','Penyakit infeksi'),
('E11','Diabetes melitus tipe 2','Endokrin'),
('I10','Hipertensi esensial (primer)','Sirkulasi'),
('I21.9','Infark miokard akut','Sirkulasi'),
('J06.9','Infeksi saluran pernapasan atas akut','Pernapasan'),
('J18.9','Pneumonia','Pernapasan'),
('J45.9','Asma','Pernapasan'),
('K29.7','Gastritis','Pencernaan'),
('K35.8','Apendisitis akut','Pencernaan'),
('M54.5','Nyeri punggung bawah','Muskuloskeletal'),
('N39.0','Infeksi saluran kemih','Genitourinari'),
('O80','Persalinan normal','Kehamilan'),
('S06.0','Gegar otak (komosio)','Cedera'),
('R50.9','Demam, tidak spesifik','Gejala');

INSERT INTO `drugs` (`id`,`code`,`name`,`form`,`unit`,`category`,`price`,`stock`,`min_stock`,`expiry_date`) VALUES
(1,'OBT-001','Paracetamol 500 mg','Tablet','tablet','Analgesik',500,2400,500,'2027-08-31'),
(2,'OBT-002','Amoxicillin 500 mg','Kapsul','kapsul','Antibiotik',1200,850,300,'2027-03-31'),
(3,'OBT-003','Amlodipine 10 mg','Tablet','tablet','Antihipertensi',900,620,200,'2027-11-30'),
(4,'OBT-004','Metformin 500 mg','Tablet','tablet','Antidiabetes',700,180,250,'2027-06-30'),
(5,'OBT-005','Omeprazole 20 mg','Kapsul','kapsul','Gastrointestinal',1500,410,150,'2027-01-31'),
(6,'OBT-006','Ceftriaxone 1 g','Injeksi','vial','Antibiotik',18000,95,60,'2026-12-31'),
(7,'OBT-007','Ringer Laktat 500 ml','Infus','kolf','Cairan',15000,300,100,'2027-09-30'),
(8,'OBT-008','Salbutamol 2 mg','Tablet','tablet','Bronkodilator',400,40,100,'2027-02-28'),
(9,'OBT-009','Ondansetron 4 mg','Injeksi','ampul','Antiemetik',12000,120,50,'2026-12-15'),
(10,'OBT-010','Cetirizine 10 mg','Tablet','tablet','Antihistamin',600,760,200,'2027-10-31');

INSERT INTO `lab_tests` (`id`,`code`,`name`,`category`,`unit`,`normal_range`,`price`) VALUES
(1,'LAB-HB','Hemoglobin','Hematologi','g/dL','13 - 17',35000),
(2,'LAB-LEU','Leukosit','Hematologi','/µL','4.000 - 10.000',35000),
(3,'LAB-TRB','Trombosit','Hematologi','/µL','150.000 - 400.000',35000),
(4,'LAB-GDS','Gula Darah Sewaktu','Kimia Klinik','mg/dL','< 200',30000),
(5,'LAB-GDP','Gula Darah Puasa','Kimia Klinik','mg/dL','70 - 100',30000),
(6,'LAB-KOL','Kolesterol Total','Kimia Klinik','mg/dL','< 200',45000),
(7,'LAB-SGOT','SGOT','Kimia Klinik','U/L','< 35',40000),
(8,'LAB-SGPT','SGPT','Kimia Klinik','U/L','< 45',40000),
(9,'LAB-URE','Ureum','Kimia Klinik','mg/dL','10 - 50',40000),
(10,'LAB-NS1','NS1 Antigen Dengue','Imunoserologi',NULL,'Negatif',175000),
(11,'LAB-WID','Widal','Imunoserologi',NULL,'Negatif',60000),
(12,'LAB-UR','Urinalisis Lengkap','Urinalisis',NULL,'Normal',50000);

INSERT INTO `radiology_exams` (`id`,`code`,`name`,`modality`,`price`) VALUES
(1,'RAD-TRX','Foto Thorax PA','X-Ray',150000),
(2,'RAD-ABD','BNO / Foto Abdomen','X-Ray',175000),
(3,'RAD-CTK','CT Scan Kepala','CT Scan',950000),
(4,'RAD-USA','USG Abdomen','USG',350000),
(5,'RAD-MRI','MRI Lumbal','MRI',2500000),
(6,'RAD-MMG','Mammografi','Mammografi',600000);

INSERT INTO `patients` (`id`,`mrn`,`nik`,`name`,`gender`,`birth_place`,`birth_date`,`blood_type`,`address`,`phone`,`allergies`,`insurer_id`,`insurance_number`,`created_at`) VALUES
(1,'00012345','3175010101900012','Budi Santoso','L','Jakarta','1990-01-01','O','Jl. Melati No. 10, Jakarta','081234560001','Penisilin',2,'0001234567801',NOW() - INTERVAL 400 DAY),
(2,'00012346','3175010203920005','Siti Aminah','P','Bogor','1992-03-02','A','Jl. Kenanga No. 5, Jakarta','081234560002',NULL,2,'0001234567802',NOW() - INTERVAL 380 DAY),
(3,'00012347','3175011505890008','Roy Kiyoshi','L','Bandung','1989-05-15','B','Jl. Mawar No. 8, Jakarta','081234560003',NULL,3,'PRU-889201',NOW() - INTERVAL 300 DAY),
(4,'00012348','3175012207850003','Dewi Lestari','P','Jakarta','1985-07-22','AB','Jl. Anggrek No. 21, Jakarta','081234560004','Seafood',1,NULL,NOW() - INTERVAL 250 DAY),
(5,'00012349','3175010911700002','Hadi Susanto','L','Semarang','1970-11-09','O','Jl. Cempaka No. 3, Jakarta','081234560005',NULL,2,'0001234567805',NOW() - INTERVAL 200 DAY),
(6,'00012350','3175014403150001','Aisyah Putri','P','Jakarta','2015-03-04','A','Jl. Dahlia No. 14, Jakarta','081234560006',NULL,2,'0001234567806',NOW() - INTERVAL 150 DAY),
(7,'00012351','3175012808600004','Slamet Riyadi','L','Solo','1960-08-28','B','Jl. Teratai No. 9, Jakarta','081234560007','Sulfa',2,'0001234567807',NOW() - INTERVAL 120 DAY),
(8,'00012352','3175016612950006','Maya Sari','P','Depok','1995-12-26','O','Jl. Flamboyan No. 2, Depok','081234560008',NULL,4,'ALZ-552910',NOW() - INTERVAL 90 DAY),
(9,'00012353','3175010304880007','Rizky Ramadhan','L','Jakarta','1988-04-03','A','Jl. Kamboja No. 17, Jakarta','081234560009',NULL,1,NULL,NOW() - INTERVAL 60 DAY),
(10,'00012354','3175015506750009','Nurhayati','P','Bekasi','1975-06-15','B','Jl. Bougenville No. 6, Bekasi','081234560010','Aspirin',2,'0001234567810',NOW() - INTERVAL 30 DAY),
(11,'00012355','3175010101010010','Fajar Nugroho','L','Tangerang','2001-01-01','O','Jl. Seroja No. 11, Tangerang','081234560011',NULL,5,'MDK-110293',NOW() - INTERVAL 10 DAY),
(12,'00012356','3175014909980011','Lestari Wulan','P','Jakarta','1998-09-09','AB','Jl. Tulip No. 4, Jakarta','081234560012',NULL,2,'0001234567812',NOW() - INTERVAL 2 DAY),
(13,'00012357','3175011212820012','Joko Prasetyo','L','Yogyakarta','1982-12-12','A','Jl. Sakura No. 15, Jakarta','081234560013',NULL,1,NULL,NOW()),
(14,'00012358','3175016707930013','Rina Kartika','P','Jakarta','1993-07-27','O','Jl. Lily No. 7, Jakarta','081234560014',NULL,2,'0001234567814',NOW());

-- Kunjungan: hari ini (rawat jalan, IGD) + hari-hari sebelumnya untuk statistik
INSERT INTO `visits` (`id`,`visit_no`,`patient_id`,`visit_type`,`poli_id`,`doctor_id`,`insurer_id`,`queue_no`,`visit_date`,`complaint`,`status`,`created_by`) VALUES
(1,'RJ-0001',1,'rawat_jalan',1,1,2,'A-001',CONCAT(CURDATE(),' 08:05:00'),'Pusing dan tekanan darah tinggi','selesai',2),
(2,'RJ-0002',2,'rawat_jalan',1,1,2,'A-002',CONCAT(CURDATE(),' 08:20:00'),'Kontrol diabetes','diperiksa',2),
(3,'RJ-0003',4,'rawat_jalan',1,1,1,'A-003',CONCAT(CURDATE(),' 08:45:00'),'Nyeri ulu hati','menunggu',2),
(4,'RJ-0004',6,'rawat_jalan',2,2,2,'B-001',CONCAT(CURDATE(),' 09:00:00'),'Demam 3 hari','menunggu',2),
(5,'RJ-0005',8,'rawat_jalan',3,3,4,'C-001',CONCAT(CURDATE(),' 09:10:00'),'Kontrol kehamilan','diperiksa',2),
(6,'RJ-0006',9,'rawat_jalan',7,7,1,'U-001',CONCAT(CURDATE(),' 09:30:00'),'Batuk pilek','selesai',2),
(7,'RJ-0007',13,'rawat_jalan',6,6,1,'J-001',CONCAT(CURDATE(),' 10:00:00'),'Nyeri dada saat aktivitas','menunggu',2),
(8,'RJ-0008',14,'rawat_jalan',8,8,2,'G-001',CONCAT(CURDATE(),' 10:15:00'),'Sakit gigi','menunggu',2),
(9,'IGD-0001',11,'igd',9,9,5,'IGD-01',CONCAT(CURDATE(),' 07:40:00'),'Kecelakaan motor, luka robek di kaki','diperiksa',2),
(10,'IGD-0002',12,'igd',9,9,2,'IGD-02',CONCAT(CURDATE(),' 09:55:00'),'Demam tinggi, bintik merah','diperiksa',2),
(11,'RI-0001',5,'rawat_inap',1,1,2,NULL,NOW() - INTERVAL 3 DAY,'Pneumonia','dirawat',2),
(12,'RI-0002',7,'rawat_inap',6,6,2,NULL,NOW() - INTERVAL 5 DAY,'Nyeri dada hebat','dirawat',2),
(13,'RI-0003',10,'rawat_inap',1,1,2,NULL,NOW() - INTERVAL 2 DAY,'Demam berdarah','dirawat',2),
(14,'RI-0004',3,'rawat_inap',4,4,3,NULL,NOW() - INTERVAL 8 DAY,'Apendisitis akut','selesai',2),
(15,'RJ-0009',3,'rawat_jalan',1,1,3,'A-004',NOW() - INTERVAL 1 DAY,'Kontrol pasca operasi','selesai',2),
(16,'RJ-0010',5,'rawat_jalan',1,1,2,'A-005',NOW() - INTERVAL 4 DAY,'Sesak napas','selesai',2),
(17,'RJ-0011',10,'rawat_jalan',7,7,2,'U-002',NOW() - INTERVAL 6 DAY,'Demam','selesai',2),
(18,'RJ-0012',1,'rawat_jalan',6,6,2,'J-002',NOW() - INTERVAL 12 DAY,'Kontrol jantung','selesai',2),
(19,'RJ-0013',2,'rawat_jalan',7,7,2,'U-003',NOW() - INTERVAL 20 DAY,'Batuk','selesai',2),
(20,'RJ-0014',4,'rawat_jalan',5,5,1,'S-001',NOW() - INTERVAL 25 DAY,'Sakit kepala','selesai',2);

INSERT INTO `doctor_schedules` (`doctor_id`,`poli_id`,`day_of_week`,`start_time`,`end_time`,`quota`) VALUES
(1,1,1,'08:00','12:00',25),(1,1,3,'08:00','12:00',25),(1,1,5,'13:00','16:00',20),
(2,2,1,'09:00','13:00',20),(2,2,4,'09:00','13:00',20),
(3,3,2,'08:00','12:00',15),(3,3,4,'13:00','16:00',15),
(4,4,2,'10:00','14:00',15),(5,5,3,'09:00','12:00',15),
(6,6,1,'13:00','16:00',15),(6,6,5,'08:00','11:00',15),
(7,7,1,'07:00','14:00',40),(7,7,2,'07:00','14:00',40),(7,7,3,'07:00','14:00',40),(7,7,4,'07:00','14:00',40),(7,7,5,'07:00','14:00',40),
(8,8,2,'08:00','12:00',12),(8,8,6,'08:00','12:00',12);

INSERT INTO `appointments` (`appointment_no`,`patient_id`,`doctor_id`,`poli_id`,`appointment_date`,`appointment_time`,`queue_no`,`status`,`notes`) VALUES
('APT-0001',1,1,1,CURDATE(),'08:00','A-001','selesai',NULL),
('APT-0002',2,1,1,CURDATE(),'08:15','A-002','check_in',NULL),
('APT-0003',8,3,3,CURDATE(),'09:00','C-001','check_in','Kontrol kehamilan 28 minggu'),
('APT-0004',13,6,6,CURDATE(),'10:00','J-001','terjadwal',NULL),
('APT-0005',3,1,1,CURDATE() + INTERVAL 1 DAY,'08:30','A-001','terjadwal','Kontrol ulang'),
('APT-0006',6,2,2,CURDATE() + INTERVAL 2 DAY,'09:15','B-002','terjadwal',NULL),
('APT-0007',9,7,7,CURDATE() + INTERVAL 3 DAY,'07:30','U-001','reschedule','Diundur atas permintaan pasien'),
('APT-0008',4,5,5,CURDATE() - INTERVAL 1 DAY,'09:00','S-001','dibatalkan','Pasien berhalangan');

INSERT INTO `medical_records` (`id`,`visit_id`,`patient_id`,`doctor_id`,`subjective`,`objective`,`assessment`,`plan`) VALUES
(1,1,1,1,'Pusing sejak 2 hari, tengkuk terasa berat','TD 160/100 mmHg, N 88x/m','Hipertensi esensial','Amlodipine 10 mg 1x1, kontrol 2 minggu'),
(2,6,9,7,'Batuk pilek 3 hari','Faring hiperemis, T 37,8 C','ISPA','Paracetamol, Cetirizine, istirahat cukup'),
(3,11,5,1,'Sesak dan batuk berdahak','Ronki basah kedua paru, SpO2 93%','Pneumonia','Rawat inap, Ceftriaxone IV, foto thorax'),
(4,12,7,6,'Nyeri dada kiri menjalar ke lengan','EKG ST elevasi','Infark miokard akut','Rawat ICU, konsul jantung'),
(5,13,10,1,'Demam 4 hari, mual','Trombosit 85.000','Demam berdarah dengue','Rawat inap, cairan RL, cek trombosit berkala'),
(6,14,3,4,'Nyeri perut kanan bawah','McBurney (+)','Apendisitis akut','Apendektomi'),
(7,15,3,1,'Kontrol pasca operasi','Luka operasi kering','Pasca apendektomi','Kontrol 1 bulan'),
(8,16,5,1,'Sesak napas','Wheezing (+)','Asma','Salbutamol'),
(9,17,10,7,'Demam','T 38,5 C','Demam tifoid','Lab widal'),
(10,18,1,6,'Kontrol jantung','TD 140/90','Hipertensi esensial','Lanjut obat'),
(11,19,2,7,'Batuk','Ronki (-)','ISPA','Obat simptomatik'),
(12,20,4,5,'Sakit kepala','Neurologis normal','Nyeri kepala','Analgesik');

INSERT INTO `diagnoses` (`record_id`,`icd10_code`,`type`) VALUES
(1,'I10','primer'),(2,'J06.9','primer'),(3,'J18.9','primer'),(4,'I21.9','primer'),(4,'I10','sekunder'),
(5,'A91','primer'),(6,'K35.8','primer'),(7,'K35.8','primer'),(8,'J45.9','primer'),(9,'A01.0','primer'),
(10,'I10','primer'),(11,'J06.9','primer'),(12,'R50.9','primer');

INSERT INTO `vital_signs` (`visit_id`,`patient_id`,`recorded_by`,`systolic`,`diastolic`,`pulse`,`respiration`,`temperature`,`spo2`,`weight`,`height`,`recorded_at`) VALUES
(1,1,5,160,100,88,20,36.7,98,78,170,CONCAT(CURDATE(),' 08:10:00')),
(2,2,5,130,85,80,18,36.5,99,65,158,CONCAT(CURDATE(),' 08:25:00')),
(9,11,5,120,80,96,22,36.9,98,68,172,CONCAT(CURDATE(),' 07:45:00')),
(10,12,5,110,70,104,22,39.2,97,52,160,CONCAT(CURDATE(),' 10:00:00')),
(11,5,5,130,80,100,26,38.4,93,70,168,NOW() - INTERVAL 2 HOUR),
(12,7,5,100,60,110,24,36.8,95,72,165,NOW() - INTERVAL 3 HOUR),
(13,10,5,110,70,92,20,37.9,98,60,155,NOW() - INTERVAL 1 HOUR);

INSERT INTO `orders` (`id`,`order_no`,`visit_id`,`patient_id`,`doctor_id`,`order_type`,`priority`,`status`,`notes`,`ordered_at`) VALUES
(1,'ORD-LAB-0001',2,2,1,'laboratorium','normal','diproses','Kontrol gula darah',CONCAT(CURDATE(),' 08:40:00')),
(2,'ORD-LAB-0002',10,12,9,'laboratorium','cito','baru','Curiga DBD',CONCAT(CURDATE(),' 10:05:00')),
(3,'ORD-LAB-0003',13,10,1,'laboratorium','normal','selesai','Trombosit serial',NOW() - INTERVAL 1 DAY),
(4,'ORD-RAD-0001',11,5,1,'radiologi','normal','diproses','Foto thorax pneumonia',NOW() - INTERVAL 3 DAY),
(5,'ORD-RAD-0002',9,11,9,'radiologi','cito','baru','Curiga fraktur cruris',CONCAT(CURDATE(),' 07:55:00')),
(6,'ORD-RAD-0003',7,13,6,'radiologi','normal','baru','Thorax PA pre-EKG',CONCAT(CURDATE(),' 10:10:00')),
(7,'ORD-OBT-0001',1,1,1,'obat','normal','selesai',NULL,CONCAT(CURDATE(),' 08:30:00')),
(8,'ORD-OBT-0002',6,9,7,'obat','normal','diproses',NULL,CONCAT(CURDATE(),' 09:50:00')),
(9,'ORD-TND-0001',9,11,9,'tindakan','cito','selesai','Hecting luka kaki',CONCAT(CURDATE(),' 08:00:00')),
(10,'ORD-RI-0001',10,12,9,'rawat_inap','normal','baru','Rencana rawat inap bila trombosit turun',CONCAT(CURDATE(),' 10:20:00')),
(11,'ORD-KNS-0001',12,7,6,'konsultasi','cito','diproses','Konsul bedah thorax',NOW() - INTERVAL 4 DAY);

INSERT INTO `order_items` (`order_id`,`item_code`,`item_name`,`qty`) VALUES
(1,'LAB-GDP','Gula Darah Puasa',1),(1,'LAB-KOL','Kolesterol Total',1),
(2,'LAB-NS1','NS1 Antigen Dengue',1),(2,'LAB-TRB','Trombosit',1),
(3,'LAB-TRB','Trombosit',1),(3,'LAB-HB','Hemoglobin',1),
(4,'RAD-TRX','Foto Thorax PA',1),(5,'RAD-TRX','Foto Cruris AP/Lat',1),(6,'RAD-TRX','Foto Thorax PA',1),
(7,'OBT-003','Amlodipine 10 mg',30),(8,'OBT-001','Paracetamol 500 mg',10),(8,'OBT-010','Cetirizine 10 mg',6),
(9,'TND-02','Hecting / Jahit Luka',1),(10,NULL,'Rawat inap kelas II',1),(11,NULL,'Konsultasi Sp.BTKV',1);

INSERT INTO `referrals` (`visit_id`,`referral_type`,`destination`,`reason`,`control_date`) VALUES
(1,'kontrol_ulang','Poli Penyakit Dalam','Evaluasi tekanan darah',CURDATE() + INTERVAL 14 DAY),
(15,'kontrol_ulang','Poli Bedah','Evaluasi luka operasi',CURDATE() + INTERVAL 30 DAY),
(12,'eksternal','RSUP Harapan Kita','Tindakan PCI',NULL),
(6,'internal','Poli Penyakit Dalam','Batuk berulang',NULL);

INSERT INTO `admissions` (`id`,`admission_no`,`visit_id`,`patient_id`,`bed_id`,`doctor_id`,`admit_date`,`discharge_date`,`diagnosis`,`status`,`discharge_summary`) VALUES
(1,'ADM-0001',11,5,7,1,NOW() - INTERVAL 3 DAY,NULL,'Pneumonia','dirawat',NULL),
(2,'ADM-0002',12,7,21,6,NOW() - INTERVAL 5 DAY,NULL,'Infark miokard akut','dirawat',NULL),
(3,'ADM-0003',13,10,3,1,NOW() - INTERVAL 2 DAY,NULL,'Demam berdarah dengue','dirawat',NULL),
(4,'ADM-0004',14,3,4,4,NOW() - INTERVAL 8 DAY,NOW() - INTERVAL 4 DAY,'Apendisitis akut','pulang','Pasca apendektomi, kondisi baik, kontrol poli bedah');

INSERT INTO `bed_transfers` (`admission_id`,`from_bed_id`,`to_bed_id`,`reason`,`transferred_at`) VALUES
(2,25,21,'Perburukan kondisi, pindah ke ICU',NOW() - INTERVAL 5 DAY + INTERVAL 4 HOUR);

INSERT INTO `triages` (`visit_id`,`patient_id`,`triage_level`,`chief_complaint`,`gcs`,`arrival_mode`,`disposition`,`triaged_at`) VALUES
(9,11,'kuning','Luka robek kaki kanan pasca kecelakaan',15,'ambulans','observasi',CONCAT(CURDATE(),' 07:42:00')),
(10,12,'kuning','Demam tinggi 4 hari, petekie',15,'sendiri','observasi',CONCAT(CURDATE(),' 09:57:00')),
(12,7,'merah','Nyeri dada hebat',14,'ambulans','rawat_inap',NOW() - INTERVAL 5 DAY);

INSERT INTO `lab_results` (`order_id`,`lab_test_id`,`result_value`,`flag`,`status`,`validated_by`,`validated_at`) VALUES
(1,5,'186','tinggi','menunggu_validasi',NULL,NULL),
(1,6,'230','tinggi','menunggu_validasi',NULL,NULL),
(2,10,NULL,NULL,'menunggu_sampel',NULL,NULL),
(2,3,NULL,NULL,'menunggu_sampel',NULL,NULL),
(3,3,'85.000','rendah','tervalidasi',8,NOW() - INTERVAL 20 HOUR),
(3,1,'14,2','normal','tervalidasi',8,NOW() - INTERVAL 20 HOUR);

INSERT INTO `lab_specimens` (`order_id`,`specimen_type`,`barcode`,`received_at`,`checklist`,`status`,`notes`,`verified_by`) VALUES
(1,'Darah vena (serum)','SPC-000101',CONCAT(CURDATE(),' 08:55:00'),'Identitas sesuai, Jenis spesimen sesuai, Kondisi baik, Volume cukup','diterima',NULL,8),
(3,'Darah EDTA','SPC-000098',NOW() - INTERVAL 23 HOUR,'Identitas sesuai, Jenis spesimen sesuai, Kondisi baik, Volume cukup','diterima',NULL,8);

INSERT INTO `lab_communications` (`order_id`,`recipient`,`communicated_at`,`note`,`created_by`) VALUES
(3,'dr. Ahmad Fauzi, Sp.PD (DPJP)',NOW() - INTERVAL 20 HOUR,'Trombosit 85.000/uL disampaikan via telepon, DPJP konfirmasi',8);

INSERT INTO `radiology_results` (`order_id`,`exam_id`,`scheduled_at`,`findings`,`impression`,`radiologist_id`,`status`,`reported_at`) VALUES
(4,1,NOW() - INTERVAL 3 DAY,'Tampak infiltrat di lapang bawah paru kanan','Pneumonia lobus inferior dextra',3,'selesai',NOW() - INTERVAL 3 DAY + INTERVAL 3 HOUR),
(5,1,CONCAT(CURDATE(),' 08:30:00'),NULL,NULL,NULL,'menunggu_bacaan',NULL),
(6,1,CONCAT(CURDATE(),' 11:00:00'),NULL,NULL,NULL,'terjadwal',NULL);

INSERT INTO `prescriptions` (`id`,`prescription_no`,`visit_id`,`patient_id`,`doctor_id`,`status`,`verified_by`,`created_at`) VALUES
(1,'RSP-0001',1,1,1,'diserahkan',7,CONCAT(CURDATE(),' 08:35:00')),
(2,'RSP-0002',6,9,7,'diverifikasi',7,CONCAT(CURDATE(),' 09:52:00')),
(3,'RSP-0003',2,2,1,'baru',NULL,CONCAT(CURDATE(),' 09:05:00')),
(4,'RSP-0004',11,5,1,'disiapkan',7,NOW() - INTERVAL 2 HOUR),
(5,'RSP-0005',13,10,1,'baru',NULL,NOW() - INTERVAL 30 MINUTE);
UPDATE `prescriptions` SET `order_id` = 7 WHERE `id` = 1;
UPDATE `prescriptions` SET `order_id` = 8 WHERE `id` = 2;

INSERT INTO `prescription_items` (`prescription_id`,`drug_id`,`qty`,`dosage`,`instructions`) VALUES
(1,3,30,'1 x 1 tablet','Pagi hari'),
(2,1,10,'3 x 1 tablet','Bila demam'),(2,10,6,'1 x 1 tablet','Malam hari'),
(3,4,60,'2 x 1 tablet','Sesudah makan'),
(4,6,6,'2 x 1 g IV','Per 12 jam'),
(5,7,4,'500 ml / 8 jam','IV drip'),(5,1,10,'3 x 1 tablet','Bila demam');

INSERT INTO `drug_returns` (`return_no`,`prescription_id`,`source`,`drug_id`,`qty`,`drug_condition`,`reason`,`status`,`verified_by`,`returned_at`) VALUES
('RT-0001',1,'Hendra Wijaya',3,5,'Kemasan utuh, belum dibuka','Kelebihan jumlah resep','diterima',7,NOW() - INTERVAL 1 DAY),
('RT-0002',4,'Bangsal Melati',6,2,'Kemasan utuh, belum dibuka','Terapi diganti oleh DPJP','menunggu',NULL,NOW() - INTERVAL 2 HOUR);

INSERT INTO `drug_stock_movements` (`drug_id`,`movement_type`,`qty`,`reference`,`notes`,`created_by`,`created_at`) VALUES
(1,'masuk',500,'PO-FAR-2026-031','Pengadaan rutin',7,NOW() - INTERVAL 6 DAY),
(3,'keluar',-30,'RSP-0001','Penyerahan resep',7,CONCAT(CURDATE(),' 08:50:00')),
(3,'retur',5,'RT-0001','Retur diterima',7,NOW() - INTERVAL 1 DAY),
(6,'keluar',-6,'RSP-0004','Penyiapan resep rawat inap',7,NOW() - INTERVAL 2 HOUR);

INSERT INTO `invoices` (`id`,`invoice_no`,`visit_id`,`patient_id`,`insurer_id`,`total`,`discount`,`insurance_cover`,`patient_pay`,`status`,`created_at`,`paid_at`) VALUES
(1,'INV-0001',1,1,2,340000,0,340000,0,'klaim',CONCAT(CURDATE(),' 09:00:00'),NULL),
(2,'INV-0002',6,9,1,178000,0,0,178000,'lunas',CONCAT(CURDATE(),' 10:05:00'),CONCAT(CURDATE(),' 10:10:00')),
(3,'INV-0003',9,11,5,825000,0,600000,225000,'belum_bayar',CONCAT(CURDATE(),' 09:30:00'),NULL),
(4,'INV-0004',14,3,3,12850000,0,10000000,2850000,'cicilan',NOW() - INTERVAL 4 DAY,NULL),
(5,'INV-0005',15,3,3,215000,0,215000,0,'lunas',NOW() - INTERVAL 1 DAY,NOW() - INTERVAL 1 DAY),
(6,'INV-0006',16,5,2,265000,0,265000,0,'klaim',NOW() - INTERVAL 4 DAY,NULL),
(7,'INV-0007',17,10,2,290000,0,290000,0,'klaim',NOW() - INTERVAL 6 DAY,NULL),
(8,'INV-0008',18,1,2,410000,0,410000,0,'klaim',NOW() - INTERVAL 12 DAY,NULL),
(9,'INV-0009',19,2,2,160000,0,160000,0,'klaim',NOW() - INTERVAL 20 DAY,NULL),
(10,'INV-0010',20,4,1,325000,25000,0,300000,'lunas',NOW() - INTERVAL 25 DAY,NOW() - INTERVAL 25 DAY);

UPDATE `invoices` SET `verified` = 1 WHERE `id` IN (1,2,4,5,6,7,8,9,10);
UPDATE `invoices` i JOIN `patients` p ON p.id = i.patient_id SET i.insurance_number = p.insurance_number WHERE i.insurer_id <> 1;

INSERT INTO `invoice_items` (`invoice_id`,`description`,`category`,`qty`,`price`,`subtotal`) VALUES
(1,'Administrasi Rawat Jalan','administrasi',1,25000,25000),(1,'Konsultasi Dokter Spesialis','konsultasi',1,150000,150000),(1,'Amlodipine 10 mg','obat',30,900,27000),(1,'Pemeriksaan EKG','tindakan',1,120000,120000),(1,'Jasa farmasi','lainnya',1,18000,18000),
(2,'Administrasi Rawat Jalan','administrasi',1,25000,25000),(2,'Konsultasi Dokter Umum','konsultasi',1,75000,75000),(2,'Paracetamol 500 mg','obat',10,500,5000),(2,'Cetirizine 10 mg','obat',6,600,3600),(2,'Jasa farmasi','lainnya',1,69400,69400),
(3,'Pelayanan IGD','tindakan',1,200000,200000),(3,'Hecting / Jahit Luka','tindakan',1,150000,150000),(3,'Foto Rontgen Cruris','radiologi',1,175000,175000),(3,'Observasi IGD','kamar',1,300000,300000),
(4,'Kamar Melati Kelas I (4 hari)','kamar',4,750000,3000000),(4,'Tindakan Apendektomi','tindakan',1,8500000,8500000),(4,'Obat & BHP','obat',1,1350000,1350000);

INSERT INTO `payments` (`invoice_id`,`amount`,`method`,`cashier_id`,`paid_at`) VALUES
(2,178000,'qris',6,CONCAT(CURDATE(),' 10:10:00')),
(4,1000000,'debit',6,NOW() - INTERVAL 4 DAY),
(10,300000,'tunai',6,NOW() - INTERVAL 25 DAY);

INSERT INTO `insurance_claims` (`claim_no`,`invoice_id`,`insurer_id`,`amount`,`status`,`submitted_at`) VALUES
('KLM-BPJS-0001',1,2,340000,'diajukan',CONCAT(CURDATE(),' 09:05:00')),
('KLM-PRU-0001',4,3,10000000,'disetujui',NOW() - INTERVAL 4 DAY),
('KLM-BPJS-0002',6,2,265000,'diverifikasi',NOW() - INTERVAL 4 DAY),
('KLM-BPJS-0003',7,2,290000,'dibayar',NOW() - INTERVAL 6 DAY),
('KLM-BPJS-0004',8,2,410000,'dibayar',NOW() - INTERVAL 12 DAY),
('KLM-BPJS-0005',9,2,160000,'ditolak',NOW() - INTERVAL 20 DAY);

INSERT INTO `nursing_notes` (`visit_id`,`patient_id`,`nurse_id`,`assessment`,`nursing_diagnosis`,`intervention`,`evaluation`,`created_at`) VALUES
(11,5,5,'Sesak, RR 26x/m, SpO2 93%','Bersihan jalan napas tidak efektif','Posisi semi fowler, O2 nasal 3 lpm, nebulizer','SpO2 naik menjadi 96%',NOW() - INTERVAL 2 HOUR),
(12,7,5,'Nyeri dada skala 6','Nyeri akut','Monitoring EKG, kolaborasi analgesik','Nyeri turun skala 3',NOW() - INTERVAL 3 HOUR),
(13,10,5,'Demam 37,9 C, petekie (+)','Risiko perdarahan','Monitoring trombosit, cairan sesuai advis','Tidak ada perdarahan aktif',NOW() - INTERVAL 1 HOUR);

INSERT INTO `medication_administrations` (`admission_id`,`drug_id`,`dose`,`scheduled_at`,`given_at`,`nurse_id`,`status`) VALUES
(1,6,'1 g IV',CONCAT(CURDATE(),' 06:00:00'),CONCAT(CURDATE(),' 06:05:00'),5,'diberikan'),
(1,6,'1 g IV',CONCAT(CURDATE(),' 18:00:00'),NULL,NULL,'terjadwal'),
(3,7,'500 ml',CONCAT(CURDATE(),' 08:00:00'),CONCAT(CURDATE(),' 08:10:00'),5,'diberikan'),
(3,1,'500 mg PO',CONCAT(CURDATE(),' 12:00:00'),NULL,NULL,'terjadwal'),
(2,9,'4 mg IV',CONCAT(CURDATE(),' 14:00:00'),NULL,NULL,'terjadwal');

INSERT INTO `inventory_items` (`id`,`code`,`name`,`category`,`unit`,`stock`,`min_stock`,`location`,`price`,`expiry_date`) VALUES
(1,'BRG-001','Sarung Tangan Steril (M)','alkes','box',120,50,'Gudang Utama',85000,'2027-05-31'),
(2,'BRG-002','Masker Bedah','alkes','box',35,60,'Gudang Utama',45000,'2027-12-31'),
(3,'BRG-003','Spuit 3 ml','alkes','pcs',2400,1000,'Gudang Utama',2500,'2028-01-31'),
(4,'BRG-004','Infus Set Dewasa','alkes','pcs',380,200,'Gudang Utama',12000,'2027-08-31'),
(5,'BRG-005','Kasa Steril 16x16','medis','pack',520,300,'Gudang Utama',9000,'2026-12-20'),
(6,'BRG-006','Alkohol Swab','medis','box',90,40,'Gudang Farmasi',30000,'2026-12-10'),
(7,'BRG-007','Kertas HVS A4','nonmedis','rim',64,30,'Gudang Umum',55000,NULL),
(8,'BRG-008','Linen Sprei','nonmedis','pcs',210,100,'Laundry',75000,NULL),
(9,'BRG-009','Cairan Desinfektan 5L','nonmedis','jerigen',12,20,'Gudang Umum',120000,'2027-04-30'),
(10,'BRG-010','Kateter Urin No. 16','alkes','pcs',140,80,'Gudang Utama',18000,'2027-02-28');

INSERT INTO `stock_movements` (`item_id`,`movement_type`,`qty`,`from_location`,`to_location`,`reference`,`created_by`,`created_at`) VALUES
(1,'masuk',100,NULL,'Gudang Utama','PO-2026-118',9,NOW() - INTERVAL 5 DAY),
(3,'masuk',2000,NULL,'Gudang Utama','PO-2026-119',9,NOW() - INTERVAL 4 DAY),
(5,'mutasi',80,'Gudang Utama','Bangsal Melati','MUT-0031',9,NOW() - INTERVAL 2 DAY),
(2,'keluar',25,'Gudang Utama','IGD','REQ-IGD-077',9,NOW() - INTERVAL 1 DAY),
(4,'mutasi',50,'Gudang Utama','IGD','MUT-0032',9,NOW() - INTERVAL 6 HOUR),
(9,'penyesuaian',-2,'Gudang Umum',NULL,'SO-2026-010',10,NOW() - INTERVAL 3 DAY);

INSERT INTO `purchase_requests` (`id`,`pr_no`,`requested_by`,`status`,`notes`,`created_at`) VALUES
(1,'PR-2026-041',9,'diajukan','Stok masker & desinfektan di bawah minimum',NOW() - INTERVAL 1 DAY),
(2,'PR-2026-040',9,'disetujui','Pengadaan rutin alkes',NOW() - INTERVAL 7 DAY);

INSERT INTO `purchase_request_items` (`pr_id`,`item_id`,`qty`) VALUES (1,2,100),(1,9,30),(2,3,2000),(2,1,100);

INSERT INTO `stock_opnames` (`id`,`opname_no`,`location`,`period`,`count_date`,`reason`,`notes`,`status`,`created_by`,`approved_by`,`created_at`,`approved_at`) VALUES
(1,'SO-2026-011','Gudang Utama',DATE_FORMAT(CURDATE(),'%Y-%m'),CURDATE() - INTERVAL 1 DAY,'Barang rusak / pecah','2 box sarung tangan rusak saat penyimpanan','menunggu_approval',9,NULL,NOW() - INTERVAL 1 DAY,NULL),
(2,'SO-2026-010','Gudang Umum',DATE_FORMAT(CURDATE(),'%Y-%m'),CURDATE() - INTERVAL 3 DAY,'Barang rusak / pecah','Jerigen bocor','disetujui',9,10,NOW() - INTERVAL 3 DAY,NOW() - INTERVAL 3 DAY);

-- Batch per lokasi (stok gudang = jumlah batch di lokasi gudang barang tsb)
INSERT INTO `inventory_batches` (`id`,`item_id`,`batch_no`,`expiry_date`,`location`,`qty`) VALUES
(1,1,'B260512',CURDATE() + INTERVAL 75 DAY,'Gudang Utama',40),
(2,1,'B270531','2027-05-31','Gudang Utama',80),
(3,2,'B271231','2027-12-31','Gudang Utama',35),
(4,3,'B280131','2028-01-31','Gudang Utama',2400),
(5,4,'B270831','2027-08-31','Gudang Utama',300),
(6,4,'B270215','2027-02-15','Gudang Utama',80),
(7,5,'B261220','2026-12-20','Gudang Utama',520),
(8,6,'B261210','2026-12-10','Gudang Farmasi',90),
(9,7,'NB-001',NULL,'Gudang Umum',64),
(10,8,'NB-002',NULL,'Laundry',210),
(11,9,'B270430','2027-04-30','Gudang Umum',12),
(12,10,'B270228','2027-02-28','Gudang Utama',140),
(13,5,'B261220','2026-12-20','Bangsal Melati',80),
(14,4,'B270215','2027-02-15','IGD',50);

INSERT INTO `stock_opname_items` (`opname_id`,`item_id`,`batch_id`,`system_qty`,`physical_qty`) VALUES
(1,1,2,80,78),(1,3,4,2400,2400),(1,4,5,300,300),
(2,9,11,14,12);

INSERT INTO `goods_receipts` (`id`,`receipt_no`,`delivery_note`,`po_number`,`supplier`,`received_date`,`checklist`,`created_by`,`created_at`) VALUES
(1,'GR-2026-0001','SJ-2026-118','PO-2026-118','PT Medika Sejahtera',CURDATE() - INTERVAL 5 DAY,'Kuantitas sesuai, Kemasan baik, Batch sesuai, Expiry sesuai',9,NOW() - INTERVAL 5 DAY),
(2,'GR-2026-0002','SJ-2026-119','PO-2026-119','PT Alkes Nusantara',CURDATE() - INTERVAL 4 DAY,'Kuantitas sesuai, Kemasan baik, Batch sesuai, Expiry sesuai',9,NOW() - INTERVAL 4 DAY);

INSERT INTO `goods_receipt_items` (`receipt_id`,`item_id`,`batch_no`,`expiry_date`,`qty`) VALUES
(1,1,'B270531','2027-05-31',100),(2,3,'B280131','2028-01-31',2000);

INSERT INTO `stock_requisitions` (`id`,`req_no`,`destination`,`request_date`,`status`,`notes`,`requested_by`,`processed_by`,`processed_at`) VALUES
(1,'REQ-2026-0031','Bangsal Melati',CURDATE() - INTERVAL 2 DAY,'dikirim','Kebutuhan perawatan luka',5,9,NOW() - INTERVAL 2 DAY),
(2,'REQ-2026-0032','IGD',CURDATE(),'menunggu','Stok IGD menipis',NULL,NULL,NULL),
(3,'REQ-2026-0033','Depo Farmasi Rawat Inap',CURDATE(),'menunggu','Permintaan rutin mingguan',7,NULL,NULL);

INSERT INTO `stock_requisition_items` (`req_id`,`item_id`,`qty`,`batch_id`,`issued_qty`) VALUES
(1,5,80,7,80),
(2,4,40,NULL,NULL),(2,2,10,NULL,NULL),
(3,1,30,NULL,NULL),(3,10,20,NULL,NULL);

INSERT INTO `audit_logs` (`user_id`,`username`,`action`,`module`,`description`,`ip_address`,`created_at`) VALUES
(2,'petugas','CREATE','Patient Registration','Registrasi pasien baru Rina Kartika (00012358)','192.168.1.21',NOW() - INTERVAL 2 HOUR),
(4,'dokter','CREATE','CPOE','Membuat order laboratorium ORD-LAB-0002 (CITO)','192.168.1.34',NOW() - INTERVAL 90 MINUTE),
(7,'farmasi','UPDATE','Pharmacy','Verifikasi resep RSP-0002','192.168.1.40',NOW() - INTERVAL 80 MINUTE),
(6,'kasir','CREATE','Billing & Insurance','Pembayaran INV-0002 via QRIS','192.168.1.45',NOW() - INTERVAL 70 MINUTE),
(5,'perawat','CREATE','Nursing','Catatan keperawatan pasien Nurhayati','192.168.1.38',NOW() - INTERVAL 60 MINUTE),
(9,'gudang','CREATE','Inventory & Warehouse','Mutasi Infus Set ke IGD (MUT-0032)','192.168.1.50',NOW() - INTERVAL 50 MINUTE),
(1,'admin','UPDATE','Master Data & Configuration','Ubah tarif Konsultasi Dokter Spesialis','192.168.1.10',NOW() - INTERVAL 1 DAY),
(1,'admin','LOGIN','Authentication','Login berhasil','192.168.1.10',NOW() - INTERVAL 1 DAY);
