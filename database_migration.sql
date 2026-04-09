-- ============================================================
-- MediQueue — Database Migration (ALTER statements)
-- Run this on your EXISTING ByetHost database via phpMyAdmin SQL tab
-- Compatible with MySQL 5.7+
-- ============================================================
-- IMPORTANT: Run these statements ONE SECTION AT A TIME in phpMyAdmin.
-- If a statement fails (e.g. column already exists), skip it and continue.
-- ============================================================

-- ============================================================
-- 1. ADD COLUMNS TO `users` TABLE
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `email_verification_token` VARCHAR(255) DEFAULT NULL AFTER `is_verified`;

-- Mark all existing users as verified (so they can still log in)
UPDATE `users` SET `is_verified` = 1;

-- ============================================================
-- 2. MODIFY `appointments` STATUS ENUM (add no_show, in_progress)
-- ============================================================
ALTER TABLE `appointments`
  MODIFY COLUMN `status` ENUM('pending','confirmed','in_progress','completed','cancelled','no_show','rescheduled')
    NOT NULL DEFAULT 'pending';

-- ============================================================
-- 3. ADD NEW COLUMNS TO `appointments` TABLE
-- ============================================================
ALTER TABLE `appointments`
  ADD COLUMN `visit_type` VARCHAR(50) DEFAULT NULL AFTER `status`,
  ADD COLUMN `reminder_preference` ENUM('email','sms','both') NOT NULL DEFAULT 'email' AFTER `reason_for_visit`,
  ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL AFTER `notes`,
  ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL AFTER `cancelled_at`;

-- ============================================================
-- 4. CREATE `patient_profiles` TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `patient_profiles` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `user_id`         INT          NOT NULL,
  `date_of_birth`   DATE         DEFAULT NULL,
  `gender`          ENUM('male','female','prefer_not_to_say') DEFAULT NULL,
  `address`         VARCHAR(250) DEFAULT NULL,
  `blood_type`      VARCHAR(5)   DEFAULT NULL,
  `allergies`       TEXT         DEFAULT NULL,
  `medical_history` TEXT         DEFAULT NULL,
  `emergency_contact_name`  VARCHAR(100) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20)  DEFAULT NULL,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pp_user_id` (`user_id`),
  CONSTRAINT `fk_pp_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert profiles for existing patients (optional seed data)
INSERT IGNORE INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `address`) VALUES
  (5, '1990-05-15', 'male',   'Malolos, Bulacan'),
  (6, '1988-11-22', 'female', 'Meycauayan, Bulacan'),
  (7, '1995-03-10', 'male',   'San Jose del Monte, Bulacan'),
  (8, '1992-07-04', 'female', 'Obando, Bulacan'),
  (9, '1997-01-28', 'male',   'Marilao, Bulacan');

-- ============================================================
-- 5. CREATE `slot_reservations` TABLE (temporary hold during booking)
-- ============================================================
CREATE TABLE IF NOT EXISTS `slot_reservations` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `slot_id`    INT          NOT NULL,
  `user_id`    INT          NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sr_slot` (`slot_id`),
  CONSTRAINT `fk_sr_slot`
    FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. CREATE `waitlist` TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `waitlist` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `patient_id` INT          NOT NULL,
  `doctor_id`  INT          NOT NULL,
  `preferred_date` DATE     NOT NULL,
  `status`     ENUM('waiting','notified','booked','expired') NOT NULL DEFAULT 'waiting',
  `notified_at` DATETIME   DEFAULT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wl_patient` (`patient_id`),
  KEY `idx_wl_doctor` (`doctor_id`),
  KEY `idx_wl_status` (`status`),
  CONSTRAINT `fk_wl_patient`
    FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_wl_doctor`
    FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONE! All migration statements applied.
-- ============================================================
