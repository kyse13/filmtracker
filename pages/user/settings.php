<?php

$page_title = 'Настройки - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = 'Ошибка безопасности';
    } else {
        $username = $_POST['username'] ?? '';
        $language = $_POST['language'] ?? 'ru';
        
        if (strlen($username) < 3) {
            $errors[] = 'Имя пользователя должно содержать минимум 3 символа';
        } else {
            $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $current_user['id']]);
            if ($existing) {
                $errors[] = 'Имя пользователя уже занято';
            } else {
                $db->query("UPDATE users SET username = ?, language = ? WHERE id = ?", 
                    [$username, $language, $current_user['id']]);
                $_SESSION['username'] = $username;
                
                $success = true;
                setFlashMessage('success', 'Настройки сохранены');
            }
        }
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $validation = validateImage($_FILES['avatar']);
            if ($validation['valid']) {
                $result = uploadImage($_FILES['avatar'], UPLOADS_PATH . '/avatars', 'avatar_');
                if ($result['success']) {
                    if (!empty($current_user['avatar'])) {
                        deleteFile(UPLOADS_PATH . '/avatars/' . $current_user['avatar']);
                    }
                    $db->query("UPDATE users SET avatar = ? WHERE id = ?", [$result['filename'], $current_user['id']]);
                    $_SESSION['avatar'] = $result['filename'];
                    $success = true;
                }
            }
        }
    }
}

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$current_user['id']]);
if ($user) {
    $_SESSION['avatar'] = $user['avatar'] ?? null;
}
$csrf_token = $auth->generateCSRFToken();
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Настройки</h1>
    
    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-200 rounded-lg">
            Настройки успешно сохранены!
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-200 rounded-lg">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div>
                <label class="block text-sm font-medium mb-2">Аватар</label>
                <div class="flex items-center gap-4">
                    <img src="<?php echo getAvatarUrl($user['avatar'] ?? '', $user['username']); ?>" 
                         alt="Avatar" 
                         class="w-20 h-20 rounded-full">
                    <input type="file" name="avatar" accept="image/*" class="text-sm">
                </div>
            </div>
            
            <div>
                <label for="username" class="block text-sm font-medium mb-2">Имя пользователя</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo e($user['username']); ?>" 
                       required
                       minlength="3"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
            </div>
            
            <div>
                <label for="language" class="block text-sm font-medium mb-2">Язык</label>
                <select id="language" name="language" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                    <option value="ru" <?php echo $user['language'] === 'ru' ? 'selected' : ''; ?>>Русский</option>
                    <option value="en" <?php echo $user['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                </select>
            </div>
            
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Сохранить изменения
            </button>
        </form>
    </div>
</div>

