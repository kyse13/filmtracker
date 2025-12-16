<?php
if (!defined('FILMTRACKER')) {
    define('FILMTRACKER', true);
}

define('DEBUG_MODE', true);

// Базовые пути
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// URL конфигурация
define('BASE_URL', 'http://localhost/filmtracker');
define('ASSETS_URL', BASE_URL . '/public');

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'filmtracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Настройки сессии
define('SESSION_NAME', 'FILMTRACKER_SESSION');
define('SESSION_LIFETIME', 86400); // 24 часа

// Настройки безопасности
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 минут

// Email настройки (Mail.ru SMTP)
define('SMTP_HOST', 'smtp.mail.ru');
define('SMTP_PORT', 465);
define('SMTP_USER', 'gresshhkkii@mail.ru');
define('SMTP_PASS', 'VVWcmZ9BDqXog3DQPy3G');
define('SMTP_SECURE', 'ssl');
define('EMAIL_FROM', 'gresshhkkii@mail.ru');
define('EMAIL_FROM_NAME', 'FilmTracker');
define('EMAIL_REPLY_TO', 'gresshhkkii@mail.ru');

// Email настройки токенов
define('VERIFICATION_TOKEN_EXPIRY', 86400); // 24 часа
define('PASSWORD_RESET_TOKEN_EXPIRY', 3600); // 1 час

// Загрузка файлов
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('AVATAR_MAX_SIZE', 2097152); // 2MB
define('POSTER_MAX_SIZE', 5242880); // 5MB

// Пагинация
define('ITEMS_PER_PAGE', 20);
define('ADMIN_ITEMS_PER_PAGE', 50);

// Временная зона
date_default_timezone_set('Europe/Moscow');

// Локализация
define('DEFAULT_LANGUAGE', 'ru');
define('DEFAULT_THEME', 'light');

// Обработка ошибок
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');
}

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

