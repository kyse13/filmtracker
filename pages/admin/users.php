<?php
$page_title = 'Управление пользователями - Админ-панель';

$auth->requireAdmin();
$db = Database::getInstance();

$action = $_GET['action'] ?? 'list';
$user_id = intval($_GET['id'] ?? 0);

if ($action === 'toggle-ban' && $user_id) {
    $user = $db->fetchOne("SELECT username, is_banned FROM users WHERE id = ?", [$user_id]);
    if ($user) {
        if ($user['username'] === 'admin') {
            setFlashMessage('error', 'Нельзя заблокировать создателя системы');
        } else {
            $new_status = $user['is_banned'] ? 0 : 1;
            $db->query("UPDATE users SET is_banned = ? WHERE id = ?", [$new_status, $user_id]);
            setFlashMessage('success', $new_status ? 'Пользователь заблокирован' : 'Пользователь разблокирован');
        }
    }
    redirect(BASE_URL . '/admin/users');
}

if ($action === 'change-role' && $user_id) {
    $user = $db->fetchOne("SELECT username, role FROM users WHERE id = ?", [$user_id]);
    if ($user) {
        if ($user['username'] === 'admin') {
            setFlashMessage('error', 'Нельзя изменить роль создателя системы');
        } else {
            $new_role = $_GET['role'] ?? 'user';
            if (in_array($new_role, ['admin', 'user'])) {
                $db->query("UPDATE users SET role = ? WHERE id = ?", [$new_role, $user_id]);
                setFlashMessage('success', $new_role === 'admin' ? 'Пользователь назначен администратором' : 'Пользователь снят с админки');
            }
        }
    }
    redirect(BASE_URL . '/admin/users');
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = ADMIN_ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$total = $db->fetchOne("SELECT COUNT(*) as count FROM users $where_sql", $params)['count'];
$users_list = $db->fetchAll(
    "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$pagination = paginate($page, $total, $limit, BASE_URL . '/admin/users?' . http_build_query(['search' => $search]));
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6">Управление пользователями</h1>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="mb-4">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Поиск по имени или email..." class="px-4 py-2 border rounded-lg dark:bg-gray-700">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Найти</button>
        </form>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Имя</th>
                        <th class="text-left py-2">Email</th>
                        <th class="text-left py-2">Роль</th>
                        <th class="text-left py-2">Статус</th>
                        <th class="text-left py-2">Дата регистрации</th>
                        <th class="text-left py-2">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $user): ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo $user['id']; ?></td>
                            <td class="py-2"><?php echo e($user['username']); ?></td>
                            <td class="py-2"><?php echo e($user['email']); ?></td>
                            <td class="py-2">
                                <?php if ($user['username'] === 'admin'): ?>
                                    <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-700 font-bold">
                                        Создатель
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Админ' : 'Пользователь'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2">
                                <?php if ($user['is_banned']): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded">Заблокирован</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded">Активен</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2"><?php echo formatDate($user['created_at']); ?></td>
                            <td class="py-2">
                                <?php if ($user['username'] === 'admin'): ?>
                                    <span class="text-gray-400 italic">Защищен</span>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/admin/users?action=toggle-ban&id=<?php echo $user['id']; ?>" 
                                       onclick="return confirm('<?php echo $user['is_banned'] ? 'Разблокировать' : 'Заблокировать'; ?> пользователя?')"
                                       class="text-orange-600 hover:underline mr-2">
                                        <?php echo $user['is_banned'] ? 'Разблокировать' : 'Заблокировать'; ?>
                                    </a>
                                    <?php if ($user['role'] === 'user'): ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/users?action=change-role&id=<?php echo $user['id']; ?>&role=admin" 
                                           onclick="return confirm('Сделать администратором?')"
                                           class="text-purple-600 hover:underline mr-2">Сделать админом</a>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/users?action=change-role&id=<?php echo $user['id']; ?>&role=user" 
                                           onclick="return confirm('Снять с админки? Пользователь станет обычным пользователем.')"
                                           class="text-red-600 hover:underline">Снять с админки</a>
                                    <?php endif; ?>
                                <?php endif; ?>
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
</div>



