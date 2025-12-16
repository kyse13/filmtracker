<?php
/**
 * FilmTracker - Выход из системы
 */

$auth->logout();
setFlashMessage('success', 'Вы успешно вышли из системы');
// Редирект ДО вывода HTML
redirect(BASE_URL . '/login');

