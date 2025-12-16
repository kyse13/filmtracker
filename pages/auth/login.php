<?php
/**
 * FilmTracker - Страница входа
 */

$page_title = 'Вход - FilmTracker';

// Если уже авторизован редирект на дашборд
if ($auth->isLoggedIn()) {
    redirect(BASE_URL . '/dashboard');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // CSRF проверка
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            redirect(BASE_URL . '/dashboard');
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
                Вход в FilmTracker
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Войдите в свой аккаунт</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
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
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                       placeholder="••••••••">
            </div>
            
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Запомнить меня</span>
                </label>
                <a href="<?php echo BASE_URL; ?>/forgot-password" class="text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400">
                    Забыли пароль?
                </a>
            </div>
            
            <button type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition shadow-lg">
                Войти
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-400">
                Нет аккаунта? 
                <a href="<?php echo BASE_URL; ?>/register" class="text-purple-600 hover:text-purple-700 dark:text-purple-400 font-semibold">
                    Зарегистрироваться
                </a>
            </p>
        </div>
    </div>
</div>



