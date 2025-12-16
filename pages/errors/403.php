<?php
/**
 * FilmTracker - Страница 403
 */

$page_title = 'Доступ запрещен - FilmTracker';
http_response_code(403);
?>

<div class="max-w-md mx-auto text-center py-16">
    <div class="mb-8">
        <h1 class="text-9xl font-bold text-red-600">403</h1>
        <h2 class="text-3xl font-bold mt-4 mb-2">Доступ запрещен</h2>
        <p class="text-gray-600 dark:text-gray-400">У вас нет доступа к этой странице.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>" class="inline-block px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
        Вернуться на главную
    </a>
</div>



