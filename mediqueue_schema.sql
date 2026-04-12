-- ============================================================
--  MediQueue — Full Database Schema + Seed Data
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
--  Compat : MySQL 5.7+
-- ============================================================
-- SEED ACCOUNT CREDENTIALS
--   All seed users share password: password
--   Hash below = password_hash('password', PASSWORD_BCRYPT, ['cost'=>10])
--   To set real passwords after import, run via phpMyAdmin SQL tab:
--     UPDATE users SET password_hash = '<new_hash>' WHERE email = '<email>';
--   Or generate hash in PHP:
--     php -r "echo password_hash('YourPass@1', PASSWORD_BCRYPT);"
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- DROP (safe re-run)
-- ============================================================
DROP TABLE IF EXISTS `waitlist`;
DROP TABLE IF EXISTS `slot_reservations`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `patient_records`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `time_slots`;
DROP TABLE IF EXISTS `patient_profiles`;
DROP TABLE IF EXISTS `doctor_profiles`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- 1. users
-- ============================================================
CREATE TABLE `users` (
  `id`            INT            NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(150)   NOT NULL,
  `email`         VARCHAR(150)   NOT NULL,
  `password_hash` VARCHAR(255)   NOT NULL,
  `phone`         VARCHAR(20)    DEFAULT NULL,
  `role`          ENUM('patient','doctor','staff','admin') NOT NULL DEFAULT 'patient',
  `profile_photo`            VARCHAR(255)   NOT NULL DEFAULT 'default.svg',
  `is_active`                TINYINT(1)     NOT NULL DEFAULT 1,
  `is_verified`              TINYINT(1)     NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(255)   DEFAULT NULL,
  `created_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 1b. patient_profiles
-- ============================================================
CREATE TABLE `patient_profiles` (
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

-- ============================================================
-- 2. doctor_profiles
-- ============================================================
CREATE TABLE `doctor_profiles` (
  `id`                    INT             NOT NULL AUTO_INCREMENT,
  `user_id`               INT             NOT NULL,
  `specialization`        VARCHAR(100)    DEFAULT NULL,
  `bio`                   TEXT            DEFAULT NULL,
  `years_experience`      INT             NOT NULL DEFAULT 0,
  `clinic_address`        VARCHAR(255)    DEFAULT NULL,
  `consultation_fee`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `available_days`        JSON            DEFAULT NULL,
  `consultation_duration` INT             NOT NULL DEFAULT 30,
  `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dp_user_id` (`user_id`),
  CONSTRAINT `fk_dp_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. time_slots
-- ============================================================
CREATE TABLE `time_slots` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `doctor_id`    INT          NOT NULL,
  `slot_date`    DATE         NOT NULL,
  `start_time`   TIME         NOT NULL,
  `end_time`     TIME         NOT NULL,
  `is_available` TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`doctor_id`, `slot_date`, `start_time`),
  KEY `idx_ts_date` (`slot_date`),
  KEY `idx_ts_available` (`is_available`),
  CONSTRAINT `fk_ts_doctor`
    FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. appointments
-- ============================================================
CREATE TABLE `appointments` (
  `id`               INT          NOT NULL AUTO_INCREMENT,
  `patient_id`       INT          NOT NULL,
  `doctor_id`        INT          NOT NULL,
  `slot_id`          INT          NOT NULL,
  `appointment_date` DATE         NOT NULL,
  `start_time`       TIME         NOT NULL,
  `status`              ENUM('pending','confirmed','in_progress','completed','cancelled','no_show','rescheduled')
                                     NOT NULL DEFAULT 'pending',
  `visit_type`          VARCHAR(50)  DEFAULT NULL,
  `reason_for_visit`    TEXT         DEFAULT NULL,
  `reminder_preference` ENUM('email','sms','both') NOT NULL DEFAULT 'email',
  `notes`               TEXT         DEFAULT NULL,
  `cancelled_at`        DATETIME     DEFAULT NULL,
  `cancellation_reason` TEXT         DEFAULT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appt_patient`  (`patient_id`),
  KEY `idx_appt_doctor`   (`doctor_id`),
  KEY `idx_appt_date`     (`appointment_date`),
  KEY `idx_appt_status`   (`status`),
  CONSTRAINT `fk_appt_patient`
    FOREIGN KEY (`patient_id`) REFERENCES `users`       (`id`),
  CONSTRAINT `fk_appt_doctor`
    FOREIGN KEY (`doctor_id`)  REFERENCES `users`       (`id`),
  CONSTRAINT `fk_appt_slot`
    FOREIGN KEY (`slot_id`)    REFERENCES `time_slots`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. patient_records
-- ============================================================
CREATE TABLE `patient_records` (
  `id`             INT      NOT NULL AUTO_INCREMENT,
  `patient_id`     INT      NOT NULL,
  `doctor_id`      INT      NOT NULL,
  `appointment_id` INT      NOT NULL,
  `diagnosis`      TEXT     DEFAULT NULL,
  `prescription`   TEXT     DEFAULT NULL,
  `notes`          TEXT     DEFAULT NULL,
  `visit_date`     DATE     NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_patient`    (`patient_id`),
  KEY `idx_pr_doctor`     (`doctor_id`),
  KEY `idx_pr_visit_date` (`visit_date`),
  CONSTRAINT `fk_pr_patient`
    FOREIGN KEY (`patient_id`)     REFERENCES `users`        (`id`),
  CONSTRAINT `fk_pr_doctor`
    FOREIGN KEY (`doctor_id`)      REFERENCES `users`        (`id`),
  CONSTRAINT `fk_pr_appointment`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. feedback
-- ============================================================
CREATE TABLE `feedback` (
  `id`             INT       NOT NULL AUTO_INCREMENT,
  `patient_id`     INT       NOT NULL,
  `doctor_id`      INT       NOT NULL,
  `appointment_id` INT       NOT NULL,
  `rating`         TINYINT   NOT NULL,          -- enforced in app layer (1-5)
  `comments`       TEXT      DEFAULT NULL,
  `created_at`     DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fb_appointment` (`appointment_id`),
  KEY `idx_fb_doctor`  (`doctor_id`),
  KEY `idx_fb_patient` (`patient_id`),
  -- CHECK enforced only in MySQL 8.0+; app layer validates for 5.7
  CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5),
  CONSTRAINT `fk_fb_patient`
    FOREIGN KEY (`patient_id`)     REFERENCES `users`        (`id`),
  CONSTRAINT `fk_fb_doctor`
    FOREIGN KEY (`doctor_id`)      REFERENCES `users`        (`id`),
  CONSTRAINT `fk_fb_appointment`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `user_id`        INT          NOT NULL,
  `appointment_id` INT          DEFAULT NULL,
  `type`           ENUM('email','sms','system') NOT NULL DEFAULT 'email',
  `subject`        VARCHAR(255) DEFAULT NULL,
  `message`        TEXT         DEFAULT NULL,
  `status`         ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `is_read`        TINYINT(1)   NOT NULL DEFAULT 0,
  `sent_at`        DATETIME     DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`   (`user_id`),
  KEY `idx_notif_status` (`status`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`)        REFERENCES `users`        (`id`),
  CONSTRAINT `fk_notif_appt`
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. password_resets
-- ============================================================
CREATE TABLE `password_resets` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(255) NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. slot_reservations (temporary hold during booking)
-- ============================================================
CREATE TABLE `slot_reservations` (
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
-- 10. waitlist
-- ============================================================
CREATE TABLE `waitlist` (
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

SET foreign_key_checks = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- ── 1. Users ─────────────────────────────────────────────
-- password_hash('password', PASSWORD_BCRYPT) — seed password = "password"
-- Change all passwords after first login via admin panel.
INSERT INTO `users`
  (`id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `profile_photo`, `is_active`, `is_verified`)
VALUES
  -- Admin
  (1, 'System Administrator', 'admin@mediqueue.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 912 000 0001', 'admin', 'default.svg', 1, 1),

  -- Doctors
  (2, 'Ana Reyes', 'ana.reyes@mediqueue.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 912 000 0002', 'doctor', 'default.svg', 1, 1),

  (3, 'Marco Santos', 'marco.santos@mediqueue.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 912 000 0003', 'doctor', 'default.svg', 1, 1),

  (4, 'Liza Cruz', 'liza.cruz@mediqueue.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 912 000 0004', 'doctor', 'default.svg', 1, 1),

  -- Patients (Filipino names)
  (5, 'Juan dela Cruz', 'juan.delacruz@email.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 917 111 0005', 'patient', 'default.svg', 1, 1),

  (6, 'Maria Santos', 'maria.santos@email.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 917 111 0006', 'patient', 'default.svg', 1, 1),

  (7, 'Pedro Reyes', 'pedro.reyes@email.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 917 111 0007', 'patient', 'default.svg', 1, 1),

  (8, 'Rosa Garcia', 'rosa.garcia@email.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 917 111 0008', 'patient', 'default.svg', 1, 1),

  (9, 'Carlo Bautista', 'carlo.bautista@email.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   '+63 917 111 0009', 'patient', 'default.svg', 1, 1);

-- ── 1b. Patient Profiles (seed) ──────────────────────────
INSERT INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `address`) VALUES
  (5, '1990-05-15', 'male',   'Malolos, Bulacan'),
  (6, '1988-11-22', 'female', 'Meycauayan, Bulacan'),
  (7, '1995-03-10', 'male',   'San Jose del Monte, Bulacan'),
  (8, '1992-07-04', 'female', 'Obando, Bulacan'),
  (9, '1997-01-28', 'male',   'Marilao, Bulacan');

-- ── 2. Doctor Profiles ───────────────────────────────────
INSERT INTO `doctor_profiles`
  (`user_id`, `specialization`, `bio`, `years_experience`,
   `clinic_address`, `consultation_fee`, `available_days`, `consultation_duration`)
VALUES
  (2, 'General Medicine',
   'Dr. Ana Reyes has over 10 years of experience in general practice, committed to comprehensive and compassionate patient care.',
   10, 'Room 101, MediQueue Clinic, Quezon City', 500.00,
   '["Monday","Tuesday","Wednesday","Thursday","Friday"]', 30),

  (3, 'Pediatrics',
   'Dr. Marco Santos is a board-certified pediatrician specializing in child growth, development, and preventive healthcare.',
   8, 'Room 102, MediQueue Clinic, Quezon City', 600.00,
   '["Monday","Wednesday","Friday"]', 30),

  (4, 'Dermatology',
   'Dr. Liza Cruz specializes in skin conditions and cosmetic dermatology with over 6 years of clinical experience.',
   6, 'Room 103, MediQueue Clinic, Quezon City', 750.00,
   '["Tuesday","Thursday","Saturday"]', 30);

-- ── 3. Time Slots (CURDATE() + 6 days, 09:00–17:00, skip 12:00–13:00) ──
-- Uses a stored procedure so slots are always relative to import date.
DELIMITER $$

CREATE PROCEDURE `mq_generate_slots`()
BEGIN
  DECLARE d       INT  DEFAULT 0;
  DECLARE did     INT;
  DECLARE sdate   DATE;
  DECLARE stime   TIME;
  DECLARE etime   TIME;

  WHILE d < 7 DO
    SET sdate = DATE_ADD(CURDATE(), INTERVAL d DAY);

    -- Iterate doctors 2, 3, 4
    SET did = 2;
    WHILE did <= 4 DO
      -- Morning block  09:00–12:00 (6 slots × 30 min)
      SET stime = '09:00:00';
      WHILE stime < '12:00:00' DO
        SET etime = ADDTIME(stime, '00:30:00');
        INSERT IGNORE INTO `time_slots` (`doctor_id`, `slot_date`, `start_time`, `end_time`)
        VALUES (did, sdate, stime, etime);
        SET stime = etime;
      END WHILE;

      -- Afternoon block  13:00–17:00 (8 slots × 30 min)
      SET stime = '13:00:00';
      WHILE stime < '17:00:00' DO
        SET etime = ADDTIME(stime, '00:30:00');
        INSERT IGNORE INTO `time_slots` (`doctor_id`, `slot_date`, `start_time`, `end_time`)
        VALUES (did, sdate, stime, etime);
        SET stime = etime;
      END WHILE;

      SET did = did + 1;
    END WHILE;

    SET d = d + 1;
  END WHILE;
END$$

DELIMITER ;

CALL `mq_generate_slots`();
DROP PROCEDURE `mq_generate_slots`;

-- ── 4. Appointments (8 rows, mixed statuses) ─────────────
-- Find slot IDs dynamically so these work on any import date.

-- Appt 1: Juan dela Cruz — Dr. Ana Reyes — today 09:00 — COMPLETED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 5, 2, ts.`id`, ts.`slot_date`, ts.`start_time`, 'completed', 'Annual check-up and general health screening'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 2 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '09:00:00'
LIMIT 1;
SET @a1 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a1);

-- Appt 2: Maria Santos — Dr. Ana Reyes — today 09:30 — CONFIRMED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 6, 2, ts.`id`, ts.`slot_date`, ts.`start_time`, 'confirmed', 'Persistent cough and flu symptoms'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 2 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '09:30:00'
LIMIT 1;
SET @a2 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a2);

-- Appt 3: Pedro Reyes — Dr. Marco Santos — today 09:00 — COMPLETED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 7, 3, ts.`id`, ts.`slot_date`, ts.`start_time`, 'completed', 'Child vaccination and developmental assessment'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 3 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '09:00:00'
LIMIT 1;
SET @a3 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a3);

-- Appt 4: Rosa Garcia — Dr. Marco Santos — today 10:00 — CANCELLED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`, `notes`)
SELECT 8, 3, ts.`id`, ts.`slot_date`, ts.`start_time`, 'cancelled', 'Recurring skin rash', 'Patient cancelled due to scheduling conflict.'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 3 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '10:00:00'
LIMIT 1;
SET @a4 = LAST_INSERT_ID();
-- Slot stays available since appointment was cancelled

-- Appt 5: Carlo Bautista — Dr. Liza Cruz — today 09:00 — COMPLETED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 9, 4, ts.`id`, ts.`slot_date`, ts.`start_time`, 'completed', 'Acne treatment consultation'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 4 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '09:00:00'
LIMIT 1;
SET @a5 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a5);

-- Appt 6: Juan dela Cruz — Dr. Liza Cruz — today 09:30 — COMPLETED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 5, 4, ts.`id`, ts.`slot_date`, ts.`start_time`, 'completed', 'Skin patch inspection and allergy test'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 4 AND ts.`slot_date` = CURDATE() AND ts.`start_time` = '09:30:00'
LIMIT 1;
SET @a6 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a6);

-- Appt 7: Maria Santos — Dr. Ana Reyes — tomorrow 09:00 — PENDING
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`)
SELECT 6, 2, ts.`id`, ts.`slot_date`, ts.`start_time`, 'pending', 'Follow-up consultation for previous visit'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 2 AND ts.`slot_date` = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND ts.`start_time` = '09:00:00'
LIMIT 1;
SET @a7 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a7);

-- Appt 8: Pedro Reyes — Dr. Marco Santos — tomorrow 10:00 — RESCHEDULED
INSERT INTO `appointments`
  (`patient_id`, `doctor_id`, `slot_id`, `appointment_date`, `start_time`, `status`, `reason_for_visit`, `notes`)
SELECT 7, 3, ts.`id`, ts.`slot_date`, ts.`start_time`, 'rescheduled', 'High fever and chills', 'Rescheduled by patient request.'
FROM `time_slots` ts
WHERE ts.`doctor_id` = 3 AND ts.`slot_date` = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND ts.`start_time` = '10:00:00'
LIMIT 1;
SET @a8 = LAST_INSERT_ID();
UPDATE `time_slots` SET `is_available` = 0
WHERE `id` = (SELECT `slot_id` FROM `appointments` WHERE `id` = @a8);

-- ── 5. Patient Records (3 entries for completed appointments) ──
INSERT INTO `patient_records`
  (`patient_id`, `doctor_id`, `appointment_id`, `diagnosis`, `prescription`, `notes`, `visit_date`)
VALUES
  -- Record for Appt 1: Juan — Dr. Ana Reyes
  (5, 2, @a1,
   'General good health. Mild hypertension noted (130/85 mmHg).',
   'Amlodipine 5mg once daily. Monitor BP weekly.',
   'Patient advised on low-sodium diet and regular exercise.',
   CURDATE()),

  -- Record for Appt 3: Pedro — Dr. Marco Santos
  (7, 3, @a3,
   'Healthy child, age-appropriate development. Vaccination up to date.',
   'None required.',
   'Next scheduled vaccination in 6 months. Parents given growth chart.',
   CURDATE()),

  -- Record for Appt 6: Juan — Dr. Liza Cruz
  (5, 4, @a6,
   'Mild atopic dermatitis (eczema) on forearms.',
   'Hydrocortisone cream 1% — apply twice daily for 7 days. Antihistamine (Cetirizine 10mg) at night.',
   'Avoid harsh soaps and synthetic fabrics. Return if no improvement in 2 weeks.',
   CURDATE());

-- ── 6. Feedback (4 entries for completed appointments) ───
INSERT INTO `feedback`
  (`patient_id`, `doctor_id`, `appointment_id`, `rating`, `comments`)
VALUES
  (5, 2, @a1, 5, 'Dr. Reyes was very thorough and explained everything clearly. Highly recommend!'),
  (7, 3, @a3, 4, 'Dr. Santos was very professional and gentle with my child. Great experience.'),
  (9, 4, @a5, 5, 'Dr. Cruz listened carefully and gave practical advice. I felt very well cared for.'),
  (5, 4, @a6, 5, 'Excellent consultation. Dr. Cruz immediately identified the issue and the prescription worked perfectly.');

-- ============================================================
-- End of mediqueue_schema.sql
-- ============================================================
