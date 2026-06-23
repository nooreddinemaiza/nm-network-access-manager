<?php
$input = $data['input'];
$errors = $data['errors'] ?? 'errors';
?>
<p x-show="<?= $errors ?? 'errors' ?>.<?= $input ?>" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 transform translate-y-1"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd" />
    </svg>
    <span x-text="<?= $errors ?>.<?= $input ?>"></span>
</p>