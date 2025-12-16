<?php
$current_user = $auth->getCurrentUser();
$current_theme = getCurrentTheme();
$page_title = $page_title ?? 'FilmTracker - Отслеживание фильмов и сериалов';
?>
<!DOCTYPE html>
<html lang="ru"<?php echo $current_theme === 'dark' ? ' class="dark"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FilmTracker - Современная платформа для отслеживания фильмов и сериалов">
    <title><?php echo e($page_title); ?></title>
    
    <style>
        html{background-color:#f9fafb}
        html.dark{background-color:#111827}
    </style>
    
    <script>
        !function(){function e(e){const t=`; ${document.cookie}`;const n=t.split(`; ${e}=`);return 2===n.length?n.pop().split(";").shift():null}const t=e("theme"),n=localStorage.getItem("theme"),o=t||n||"<?php echo $current_theme; ?>";if("dark"===o&&!document.documentElement.classList.contains("dark"))document.documentElement.classList.add("dark");else if("light"===o&&document.documentElement.classList.contains("dark"))document.documentElement.classList.remove("dark");t?localStorage.setItem("theme",t):n&&(document.cookie="theme="+n+"; path=/; max-age="+31536000)}();
    </script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
        }
        
        .dark {
            color-scheme: dark;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #764ba2;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .dark .glass {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>" class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">FT</span>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        FilmTracker
                    </span>
                </a>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?php echo BASE_URL; ?>" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Главная</a>
                    <a href="<?php echo BASE_URL; ?>/browse" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Каталог</a>
                    <a href="<?php echo BASE_URL; ?>/search" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Поиск</a>
                    
                    <?php if ($current_user): ?>
                        <a href="<?php echo BASE_URL; ?>/dashboard" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Дашборд</a>
                        <a href="<?php echo BASE_URL; ?>/watchlist" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Мой список</a>
                        <a href="<?php echo BASE_URL; ?>/friends" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Друзья</a>
                        <a href="<?php echo BASE_URL; ?>/users" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Пользователи</a>
                    <?php endif; ?>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <span class="dark:hidden">🌙</span>
                        <span class="hidden dark:inline">☀️</span>
                    </button>
                    
                    <?php if ($current_user): ?>
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                                <img src="<?php echo getAvatarUrl($current_user['avatar'] ?? '', $current_user['username'] ?? ''); ?>" 
                                     alt="<?php echo e($current_user['username']); ?>" 
                                     class="w-8 h-8 rounded-full">
                                <span class="hidden md:inline"><?php echo e($current_user['username']); ?></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" 
                                 x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50">
                                <a href="<?php echo BASE_URL; ?>/profile" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Профиль</a>
                                <a href="<?php echo BASE_URL; ?>/friends" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Друзья</a>
                                <a href="<?php echo BASE_URL; ?>/settings" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Настройки</a>
                                <?php if ($auth->isAdmin()): ?>
                                    <a href="<?php echo BASE_URL; ?>/admin" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Админ-панель</a>
                                <?php endif; ?>
                                <hr class="my-2 border-gray-200 dark:border-gray-700">
                                <a href="<?php echo BASE_URL; ?>/logout" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600">Выход</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Auth Buttons -->
                        <a href="<?php echo BASE_URL; ?>/login" class="px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Вход</a>
                        <a href="<?php echo BASE_URL; ?>/register" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php 
    $flash_messages = getFlashMessages();
    if (!empty($flash_messages)): 
    ?>
        <div class="container mx-auto px-4 mt-4">
            <?php foreach ($flash_messages as $flash): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; ?>">
                    <?php echo e($flash['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">

