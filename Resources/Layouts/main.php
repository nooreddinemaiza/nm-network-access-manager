<?php

use Core\Helper\AssetHelper;
use Core\Helper\AssetManager;

?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth" x-data="{ open: false, cartOpen:false }">

<head>
    <?php
    echo ($meta ?? '')
    ?>
    <?= $view->yield('meta') ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="<?= AssetManager::url('images', 'favicon.png') ?>" type="image/x-icon">

    <?php
    $css = [
        'styles:output.css'
    ]; ?>
    <?= AssetHelper::styles($css) ?>
    <?= $view->yield('styles') ?>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>

<body class="min-h-screen flex flex-col font-sans antialiased safe-top safe-bottom" <?= !empty($xData) ? ("x-data=\"$xData\"") : '' ?>>
    <?php if (!empty($has_header)): ?>
        <?= $view->include('partials', 'navigation.php') ?>
    <?php endif; ?>
    <main>
        <?php if (!empty($breadcrumb)): ?>
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <ol class="flex items-center space-x-2 text-sm">
                        <?php foreach ($breadcrumb as $index => $item): ?>
                            <?php if ($index > 0): ?>
                                <li><i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 text-xs"></i></li>
                            <?php endif; ?>

                            <li>
                                <?php if (!empty($item['url']) && $index < count($breadcrumb) - 1): ?>
                                    <a href="<?= $view->e($item['url']) ?>"
                                        class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                        <?= html_entity_decode($item['title']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <?= html_entity_decode($item['title']) ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </nav>
        <?php endif; ?>
        <?= $view->yield('content') ?>
    </main>
    <?php if (!empty($has_footer)): ?>
        <?= $view->yield('footer') ?>
    <?php endif; ?>
    <?= $view->yield('scripts') ?>
    <?php if (isset($has_entrence) && $has_entrence):
        echo AssetHelper::scripts([
            'scripts:page-loader-store.js',
        ]);
    endif; ?>
</body>

</html>