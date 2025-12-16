-- Удаление неиспользуемых таблиц из базы данных FilmTracker
-- ВНИМАНИЕ: Выполняйте только после резервного копирования!

USE `filmtracker`;

-- Сначала удаляем таблицы, которые ссылаются на episodes
-- Удаление внешнего ключа episode_id из user_watch_history
-- MySQL не поддерживает IF EXISTS для DROP FOREIGN KEY, поэтому используем другой подход
SET @fk_name = (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = 'filmtracker' 
                AND TABLE_NAME = 'user_watch_history' 
                AND COLUMN_NAME = 'episode_id' 
                AND REFERENCED_TABLE_NAME = 'episodes' 
                LIMIT 1);

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE `user_watch_history` DROP FOREIGN KEY `', @fk_name, '`'), 
    'SELECT "Foreign key not found"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Удаление поля episode_id из user_watch_history
ALTER TABLE `user_watch_history` DROP COLUMN IF EXISTS `episode_id`;

-- Удаление таблицы episodes (не используется)
DROP TABLE IF EXISTS `episodes`;

-- Удаление таблицы notifications (не используется)
DROP TABLE IF EXISTS `notifications`;

-- Удаление связующей таблицы media_actors (не используется)
DROP TABLE IF EXISTS `media_actors`;

-- Удаление таблицы actors (не используется)
DROP TABLE IF EXISTS `actors`;

