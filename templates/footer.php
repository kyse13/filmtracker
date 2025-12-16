    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 dark:bg-gray-900 text-gray-300 mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">FilmTracker</h3>
                    <p class="text-sm">Современная платформа для отслеживания фильмов и сериалов.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Навигация</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo BASE_URL; ?>" class="hover:text-purple-400 transition">Главная</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/browse" class="hover:text-purple-400 transition">Каталог</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/search" class="hover:text-purple-400 transition">Поиск</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Аккаунт</h4>
                    <ul class="space-y-2 text-sm">
                        <?php if ($current_user): ?>
                            <li><a href="<?php echo BASE_URL; ?>/dashboard" class="hover:text-purple-400 transition">Дашборд</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/profile" class="hover:text-purple-400 transition">Профиль</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/settings" class="hover:text-purple-400 transition">Настройки</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>/login" class="hover:text-purple-400 transition">Вход</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/register" class="hover:text-purple-400 transition">Регистрация</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Контакты</h4>
                    <p class="text-sm">Email: <?php echo EMAIL_FROM; ?></p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> FilmTracker. Все права защищены.</p>
            </div>
        </div>
    </footer>
    
    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        (function() {
            const html = document.documentElement;
            const currentTheme = '<?php echo $current_theme; ?>';
            
            if (currentTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
            localStorage.setItem('theme', currentTheme);
            
            document.addEventListener('DOMContentLoaded', function() {
                const themeToggle = document.getElementById('theme-toggle');
                if (themeToggle) {
                    themeToggle.addEventListener('click', () => {
                        html.classList.toggle('dark');
                        const newTheme = html.classList.contains('dark') ? 'dark' : 'light';
                        localStorage.setItem('theme', newTheme);
                        
                        const cookiePath = '<?php 
                            $parsed = parse_url(BASE_URL);
                            $path = isset($parsed['path']) && $parsed['path'] !== '/' ? rtrim($parsed['path'], '/') . '/' : '/';
                            echo $path;
                        ?>';
                        document.cookie = 'theme=' + newTheme + '; path=' + cookiePath + '; max-age=' + (365 * 24 * 60 * 60);
                        
                        const url = '<?php echo BASE_URL; ?>/api?endpoint=update-theme&theme=' + newTheme;
                        
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ theme: newTheme })
                        })
                        .catch(error => {
                            console.error('Theme update error:', error);
                        });
                    });
                }
            });
            
            if (typeof tailwind !== 'undefined') {
                tailwind.config = {
                    darkMode: 'class',
                }
            }
        })();
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo ASSETS_URL . '/js/' . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

