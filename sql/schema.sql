-- FilmTracker Database Schema
-- MySQL/MariaDB Database Structure
-- Character Set: utf8mb4

CREATE DATABASE IF NOT EXISTS `filmtracker` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `filmtracker`;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `email_verified` TINYINT(1) DEFAULT 0,
    `language` VARCHAR(10) DEFAULT 'ru',
    `theme` ENUM('dark', 'light') DEFAULT 'dark',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `is_banned` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица медиа (фильмы и сериалы)
CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `original_title` VARCHAR(255) DEFAULT NULL,
    `type` ENUM('movie', 'series') NOT NULL,
    `description` TEXT,
    `release_year` INT UNSIGNED,
    `duration` INT UNSIGNED DEFAULT NULL COMMENT 'Минуты для фильмов',
    `poster_url` VARCHAR(500) DEFAULT NULL,
    `backdrop_url` VARCHAR(500) DEFAULT NULL,
    `tmdb_id` INT UNSIGNED DEFAULT NULL,
    `imdb_id` VARCHAR(20) DEFAULT NULL,
    `imdb_rating` DECIMAL(3,1) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_release_year` (`release_year`),
    INDEX `idx_tmdb_id` (`tmdb_id`),
    FULLTEXT `idx_search` (`title`, `original_title`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сезонов (для сериалов)
CREATE TABLE IF NOT EXISTS `seasons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `media_id` INT UNSIGNED NOT NULL,
    `season_number` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `episode_count` INT UNSIGNED DEFAULT 0,
    `air_date` DATE DEFAULT NULL,
    `poster_url` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_season` (`media_id`, `season_number`),
    INDEX `idx_media_id` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица эпизодов (для сериалов)
CREATE TABLE IF NOT EXISTS `episodes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `season_id` INT UNSIGNED NOT NULL,
    `episode_number` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `duration` INT UNSIGNED DEFAULT NULL,
    `air_date` DATE DEFAULT NULL,
    `still_url` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`season_id`) REFERENCES `seasons`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_episode` (`season_id`, `episode_number`),
    INDEX `idx_season_id` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица истории просмотров
CREATE TABLE IF NOT EXISTS `user_watch_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `media_id` INT UNSIGNED NOT NULL,
    `episode_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL для фильмов',
    `watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `rating` DECIMAL(3,1) DEFAULT NULL COMMENT '1-10 с шагом 0.5',
    `review` TEXT,
    `is_rewatch` TINYINT(1) DEFAULT 0,
    `rewatch_count` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_media` (`user_id`, `media_id`),
    INDEX `idx_watched_at` (`watched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица списков пользователей
CREATE TABLE IF NOT EXISTS `user_lists` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `media_id` INT UNSIGNED NOT NULL,
    `list_type` ENUM('watchlist', 'watching', 'completed', 'dropped', 'on_hold') NOT NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `progress` INT UNSIGNED DEFAULT 0 COMMENT 'Процент просмотра для сериалов',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_media_list` (`user_id`, `media_id`, `list_type`),
    INDEX `idx_user_list` (`user_id`, `list_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов и комментариев
CREATE TABLE IF NOT EXISTS `reviews_comments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `media_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `rating` DECIMAL(3,1) DEFAULT NULL,
    `contains_spoilers` TINYINT(1) DEFAULT 0,
    `is_approved` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    INDEX `idx_media_id` (`media_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица друзей и подписчиков
CREATE TABLE IF NOT EXISTS `friends_followers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Кто отправляет запрос/подписку',
    `friend_id` INT UNSIGNED NOT NULL COMMENT 'На кого подписываются',
    `status` ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    `is_follow` TINYINT(1) DEFAULT 0 COMMENT '1 = подписка, 0 = запрос в друзья',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_friendship` (`user_id`, `friend_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_friend_id` (`friend_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица уведомлений
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица жанров
CREATE TABLE IF NOT EXISTS `genres` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `name_ru` VARCHAR(100) DEFAULT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь медиа и жанров (многие ко многим)
CREATE TABLE IF NOT EXISTS `media_genres` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `media_id` INT UNSIGNED NOT NULL,
    `genre_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`genre_id`) REFERENCES `genres`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_media_genre` (`media_id`, `genre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица актеров
CREATE TABLE IF NOT EXISTS `actors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `name_ru` VARCHAR(255) DEFAULT NULL,
    `photo_url` VARCHAR(500) DEFAULT NULL,
    `biography` TEXT,
    `birth_date` DATE DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь медиа и актеров (многие ко многим)
CREATE TABLE IF NOT EXISTS `media_actors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `media_id` INT UNSIGNED NOT NULL,
    `actor_id` INT UNSIGNED NOT NULL,
    `character_name` VARCHAR(255) DEFAULT NULL,
    `order` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`actor_id`) REFERENCES `actors`(`id`) ON DELETE CASCADE,
    INDEX `idx_media_id` (`media_id`),
    INDEX `idx_order` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица токенов верификации email
CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица токенов сброса пароля
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица очереди email
CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `attempts` INT UNSIGNED DEFAULT 0,
    `max_attempts` INT UNSIGNED DEFAULT 3,
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек email пользователей
CREATE TABLE IF NOT EXISTS `user_email_preferences` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `notify_friend_requests` TINYINT(1) DEFAULT 1,
    `notify_new_episodes` TINYINT(1) DEFAULT 1,
    `notify_reviews` TINYINT(1) DEFAULT 1,
    `notify_weekly_digest` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица логов отправленных email
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `status` ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `error_message` TEXT,
    PRIMARY KEY (`id`),
    INDEX `idx_to_email` (`to_email`),
    INDEX `idx_sent_at` (`sent_at`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица попыток входа (для защиты от брутфорса)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `success` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_email_ip` (`email`, `ip_address`),
    INDEX `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



