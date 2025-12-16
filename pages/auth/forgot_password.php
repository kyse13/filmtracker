<?php
/**
 * FilmTracker - Забыли пароль
 */

$page_title = 'Восстановление пароля - FilmTracker';

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // CSRF проверка
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = 'Ошибка безопасности. Обновите страницу и попробуйте снова.';
    } else {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Введите корректный email адрес';
        } else {
            // Поиск пользователя
            $db = Database::getInstance();
            $user = $db->fetchOne("SELECT id, email FROM users WHERE email = ?", [$email]);
            
            if ($user) {
                require_once INCLUDES_PATH . '/email.php';
                $emailSystem = new EmailSystem();
                $result = $emailSystem->sendPasswordResetEmail($user['id'], $user['email']);
                
                if ($result['success']) {
                    $success = true;
                } else {
                    $errors[] = 'Ошибка при отправке email. Попробуйте позже.';
                }
            } else {
               
                $success = true; 
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
                Восстановление пароля
            </h1>
            <p class="text-gray-600 dark:text-gray-400">Введите ваш email для восстановления пароля</p>
        </div>
        
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 rounded-lg">
                <p class="font-semibold">Инструкции отправлены!</p>
                <p class="text-sm mt-2">Если аккаунт с таким email существует, мы отправили инструкции по восстановлению пароля.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/login" class="block text-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Вернуться к входу
            </a>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                
                <button type="submit" 
                        class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition shadow-lg">
                    Отправить инструкции
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="<?php echo BASE_URL; ?>/login" class="text-purple-600 hover:text-purple-700 dark:text-purple-400">
                    Вернуться к входу
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

