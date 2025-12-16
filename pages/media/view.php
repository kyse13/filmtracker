<?php
/**
 * FilmTracker - Страница просмотра медиа
 */

$page_title = 'Медиа - FilmTracker';

$media_id = $_GET['id'] ?? 0;
$db = Database::getInstance();

$media = $db->fetchOne("SELECT * FROM media WHERE id = ?", [$media_id]);

if (!$media) {
    redirect(BASE_URL . '/404');
}

$page_title = e($media['title']) . ' - FilmTracker';

// Получение жанров
$genres = $db->fetchAll(
    "SELECT g.name, g.name_ru FROM genres g 
     JOIN media_genres mg ON g.id = mg.genre_id 
     WHERE mg.media_id = ?",
    [$media_id]
);

// Получение среднего рейтинга
$rating_data = $db->fetchOne(
    "SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
     FROM user_watch_history 
     WHERE media_id = ? AND rating IS NOT NULL",
    [$media_id]
);

// Получение отзывов
$reviews = $db->fetchAll(
    "SELECT rc.*, u.username, u.avatar 
     FROM reviews_comments rc 
     JOIN users u ON rc.user_id = u.id 
     WHERE rc.media_id = ? AND rc.is_approved = 1 
     ORDER BY rc.created_at DESC 
     LIMIT 10",
    [$media_id]
);

$current_user = $auth->getCurrentUser();
$user_list_status = null;
$user_rating = null;

if ($current_user) {
    $user_list_status = $db->fetchOne(
        "SELECT list_type, progress, season_number, episode_number, drop_reason 
         FROM user_lists WHERE user_id = ? AND media_id = ?",
        [$current_user['id'], $media_id]
    );
    
    $user_rating = $db->fetchOne(
        "SELECT rating FROM user_watch_history WHERE user_id = ? AND media_id = ? ORDER BY watched_at DESC LIMIT 1",
        [$current_user['id'], $media_id]
    );
}

// Для сериалов - получение сезонов
$seasons = [];
if ($media['type'] === 'series') {
    $seasons = $db->fetchAll(
        "SELECT * FROM seasons WHERE media_id = ? ORDER BY season_number ASC",
        [$media_id]
    );
}
?>

<!-- Hero Banner -->
<div class="relative h-96 mb-8 rounded-xl overflow-hidden">
    <?php if ($media['backdrop_url']): ?>
        <img src="<?php echo e($media['backdrop_url']); ?>" 
             alt="<?php echo e($media['title']); ?>" 
             class="w-full h-full object-cover">
    <?php else: ?>
        <div class="w-full h-full bg-gradient-to-r from-purple-600 to-pink-600"></div>
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/50 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 p-8">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-2"><?php echo e($media['title']); ?></h1>
        <?php if ($media['original_title'] && $media['original_title'] !== $media['title']): ?>
            <p class="text-xl text-gray-300 mb-4"><?php echo e($media['original_title']); ?></p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-4 text-white">
            <?php if ($media['release_year']): ?>
                <span><?php echo $media['release_year']; ?></span>
            <?php endif; ?>
            <span><?php echo getMediaTypeRu($media['type']); ?></span>
            <?php if ($media['duration']): ?>
                <span><?php echo formatDuration($media['duration']); ?></span>
            <?php endif; ?>
            <?php if ($rating_data['avg_rating']): ?>
                <span class="flex items-center">
                    ⭐ <?php echo formatRating($rating_data['avg_rating']); ?> 
                    <span class="ml-1 text-sm text-gray-300">(<?php echo $rating_data['rating_count']; ?>)</span>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="md:col-span-2">
        <!-- Trailer -->
        <?php if (!empty($media['trailer_url'])): 
            $trailer_embed = getTrailerEmbedUrl($media['trailer_url']);
        ?>
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Трейлер</h2>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="relative w-full" style="padding-bottom: 56.25%;">
                        <iframe 
                            src="<?php echo e($trailer_embed); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            class="absolute top-0 left-0 w-full h-full">
                        </iframe>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Poster and Info -->
        <div class="flex gap-6 mb-8">
            <img src="<?php echo getPosterUrl($media['poster_url']); ?>" 
                 alt="<?php echo e($media['title']); ?>" 
                 class="w-48 h-72 object-cover rounded-lg shadow-lg">
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-4">Описание</h2>
                <p class="text-gray-700 dark:text-gray-300 mb-4"><?php echo nl2br(e($media['description'] ?: 'Описание отсутствует')); ?></p>
                
                <?php if (!empty($genres)): ?>
                    <div class="mb-4">
                        <h3 class="font-semibold mb-2">Жанры:</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($genres as $genre): ?>
                                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full text-sm">
                                    <?php echo e($genre['name_ru'] ?: $genre['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Actions (для авторизованных) -->
        <?php if ($current_user): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Мои действия</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Список:</label>
                        <select id="list-type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                            <option value="">Не в списке</option>
                            <option value="watchlist" <?php echo $user_list_status && $user_list_status['list_type'] === 'watchlist' ? 'selected' : ''; ?>>Хочу посмотреть</option>
                            <option value="watching" <?php echo $user_list_status && $user_list_status['list_type'] === 'watching' ? 'selected' : ''; ?>>Смотрю</option>
                            <option value="completed" <?php echo $user_list_status && $user_list_status['list_type'] === 'completed' ? 'selected' : ''; ?>>Просмотрено</option>
                            <option value="on_hold" <?php echo $user_list_status && $user_list_status['list_type'] === 'on_hold' ? 'selected' : ''; ?>>Отложено</option>
                            <option value="dropped" <?php echo $user_list_status && $user_list_status['list_type'] === 'dropped' ? 'selected' : ''; ?>>Брошено</option>
                        </select>
                    </div>
                    
                    <?php if ($media['type'] === 'series'): ?>
                        <div id="series-progress-fields" style="display: none;">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Сезон:</label>
                                    <input type="number" 
                                           id="season-number" 
                                           min="1" 
                                           value="<?php echo $user_list_status['season_number'] ?? ''; ?>"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Серия:</label>
                                    <input type="number" 
                                           id="episode-number" 
                                           min="1" 
                                           value="<?php echo $user_list_status['episode_number'] ?? ''; ?>"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div id="rating-field" style="display: none;">
                        <label class="block text-sm font-medium mb-2">Моя оценка:</label>
                        <input type="number" 
                               id="user-rating" 
                               min="1" 
                               max="10" 
                               step="0.5" 
                               value="<?php echo $user_rating ? $user_rating['rating'] : ''; ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
                               placeholder="1-10">
                    </div>
                    
                    <div id="drop-reason-field" style="display: none;">
                        <label class="block text-sm font-medium mb-2">Почему бросил:</label>
                        <textarea id="drop-reason" 
                                  rows="3" 
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700"
                                  placeholder="Опишите причину..."><?php echo e($user_list_status['drop_reason'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="button" onclick="saveMediaAction(event)" class="w-full py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
                        Сохранить
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Seasons (для сериалов) -->
        <?php if ($media['type'] === 'series' && !empty($seasons)): ?>
            <div class="mb-8">
                <h3 class="text-2xl font-bold mb-4">Сезоны</h3>
                <div class="space-y-4">
                    <?php foreach ($seasons as $season): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <h4 class="font-semibold text-lg mb-2">
                                Сезон <?php echo $season['season_number']; ?>
                                <?php if ($season['title']): ?>
                                    - <?php echo e($season['title']); ?>
                                <?php endif; ?>
                            </h4>
                            <?php if ($season['description']): ?>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2"><?php echo e($season['description']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm text-gray-500">Эпизодов: <?php echo $season['episode_count']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Add Review Form (для авторизованных) -->
        <?php if ($current_user): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Написать отзыв</h3>
                <form id="review-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Оценка (1-10)</label>
                        <input type="number" id="review-rating" min="1" max="10" step="0.5" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Отзыв</label>
                        <textarea id="review-content" rows="4" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700" placeholder="Ваш отзыв..."></textarea>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="review-spoilers" class="rounded">
                        <label for="review-spoilers" class="ml-2 text-sm">Содержит спойлеры</label>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold">
                        Опубликовать отзыв
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Reviews -->
        <div>
            <h3 class="text-2xl font-bold mb-4">Отзывы (<?php echo count($reviews); ?>)</h3>
            <?php if (!empty($reviews)): ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <img src="<?php echo getAvatarUrl($review['avatar'] ?? '', $review['username']); ?>" 
                                     alt="<?php echo e($review['username']); ?>" 
                                     class="w-10 h-10 rounded-full">
                                <div>
                                    <p class="font-semibold"><?php echo e($review['username']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo timeAgo($review['created_at']); ?></p>
                                </div>
                                <?php if ($review['rating']): ?>
                                    <div class="ml-auto text-yellow-500">
                                        ⭐ <?php echo formatRating($review['rating']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($review['contains_spoilers']): ?>
                                <div class="mb-2 px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded text-sm inline-block">
                                    ⚠️ Содержит спойлеры
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-700 dark:text-gray-300"><?php echo nl2br(e($review['content'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-400">Пока нет отзывов. Будьте первым!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listTypeSelect = document.getElementById('list-type');
    const mediaType = '<?php echo $media['type']; ?>';
    
    function updateFieldsVisibility() {
        const listType = listTypeSelect.value;
        const seriesFields = document.getElementById('series-progress-fields');
        const ratingField = document.getElementById('rating-field');
        const dropReasonField = document.getElementById('drop-reason-field');
        
        if (mediaType === 'series') {
            if (listType === 'watching' || listType === 'on_hold' || listType === 'dropped') {
                seriesFields.style.display = 'block';
            } else {
                seriesFields.style.display = 'none';
            }
        }
        
        if (listType === 'watchlist' && mediaType === 'movie') {
            ratingField.style.display = 'none';
        } else if (listType === 'completed' || listType === 'dropped' || (listType === 'watchlist' && mediaType === 'series')) {
            ratingField.style.display = 'block';
        } else if (listType === 'watching' || listType === 'on_hold') {
            ratingField.style.display = 'block';
        } else {
            ratingField.style.display = 'none';
        }
        
        if (listType === 'dropped') {
            dropReasonField.style.display = 'block';
        } else {
            dropReasonField.style.display = 'none';
        }
    }
    
    listTypeSelect.addEventListener('change', updateFieldsVisibility);
    updateFieldsVisibility();
});

function saveMediaAction(event) {
    event.preventDefault();
    
    const listType = document.getElementById('list-type').value;
    const ratingInput = document.getElementById('user-rating');
    const rating = ratingInput && ratingInput.value ? parseFloat(ratingInput.value) : null;
    const mediaId = <?php echo $media_id; ?>;
    const mediaType = '<?php echo $media['type']; ?>';
    
    const seasonNumberEl = document.getElementById('season-number');
    const episodeNumberEl = document.getElementById('episode-number');
    const dropReasonEl = document.getElementById('drop-reason');
    
    const seasonNumber = seasonNumberEl && seasonNumberEl.value ? parseInt(seasonNumberEl.value) : null;
    const episodeNumber = episodeNumberEl && episodeNumberEl.value ? parseInt(episodeNumberEl.value) : null;
    const dropReason = dropReasonEl && dropReasonEl.value ? dropReasonEl.value.trim() : null;
    
    if (rating !== null && (rating < 1 || rating > 10)) {
        alert('Оценка должна быть от 1 до 10');
        return;
    }
    
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Сохранение...';
    
    const requestData = {
        media_id: mediaId,
        list_type: listType || '',
        rating: rating || null,
        season_number: seasonNumber || null,
        episode_number: episodeNumber || null,
        drop_reason: dropReason || null
    };
    
    if (requestData.rating === null) {
        delete requestData.rating;
    }
    if (requestData.season_number === null) {
        delete requestData.season_number;
    }
    if (requestData.episode_number === null) {
        delete requestData.episode_number;
    }
    if (requestData.drop_reason === null || requestData.drop_reason === '') {
        delete requestData.drop_reason;
    }
    
    console.log('Sending request:', requestData);
    
    // Пробуем POST, если не работает - используем GET с данными в URL
    const apiUrl = '<?php echo BASE_URL; ?>/api?endpoint=media-action';
    
    // Сначала пробуем POST
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(requestData)
    })
    .then(async response => {
        console.log('Response status:', response.status);
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Сервер вернул не JSON ответ. Проверьте консоль.');
        }
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Неизвестная ошибка' }));
            console.error('Error response:', errorData);
            throw new Error(errorData.error || 'HTTP error! status: ' + response.status);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data && data.success) {
            alert('Сохранено успешно!');
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Ошибка: ' + (data?.error || 'Неизвестная ошибка'));
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('POST failed, trying GET fallback:', error);
        
        const getUrl = apiUrl + '&media_id=' + encodeURIComponent(mediaId) + 
                      '&list_type=' + encodeURIComponent(listType || '') +
                      '&rating=' + encodeURIComponent(rating || '') +
                      (seasonNumber ? '&season_number=' + encodeURIComponent(seasonNumber) : '') +
                      (episodeNumber ? '&episode_number=' + encodeURIComponent(episodeNumber) : '') +
                      (dropReason ? '&drop_reason=' + encodeURIComponent(dropReason) : '');
        
        return fetch(getUrl, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(async response => {
            console.log('GET Response status:', response.status);
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Сервер вернул не JSON ответ. Проверьте консоль.');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Неизвестная ошибка' }));
                console.error('Error response:', errorData);
                throw new Error(errorData.error || 'HTTP error! status: ' + response.status);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('GET Response data:', data);
            if (data && data.success) {
                alert('Сохранено успешно!');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Ошибка: ' + (data?.error || 'Неизвестная ошибка'));
                button.disabled = false;
                button.textContent = originalText;
            }
        })
        .catch(getError => {
            console.error('GET also failed:', getError);
            alert('Ошибка при сохранении:\n' + getError.message + '\n\nОткройте консоль (F12) для подробностей.');
            button.disabled = false;
            button.textContent = originalText;
        });
    });
}

// Форма отзыва
<?php if ($current_user): ?>
document.getElementById('review-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rating = document.getElementById('review-rating').value;
    const content = document.getElementById('review-content').value;
    const spoilers = document.getElementById('review-spoilers').checked;
    const mediaId = <?php echo $media_id; ?>;
    
    if (!content.trim()) {
        alert('Введите текст отзыва');
        return;
    }
    
    // Пробуем POST, если не работает - используем GET с данными в URL
    const reviewApiUrl = '<?php echo BASE_URL; ?>/api?endpoint=add-review';
    const button = e.target.querySelector('button[type="submit"]');
    const originalText = button ? button.textContent : 'Опубликовать отзыв';
    
    if (button) {
        button.disabled = true;
        button.textContent = 'Публикация...';
    }
    
    // Сначала пробуем POST
    fetch(reviewApiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            media_id: mediaId,
            rating: rating || null,
            content: content,
            contains_spoilers: spoilers
        })
    })
    .then(async response => {
        console.log('Review POST Response status:', response.status);
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Сервер вернул не JSON ответ. Проверьте консоль.');
        }
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Неизвестная ошибка' }));
            console.error('Error response:', errorData);
            throw new Error(errorData.error || 'HTTP error! status: ' + response.status);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Review POST Response data:', data);
        if (data && data.success) {
            alert('Отзыв опубликован!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data?.error || 'Не удалось опубликовать отзыв'));
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    })
    .catch(error => {
        console.error('POST failed, trying GET fallback:', error);
        
        // Если POST не сработал, пробуем GET с данными в URL
        const getUrl = reviewApiUrl + '&media_id=' + encodeURIComponent(mediaId) + 
                      '&rating=' + encodeURIComponent(rating || '') +
                      '&content=' + encodeURIComponent(content) +
                      '&contains_spoilers=' + encodeURIComponent(spoilers ? '1' : '0');
        
        return fetch(getUrl, {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(async response => {
            console.log('Review GET Response status:', response.status);
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Сервер вернул не JSON ответ. Проверьте консоль.');
            }
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Неизвестная ошибка' }));
                console.error('Error response:', errorData);
                throw new Error(errorData.error || 'HTTP error! status: ' + response.status);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Review GET Response data:', data);
            if (data && data.success) {
                alert('Отзыв опубликован!');
                location.reload();
            } else {
                alert('Ошибка: ' + (data?.error || 'Не удалось опубликовать отзыв'));
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        })
        .catch(getError => {
            console.error('GET also failed:', getError);
            alert('Ошибка при публикации отзыва:\n' + getError.message + '\n\nОткройте консоль (F12) для подробностей.');
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
});
<?php endif; ?>
</script>

