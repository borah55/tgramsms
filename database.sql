-- =====================================================
-- Dogecoin Cloud Mining Platform - Database Schema
-- MySQL 5.7+ / MariaDB 10.2+
-- Charset: utf8mb4 / Engine: InnoDB
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet`        VARCHAR(64)  NOT NULL,
  `pin_hash`      VARCHAR(255) NOT NULL,
  `username`      VARCHAR(64)  DEFAULT NULL,
  `referral_code` VARCHAR(16)  NOT NULL,
  `referred_by`   INT(11) UNSIGNED DEFAULT NULL,
  `mining_power`  DECIMAL(20,4) NOT NULL DEFAULT 100.0000,
  `daily_reward`  DECIMAL(20,8) NOT NULL DEFAULT 0.20000000,
  `balance`       DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `total_mined`   DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `total_deposit` DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `total_withdraw`DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `referral_earnings` DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `last_mining_at` DATETIME DEFAULT NULL,
  `status`        ENUM('active','disabled','banned') NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(128) DEFAULT NULL,
  `last_ip`       VARCHAR(45) DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wallet` (`wallet`),
  UNIQUE KEY `uniq_ref` (`referral_code`),
  KEY `idx_referred_by` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Login Attempts (anti-bruteforce)
-- =====================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `wallet` VARCHAR(64) DEFAULT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip`,`created_at`),
  KEY `idx_wallet_time` (`wallet`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Mining Plans
-- =====================================================
CREATE TABLE IF NOT EXISTS `plans` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `mining_speed` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `price` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `daily_reward` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `duration_days` INT(11) NOT NULL DEFAULT 30,
  `bonus_percent` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- User Mining Plans (purchases)
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_plans` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `plan_id` INT(11) UNSIGNED NOT NULL,
  `mining_speed` DECIMAL(20,4) NOT NULL,
  `daily_reward` DECIMAL(20,8) NOT NULL,
  `start_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `is_lifetime` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_plan` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Deposits
-- =====================================================
CREATE TABLE IF NOT EXISTS `deposits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `txn_id` VARCHAR(128) DEFAULT NULL,
  `address` VARCHAR(128) DEFAULT NULL,
  `currency` VARCHAR(16) NOT NULL DEFAULT 'DOGE',
  `amount` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `amount_usd` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `status` ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `gateway` VARCHAR(32) NOT NULL DEFAULT 'coinpayments',
  `meta` TEXT DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_txn` (`txn_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Withdrawals
-- =====================================================
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `wallet` VARCHAR(128) NOT NULL,
  `amount` DECIMAL(20,8) NOT NULL,
  `fee` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `net_amount` DECIMAL(20,8) NOT NULL,
  `status` ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `txn_id` VARCHAR(128) DEFAULT NULL,
  `admin_note` VARCHAR(255) DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Mining Logs (lifetime history of mining payouts)
-- =====================================================
CREATE TABLE IF NOT EXISTS `mining_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `amount` DECIMAL(20,8) NOT NULL,
  `mining_power` DECIMAL(20,4) NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Referral Earnings
-- =====================================================
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id` INT(11) UNSIGNED NOT NULL,
  `referred_id` INT(11) UNSIGNED NOT NULL,
  `level` TINYINT NOT NULL DEFAULT 1,
  `amount` DECIMAL(20,8) NOT NULL DEFAULT 0,
  `source` VARCHAR(32) NOT NULL DEFAULT 'deposit',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_referred` (`referred_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `title` VARCHAR(120) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Support Tickets
-- =====================================================
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `subject` VARCHAR(160) NOT NULL,
  `status` ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) UNSIGNED NOT NULL,
  `from_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Announcements / Banners
-- =====================================================
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(120) NOT NULL,
  `body` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Security Logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `meta` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Admin Users
-- =====================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(32) NOT NULL DEFAULT 'admin',
  `last_login_at` DATETIME DEFAULT NULL,
  `last_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username=admin password=admin123 (change after install)
INSERT INTO `admins` (`username`, `password_hash`, `role`)
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'superadmin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- =====================================================
-- Settings (key/value store)
-- =====================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `name` VARCHAR(80) NOT NULL,
  `value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`name`, `value`) VALUES
('site_name','DogeMine Cloud'),
('site_tagline','Mine Dogecoin in the Cloud'),
('site_email','support@dogemine.local'),
('signup_bonus_power','100'),
('signup_bonus_daily','0.20000000'),
('referral_percent','15'),
('min_deposit','5'),
('min_withdraw','50'),
('withdraw_fee','1'),
('daily_withdraw_limit','1000'),
('maintenance_mode','0'),
('cp_merchant_id',''),
('cp_public_key',''),
('cp_private_key',''),
('cp_ipn_secret',''),
('ga_tracking_id',''),
('fake_notifications','1')
ON DUPLICATE KEY UPDATE `name` = `name`;

-- =====================================================
-- Default Mining Plans
-- =====================================================
INSERT INTO `plans` (`name`,`mining_speed`,`price`,`daily_reward`,`duration_days`,`bonus_percent`,`status`) VALUES
('Starter',     500,    10,   1.5,   30, 0,  'active'),
('Bronze',     1500,    25,   4.5,   60, 5,  'active'),
('Silver',     5000,    75,  17.0,   90, 10, 'active'),
('Gold',      15000,   200,  55.0,  120, 15, 'active'),
('Diamond',   50000,   600, 200.0,  180, 20, 'active');

SET FOREIGN_KEY_CHECKS = 1;
