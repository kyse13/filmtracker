<?php
$page_title = 'Профиль - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$current_user['id']]);
$stats = $db->fetchOne(
    "SELECT 
        COUNT(DISTINCT CASE WHEN ul.list_type = 'completed' THEN ul.media_id END) as completed_count,
        COUNT(DISTINCT wh.id) as total_watched,
        AVG(wh.rating) as avg_rating
     FROM users u
     LEFT JOIN user_lists ul ON u.id = ul.user_id
     LEFT JOIN user_watch_history wh ON u.id = wh.user_id
     WHERE u.id = ?",
    [$current_user['id']]
);
?>

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
                        <p class="text-2xl font-bold text-purple-600"><?php echo $stats['completed_count'] ?? 0; ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Просмотрено</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-pink-600"><?php echo $stats['total_watched'] ?? 0; ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Всего просмотров</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-yellow-600">
                            <?php echo $stats['avg_rating'] ? formatRating($stats['avg_rating']) : '—'; ?>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Средняя оценка</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="<?php echo BASE_URL; ?>/settings" class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
            Редактировать профиль
        </a>
    </div>
</div>

