<div class="flex justify-between p-4 bg-white dark:bg-gray-800 rounded-t-2xl">
    <h2 class=" text-lg font-bold text-gray-900 dark:text-white flex">
        <?= $title ?? '' ?>
    </h2>

    <?php if (isset($subtitle) || isset($xText)): ?>
        <p class="text-gray-700 dark:text-gray-300 text-sm mt-1" <?= $xText ?? '' ?>>
            <?= $subtitle ?? '' ?>
        </p>
    <?php endif; ?>

    <button @click="<?= $close ?>" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
        <i class="fas fa-close text-xl"></i>
    </button>
</div>