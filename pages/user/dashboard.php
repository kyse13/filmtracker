<?php
/**
 * FilmTracker - Дашборд пользователя
 */

$page_title = 'Дашборд - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

// Статистика
$stats = $db->fetchOne(
    "SELECT 
        COUNT(DISTINCT CASE WHEN ul.list_type = 'completed' THEN ul.media_id END) as completed_count,
        COUNT(DISTINCT CASE WHEN ul.list_type = 'watching' THEN ul.media_id END) as watching_count,
        COUNT(DISTINCT CASE WHEN ul.list_type = 'watchlist' THEN ul.media_id END) as watchlist_count,
        COUNT(DISTINCT wh.id) as total_watched,
        AVG(wh.rating) as avg_rating
     FROM users u
     LEFT JOIN user_lists ul ON u.id = ul.user_id
     LEFT JOIN user_watch_history wh ON u.id = wh.user_id
     WHERE u.id = ?",
    [$current_user['id']]
);

// Недавняя активность
$recent_activity = $db->fetchAll(
    "SELECT m.*, wh.watched_at, wh.rating, ul.list_type
     FROM user_watch_history wh
     JOIN media m ON wh.media_id = m.id
     LEFT JOIN user_lists ul ON wh.user_id = ul.user_id AND wh.media_id = ul.media_id
     WHERE wh.user_id = ?
     ORDER BY wh.watched_at DESC
     LIMIT 10",
    [$current_user['id']]
);

// Рекомендации (простые - на основе жанров)
$recommendations = $db->fetchAll(
    "SELECT DISTINCT m.*
     FROM media m
     JOIN media_genres mg ON m.id = mg.media_id
     WHERE mg.genre_id IN (
         SELECT DISTINCT mg2.genre_id
         FROM user_lists ul
         JOIN media_genres mg2 ON ul.media_id = mg2.media_id
         WHERE ul.user_id = ? AND ul.list_type = 'completed'
     )
     AND m.id NOT IN (
         SELECT media_id FROM user_lists WHERE user_id = ?
     )
     LIMIT 6",
    [$current_user['id'], $current_user['id']]
);
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Мой дашборд</h1>
    
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Просмотрено</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo $stats['completed_count'] ?? 0; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Смотрю</p>
                    <p class="text-3xl font-bold text-pink-600"><?php echo $stats['watching_count'] ?? 0; ?></p>
                </div>
                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📺</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Хочу посмотреть</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $stats['watchlist_count'] ?? 0; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📋</span>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Средняя оценка</p>
                    <p class="text-3xl font-bold text-yellow-600">
                        <?php echo $stats['avg_rating'] ? formatRating($stats['avg_rating']) : '—'; ?>
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl">⭐</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Недавняя активность</h2>
        <?php if (!empty($recent_activity)): ?>
            <div class="space-y-4">
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <img src="<?php echo getPosterUrl($activity['poster_url']); ?>" 
                             alt="<?php echo e($activity['title']); ?>" 
                             class="w-16 h-24 object-cover rounded">
                        <div class="flex-1">
                            <h3 class="font-semibold">
                                <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $activity['id']; ?>" class="hover:text-purple-600">
                                    <?php echo e($activity['title']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Просмотрено: <?php echo timeAgo($activity['watched_at']); ?>
                            </p>
                            <?php if ($activity['rating']): ?>
                                <p class="text-sm text-yellow-500">⭐ <?php echo formatRating($activity['rating']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600 dark:text-gray-400">Пока нет активности</p>
        <?php endif; ?>
    </div>
    
    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-4">Рекомендации для вас</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($recommendations as $rec): ?>
                    <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $rec['id']; ?>" class="group">
                        <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                            <img src="<?php echo getPosterUrl($rec['poster_url']); ?>" 
                                 alt="<?php echo e($rec['title']); ?>" 
                                 class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    <h3 class="text-white font-semibold text-sm"><?php echo e($rec['title']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>



