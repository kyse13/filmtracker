<?php
/**
 * FilmTracker - Подтверждение email
 */

$page_title = 'Подтверждение email - FilmTracker';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if (!empty($token)) {
    require_once INCLUDES_PATH . '/email.php';
    $emailSystem = new EmailSystem();
    $result = $emailSystem->verifyEmailToken($token);
    
    if ($result['success']) {
        $success = true;
        setFlashMessage('success', 'Email успешно подтвержден! Добро пожаловать в FilmTracker!');
        
        // Обновление сессии если пользователь авторизован
        if ($auth->isLoggedIn() && $auth->getCurrentUser()['id'] == $result['user_id']) {
            $_SESSION['email_verified'] = 1;
        }
        
        // Редирект ДО вывода HTML (всегда вызываем, redirect сам проверит)
        redirect(BASE_URL . '/login');
    } else {
        $error = $result['error'];
    }
} else {
    $error = 'Токен не предоставлен';
}
?>

<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 text-center">
        <?php if ($success): ?>
            <div class="mb-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Email подтвержден!</h2>
                <p class="text-gray-600 dark:text-gray-400">Ваш аккаунт активирован.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/login" class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Войти
            </a>
        <?php else: ?>
            <div class="mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Ошибка</h2>
                <p class="text-gray-600 dark:text-gray-400"><?php echo e($error); ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>/register" class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Зарегистрироваться
            </a>
        <?php endif; ?>
    </div>
</div>

