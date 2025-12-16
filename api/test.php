<?php
/**
 * Тестовый файл для проверки доступа к API
 */

header('Content-Type: application/json');
echo json_encode(['test' => 'ok', 'message' => 'API доступен']);



