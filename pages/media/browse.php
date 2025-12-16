<?php
/**
 * FilmTracker - Каталог медиа
 */

$page_title = 'Каталог - FilmTracker';

$db = Database::getInstance();

// Фильтры
$type = $_GET['type'] ?? '';
$genre = $_GET['genre'] ?? '';
$year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Построение запроса
$where = [];
$params = [];

if ($type) {
    $where[] = "m.type = ?";
    $params[] = $type;
}

if ($genre) {
    $where[] = "m.id IN (SELECT media_id FROM media_genres WHERE genre_id = ?)";
    $params[] = $genre;
}

if ($year) {
    $where[] = "m.release_year = ?";
    $params[] = $year;
}

if ($search) {
    $where[] = "(m.title LIKE ? OR m.original_title LIKE ? OR m.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Подсчет общего количества
$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM media m $where_sql",
    $params
)['count'];

// Получение медиа
$media_list = $db->fetchAll(
    "SELECT m.*, AVG(wh.rating) as avg_rating 
     FROM media m 
     LEFT JOIN user_watch_history wh ON m.id = wh.media_id 
     $where_sql 
     GROUP BY m.id 
     ORDER BY m.release_year DESC, m.title ASC 
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Получение жанров для фильтра
$genres = $db->fetchAll("SELECT * FROM genres ORDER BY name_ru");

// Пагинация
$pagination = paginate($page, $total, $limit, BASE_URL . '/browse?' . http_build_query(array_filter([
    'type' => $type,
    'genre' => $genre,
    'year' => $year,
    'search' => $search
])));
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Каталог</h1>
    
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="" class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Тип</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                    <option value="">Все</option>
                    <option value="movie" <?php echo $type === 'movie' ? 'selected' : ''; ?>>Фильмы</option>
                    <option value="series" <?php echo $type === 'series' ? 'selected' : ''; ?>>Сериалы</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Жанр</label>
                <select name="genre" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                    <option value="">Все жанры</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo $genre == $g['id'] ? 'selected' : ''; ?>>
                            <?php echo e($g['name_ru'] ?: $g['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Год</label>
                <input type="number" 
                       name="year" 
                       value="<?php echo e($year); ?>" 
                       placeholder="Год"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Поиск</label>
                <input type="text" 
                       name="search" 
                       value="<?php echo e($search); ?>" 
                       placeholder="Название..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
            </div>
            
            <div class="md:col-span-4">
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                    Применить фильтры
                </button>
                <a href="<?php echo BASE_URL; ?>/browse" class="ml-4 px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Сбросить
                </a>
            </div>
        </form>
    </div>
    
    <!-- Results -->
    <div class="mb-6">
        <p class="text-gray-600 dark:text-gray-400">Найдено: <?php echo $total; ?> результатов</p>
    </div>
    
    <!-- Media Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
        <?php foreach ($media_list as $item): ?>
            <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="group">
                <div class="relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition">
                    <img src="<?php echo getPosterUrl($item['poster_url']); ?>" 
                         alt="<?php echo e($item['title']); ?>" 
                         class="w-full h-80 object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <h3 class="text-white font-semibold text-sm mb-1"><?php echo e($item['title']); ?></h3>
                            <p class="text-gray-300 text-xs mb-1"><?php echo getMediaTypeRu($item['type']); ?> • <?php echo $item['release_year']; ?></p>
                            <?php if ($item['avg_rating']): ?>
                                <div class="flex items-center text-yellow-400 text-xs">
                                    <span>⭐</span>
                                    <span class="ml-1"><?php echo formatRating($item['avg_rating']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
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
</div>



