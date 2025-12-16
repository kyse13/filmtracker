<?php
/**
 * FilmTracker - Поиск
 */

$page_title = 'Поиск - FilmTracker';

$query = $_GET['q'] ?? '';
$db = Database::getInstance();
$results = [];

if (!empty($query)) {
    $results = $db->fetchAll(
        "SELECT m.*, AVG(wh.rating) as avg_rating 
         FROM media m 
         LEFT JOIN user_watch_history wh ON m.id = wh.media_id 
         WHERE m.title LIKE ? OR m.original_title LIKE ? OR m.description LIKE ?
         GROUP BY m.id 
         ORDER BY m.title ASC 
         LIMIT 50",
        ["%$query%", "%$query%", "%$query%"]
    );
}
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Поиск</h1>
    
    <form method="GET" action="" class="mb-8">
        <div class="flex gap-4">
            <input type="text" 
                   name="q" 
                   value="<?php echo e($query); ?>" 
                   placeholder="Введите название фильма или сериала..."
                   class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700">
            <button type="submit" 
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                Найти
            </button>
        </div>
    </form>
    
    <?php if (!empty($query)): ?>
        <?php if (!empty($results)): ?>
            <div class="mb-4">
                <p class="text-gray-600 dark:text-gray-400">Найдено результатов: <?php echo count($results); ?></p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($results as $item): ?>
                    <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="group">
                        <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                            <img src="<?php echo getPosterUrl($item['poster_url']); ?>" 
                                 alt="<?php echo e($item['title']); ?>" 
                                 class="w-full h-80 object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    <h3 class="text-white font-semibold text-sm mb-1"><?php echo e($item['title']); ?></h3>
                                    <p class="text-gray-300 text-xs"><?php echo getMediaTypeRu($item['type']); ?></p>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-600 dark:text-gray-400 text-lg">Ничего не найдено</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>



