<?php
/**
 * FilmTracker - История просмотров
 */

$page_title = 'История просмотров - FilmTracker';

$auth->requireLogin();
$current_user = $auth->getCurrentUser();
$db = Database::getInstance();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM user_watch_history WHERE user_id = ?",
    [$current_user['id']]
)['count'];

$history = $db->fetchAll(
    "SELECT m.*, wh.watched_at, wh.rating, wh.is_rewatch, wh.rewatch_count
     FROM user_watch_history wh
     JOIN media m ON wh.media_id = m.id
     WHERE wh.user_id = ?
     ORDER BY wh.watched_at DESC
     LIMIT ? OFFSET ?",
    [$current_user['id'], $limit, $offset]
);

$pagination = paginate($page, $total, $limit, BASE_URL . '/history');
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">История просмотров</h1>
    
    <?php if (!empty($history)): ?>
        <div class="space-y-4 mb-8">
            <?php foreach ($history as $item): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex gap-4">
                    <img src="<?php echo getPosterUrl($item['poster_url']); ?>" 
                         alt="<?php echo e($item['title']); ?>" 
                         class="w-20 h-28 object-cover rounded">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-1">
                            <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="hover:text-purple-600">
                                <?php echo e($item['title']); ?>
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Просмотрено: <?php echo timeAgo($item['watched_at']); ?>
                        </p>
                        <?php if ($item['rating']): ?>
                            <p class="text-sm text-yellow-500">⭐ <?php echo formatRating($item['rating']); ?></p>
                        <?php endif; ?>
                        <?php if ($item['is_rewatch']): ?>
                            <p class="text-xs text-gray-500">Пересмотр #<?php echo $item['rewatch_count']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="flex justify-center gap-2">
                <?php if ($pagination['has_prev']): ?>
                    <a href="<?php echo $pagination['base_url']; ?>&page=<?php echo $pagination['prev_page']; ?>" 
                       class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                        Назад
                    </a>
                <?php endif; ?>
                
                <span class="px-4 py-2">
                    Страница <?php echo $pagination['current_page']; ?> из <?php echo $pagination['total_pages']; ?>
                </span>
                
                <?php if ($pagination['has_next']): ?>
                    <a href="<?php echo $pagination['base_url']; ?>&page=<?php echo $pagination['next_page']; ?>" 
                       class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                        Вперед
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-600 dark:text-gray-400 text-lg">История пуста</p>
        </div>
    <?php endif; ?>
</div>



