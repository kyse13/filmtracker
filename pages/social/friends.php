<?php
$page_title = 'Друзья - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$friends = $db->fetchAll(
    "SELECT u.*, ff.status, ff.created_at 
     FROM friends_followers ff 
     JOIN users u ON (ff.friend_id = u.id AND ff.user_id = ?) OR (ff.user_id = u.id AND ff.friend_id = ?)
     WHERE (ff.user_id = ? OR ff.friend_id = ?) AND ff.status = 'accepted' AND ff.is_follow = 0",
    [$current_user['id'], $current_user['id'], $current_user['id'], $current_user['id']]
);

$pending_requests = $db->fetchAll(
    "SELECT u.*, ff.created_at 
     FROM friends_followers ff 
     JOIN users u ON ff.user_id = u.id 
     WHERE ff.friend_id = ? AND ff.status = 'pending' AND ff.is_follow = 0",
    [$current_user['id']]
);
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Друзья</h1>
    
    <!-- Pending Requests -->
    <?php if (!empty($pending_requests)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Запросы в друзья</h2>
            <div class="space-y-4">
                <?php foreach ($pending_requests as $request): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-4">
                            <img src="<?php echo getAvatarUrl($request['avatar'] ?? '', $request['username']); ?>" 
                                 alt="<?php echo e($request['username']); ?>" 
                                 class="w-12 h-12 rounded-full">
                            <div>
                                <p class="font-semibold"><?php echo e($request['username']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Запрос отправлен <?php echo timeAgo($request['created_at']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="handleFriendRequest(<?php echo $request['id']; ?>, 'accept')" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Принять
                            </button>
                            <button onclick="handleFriendRequest(<?php echo $request['id']; ?>, 'decline')" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Отклонить
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Friends List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4">Мои друзья (<?php echo count($friends); ?>)</h2>
        <?php if (!empty($friends)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($friends as $friend): ?>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-4 mb-4">
                            <img src="<?php echo getAvatarUrl($friend['avatar'] ?? '', $friend['username']); ?>" 
                                 alt="<?php echo e($friend['username']); ?>" 
                                 class="w-16 h-16 rounded-full">
                            <div>
                                <h3 class="font-semibold">
                                    <a href="<?php echo BASE_URL; ?>/users?id=<?php echo $friend['id']; ?>" class="hover:text-purple-600">
                                        <?php echo e($friend['username']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Друзья с <?php echo timeAgo($friend['created_at']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600 dark:text-gray-400">У вас пока нет друзей</p>
        <?php endif; ?>
    </div>
</div>

<script>
function handleFriendRequest(userId, action) {
    const url = '<?php echo BASE_URL; ?>/api?endpoint=friend-request&user_id=' + userId + '&action=' + action;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: userId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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

