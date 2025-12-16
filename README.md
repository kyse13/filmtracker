# FilmTracker

Веб-приложение для отслеживания просмотренных фильмов и сериалов.

## Возможности

- Система аутентификации (регистрация, вход, восстановление пароля)
- Email верификация через SMTP
- Отслеживание медиа (фильмы и сериалы)
- Рейтинги и отзывы
- Списки просмотра (Хочу посмотреть, Смотрю, Просмотрено, Брошено, Отложено)
- Пользовательские профили и дашборд
- Админ-панель
- Темная/светлая тема
- Адаптивный дизайн с Tailwind CSS

## Требования

- PHP 8.0 или выше
- MySQL/MariaDB 5.7 или выше
- Apache с mod_rewrite
- XAMPP/LAMP/WAMP

## Установка

### 1. Копирование проекта

Скопируйте все файлы в директорию `htdocs/filmtracker` (для XAMPP) или в корневую директорию веб-сервера.

### 2. Настройка базы данных

1. Откройте phpMyAdmin или MySQL клиент
2. Создайте базу данных `filmtracker`
3. Импортируйте схему:
   ```sql
   mysql -u root -p filmtracker < sql/schema.sql
   ```
4. Импортируйте тестовые данные (опционально):
   ```sql
   mysql -u root -p filmtracker < sql/sample_data.sql
   ```

### 3. Настройка конфигурации

Откройте `includes/config.php` и настройте:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'filmtracker');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'http://localhost/filmtracker');
```

### 4. Настройка прав доступа

Убедитесь, что директория `public/uploads` имеет права на запись:

```bash
chmod 755 public/uploads
chmod 755 public/uploads/avatars
```

### 5. Настройка Apache

Убедитесь, что mod_rewrite включен. Файл `.htaccess` уже настроен.

## Настройка Email

Email система настроена в `includes/config.php`:

```php
define('SMTP_HOST', 'smtp.mail.ru');
define('SMTP_PORT', 465);
define('SMTP_USER', 'your-email@mail.ru');
define('SMTP_PASS', 'your-password');
```

Для работы email системы рекомендуется установить PHPMailer:

```bash
composer require phpmailer/phpmailer
```

## Тестовые аккаунты

После импорта `sql/sample_data.sql`:


## Структура проекта

```
filmtracker/
├── index.php                 # Front controller
├── .htaccess                 # Apache конфигурация
├── includes/                 # Основные файлы
│   ├── config.php           # Конфигурация
│   ├── database.php         # База данных
│   ├── auth.php             # Аутентификация
│   ├── email.php            # Email система
│   └── functions.php        # Вспомогательные функции
├── pages/                   # Страницы
│   ├── auth/                # Авторизация
│   ├── media/               # Медиа
│   ├── user/                # Пользовательские страницы
│   ├── admin/               # Админ-панель
│   └── errors/              # Страницы ошибок
├── templates/               # Шаблоны
│   ├── header.php
│   ├── footer.php
│   └── emails/              # Email шаблоны
├── public/                  # Публичные файлы
│   └── uploads/            # Загруженные файлы
├── sql/                     # SQL файлы
│   ├── schema.sql
│   └── sample_data.sql
└── api/                     # API endpoints
```

## Использование

1. Откройте браузер и перейдите на `http://localhost/filmtracker`
2. Зарегистрируйтесь или войдите с тестовым аккаунтом
3. Подтвердите email (если настроен SMTP)
4. Начните отслеживать фильмы и сериалы

## Безопасность

- Пароли хешируются с помощью `password_hash()`
- Все SQL запросы используют подготовленные выражения
- CSRF защита на всех формах
- XSS защита через `htmlspecialchars()`
- Валидация загружаемых файлов
- Защита от брутфорса (блокировка после 5 попыток)

## Разработка

Для разработки установите `DEBUG_MODE = true` в `includes/config.php`.

## Поддержка

При возникновении проблем проверьте:
1. Права доступа к директориям
2. Настройки базы данных
3. Логи ошибок PHP
4. Настройки Apache mod_rewrite
