<?php
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . (defined('BASE_URL') ? BASE_URL : '*'));
    header('Access-Control-Allow-Credentials: true');
}

ob_start();

if (!defined('FILMTRACKER')) {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
}

ob_clean();

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if (!DEBUG_MODE) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$endpoint = $_GET['endpoint'] ?? $_REQUEST['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

$has_body = false;
if ($method === 'GET') {
    $content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;
    if ($content_length > 0) {
        $has_body = true;
    }
    if (strpos($content_type, 'application/json') !== false) {
        $has_body = true;
    }
}

if ($method === 'GET' && ($has_body || strpos($content_type, 'application/json') !== false)) {
    $method = 'POST';
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

if (empty($endpoint)) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/[?&]endpoint=([^&]+)/', $request_uri, $matches)) {
        $endpoint = urldecode($matches[1]);
    }
    if (empty($endpoint) && isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $query_params);
        $endpoint = $query_params['endpoint'] ?? '';
    }
}

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("API Debug: endpoint=$endpoint, method=$method, REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . ", CONTENT_TYPE=$content_type, has_body=" . ($has_body ? 'yes' : 'no') . ", CONTENT_LENGTH=" . ($_SERVER['CONTENT_LENGTH'] ?? '0'));
}

$protected_endpoints = ['media-action', 'update-theme', 'update-profile', 'friend-request', 'add-review'];
$is_protected = in_array($endpoint, $protected_endpoints);

if ($is_protected && !$auth->isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($endpoint) {
    case 'media-action':
        if ($method === 'POST' || $method === 'GET' || $method === 'OPTIONS') {
            if ($method === 'OPTIONS') {
                ob_clean();
                http_response_code(200);
                exit;
            }
            
            if (!$auth->isLoggedIn()) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = null;
            
            $input = @file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
            }
            
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }
            
            if (empty($data) && !empty($_GET['data'])) {
                $data = json_decode($_GET['data'], true);
            }
            
            if (empty($data)) {
                $data = [];
            }
            
            if (!empty($_GET)) {
                foreach ($_GET as $key => $value) {
                    if ($key !== 'endpoint' && !isset($data[$key])) {
                        $data[$key] = $value;
                    }
                }
            }
            
            $media_id = 0;
            if (isset($data['media_id'])) {
                $media_id = intval($data['media_id']);
            }
            if ($media_id <= 0 && isset($_GET['media_id'])) {
                $media_id = intval($_GET['media_id']);
            }
            
            $list_type = $data['list_type'] ?? $_GET['list_type'] ?? '';
            $rating = null;
            if (isset($data['rating']) && $data['rating'] !== '' && $data['rating'] !== null) {
                $rating = floatval($data['rating']);
            } elseif (isset($_GET['rating']) && $_GET['rating'] !== '') {
                $rating = floatval($_GET['rating']);
                }
            
            $season_number = null;
            if (isset($data['season_number']) && $data['season_number'] !== '' && $data['season_number'] !== null) {
                $season_number = intval($data['season_number']);
            } elseif (isset($_GET['season_number']) && $_GET['season_number'] !== '') {
                $season_number = intval($_GET['season_number']);
            }
            
            $episode_number = null;
            if (isset($data['episode_number']) && $data['episode_number'] !== '' && $data['episode_number'] !== null) {
                $episode_number = intval($data['episode_number']);
            } elseif (isset($_GET['episode_number']) && $_GET['episode_number'] !== '') {
                $episode_number = intval($_GET['episode_number']);
            }
            
            $drop_reason = null;
            if (isset($data['drop_reason']) && $data['drop_reason'] !== '' && $data['drop_reason'] !== null) {
                $drop_reason = trim($data['drop_reason']);
            } elseif (isset($_GET['drop_reason']) && $_GET['drop_reason'] !== '') {
                $drop_reason = trim(urldecode($_GET['drop_reason']));
            }
            if ($drop_reason === '') {
                $drop_reason = null;
            }
            
            if ($media_id <= 0) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Неверный ID медиа: ' . ($data['media_id'] ?? 'не указан')], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $user = $auth->getCurrentUser();
            if (!$user) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Пользователь не найден'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $db = Database::getInstance();
            
            $media = $db->fetchOne("SELECT type FROM media WHERE id = ?", [$media_id]);
            if (!$media) {
                ob_clean();
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Медиа не найдено'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            try {
                if (!empty($list_type)) {
                    $valid_types = ['watchlist', 'watching', 'completed', 'dropped', 'on_hold'];
                    if (!in_array($list_type, $valid_types)) {
                        throw new Exception('Неверный тип списка');
                    }
                    
                    if ($media['type'] === 'series' && ($list_type === 'watching' || $list_type === 'on_hold')) {
                        if ($season_number === null || $episode_number === null) {
                            throw new Exception('Для сериалов укажите сезон и серию');
                        }
                    }
                    
                    if ($list_type === 'dropped' && ($drop_reason === null || $drop_reason === '')) {
                        throw new Exception('Укажите причину, почему бросили');
                    }
                    
                    $existing = $db->fetchOne(
                        "SELECT id, list_type FROM user_lists WHERE user_id = ? AND media_id = ?",
                        [$user['id'], $media_id]
                    );
                    
                    $db->query(
                        "DELETE FROM user_lists WHERE user_id = ? AND media_id = ?",
                        [$user['id'], $media_id]
                    );
                    
                    try {
                        $db->query(
                            "INSERT INTO user_lists (user_id, media_id, list_type, season_number, episode_number, drop_reason, added_at) 
                             VALUES (?, ?, ?, ?, ?, ?, NOW())",
                            [$user['id'], $media_id, $list_type, $season_number, $episode_number, $drop_reason]
                        );
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'drop_reason') !== false) {
                    $db->query(
                                "INSERT INTO user_lists (user_id, media_id, list_type, season_number, episode_number, added_at) 
                                 VALUES (?, ?, ?, ?, ?, NOW())",
                                [$user['id'], $media_id, $list_type, $season_number, $episode_number]
                    );
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    $db->query(
                        "DELETE FROM user_lists WHERE user_id = ? AND media_id = ?",
                        [$user['id'], $media_id]
                    );
                }
                
                if ($rating !== null && $rating > 0 && $rating <= 10) {
                    if ($list_type === 'watchlist' && $media['type'] === 'movie') {
                    } else {
                    $existing = $db->fetchOne(
                        "SELECT id FROM user_watch_history WHERE user_id = ? AND media_id = ?",
                        [$user['id'], $media_id]
                    );
                    
                    if ($existing) {
                        $db->query(
                            "UPDATE user_watch_history SET rating = ? WHERE user_id = ? AND media_id = ?",
                            [$rating, $user['id'], $media_id]
                        );
                    } else {
                        $db->query(
                            "INSERT INTO user_watch_history (user_id, media_id, rating, watched_at) 
                             VALUES (?, ?, ?, NOW())",
                            [$user['id'], $media_id, $rating]
                        );
                    }
                    }
                }
                
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Сохранено успешно'], JSON_UNESCAPED_UNICODE);
                exit;
            } catch (PDOException $e) {
                ob_clean();
                error_log("Database error in media-action: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . (DEBUG_MODE ? $e->getMessage() : 'Попробуйте позже')], JSON_UNESCAPED_UNICODE);
                exit;
            } catch (Exception $e) {
                ob_clean();
                error_log("Error in media-action: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            ob_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        break;
        
    case 'update-theme':
        if ($method === 'POST' || $method === 'GET') {
            $data = [];
            
            $input = @file_get_contents('php://input');
            if (!empty($input)) {
                $decoded = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }
            
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }
            
            if (isset($_GET['theme'])) {
                $data['theme'] = $_GET['theme'];
            }
            
            $theme = $data['theme'] ?? 'light';
            
            if (in_array($theme, ['dark', 'light'])) {
                $user = $auth->getCurrentUser();
                if ($user) {
                $db = Database::getInstance();
                $db->query("UPDATE users SET theme = ? WHERE id = ?", [$theme, $user['id']]);
                }
                
                $cookie_path = '/';
                if (defined('BASE_URL')) {
                    $parsed = parse_url(BASE_URL);
                    if (isset($parsed['path']) && $parsed['path'] !== '/') {
                        $cookie_path = rtrim($parsed['path'], '/') . '/';
                    }
                }
                
                setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), $cookie_path);
                $_COOKIE['theme'] = $theme;
                
                ob_clean();
                echo json_encode(['success' => true, 'theme' => $theme], JSON_UNESCAPED_UNICODE);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Неверная тема'], JSON_UNESCAPED_UNICODE);
            }
        } else {
            ob_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE);
        }
        exit;
        
    case 'friend-request':
        if ($method === 'POST' || $method === 'GET' || $method === 'OPTIONS') {
            if ($method === 'OPTIONS') {
                ob_clean();
                http_response_code(200);
                exit;
            }
            
            if (!$auth->isLoggedIn()) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = [];
            
            $input = @file_get_contents('php://input');
            if (!empty($input)) {
                $decoded = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = array_merge($data, $decoded);
                }
            }
            
            if (!empty($_POST)) {
                $data = array_merge($data, $_POST);
            }
            
            if (!empty($_GET['data'])) {
                $decoded = json_decode($_GET['data'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = array_merge($data, $decoded);
                }
            }
            
            if (isset($_GET['user_id'])) {
                $data['user_id'] = $_GET['user_id'];
            }
            if (isset($_GET['action'])) {
                $data['action'] = $_GET['action'];
            }
            
            $target_user_id = intval($data['user_id'] ?? $_GET['user_id'] ?? 0);
            $action = $data['action'] ?? $_GET['action'] ?? '';
            
            if (empty($action)) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Действие не указано', 'debug_data' => $data], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if ($target_user_id <= 0) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Неверный ID пользователя'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $user = $auth->getCurrentUser();
            if (!$user) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Пользователь не найден'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $db = Database::getInstance();
            
            if ($action === 'send') {
                try {
                $db->query(
                        "INSERT INTO friends_followers (user_id, friend_id, status, is_follow) VALUES (?, ?, 'pending', 0)
                     ON DUPLICATE KEY UPDATE status = 'pending'",
                    [$user['id'], $target_user_id]
                );
                    ob_clean();
                    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    ob_clean();
                    error_log("Friend request error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Ошибка при отправке запроса'], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($action === 'accept') {
                try {
                $db->query(
                        "UPDATE friends_followers SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND is_follow = 0",
                    [$target_user_id, $user['id']]
                );
                    ob_clean();
                    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    ob_clean();
                    error_log("Friend accept error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Ошибка при принятии запроса'], JSON_UNESCAPED_UNICODE);
                }
            } elseif ($action === 'decline') {
                try {
                $db->query(
                        "DELETE FROM friends_followers WHERE user_id = ? AND friend_id = ? AND is_follow = 0",
                    [$target_user_id, $user['id']]
                );
                    ob_clean();
                    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    ob_clean();
                    error_log("Friend decline error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Ошибка при отклонении запроса'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Неверное действие: ' . $action], JSON_UNESCAPED_UNICODE);
            }
        } else {
            ob_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE);
        }
        exit;
        
    case 'add-review':
        if ($method === 'POST' || $method === 'GET' || $method === 'OPTIONS') {
            if ($method === 'OPTIONS') {
                ob_clean();
                http_response_code(200);
                exit;
            }
            
            if (!$auth->isLoggedIn()) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = null;
            
            $input = @file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
            }
            
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }
            
            if (empty($data) && !empty($_GET['data'])) {
                $data = json_decode($_GET['data'], true);
            }
            
            if (empty($data) || !is_array($data)) {
                $data = [];
                if (isset($_GET['media_id'])) {
                    $data['media_id'] = $_GET['media_id'];
                }
                if (isset($_GET['rating'])) {
                    $data['rating'] = $_GET['rating'];
                }
                if (isset($_GET['content'])) {
                    $data['content'] = $_GET['content'];
                }
                if (isset($_GET['contains_spoilers'])) {
                    $data['contains_spoilers'] = $_GET['contains_spoilers'] === 'true' || $_GET['contains_spoilers'] === '1';
                }
            }
            
            $media_id = intval($data['media_id'] ?? 0);
            $rating = !empty($data['rating']) ? floatval($data['rating']) : null;
            $content = trim($data['content'] ?? '');
            $spoilers = isset($data['contains_spoilers']) && $data['contains_spoilers'];
            
            $user = $auth->getCurrentUser();
            if (!$user) {
                ob_clean();
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Требуется авторизация'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $db = Database::getInstance();
            
            if (empty($content)) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Текст отзыва обязателен'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if ($rating && ($rating < 1 || $rating > 10)) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Оценка должна быть от 1 до 10'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            try {
                $db->insert(
                    "INSERT INTO reviews_comments (user_id, media_id, content, rating, contains_spoilers, is_approved) 
                     VALUES (?, ?, ?, ?, ?, 1)",
                    [$user['id'], $media_id, $content, $rating, $spoilers ? 1 : 0]
                );
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Отзыв опубликован'], JSON_UNESCAPED_UNICODE);
                exit;
            } catch (Exception $e) {
                ob_clean();
                error_log("Error in add-review: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        break;
        
    default:
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint не найден'], JSON_UNESCAPED_UNICODE);
        exit;
}

ob_clean();
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint не найден'], JSON_UNESCAPED_UNICODE);
exit;

