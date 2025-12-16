<?php
/**
 * FilmTracker - Страница 404
 */

$page_title = 'Страница не найдена - FilmTracker';
http_response_code(404);
?>

<div class="max-w-md mx-auto text-center py-16">
    <div class="mb-8">
        <h1 class="text-9xl font-bold text-purple-600">404</h1>
        <h2 class="text-3xl font-bold mt-4 mb-2">Страница не найдена</h2>
        <p class="text-gray-600 dark:text-gray-400">Извините, запрашиваемая страница не существует.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>" class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
        Вернуться на главную
    </a>
</div>



