<?php
if (isset($_SERVER['REDIRECT_REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REDIRECT_REQUEST_METHOD'];
} elseif (isset($_ENV['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = $_ENV['REQUEST_METHOD'];
}

$content_type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && strpos($content_type, 'application/json') !== false) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

require_once __DIR__ . '/router.php';

