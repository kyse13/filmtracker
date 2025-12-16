<?php
$page_title = 'Пользователи - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$user_id = $_GET['id'] ?? 0;
$search = $_GET['search'] ?? '';

if ($user_id) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ? AND id != ?", [$user_id, $current_user['id']]);
    
    if (!$user) {
        redirect(BASE_URL . '/404');
    }
    
    $user_stats = $db->fetchOne(
        "SELECT 
            COUNT(DISTINCT CASE WHEN ul.list_type = 'completed' THEN ul.media_id END) as completed_count,
            COUNT(DISTINCT wh.id) as total_watched,
            AVG(wh.rating) as avg_rating
         FROM users u
         LEFT JOIN user_lists ul ON u.id = ul.user_id
         LEFT JOIN user_watch_history wh ON u.id = wh.user_id
         WHERE u.id = ?",
        [$user_id]
    );
    
    $friendship = $db->fetchOne(
        "SELECT * FROM friends_followers 
         WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)",
        [$current_user['id'], $user_id, $user_id, $current_user['id']]
    );
    
    $page_title = e($user['username']) . ' - Профиль';
} else {
    $users = $db->fetchAll(
        "SELECT * FROM users WHERE id != ? AND username LIKE ? ORDER BY username ASC LIMIT 50",
        [$current_user['id'], "%$search%"]
    );
}
?>

<?php if ($user_id && isset($user)): ?>
    <!-- User Profile View -->
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-6">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                <img src="<?php echo getAvatarUrl($user['avatar'] ?? '', $user['username']); ?>" 
                     alt="<?php echo e($user['username']); ?>" 
                     class="w-32 h-32 rounded-full border-4 border-purple-500">
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-3xl font-bold mb-2"><?php echo e($user['username']); ?></h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-4"><?php echo e($user['email']); ?></p>
                    <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600"><?php echo $user_stats['completed_count'] ?? 0; ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Просмотрено</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-pink-600"><?php echo $user_stats['total_watched'] ?? 0; ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Всего просмотров</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600">
                                <?php echo $user_stats['avg_rating'] ? formatRating($user_stats['avg_rating']) : '—'; ?>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Средняя оценка</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!$friendship || $friendship['status'] !== 'accepted'): ?>
                <div class="mt-6 text-center">
                    <button onclick="sendFriendRequest(<?php echo $user['id']; ?>)" 
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                        Добавить в друзья
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Users List -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-6">Пользователи</h1>
        
        <form method="GET" action="" class="mb-6">
            <div class="flex gap-4">
                <input type="text" 
                       name="search" 
                       value="<?php echo e($search); ?>" 
                       placeholder="Поиск пользователей..."
                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                    Найти
                </button>
            </div>
        </form>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($users as $u): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo getAvatarUrl($u['avatar'] ?? '', $u['username']); ?>" 
                             alt="<?php echo e($u['username']); ?>" 
                             class="w-16 h-16 rounded-full">
                        <div>
                            <h3 class="font-semibold">
                                <a href="<?php echo BASE_URL; ?>/users?id=<?php echo $u['id']; ?>" class="hover:text-purple-600">
                                    <?php echo e($u['username']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Зарегистрирован <?php echo timeAgo($u['created_at']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function sendFriendRequest(userId) {
    const url = '<?php echo BASE_URL; ?>/api?endpoint=friend-request&user_id=' + userId + '&action=send';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            action: 'send'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Запрос отправлен!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка соединения');
    });
}
</script>

