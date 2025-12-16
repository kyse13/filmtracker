<?php
/**
 * FilmTracker - Мой список
 */

$page_title = 'Мой список - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$list_type = $_GET['type'] ?? 'watchlist';
$allowed_types = ['watchlist', 'watching', 'completed', 'dropped', 'on_hold'];

if (!in_array($list_type, $allowed_types)) {
    $list_type = 'watchlist';
}

$media_list = $db->fetchAll(
    "SELECT DISTINCT m.*, ul.progress, ul.added_at, ul.season_number, ul.episode_number, ul.drop_reason,
            (SELECT wh.rating FROM user_watch_history wh 
             WHERE wh.user_id = ul.user_id AND wh.media_id = ul.media_id 
             ORDER BY wh.watched_at DESC LIMIT 1) as rating,
            (SELECT wh.watched_at FROM user_watch_history wh 
             WHERE wh.user_id = ul.user_id AND wh.media_id = ul.media_id 
             ORDER BY wh.watched_at DESC LIMIT 1) as watched_at
     FROM user_lists ul 
     JOIN media m ON ul.media_id = m.id 
     WHERE ul.user_id = ? AND ul.list_type = ? 
     ORDER BY ul.added_at DESC",
    [$current_user['id'], $list_type]
);
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Мой список</h1>
    
    <!-- Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
        <?php foreach ($allowed_types as $type): ?>
            <a href="<?php echo BASE_URL; ?>/watchlist?type=<?php echo $type; ?>" 
               class="px-4 py-2 <?php echo $list_type === $type ? 'border-b-2 border-purple-600 text-purple-600 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-purple-600'; ?>">
                <?php echo getListTypeRu($type); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Media List -->
    <?php if (!empty($media_list)): ?>
        <div class="space-y-4">
            <?php foreach ($media_list as $item): ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="flex flex-col md:flex-row">
                        <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="flex-shrink-0">
                            <img src="<?php echo getPosterUrl($item['poster_url']); ?>" 
                                 alt="<?php echo e($item['title']); ?>" 
                                 class="w-full md:w-32 h-48 md:h-32 object-cover">
                        </a>
                        <div class="flex-1 p-4">
                            <div class="flex justify-between items-start mb-2">
                                <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="hover:text-purple-600">
                                    <h3 class="text-lg font-semibold"><?php echo e($item['title']); ?></h3>
                                    <?php if ($item['original_title'] && $item['original_title'] !== $item['title']): ?>
                                        <p class="text-sm text-gray-500"><?php echo e($item['original_title']); ?></p>
                                    <?php endif; ?>
                                </a>
                                <?php if ($item['rating']): ?>
                                    <div class="flex items-center text-yellow-500">
                                        <span class="text-lg font-semibold">⭐ <?php echo formatRating($item['rating']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <?php if ($list_type === 'completed' || $list_type === 'dropped'): ?>
                                    <?php if ($item['watched_at']): ?>
                                        <span>📅 <?php echo $list_type === 'completed' ? 'Просмотрено' : 'Брошено'; ?>: <?php echo formatDate($item['watched_at'], 'd.m.Y'); ?></span>
                                    <?php elseif ($item['added_at']): ?>
                                        <span>📅 <?php echo $list_type === 'completed' ? 'Просмотрено' : 'Брошено'; ?>: <?php echo formatDate($item['added_at'], 'd.m.Y'); ?></span>
                                    <?php endif; ?>
                                <?php elseif ($list_type === 'on_hold'): ?>
                                    <?php if ($item['added_at']): ?>
                                        <span>📅 Отложено: <?php echo formatDate($item['added_at'], 'd.m.Y'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($item['type'] === 'series' && ($item['season_number'] || $item['episode_number'])): ?>
                                    <span>📺 Сезон <?php echo $item['season_number'] ?? '?'; ?>, Серия <?php echo $item['episode_number'] ?? '?'; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($list_type === 'dropped' && $item['drop_reason']): ?>
                                <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded text-sm">
                                    <span class="font-semibold text-red-700 dark:text-red-400">Почему бросил:</span>
                                    <p class="text-red-600 dark:text-red-300"><?php echo nl2br(e($item['drop_reason'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-600 dark:text-gray-400 text-lg">Список пуст</p>
        </div>
    <?php endif; ?>
</div>



