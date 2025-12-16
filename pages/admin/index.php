<?php
$page_title = 'Админ-панель - FilmTracker';

$auth->requireAdmin();
$db = Database::getInstance();

$stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_media' => $db->fetchOne("SELECT COUNT(*) as count FROM media")['count'],
    'total_reviews' => $db->fetchOne("SELECT COUNT(*) as count FROM reviews_comments")['count'],
    'pending_emails' => $db->fetchOne("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'")['count']
];

$recent_users = $db->fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 10"
);

$popular_media = $db->fetchAll(
    "SELECT m.*, COUNT(wh.id) as watch_count 
     FROM media m 
     LEFT JOIN user_watch_history wh ON m.id = wh.media_id 
     GROUP BY m.id 
     ORDER BY watch_count DESC 
     LIMIT 10"
);
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Админ-панель</h1>
    
    <!-- Навигация админа -->
    <div class="flex gap-4 mb-6 border-b border-gray-200 dark:border-gray-700">
        <a href="<?php echo BASE_URL; ?>/admin" class="px-4 py-2 border-b-2 border-purple-600">Дашборд</a>
        <a href="<?php echo BASE_URL; ?>/admin/media" class="px-4 py-2 hover:border-b-2 border-purple-600">Медиа</a>
        <a href="<?php echo BASE_URL; ?>/admin/genres" class="px-4 py-2 hover:border-b-2 border-purple-600">Жанры</a>
        <a href="<?php echo BASE_URL; ?>/admin/users" class="px-4 py-2 hover:border-b-2 border-purple-600">Пользователи</a>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Всего пользователей</p>
            <p class="text-3xl font-bold text-purple-600"><?php echo $stats['total_users']; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Всего медиа</p>
            <p class="text-3xl font-bold text-pink-600"><?php echo $stats['total_media']; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Отзывов</p>
            <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_reviews']; ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">Email в очереди</p>
            <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending_emails']; ?></p>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Последние пользователи</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Имя</th>
                        <th class="text-left py-2">Email</th>
                        <th class="text-left py-2">Роль</th>
                        <th class="text-left py-2">Дата регистрации</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-2"><?php echo $user['id']; ?></td>
                            <td class="py-2"><?php echo e($user['username']); ?></td>
                            <td class="py-2"><?php echo e($user['email']); ?></td>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Админ' : 'Пользователь'; ?>
                                </span>
                            </td>
                            <td class="py-2"><?php echo formatDate($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Popular Media -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4">Популярные медиа</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Название</th>
                        <th class="text-left py-2">Тип</th>
                        <th class="text-left py-2">Просмотров</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popular_media as $media): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="py-2"><?php echo $media['id']; ?></td>
                            <td class="py-2">
                                <a href="<?php echo BASE_URL; ?>/media?id=<?php echo $media['id']; ?>" class="hover:text-purple-600">
                                    <?php echo e($media['title']); ?>
                                </a>
                            </td>
                            <td class="py-2"><?php echo getMediaTypeRu($media['type']); ?></td>
                            <td class="py-2"><?php echo $media['watch_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

