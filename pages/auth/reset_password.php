<?php
/**
 * FilmTracker - Сброс пароля
 */

$page_title = 'Сброс пароля - FilmTracker';

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;

if (empty($token)) {
    $errors[] = 'Токен не предоставлен';
} else {
    require_once INCLUDES_PATH . '/email.php';
    $emailSystem = new EmailSystem();
    $token_valid = $emailSystem->verifyPasswordResetToken($token);
    
    if (!$token_valid['success']) {
        $errors[] = $token_valid['error'];
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // CSRF проверка
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!$auth->verifyCSRFToken($csrf_token)) {
                $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
            } else {
                if (strlen($password) < PASSWORD_MIN_LENGTH) {
                    $errors[] = 'Пароль должен содержать минимум ' . PASSWORD_MIN_LENGTH . ' символов';
                }
                
                if ($password !== $confirm_password) {
                    $errors[] = 'Пароли не совпадают';
                }
                
                if (empty($errors)) {
                    $result = $emailSystem->resetPassword($token, $password);
                    
                    if ($result['success']) {
                        $success = true;
                        setFlashMessage('success', $result['message']);
                        // Редирект ДО вывода HTML
                        redirect(BASE_URL . '/login');
                    } else {
                        $errors[] = $result['error'];
                    }
                }
            }
        }
    }
}

$csrf_token = $auth->generateCSRFToken();
?>

<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                Сброс пароля
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Введите новый пароль</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($errors) || !empty($token)): ?>
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Новый пароль
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="Минимум <?php echo PASSWORD_MIN_LENGTH; ?> символов">
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Подтвердите пароль
                </label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="Повторите пароль">
            </div>
            
            <button type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition shadow-lg">
                Изменить пароль
            </button>
        </form>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/forgot-password" class="block text-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Запросить новую ссылку
            </a>
        <?php endif; ?>
    </div>
</div>

