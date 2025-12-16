<?php
/**
 * FilmTracker - Страница регистрации
 */

$page_title = 'Регистрация - FilmTracker';

// Если уже авторизован, редирект на дашборд
if ($auth->isLoggedIn()) {
    redirect(BASE_URL . '/dashboard');
}

$errors = [];
$success = false;
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // CSRF проверка
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $result = $auth->register($username, $email, $password, $confirm_password);
        
        if ($result['success']) {
            $success = true;
            setFlashMessage('success', $result['message']);
        } else {
            $errors = $result['errors'];
        }
    }
}

$csrf_token = $auth->generateCSRFToken();
?>

<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
                Регистрация
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Создайте аккаунт в FilmTracker</p>
        </div>
        
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 rounded-lg">
                <p class="font-semibold">Регистрация успешна!</p>
                <p class="text-sm mt-2">Проверьте ваш email для подтверждения аккаунта.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Имя пользователя
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo e($username); ?>" 
                       required
                       minlength="3"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="username">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Email
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo e($email); ?>" 
                       required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="your@email.com">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Пароль
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="Минимум <?php echo PASSWORD_MIN_LENGTH; ?> символов">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Минимум <?php echo PASSWORD_MIN_LENGTH; ?> символов</p>
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
                Зарегистрироваться
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-400">
                Уже есть аккаунт? 
                <a href="<?php echo BASE_URL; ?>/login" class="text-purple-600 hover:text-purple-700 dark:text-purple-400 font-semibold">
                    Войти
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>



