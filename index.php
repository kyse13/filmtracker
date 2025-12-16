<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$url = $_GET['url'] ?? 'home';
$url = trim($url, '/');
$url_parts = explode('/', $url);

if (DEBUG_MODE && isset($_GET['url'])) {
    error_log("URL: " . $url . " | Parts: " . print_r($url_parts, true));
}

$controller = $url_parts[0] ?? 'home';
$action = $url_parts[1] ?? 'index';
$params = array_slice($url_parts, 2);

if ($controller === 'admin' && !empty($action) && $action !== 'index') {
    $controller = 'admin/' . $action;
}

if ($controller === 'api' || $url === 'api' || strpos($url, 'api') === 0 || (isset($_GET['endpoint']) && strpos($_SERVER['REQUEST_URI'], '/api') !== false)) {
    require_once __DIR__ . '/api/router.php';
    exit;
}

$routes = [
    'home' => 'pages/home.php',
    'login' => 'pages/auth/login.php',
    'register' => 'pages/auth/register.php',
    'logout' => 'pages/auth/logout.php',
    'verify-email' => 'pages/auth/verify_email.php',
    'forgot-password' => 'pages/auth/forgot_password.php',
    'reset-password' => 'pages/auth/reset_password.php',
    'media' => 'pages/media/view.php',
    'search' => 'pages/media/search.php',
    'browse' => 'pages/media/browse.php',
    'dashboard' => 'pages/user/dashboard.php',
    'profile' => 'pages/user/profile.php',
    'settings' => 'pages/user/settings.php',
    'watchlist' => 'pages/user/watchlist.php',
    'history' => 'pages/user/history.php',
    'admin' => 'pages/admin/index.php',
    'admin-media' => 'pages/admin/media.php',
    'admin-users' => 'pages/admin/users.php',
    'admin-genres' => 'pages/admin/genres.php',
    'admin/media' => 'pages/admin/media.php',
    'admin/users' => 'pages/admin/users.php',
    'admin/genres' => 'pages/admin/genres.php',
    'friends' => 'pages/social/friends.php',
    'users' => 'pages/social/users.php',
    'api' => 'api/router.php',
    '404' => 'pages/errors/404.php',
    '403' => 'pages/errors/403.php',
];

if (isset($routes[$controller])) {
    $page_file = __DIR__ . '/pages/' . str_replace('pages/', '', $routes[$controller]);
    
    $redirect_pages = ['verify-email', 'logout', 'reset-password'];
    if (in_array($controller, $redirect_pages) && file_exists($page_file)) {
        ob_start();
        require $page_file;
        $page_output = ob_get_clean();
        require_once __DIR__ . '/templates/header.php';
        echo $page_output;
        require_once __DIR__ . '/templates/footer.php';
    } elseif (file_exists($page_file)) {
        require_once __DIR__ . '/templates/header.php';
        require $page_file;
        require_once __DIR__ . '/templates/footer.php';
    } else {
        require_once __DIR__ . '/templates/header.php';
        require_once __DIR__ . '/pages/errors/404.php';
        require_once __DIR__ . '/templates/footer.php';
    }
} else {
    require_once __DIR__ . '/templates/header.php';
    require_once __DIR__ . '/pages/errors/404.php';
    require_once __DIR__ . '/templates/footer.php';
}

