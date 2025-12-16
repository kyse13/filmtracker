-- FilmTracker - Sample Data
-- Тестовые данные для разработки

USE `filmtracker`;

-- Вставка жанров
INSERT INTO `genres` (`name`, `name_ru`, `slug`) VALUES
('Action', 'Боевик', 'action'),
('Comedy', 'Комедия', 'comedy'),
('Drama', 'Драма', 'drama'),
('Thriller', 'Триллер', 'thriller'),
('Horror', 'Ужасы', 'horror'),
('Sci-Fi', 'Научная фантастика', 'sci-fi'),
('Romance', 'Романтика', 'romance'),
('Crime', 'Криминал', 'crime'),
('Mystery', 'Детектив', 'mystery'),
('Fantasy', 'Фэнтези', 'fantasy'),
('Adventure', 'Приключения', 'adventure'),
('Animation', 'Анимация', 'animation');

-- Создание тестового администратора
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `email_verified`, `language`, `theme`) VALUES
('admin', 'admin@filmtracker.test', '$2y$10$H.yI0ZjT.FEwycNOxX58N.52gChUJtVIs4qjSZTJjLurVMvcLqbde', 'admin', 1, 'ru', 'light');

-- Создание тестового пользователя

INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `email_verified`, `language`, `theme`) VALUES
('testuser', 'user@filmtracker.test', '$2y$10$H.yI0ZjT.FEwycNOxX58N.52gChUJtVIs4qjSZTJjLurVMvcLqbde', 'user', 1, 'ru', 'light');

-- Примеры медиа (фильмы)
INSERT INTO `media` (`title`, `original_title`, `type`, `description`, `release_year`, `duration`, `imdb_rating`) VALUES
('Матрица', 'The Matrix', 'movie', 'Хакер по имени Нео узнает правду о реальности и присоединяется к группе повстанцев, чтобы бороться против машин.', 1999, 136, 8.7),
('Начало', 'Inception', 'movie', 'Профессиональный вор, специализирующийся на краже секретов из подсознания, получает задание, которое может изменить его жизнь.', 2010, 148, 8.8),
('Интерстеллар', 'Interstellar', 'movie', 'Группа исследователей использует недавно обнаруженный червоточину для путешествия за пределы нашей галактики.', 2014, 169, 8.6),
('Побег из Шоушенка', 'The Shawshank Redemption', 'movie', 'Два заключенных заводят дружбу, находя утешение и искупление через акты обычной доброты.', 1994, 142, 9.3),
('Крестный отец', 'The Godfather', 'movie', 'Старший сын мафиозного босса возвращается домой только для того, чтобы быть втянутым в преступный мир.', 1972, 175, 9.2);

-- Примеры медиа (сериалы)
INSERT INTO `media` (`title`, `original_title`, `type`, `description`, `release_year`, `imdb_rating`) VALUES
('Во все тяжкие', 'Breaking Bad', 'series', 'Учитель химии превращается в производителя метамфетамина после того, как узнает, что у него рак.', 2008, 9.5),
('Игра престолов', 'Game of Thrones', 'series', 'Несколько семей борются за контроль над мифической землей Вестерос.', 2011, 9.2),
('Настоящий детектив', 'True Detective', 'series', 'Детективы расследуют серию ритуальных убийств в Луизиане.', 2014, 9.0),
('Чернобыль', 'Chernobyl', 'series', 'История одной из самых страшных техногенных катастроф в истории человечества.', 2019, 9.4),
('Офис', 'The Office', 'series', 'Мокьюментари о повседневной работе сотрудников офиса в Скрантоне, Пенсильвания.', 2005, 8.9);

-- Связь медиа с жанрами
INSERT INTO `media_genres` (`media_id`, `genre_id`) VALUES
(1, 1), (1, 6), -- Матрица: Боевик, Научная фантастика
(2, 1), (2, 6), (2, 4), -- Начало: Боевик, Научная фантастика, Триллер
(3, 6), (3, 3), -- Интерстеллар: Научная фантастика, Драма
(4, 3), (4, 8), 
(5, 3), (5, 8), 
(6, 3), (6, 8), (6, 4), -- Во все тяжкие: Драма, Криминал, Триллер
(7, 10), (7, 3), 
(8, 4), (8, 9), 
(9, 3), (9, 4), 
(10, 2); 

-- Примеры сезонов для сериала "Во все тяжкие"
INSERT INTO `seasons` (`media_id`, `season_number`, `title`, `episode_count`, `air_date`) VALUES
(6, 1, 'Первый сезон', 7, '2008-01-20'),
(6, 2, 'Второй сезон', 13, '2009-03-08'),
(6, 3, 'Третий сезон', 13, '2010-03-21');

-- Примеры списков пользователя
INSERT INTO `user_lists` (`user_id`, `media_id`, `list_type`, `progress`) VALUES
(2, 1, 'completed', 100),
(2, 2, 'watching', 50),
(2, 6, 'watching', 30),
(2, 7, 'watchlist', 0);

-- Примеры истории просмотров
INSERT INTO `user_watch_history` (`user_id`, `media_id`, `watched_at`, `rating`) VALUES
(2, 1, '2024-01-15 10:00:00', 9.5),
(2, 2, '2024-01-20 14:30:00', 9.0);

-- Настройки email для пользователей
INSERT INTO `user_email_preferences` (`user_id`) VALUES
(1), (2);

