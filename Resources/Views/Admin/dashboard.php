<?php

use Core\Helper\AssetHelper;


$view->layout('layouts', 'main.php');
$view->section('meta');
?>
<meta name="dashbord-csrf" content="<?= $csrf_token ?>">
<?php
$view->endSection();

$view->section('styles');
echo AssetHelper::styles([
    'styles:admin/main.css',
]);
?>

<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
$view->endSection();
$view->section("content")
?>
<div x-data="{ 
    sidebarOpen: false, 
    userMenuOpen: false,
    activeMenus: {},
    darkMode: false,
    toggleMenu(menu) {
        this.activeMenus[menu] = !this.activeMenus[menu];
    }
}">

    <?= $view->inc('partials', 'admin/navbar.php', []); ?>

    <!-- Contenu principal -->
    <div class="lg:pl-64">
        <!-- Header -->
        <div
            class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
            <!-- Bouton menu mobile -->
            <button @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden">
                <span class="sr-only">Open sidebar</span>
                <i class="fa fa-bars h-6 w-6"></i>
            </button>

            <!-- Séparateur -->
            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden"></div>

            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                <div class="flex flex-1"></div>
                <div class="flex items-center gap-x-4 lg:gap-x-6">
                    <!-- Menu utilisateur -->
                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-x-3 text-sm leading-6 dark:text-gray-100">
                            <div
                                class="h-8 w-8 rounded-full bg-indigo-600 dark:bg-indigo-700 flex items-center justify-center text-white font-medium">
                                <?php
                                $initials = '';
                                if (!empty($user['fullname'])) {
                                    $parts = preg_split('/\s+/', trim($user['fullname']));
                                    if (count($parts) >= 2) {
                                        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                                    } else {
                                        $initials = strtoupper(substr($user['fullname'], 0, 1));
                                    }
                                }
                                ?>
                                <?= $initials ?>
                            </div>
                            <span class="hidden lg:flex lg:items-center">
                                <span
                                    class="text-sm font-semibold leading-6 dark:text-gray-100"><?= $user['fullname'] ?></span>
                                <i class="fa fa-chevron-down ml-2 h-3 w-3 text-gray-400 dark:text-gray-500"></i>
                            </span>
                        </button>

                        <!-- Dropdown menu -->
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 py-2 shadow-lg ring-1 ring-gray-900/5 dark:ring-gray-700/50 focus:outline-none"
                            style="display: none;">
                            <div x-data="profileManager()">
                                <a @click="openProfile()"
                                    class="block px-3 py-1 text-sm leading-6 cursor-pointer dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <i
                                        class="fa fa-user mr-3 h-4 w-4 text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400"></i>
                                    Profil
                                </a>
                                <?= $view->inc('partials', 'admin/profile.php', []); ?>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                            <a href="/logout"
                                class="block px-3 py-1 text-sm leading-6 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fa fa-sign-out-alt mr-2 h-4 w-4 text-gray-400 dark:text-gray-500"></i>
                                Déconnexion
                            </a>

                            <!-- Switch Theme avec classes TailwindCSS -->
                            <div
                                class="block px-3 py-1 text-sm leading-6 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <?= $view->inc('components', 'sombre.php', []); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <main class="z-50">
            <?= $view->inc('partials', 'admin/stats.totals.php', []); ?>
        </main>
        <div class="z-100">
            <?php if ($user['type'] === 'root'): ?>
                <?= $view->inc('partials', 'admin/managers.php', []); ?>
                <?= $view->inc('partials', 'admin/groups.php', []); ?>
            <?php endif; ?>
            <?= $view->inc('partials', 'admin/policies.php', []); ?>
            <?= $view->inc('partials', 'admin/policies.items.php', []); ?>
            <?= $view->inc('partials', 'admin/stats.groups.php', []); ?>
            <?= $view->inc('partials', 'admin/stats.php', []); ?>
            <?= $view->inc('partials', 'admin/users.php', []); ?>
            <?php $view->inc('partials', 'admin/users_list.php', []); ?>
            <?= $view->inc('partials', 'admin/invites.php', []); ?>
            <?= $view->inc('partials', 'admin/remove-confirmation.php', []); ?>
        </div>
    </div>
    <!-- Sidebar pour desktop -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
        <div class="flex min-h-0 flex-1 flex-col bg-white dark:bg-gray-800 shadow-lg">
            <!-- Logo -->
            <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                <?= $view->inc('components', 'admin/navbar.php', []); ?>
            </div>
        </div>
    </div>

    <!-- Sidebar mobile -->
    <div x-show="sidebarOpen" class="relative lg:hidden" x-cloak>
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900 bg-opacity-75 dark:bg-black dark:bg-opacity-75"></div>
        <div class="fixed inset-0 flex">
            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="relative mr-16 flex w-full max-w-xs flex-1">
                <div x-show="sidebarOpen" x-transition:enter="ease-in-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="absolute left-full top-0 flex w-16 justify-center pt-5">
                    <button @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                        <span class="sr-only">Close sidebar</span>
                        <i class="fa fa-times h-6 w-6 text-white"></i>
                    </button>
                </div>
                <!-- Contenu sidebar mobile (même que desktop) -->
                <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-800 px-6 pb-4">
                    <?= $view->inc('components', 'admin/navbar.php', []); ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Conteneur pour les toasts -->
<div id="toaster" class="fixed bottom-5 right-5 space-y-2 flex flex-col items-end z-50"></div>
<?php
$view->endSection();

?>


<?php

$view->section('scripts');

?>

<?php


$scripts = [
    'scripts:admin/main.js',
    'scripts:admin/test.js',
];

if ($user['type'] == 'root') {
    $scripts[] = 'scripts:admin/admin.js';
}
echo AssetHelper::scripts($scripts);
$view->endSection();
?>