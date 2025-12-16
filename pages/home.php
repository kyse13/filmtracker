<?php
/**
 * FilmTracker - Главная страница
 */

$page_title = 'FilmTracker - Отслеживание фильмов и сериалов';

// Получение популярного контента
$db = Database::getInstance();
$trending_movies = $db->fetchAll(
    "SELECT m.*, AVG(wh.rating) as avg_rating, COUNT(wh.id) as watch_count 
     FROM media m 
     LEFT JOIN user_watch_history wh ON m.id = wh.media_id 
     WHERE m.type = 'movie' 
     GROUP BY m.id 
     ORDER BY watch_count DESC, avg_rating DESC 
     LIMIT 6"
);

$trending_series = $db->fetchAll(
    "SELECT m.*, AVG(wh.rating) as avg_rating, COUNT(wh.id) as watch_count 
     FROM media m 
     LEFT JOIN user_watch_history wh ON m.id = wh.media_id 
     WHERE m.type = 'series' 
     GROUP BY m.id 
     ORDER BY watch_count DESC, avg_rating DESC 
     LIMIT 6"
);

$current_user = $auth->getCurrentUser();
$continue_watching = [];

if ($current_user) {
    $continue_watching = $db->fetchAll(
        "SELECT m.*, ul.progress, ul.added_at 
         FROM user_lists ul 
         JOIN media m ON ul.media_id = m.id 
         WHERE ul.user_id = ? AND ul.list_type = 'watching' 
         ORDER BY ul.added_at DESC 
         LIMIT 6",
        [$current_user['id']]
    );
}
?>

<!-- Hero Section -->
<section class="mb-12">
    <div class="relative h-96 rounded-2xl overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center text-white px-4">
                <h1 class="text-5xl md:text-6xl font-bold mb-4">FilmTracker</h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">Отслеживайте фильмы и сериалы</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo BASE_URL; ?>/browse" class="px-8 py-3 bg-white text-purple-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                        Каталог
                    </a>
                    <?php if (!$current_user): ?>
                        <a href="<?php echo BASE_URL; ?>/register" class="px-8 py-3 bg-transparent border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-purple-600 transition">
                            Начать
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Continue Watching (для авторизованных) -->
<?php if ($current_user && !empty($continue_watching)): ?>
<section class="mb-12">
    <h2 class="text-2xl font-bold mb-6">Продолжить просмотр</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach ($continue_watching as $media): ?>
            <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $media['id']; ?>" class="group">
                <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                    <img src="<?php echo getPosterUrl($media['poster_url']); ?>" 
                         alt="<?php echo e($media['title']); ?>" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3">
                        <h3 class="text-white font-semibold text-sm truncate"><?php echo e($media['title']); ?></h3>
                        <?php if ($media['progress'] > 0): ?>
                            <div class="mt-2 bg-gray-700 rounded-full h-1">
                                <div class="bg-purple-500 h-1 rounded-full" style="width: <?php echo $media['progress']; ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Trending Movies -->
<?php if (!empty($trending_movies)): ?>
<section class="mb-12">
    <h2 class="text-2xl font-bold mb-6">Популярные фильмы</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach ($trending_movies as $movie): ?>
            <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $movie['id']; ?>" class="group">
                <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                    <img src="<?php echo getPosterUrl($movie['poster_url']); ?>" 
                         alt="<?php echo e($movie['title']); ?>" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <h3 class="text-white font-semibold text-sm mb-1"><?php echo e($movie['title']); ?></h3>
                            <?php if ($movie['avg_rating']): ?>
                                <div class="flex items-center text-yellow-400 text-xs">
                                    <span>⭐</span>
                                    <span class="ml-1"><?php echo formatRating($movie['avg_rating']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Trending Series -->
<?php if (!empty($trending_series)): ?>
<section class="mb-12">
    <h2 class="text-2xl font-bold mb-6">Популярные сериалы</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach ($trending_series as $series): ?>
            <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $series['id']; ?>" class="group">
                <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                    <img src="<?php echo getPosterUrl($series['poster_url']); ?>" 
                         alt="<?php echo e($series['title']); ?>" 
                         class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <h3 class="text-white font-semibold text-sm mb-1"><?php echo e($series['title']); ?></h3>
                            <?php if ($series['avg_rating']): ?>
                                <div class="flex items-center text-yellow-400 text-xs">
                                    <span>⭐</span>
                                    <span class="ml-1"><?php echo formatRating($series['avg_rating']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="mb-12">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6 text-center">Возможности FilmTracker</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">📺</span>
                </div>
                <h3 class="font-semibold text-lg mb-2">Отслеживание</h3>
                <p class="text-gray-600 dark:text-gray-400">Отмечайте просмотренные фильмы и сериалы</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-pink-100 dark:bg-pink-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">⭐</span>
                </div>
                <h3 class="font-semibold text-lg mb-2">Рейтинги</h3>
                <p class="text-gray-600 dark:text-gray-400">Ставьте оценки и пишите отзывы</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">👥</span>
                </div>
                <h3 class="font-semibold text-lg mb-2">Социальные функции</h3>
                <p class="text-gray-600 dark:text-gray-400">Находите друзей и делитесь активностью</p>
            </div>
        </div>
    </div>
</section>

