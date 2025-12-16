<?php
$page_title = 'Управление жанрами - Админ-панель';

$auth->requireAdmin();
$db = Database::getInstance();

$action = $_GET['action'] ?? 'list';
$genre_id = intval($_GET['id'] ?? 0);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $name = trim($_POST['name'] ?? '');
    $name_ru = trim($_POST['name_ru'] ?? '');
    
    if (empty($name)) {
        $errors[] = 'Название жанра обязательно';
    } else {
        $slug = generateSlug($name);
        
        if ($action === 'add') {
            $existing = $db->fetchOne("SELECT id FROM genres WHERE name = ? OR slug = ?", [$name, $slug]);
            if ($existing) {
                $errors[] = 'Жанр с таким названием уже существует';
            } else {
                $db->insert(
                    "INSERT INTO genres (name, name_ru, slug) VALUES (?, ?, ?)",
                    [$name, $name_ru, $slug]
                );
                setFlashMessage('success', 'Жанр успешно добавлен!');
                redirect(BASE_URL . '/admin/genres');
            }
        } else {
            $existing = $db->fetchOne("SELECT id FROM genres WHERE (name = ? OR slug = ?) AND id != ?", [$name, $slug, $genre_id]);
            if ($existing) {
                $errors[] = 'Жанр с таким названием уже существует';
            } else {
                $db->query(
                    "UPDATE genres SET name = ?, name_ru = ?, slug = ? WHERE id = ?",
                    [$name, $name_ru, $slug, $genre_id]
                );
                setFlashMessage('success', 'Жанр обновлен!');
                redirect(BASE_URL . '/admin/genres');
            }
        }
    }
}

if ($action === 'delete' && $genre_id) {
    $db->query("DELETE FROM genres WHERE id = ?", [$genre_id]);
    setFlashMessage('success', 'Жанр удален!');
    redirect(BASE_URL . '/admin/genres');
}

$genre = null;
if ($action === 'edit' && $genre_id) {
    $genre = $db->fetchOne("SELECT * FROM genres WHERE id = ?", [$genre_id]);
    if (!$genre) {
        redirect(BASE_URL . '/admin/genres');
    }
}

$genres_list = $db->fetchAll("SELECT * FROM genres ORDER BY name_ru, name");
?>

<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Управление жанрами</h1>
        <a href="<?php echo BASE_URL; ?>/admin/genres?action=add" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition">
            + Добавить жанр
        </a>
    </div>
    
    <?php if ($action === 'list'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">ID</th>
                            <th class="text-left py-2">Название (EN)</th>
                            <th class="text-left py-2">Название (RU)</th>
                            <th class="text-left py-2">Slug</th>
                            <th class="text-left py-2">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($genres_list as $g): ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo $g['id']; ?></td>
                                <td class="py-2"><?php echo e($g['name']); ?></td>
                                <td class="py-2"><?php echo e($g['name_ru'] ?? '—'); ?></td>
                                <td class="py-2"><code class="text-sm"><?php echo e($g['slug']); ?></code></td>
                                <td class="py-2">
                                    <a href="<?php echo BASE_URL; ?>/admin/genres?action=edit&id=<?php echo $g['id']; ?>" class="text-green-600 hover:underline mr-2">Редактировать</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/genres?action=delete&id=<?php echo $g['id']; ?>" 
                                       onclick="return confirm('Удалить этот жанр?')" 
                                       class="text-red-600 hover:underline">Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6"><?php echo $action === 'add' ? 'Добавить жанр' : 'Редактировать жанр'; ?></h2>
            
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
                <div>
                    <label class="block mb-2">Название (EN) *</label>
                    <input type="text" name="name" value="<?php echo e($genre['name'] ?? ''); ?>" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                </div>
                
                <div>
                    <label class="block mb-2">Название (RU)</label>
                    <input type="text" name="name_ru" value="<?php echo e($genre['name_ru'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                </div>
                
                <div class="flex gap-4">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold">
                        <?php echo $action === 'add' ? 'Добавить' : 'Сохранить'; ?>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/genres" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 rounded-lg">Отмена</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

