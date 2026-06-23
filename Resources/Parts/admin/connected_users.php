<div x-data="userStatsModal()" x-show="isOpen" x-cloak @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

    <!-- Modal Principal -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-7xl bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-h-[95vh] flex flex-col"
            @click.stop>
            <?= $view->inc('components', 'admin/modal.header.php', [
                'title' => '<svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                            </svg>
                    <h2 class="text-lg font-bold">Utilisateurs</h2>',
                'subtitle' => '',
                'close' => 'closeModal()',
            ]); ?>

            <!-- Statistiques Globales (Réductible) -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-900/50 dark:to-gray-800/50">
                <button @click="showGlobalStats = !showGlobalStats"
                    class="w-full px-6 py-3 flex items-center justify-between hover:bg-purple-100/50 dark:hover:bg-gray-800/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Statistiques Globales</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            (<span x-text="globalStats.total_users"></span> utilisateurs,
                            <span class="text-green-600 dark:text-green-400" x-text="globalStats.online_users"></span> en ligne)
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                        :class="{'rotate-180': showGlobalStats}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="showGlobalStats" x-collapse class="px-6 pb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Total Utilisateurs -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Total</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="globalStats.total_users"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Utilisateurs En Ligne -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-green-500 to-green-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">En ligne</p>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="globalStats.online_users"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Sessions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Sessions</p>
                                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="globalStats.total_sessions"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Temps Total -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Temps total</p>
                                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400" x-text="formatDuration(globalStats.total_time)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Download -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-cyan-500 to-cyan-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Download</p>
                                    <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400" x-text="formatBytes(globalStats.total_upload)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Upload -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Upload</p>
                                    <p class="text-lg font-bold text-teal-600 dark:text-teal-400" x-text="formatBytes(globalStats.total_upload)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Consommation -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-pink-500 to-pink-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Conso</p>
                                    <p class="text-lg font-bold text-pink-600 dark:text-pink-400" x-text="formatBytes(globalStats.total_consumption)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Moyenne par Utilisateur -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Moy Temps/User</p>
                                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="formatDuration(Math.round(globalStats.avg_time_per_user))"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et Tri (Réductible) -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                <button @click="showFilters = !showFilters"
                    class="w-full px-6 py-3 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Filtres et Tri</span>
                        <span x-show="searchTerm || selectedGroup !== 'all' || selectedStatus !== 'all' || onlineFilter !== 'all' || selectedYear !== 'all' || selectedMonth !== 'all' || dateFrom || dateTo"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                            Actifs
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                        :class="{'rotate-180': showFilters}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="showFilters" x-collapse class="px-6 pb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                        <!-- Recherche -->
                        <div class="relative">
                            <input type="text" x-model="searchTerm" @input="onSearchInput()"
                                placeholder="Rechercher..."
                                class="w-full px-3 py-2 pl-9 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                            <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Filtre Groupe -->
                        <select x-model="selectedGroup" @change="applyFilters()"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                            <option value="all">Tous les groupes</option>
                            <template x-for="(group, idx) in groups" :key="idx">
                                <option :value="group.name" x-text="group.name"></option>
                            </template>
                        </select>

                        <!-- Filtre Statut -->
                        <select x-model="selectedStatus" @change="applyFilters()"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                            <option value="all">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="suspended">Suspendu</option>
                            <option value="expired">Expiré</option>
                        </select>

                        <!-- Filtre En ligne -->
                        <select x-model="onlineFilter" @change="applyFilters()"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                            <option value="all">Tous</option>
                            <option value="online">En ligne</option>
                            <option value="offline">Hors ligne</option>
                        </select>
                        <!-- Bloc Filtres Date - Reorganisé -->
                        <div class="col-span-full">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Filtrer par date</span>
                                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                                <!-- Toggle Mode -->
                                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
                                    <button @click="dateMode = 'picker'; dateFrom = ''; dateTo = ''; applyFilters()"
                                        :class="dateMode === 'picker' 
                    ? 'bg-white dark:bg-gray-600 text-purple-600 dark:text-purple-400 shadow-sm' 
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                                        Année / Mois / Jour
                                    </button>
                                    <button @click="dateMode = 'range'; selectedYear = 'all'; selectedMonth = 'all'; selectedDay = 'all'; applyFilters()"
                                        :class="dateMode === 'range' 
                    ? 'bg-white dark:bg-gray-600 text-purple-600 dark:text-purple-400 shadow-sm' 
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                                        Période
                                    </button>
                                </div>
                            </div>

                            <!-- Mode Année / Mois / Jour -->
                            <div x-show="dateMode === 'picker'" class="flex items-center gap-2">

                                <!-- Année -->
                                <div class="relative flex-1">
                                    <select x-model="selectedYear" @change="applyFilters()"
                                        class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100 appearance-none pr-8">
                                        <option value="all">Année</option>
                                        <template x-for="year in availableYears" :key="year">
                                            <option :value="year" x-text="year"></option>
                                        </template>
                                    </select>
                                    <svg class="absolute right-2.5 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>

                                <!-- Mois -->
                                <div class="relative flex-1">
                                    <select x-model="selectedMonth" @change="applyFilters(); onMonthChange()"
                                        :disabled="selectedYear === 'all'"
                                        :class="selectedYear === 'all' ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100 appearance-none pr-8 disabled:opacity-50">
                                        <option value="all">Mois</option>
                                        <option value="1">Janvier</option>
                                        <option value="2">Février</option>
                                        <option value="3">Mars</option>
                                        <option value="4">Avril</option>
                                        <option value="5">Mai</option>
                                        <option value="6">Juin</option>
                                        <option value="7">Juillet</option>
                                        <option value="8">Août</option>
                                        <option value="9">Septembre</option>
                                        <option value="10">Octobre</option>
                                        <option value="11">Novembre</option>
                                        <option value="12">Décembre</option>
                                    </select>
                                    <svg class="absolute right-2.5 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>

                                <!-- Jour -->
                                <div class="relative flex-1">
                                    <select x-model="selectedDay" @change="applyFilters()"
                                        :disabled="selectedMonth === 'all' || selectedYear === 'all'"
                                        :class="selectedMonth === 'all' || selectedYear === 'all' ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100 appearance-none pr-8 disabled:opacity-50">
                                        <option value="all">Jour</option>
                                        <template x-for="day in availableDays" :key="day">
                                            <option :value="day" x-text="day"></option>
                                        </template>
                                    </select>
                                    <svg class="absolute right-2.5 top-2.5 w-3.5 h-3.5 text-gray-400 pointer-events-none" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <!-- Reset date picker -->
                                <button x-show="selectedYear !== 'all' || selectedMonth !== 'all' || selectedDay !== 'all'"
                                    @click="selectedYear = 'all'; selectedMonth = 'all'; selectedDay = 'all'; applyFilters()"
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0"
                                    title="Réinitialiser les dates">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Mode Période personnalisée -->
                            <div x-show="dateMode === 'range'" class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <span class="absolute left-2.5 top-2 text-xs text-gray-400 leading-none">Du</span>
                                    <input type="date" x-model="dateFrom" @change="applyFilters()"
                                        class="w-full pt-5 pb-1.5 px-3 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                                </div>

                                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>

                                <div class="relative flex-1">
                                    <span class="absolute left-2.5 top-2 text-xs text-gray-400 leading-none">Au</span>
                                    <input type="date" x-model="dateTo" @change="applyFilters()"
                                        :min="dateFrom"
                                        class="w-full pt-5 pb-1.5 px-3 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-500 text-gray-900 dark:text-gray-100">
                                </div>

                                <!-- Raccourcis rapides -->
                                <div class="flex gap-1 shrink-0">
                                    <button @click="setQuickRange(7)"
                                        :class="isQuickRange(7) ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 border border-gray-200 dark:border-gray-600'"
                                        class="px-2 py-1.5 text-xs rounded-lg transition-colors font-medium">
                                        7j
                                    </button>
                                    <button @click="setQuickRange(30)"
                                        :class="isQuickRange(30) ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 border border-gray-200 dark:border-gray-600'"
                                        class="px-2 py-1.5 text-xs rounded-lg transition-colors font-medium">
                                        30j
                                    </button>
                                    <button @click="setQuickRange(90)"
                                        :class="isQuickRange(90) ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 border border-gray-200 dark:border-gray-600'"
                                        class="px-2 py-1.5 text-xs rounded-lg transition-colors font-medium">
                                        90j
                                    </button>
                                </div>

                                <!-- Reset période -->
                                <button x-show="dateFrom || dateTo"
                                    @click="dateFrom = ''; dateTo = ''; applyFilters()"
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0"
                                    title="Effacer la période">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tri -->
                    <div class="flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 self-center">Trier par:</span>
                        <button @click="changeSortBy('total_time')"
                            :class="sortBy === 'total_time' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Temps total
                            <svg x-show="sortBy === 'total_time'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('total_sessions')"
                            :class="sortBy === 'total_sessions' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Sessions
                            <svg x-show="sortBy === 'total_sessions'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('total_consumption')"
                            :class="sortBy === 'total_consumption' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Consommation
                            <svg x-show="sortBy === 'total_consumption'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('total_download')"
                            :class="sortBy === 'total_download' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Download
                            <svg x-show="sortBy === 'total_download'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('total_upload')"
                            :class="sortBy === 'total_upload' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Upload
                            <svg x-show="sortBy === 'total_upload'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('last_login_at')"
                            :class="sortBy === 'last_login_at' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Dernière connexion
                            <svg x-show="sortBy === 'last_login_at'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="resetFilters()"
                            class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenu Principal -->
            <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Loading -->
                <div x-show="loading" class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-purple-600 border-t-transparent"></div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Chargement des statistiques...</p>
                    </div>
                </div>

                <!-- Table -->
                <div x-show="!loading" class="flex-1 overflow-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Utilisateur
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Groupe
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Statut
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Sessions
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Temps total
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Consommation
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Dernière connexion
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(stat, idx) in paginatedStats" :key="`stat-${stat.id}-${idx}`">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <!-- Utilisateur -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <div class="w-10 h-10 rounded-full bg-linear-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                                    <span class="text-white font-bold text-sm" x-text="getInitials(stat.username)"></span>
                                                </div>
                                                <div x-show="stat.is_online" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="stat.username"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="stat.fullname || '-'"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Groupe -->
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" x-text="stat.group_name || 'Sans groupe'"></span>
                                    </td>

                                    <!-- Statut -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(stat.account_status)" x-text="stat.account_status === 'active' ? 'Actif' : stat.account_status === 'suspended' ? 'Suspendu' : 'Expiré'"></span>
                                    </td>

                                    <!-- Sessions -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="stat.total_sessions"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-show="stat.active_sessions > 0">
                                            <span class="text-green-600 dark:text-green-400" x-text="stat.active_sessions"></span> active(s)
                                        </div>
                                    </td>

                                    <!-- Temps total -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium" :class="getTimeColor(stat.total_time)" x-text="formatDuration(stat.total_time)"></div>
                                    </td>

                                    <!-- Consommation avec Tooltip -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="relative inline-block group">
                                            <div class="text-sm font-medium cursor-help" :class="getConsumptionColor(stat.total_consumption)">
                                                <span x-text="formatBytes(stat.total_consumption)"></span>
                                            </div>

                                            <!-- Tooltip -->
                                            <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block z-10 w-48">
                                                <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg p-3 border border-gray-700">
                                                    <div class="space-y-1.5">
                                                        <div class="flex justify-between items-center pb-1.5 border-b border-gray-600">
                                                            <span class="text-gray-300">Détails:</span>
                                                        </div>
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-gray-400 flex items-center gap-1">
                                                                <svg class="w-3 h-3 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                                </svg>
                                                                Download:
                                                            </span>
                                                            <span class="font-semibold text-cyan-400" x-text="formatBytes(stat.total_upload)"></span>
                                                        </div>
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-gray-400 flex items-center gap-1">
                                                                <svg class="w-3 h-3 text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                                </svg>
                                                                Upload:
                                                            </span>
                                                            <span class="font-semibold text-teal-400" x-text="formatBytes(stat.total_download)"></span>
                                                        </div>
                                                        <div class="flex justify-between items-center pt-1.5 border-t border-gray-600">
                                                            <span class="text-gray-300 flex items-center gap-1">
                                                                <svg class="w-3 h-3 text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" />
                                                                </svg>
                                                                Total:
                                                            </span>
                                                            <span class="font-bold text-pink-400" x-text="formatBytes(stat.total_consumption)"></span>
                                                        </div>
                                                    </div>
                                                    <div class="absolute bottom-0 right-4 transform translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900 dark:bg-gray-700 border-r border-b border-gray-700"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Dernière connexion -->
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900 dark:text-white" x-text="formatRelativeDate(stat.last_login_at)"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(stat.last_login_at)"></div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 text-center">
                                        <button @click="viewDetails(stat)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                            </svg>
                                            Détails
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div x-show="filteredStats.length === 0 && !loading"
                        class="flex-1 flex flex-col items-center justify-center py-16 px-4">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-gray-100">Aucune statistique trouvée</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de filtrage.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div x-show="totalPages > 1 && !loading"
                    class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <span class="font-semibold" x-text="startIndex + 1"></span>-<span class="font-semibold" x-text="endIndex"></span>
                            sur <span class="font-semibold" x-text="filteredStats.length"></span> utilisateur(s)
                        </div>

                        <div class="flex items-center gap-1">
                            <!-- Précédent -->
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors font-medium">
                                ←
                            </button>

                            <!-- Pages -->
                            <template x-for="page in visiblePages" :key="page">
                                <button @click="goToPage(page)"
                                    :class="page === currentPage ? 
                                        'bg-purple-600 text-white hover:bg-purple-700' : 
                                        'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-3 py-1.5 text-xs rounded transition-colors font-medium min-w-[32px]"
                                    x-text="page"></button>
                            </template>

                            <!-- Suivant -->
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors font-medium">
                                →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 rounded-b-xl">
                <div class="flex justify-between items-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="filteredStats.length !== stats.length">
                            Affichage de <span class="font-semibold text-purple-600 dark:text-purple-400" x-text="filteredStats.length"></span>
                            sur <span class="font-semibold" x-text="stats.length"></span> utilisateur(s)
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="loadStats()" :disabled="loading"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors font-medium disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                    clip-rule="evenodd" />
                            </svg>
                            Actualiser
                        </button>
                        <button @click="closeModal()"
                            class="px-4 py-1.5 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors font-medium">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>