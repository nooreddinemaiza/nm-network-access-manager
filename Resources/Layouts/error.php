<?php

use Core\ViewEngine\View;
use Core\Helper\AssetHelper;
use Core\Helper\AssetManager;

$title = $title ?? 'Erreur inattendu'; ?>
<?php

?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <title><?= $title ?></title>
    <?= $view->yield('meta') ?>
    <?= $view->yield('styles') ?>
    <link rel="shortcut icon" href="<?= AssetManager::url('images', 'favicon.png') ?>" type="image/x-icon">
    <?php
    $css = [
        'styles:output.css',
    ]; ?>
    <?= AssetHelper::styles($css) ?>
    <?= AssetHelper::scripts([
        'scripts:cdn.min.js'
    ]) ?>
</head>

<body class="min-h-screen flex flex-col font-sans antialiased safe-top safe-bottom">
    <main>
        <div>
            <?= $view->yield('content') ?>
        </div>
    </main>
</body>

</html>