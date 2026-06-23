<!-- Sidebar pour desktop -->
<div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
    <div class="flex min-h-0 flex-1 flex-col bg-white dark:bg-gray-900 shadow-lg">
        <!-- Logo -->
        <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
            <?= $view->inc('components', 'admin/navbar.php', []); ?>
        </div>
    </div>
</div>

<!-- Sidebar mobile -->
<div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-cloak>
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900 bg-opacity-75 dark:bg-gray-800 dark:bg-opacity-90"></div>
    <div class="fixed inset-0 flex">
        <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            class="relative mr-16 flex w-full max-w-xs flex-1">
            <div x-show="sidebarOpen" x-transition:enter="ease-in-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in-out duration-300"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="absolute left-full top-0 flex w-16 justify-center pt-5">
                <button @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                    <span class="sr-only">Close sidebar</span>
                    <i class="fa fa-times h-6 w-6 text-white dark:text-gray-200"></i>
                </button>
            </div>
            <!-- Contenu sidebar mobile (même que desktop) -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-900 px-6 pb-4">
                <?= $view->inc('components', 'admin/navbar.php', []); ?>
            </div>
        </div>
    </div>
</div>