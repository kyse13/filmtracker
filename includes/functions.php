<?php
if (!defined('FILMTRACKER')) {
    die('Прямой доступ запрещен');
}

function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    if (headers_sent()) {
        echo '<script>window.location.href = "' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
    }
    
    if (strpos($url, 'http') === 0) {
        header('Location: ' . $url);
        exit;
    }
    
    if (strpos($url, '/') === 0 && strpos($url, BASE_URL) === false) {
        $url = BASE_URL . $url;
    } elseif (strpos($url, BASE_URL) === false) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    
    header('Location: ' . $url);
    exit;
}

function formatDate($date, $format = 'd.m.Y') {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
    if ($diff < 604800) return floor($diff / 86400) . ' дн. назад';
    if ($diff < 2592000) return floor($diff / 604800) . ' нед. назад';
    if ($diff < 31536000) return floor($diff / 2592000) . ' мес. назад';
    return floor($diff / 31536000) . ' г. назад';
}

function formatRating($rating) {
    if (empty($rating)) return '—';
    return number_format($rating, 1);
}

function formatDuration($minutes) {
    if (empty($minutes)) return '';
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . ' ч. ' . $mins . ' мин.';
    }
    return $mins . ' мин.';
}

function truncate($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    $string = transliterate($string);
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

function transliterate($string) {
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];
    
    return strtr($string, $translit);
}

function validateImage($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Файл не загружен'];
    }
    
    $allowed_types = ALLOWED_IMAGE_TYPES;
    $max_size = MAX_UPLOAD_SIZE;
    
    $file_type = $file['type'];
    $file_size = $file['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Недопустимый тип файла'];
    }
    
    if ($file_size > $max_size) {
        return ['valid' => false, 'error' => 'Файл слишком большой'];
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return ['valid' => false, 'error' => 'Неверный формат изображения'];
    }
    
    return ['valid' => true];
}

function uploadImage($file, $destination_dir, $prefix = '') {
    $validation = validateImage($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
    $destination = $destination_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => $destination];
    }
    
    return ['success' => false, 'error' => 'Ошибка при загрузке файла'];
}

function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

function getAvatarUrl($avatar, $username = '') {
    if (!empty($avatar)) {
        if (strpos($avatar, 'http') === 0) {
            return $avatar;
        }
        $file_path = UPLOADS_PATH . '/avatars/' . $avatar;
        if (file_exists($file_path)) {
            return BASE_URL . '/public/uploads/avatars/' . $avatar;
        }
    }
    $initials = 'U';
    if (!empty($username)) {
        $words = explode(' ', $username);
        if (count($words) >= 2) {
            $initials = mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr($words[1], 0, 1, 'UTF-8');
        } else {
            $initials = mb_substr($username, 0, 2, 'UTF-8');
        }
        $initials = mb_strtoupper($initials, 'UTF-8');
    }
    
    return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=667eea&color=fff&size=128&bold=true';
}

function getPosterUrl($poster_url) {
    if (empty($poster_url)) {
        return 'https://via.placeholder.com/300x450/667eea/ffffff?text=No+Poster';
    }
    if (strpos($poster_url, 'http') === 0) {
        return $poster_url;
    }
    if (strpos($poster_url, '/') === 0) {
        return BASE_URL . $poster_url;
    }
    return BASE_URL . '/public/uploads/posters/' . $poster_url;
}

function getTrailerEmbedUrl($trailer_url) {
    if (empty($trailer_url)) {
        return null;
    }
    
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $trailer_url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    if (preg_match('/vimeo\.com\/(\d+)/', $trailer_url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return $trailer_url;
}

function paginate($current_page, $total_items, $items_per_page, $base_url) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    return [
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1,
        'base_url' => $base_url
    ];
}

function getMediaTypeRu($type) {
    return $type === 'movie' ? 'Фильм' : 'Сериал';
}

function getListTypeRu($type) {
    $types = [
        'watchlist' => 'Хочу посмотреть',
        'watching' => 'Смотрю',
        'completed' => 'Просмотрено',
        'dropped' => 'Брошено',
        'on_hold' => 'Отложено'
    ];
    return $types[$type] ?? $type;
}

function getFlashMessages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getCurrentTheme() {
    global $auth;
    
    $cookie_path = '/';
    if (defined('BASE_URL')) {
        $parsed = parse_url(BASE_URL);
        if (isset($parsed['path']) && $parsed['path'] !== '/') {
            $cookie_path = rtrim($parsed['path'], '/') . '/';
        }
    }
    
    $theme = $_COOKIE['theme'] ?? null;
    
    if ($theme && in_array($theme, ['dark', 'light'])) {
        return $theme;
    }
    
    $user = $auth->getCurrentUser();
    
    if ($user) {
        $db = Database::getInstance();
        $pref = $db->fetchOne("SELECT theme FROM users WHERE id = ?", [$user['id']]);
        $theme = $pref['theme'] ?? DEFAULT_THEME;
    } else {
        $theme = DEFAULT_THEME;
    }
    
    if (!in_array($theme, ['dark', 'light'])) {
        $theme = DEFAULT_THEME;
}

    if (!isset($_COOKIE['theme'])) {
        setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), $cookie_path);
        $_COOKIE['theme'] = $theme;
    }
    
    return $theme;
}

function getCurrentLanguage() {
    global $auth;
    $user = $auth->getCurrentUser();
    
    if ($user) {
        $db = Database::getInstance();
        $pref = $db->fetchOne("SELECT language FROM users WHERE id = ?", [$user['id']]);
        return $pref['language'] ?? DEFAULT_LANGUAGE;
    }
    
    return $_COOKIE['language'] ?? DEFAULT_LANGUAGE;
}

