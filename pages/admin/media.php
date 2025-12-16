<?php
$page_title = 'Управление медиа - Админ-панель';

$auth->requireAdmin();
$db = Database::getInstance();

$action = $_GET['action'] ?? 'list';
$media_id = intval($_GET['id'] ?? 0);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $title = trim($_POST['title'] ?? '');
    $original_title = trim($_POST['original_title'] ?? '');
    $type = $_POST['type'] ?? 'movie';
    $description = trim($_POST['description'] ?? '');
    $release_year = intval($_POST['release_year'] ?? 0);
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : null;
    $poster_url = trim($_POST['poster_url'] ?? '');
    $backdrop_url = trim($_POST['backdrop_url'] ?? '');
    $trailer_url = trim($_POST['trailer_url'] ?? '');
    $imdb_id = trim($_POST['imdb_id'] ?? '');
    $imdb_rating = !empty($_POST['imdb_rating']) ? floatval($_POST['imdb_rating']) : null;
    $genres = $_POST['genres'] ?? [];
    $total_seasons = !empty($_POST['total_seasons']) ? intval($_POST['total_seasons']) : null;
    $episodes_per_season = !empty($_POST['episodes_per_season']) ? intval($_POST['episodes_per_season']) : null;
    
    if (empty($title)) {
        $errors[] = 'Название обязательно';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            if ($action === 'add') {
                $new_media_id = $db->insert(
                    "INSERT INTO media (title, original_title, type, description, release_year, duration, poster_url, backdrop_url, trailer_url, imdb_id, imdb_rating) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$title, $original_title, $type, $description, $release_year, $duration, $poster_url, $backdrop_url, $trailer_url, $imdb_id, $imdb_rating]
                );
                $media_id = $new_media_id;
            } else {
                $db->query(
                    "UPDATE media SET title = ?, original_title = ?, type = ?, description = ?, release_year = ?, duration = ?, poster_url = ?, backdrop_url = ?, trailer_url = ?, imdb_id = ?, imdb_rating = ? WHERE id = ?",
                    [$title, $original_title, $type, $description, $release_year, $duration, $poster_url, $backdrop_url, $trailer_url, $imdb_id, $imdb_rating, $media_id]
                );
            }
            
            $db->query("DELETE FROM media_genres WHERE media_id = ?", [$media_id]);
            foreach ($genres as $genre_id) {
                $genre_id = intval($genre_id);
                if ($genre_id > 0) {
                    $db->query("INSERT INTO media_genres (media_id, genre_id) VALUES (?, ?)", [$media_id, $genre_id]);
                }
            }
            
            if ($type === 'series' && $total_seasons && $episodes_per_season) {
                $db->query("DELETE FROM seasons WHERE media_id = ?", [$media_id]);
                for ($s = 1; $s <= $total_seasons; $s++) {
                    $db->insert(
                        "INSERT INTO seasons (media_id, season_number, episode_count) VALUES (?, ?, ?)",
                        [$media_id, $s, $episodes_per_season]
                    );
                }
            }
            
            $db->commit();
            setFlashMessage('success', 'Медиа успешно ' . ($action === 'add' ? 'добавлено' : 'обновлено') . '!');
            redirect(BASE_URL . '/admin/media');
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Ошибка: ' . $e->getMessage();
        }
    }
}

if ($action === 'delete' && $media_id) {
    $db->query("DELETE FROM media WHERE id = ?", [$media_id]);
    setFlashMessage('success', 'Медиа удалено!');
    redirect(BASE_URL . '/admin/media');
}

$media = null;
$media_genres = [];
$media_seasons = [];
if ($action === 'edit' && $media_id) {
    $media = $db->fetchOne("SELECT * FROM media WHERE id = ?", [$media_id]);
    if (!$media) {
        redirect(BASE_URL . '/admin/media');
    }
    $media_genres = $db->fetchAll("SELECT genre_id FROM media_genres WHERE media_id = ?", [$media_id]);
    $media_genres = array_column($media_genres, 'genre_id');
    if ($media['type'] === 'series') {
        $media_seasons = $db->fetchAll("SELECT * FROM seasons WHERE media_id = ? ORDER BY season_number ASC", [$media_id]);
    }
}

$all_genres = $db->fetchAll("SELECT * FROM genres ORDER BY name_ru, name");

$page = max(1, intval($_GET['page'] ?? 1));
$limit = ADMIN_ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(title LIKE ? OR original_title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$total = $db->fetchOne("SELECT COUNT(*) as count FROM media $where_sql", $params)['count'];
$media_list = $db->fetchAll(
    "SELECT * FROM media $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$pagination = paginate($page, $total, $limit, BASE_URL . '/admin/media?' . http_build_query(['search' => $search]));
?>

<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Управление медиа</h1>
        <a href="<?php echo BASE_URL; ?>/admin/media?action=add" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
            + Добавить медиа
        </a>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Список медиа -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <form method="GET" class="mb-4">
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Поиск..." class="px-4 py-2 border rounded-lg dark:bg-gray-700">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Найти</button>
            </form>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">ID</th>
                            <th class="text-left py-2">Название</th>
                            <th class="text-left py-2">Тип</th>
                            <th class="text-left py-2">Год</th>
                            <th class="text-left py-2">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($media_list as $item): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo $item['id']; ?></td>
                                <td class="py-2"><?php echo e($item['title']); ?></td>
                                <td class="py-2"><?php echo getMediaTypeRu($item['type']); ?></td>
                                <td class="py-2"><?php echo $item['release_year']; ?></td>
                                <td class="py-2">
                                    <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:underline mr-2">Просмотр</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/media?action=edit&id=<?php echo $item['id']; ?>" class="text-green-600 hover:underline mr-2">Редактировать</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/media?action=delete&id=<?php echo $item['id']; ?>" 
                                       onclick="return confirm('Удалить это медиа?')" 
                                       class="text-red-600 hover:underline">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-4 flex justify-center gap-2">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="<?php echo $pagination['base_url']; ?>&page=<?php echo $pagination['prev_page']; ?>" class="px-4 py-2 bg-gray-200 rounded">Назад</a>
                    <?php endif; ?>
                    <span class="px-4 py-2">Страница <?php echo $pagination['current_page']; ?> из <?php echo $pagination['total_pages']; ?></span>
                    <?php if ($pagination['has_next']): ?>
                        <a href="<?php echo $pagination['base_url']; ?>&page=<?php echo $pagination['next_page']; ?>" class="px-4 py-2 bg-gray-200 rounded">Вперед</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- Форма добавления/редактирования -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6"><?php echo $action === 'add' ? 'Добавить медиа' : 'Редактировать медиа'; ?></h2>
            
            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">Название *</label>
                        <input type="text" name="title" value="<?php echo e($media['title'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block mb-2">Оригинальное название</label>
                        <input type="text" name="original_title" value="<?php echo e($media['original_title'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">Тип *</label>
                        <select name="type" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                            <option value="movie" <?php echo ($media['type'] ?? '') === 'movie' ? 'selected' : ''; ?>>Фильм</option>
                            <option value="series" <?php echo ($media['type'] ?? '') === 'series' ? 'selected' : ''; ?>>Сериал</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2">Год выпуска</label>
                        <input type="number" name="release_year" value="<?php echo $media['release_year'] ?? ''; ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                </div>
                
                <div>
                    <label class="block mb-2">Описание</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700"><?php echo e($media['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label class="block mb-2">Жанры</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 max-h-48 overflow-y-auto p-2 border rounded-lg dark:bg-gray-700">
                        <?php foreach ($all_genres as $genre): ?>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" 
                                       name="genres[]" 
                                       value="<?php echo $genre['id']; ?>"
                                       <?php echo in_array($genre['id'], $media_genres) ? 'checked' : ''; ?>
                                       class="rounded">
                                <span class="text-sm"><?php echo e($genre['name_ru'] ?: $genre['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div id="series-fields" style="display: <?php echo ($media['type'] ?? 'movie') === 'series' ? 'block' : 'none'; ?>;">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2">Всего сезонов</label>
                            <input type="number" 
                                   name="total_seasons" 
                                   id="total-seasons"
                                   min="1" 
                                   value="<?php echo !empty($media_seasons) ? count($media_seasons) : ''; ?>" 
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block mb-2">Серий в сезоне</label>
                            <input type="number" 
                                   name="episodes_per_season" 
                                   id="episodes-per-season"
                                   min="1" 
                                   value="<?php echo !empty($media_seasons) ? ($media_seasons[0]['episode_count'] ?? '') : ''; ?>" 
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                        </div>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">Длительность (минуты)</label>
                        <input type="number" name="duration" value="<?php echo $media['duration'] ?? ''; ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block mb-2">IMDB ID</label>
                        <input type="text" name="imdb_id" value="<?php echo e($media['imdb_id'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">URL постера</label>
                        <input type="url" name="poster_url" value="<?php echo e($media['poster_url'] ?? ''); ?>" placeholder="https://..." class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                        <p class="text-sm text-gray-500 mt-1">Можно использовать ссылку с Кинопоиска или другого источника</p>
                    </div>
                    <div>
                        <label class="block mb-2">URL фона</label>
                        <input type="url" name="backdrop_url" value="<?php echo e($media['backdrop_url'] ?? ''); ?>" placeholder="https://..." class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                </div>
                
                <div>
                    <label class="block mb-2">Трейлер (YouTube/Vimeo)</label>
                    <input type="url" name="trailer_url" value="<?php echo e($media['trailer_url'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    <p class="text-sm text-gray-500 mt-1">Вставьте ссылку на YouTube или Vimeo. Поддерживаются обычные ссылки и короткие (youtu.be)</p>
                </div>
                
                <div>
                    <label class="block mb-2">IMDB Рейтинг</label>
                    <input type="number" step="0.1" min="0" max="10" name="imdb_rating" value="<?php echo $media['imdb_rating'] ?? ''; ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold">
                        <?php echo $action === 'add' ? 'Добавить' : 'Сохранить'; ?>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/media" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 rounded-lg">Отмена</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSeriesFields() {
    const type = document.getElementById('media-type').value;
    const seriesFields = document.getElementById('series-fields');
    if (type === 'series') {
        seriesFields.style.display = 'block';
    } else {
        seriesFields.style.display = 'none';
    }
}
</script>

<script>
function toggleSeriesFields() {
    const type = document.getElementById('media-type').value;
    const seriesFields = document.getElementById('series-fields');
    if (type === 'series') {
        seriesFields.style.display = 'block';
    } else {
        seriesFields.style.display = 'none';
    }
}
</script>



