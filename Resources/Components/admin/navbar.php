<!-- Navigation -->
<nav class="mt-5 flex-1 space-y-1 px-3">
    <div class="flex shrink-0 items-center px-3 mb-8">
        <div class="flex items-center space-x-3 w-full">
            <h1 class="text-2xl font-boldbg-clip-text text-transparent">
                <a href="/dashboard" target="" class=" text-black dark:text-white " @click.stop>
                    Dashboard
                </a>
            </h1>
        </div>
    </div>

    <?php if ($user['type'] === 'root'): ?>
        <!-- Managers -->
        <div>
            <button @click="toggleMenu('managers'); $event.stopPropagation()"
                class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
                <div class="flex items-center">
                    <i class="fa fa-users mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200"></i>
                    Managers
                </div>
                <i class="fa fa-chevron-right transition-transform duration-300 text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                    :class="{ 'rotate-90': activeMenus.managers }"></i>
            </button>
            <div x-show="activeMenus.managers" x-collapse class="ml-8 space-y-1 mt-1">
                <button @click="$store.addManagerModal.open(); $event.stopPropagation()"
                    class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                    <i class="fa fa-user-plus mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                    Nouveau
                </button>
                <a @click="$store.managersListModal.open(); $event.stopPropagation()"
                    class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                    <i class="fa fa-cog mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                    Gérer
                </a>
            </div>
        </div>
        <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-5"></div>

        <!-- Groupes -->
        <div>
            <button @click="$store.groupsListModal.open(); $event.stopPropagation()"
                class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
                <div class="flex items-center">
                    <i class="fa fa-users mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200"></i>
                    Groupes
                </div>
            </button>
        </div>
        <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-5"></div>
    <?php endif; ?>

    <!-- Users -->
    <div>
        <button @click="toggleMenu('users'); $event.stopPropagation()"
            class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
            <div class="flex items-center">
                <i class="fa fa-users mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200"></i>
                Utilisateurs
            </div>
            <i class="fa fa-chevron-right transition-transform duration-300 text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                :class="{ 'rotate-90': activeMenus.users }"></i>
        </button>
        <div x-show="activeMenus.users" x-collapse class="ml-8 space-y-1 mt-1">
            <button @click="$store.adduserModal.open(); $event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-user-plus mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Nouveau
            </button>
            <a onclick="window.dispatchEvent(new Event('open-users-modal2'))" @click="$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-cog mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Gérer
            </a>
        </div>
    </div>

    <!-- Liens d'invitation -->
    <div>
        <button @click="toggleMenu('links'); $event.stopPropagation()"
            class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
            <div class="flex items-center">
                <i class="fa fa-link mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200"></i>
                Liens d'invitation
            </div>
            <i class="fa fa-chevron-right transition-transform duration-300 text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                :class="{ 'rotate-90': activeMenus.links }"></i>
        </button>
        <div x-show="activeMenus.links" x-collapse class="ml-8 space-y-1 mt-1">
            <button onclick="window.dispatchEvent(new Event('open-links-modal'))" @click="$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-list mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Liste des liens
            </button>
        </div>
    </div>

    <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-5"></div>

    <!-- Statistiques -->
    <div>
        <button @click="toggleMenu('stats'); $event.stopPropagation()"
            class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
            <div class="flex items-center">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                </svg>
                Statistiques
            </div>
            <i class="fa fa-chevron-right transition-transform duration-300 text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                :class="{ 'rotate-90': activeMenus.stats }"></i>
        </button>
        <div x-show="activeMenus.stats" x-collapse class="ml-8 space-y-1 mt-1">
            <button onclick="window.dispatchEvent(new Event('open-user-stats-modal'))" @click="$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-list mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Utilisateurs
            </button>
            <button onclick="window.dispatchEvent(new Event('open-group-stats-modal'))" @click="$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-list mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Groupes
            </button>
            <button onclick="window.dispatchEvent(new Event('open-site-stats-modal'))" @click="$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-list mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Sites
            </button>
        </div>
    </div>
    <!-- Politiques -->
    <div>
        <button @click="toggleMenu('policies'); $event.stopPropagation()"
            class="group flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-linear-to-r hover:from-indigo-50 hover:to-purple-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30 dark:hover:text-indigo-300 transition-all duration-200">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h16M8 6v4M16 12v4M10 18v-4" />
                </svg>
                Politiques
            </div>

            <i class="fa fa-chevron-right transition-transform duration-300 text-gray-400 dark:text-gray-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                :class="{ 'rotate-90': activeMenus.policies }"></i>
        </button>
        <div x-show="activeMenus.policies" x-collapse class="ml-8 space-y-1 mt-1">
            <button @click="$store.policyListModal.open();$event.stopPropagation()"
                class="group flex items-center cursor-pointer rounded-lg py-2.5 pl-4 pr-3 text-sm font-medium text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-400 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-300 w-full text-left transition-all duration-200 border-l-2 border-transparent hover:border-indigo-500">
                <i class="fa fa-copyright mr-3 h-4 w-4 text-gray-400 group-hover:text-indigo-600 dark:text-gray-500 dark:group-hover:text-indigo-400"></i>
                Gérer
            </button>
        </div>
    </div>
</nav>