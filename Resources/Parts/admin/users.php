<div class="relative z-50">
    <!-- Modal Container -->
    <div x-data="usersModal2()" x-show="isOpen" x-cloak @keydown.escape.window="closeModal()"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal()">
        </div>

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

        <!-- Modal Liste Utilisateurs -->
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

                <div class="border-b border-gray-200 dark:border-gray-700 bg-linear-to-r from-purple-50 to-indigo-50 dark:from-gray-900/50 dark:to-gray-800/50">
                    <button @click="showGlobalUsers = !showGlobalUsers"
                        class="w-full px-6 py-3 flex items-center justify-between hover:bg-purple-100/50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Statistiques Globales</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                (<span x-text="globalusers.total_users"></span> utilisateurs,
                                <span class="text-green-600 dark:text-green-400" x-text="globalusers.online_users"></span> en ligne)
                            </span>
                        </div>
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                            :class="{'rotate-180': showGlobalUsers}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="showGlobalUsers" x-collapse class="px-6 pb-4">
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
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="globalusers.total_users"></p>
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
                                        <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="globalusers.online_users"></p>
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
                                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="globalusers.total_sessions"></p>
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
                                        <p class="text-lg font-bold text-orange-600 dark:text-orange-400" x-text="formatDuration(globalusers.total_time)"></p>
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
                                        <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400" x-text="formatBytes(globalusers.total_upload)"></p>
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
                                        <p class="text-lg font-bold text-teal-600 dark:text-teal-400" x-text="formatBytes(globalusers.total_upload)"></p>
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
                                        <p class="text-lg font-bold text-pink-600 dark:text-pink-400" x-text="formatBytes(globalusers.total_consumption)"></p>
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
                                        <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="formatDuration(Math.round(globalusers.avg_time_per_user))"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres et Tri -->
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
                        </div>

                        <!-- Tri -->
                        <div class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400 self-center">Trier par:</span>
                            <button @click="changeSortBy('user_id')"
                                :class="sortBy === 'user_id' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                                class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                                ID
                                <svg x-show="sortBy === 'user_id'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                    <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                                </svg>
                            </button> 
                            <button @click="changeSortBy('username')"
                                :class="sortBy === 'username' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                                class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                                Nom d'utilisateur
                                <svg x-show="sortBy === 'username'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
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
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Chargement des utilisateurs...</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div x-show="!loading" class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Groupe
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Expiration
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Dernière connexion
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(user, idx) in paginatedUsers" :key="idx">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <!-- Utilisateur -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="relative">
                                                    <div class="w-10 h-10 rounded-full bg-linear-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                                        <span class="text-white font-bold text-sm" x-text="user.id"></span>
                                                    </div>
                                                    <div x-show="user.is_online" class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="user.username"></div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="user.fullname || '-'"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Groupe (Dropdown) -->
                                        <td class="px-4 py-3">
                                            <select @change="changeUserGroup(user, $event.target.value, $event)"
                                                :value="user.group_id"
                                                class="rounded text-xs font-medium transition-colors <?= $user['type'] === 'root' ? "cursor-pointer" : "cursor-not-allowed" ?>"
                                                :class="{
                                                            'bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-300': !user.group_id || user.group_id == -1,
                                                            'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300': user.group_id && user.group_id != -1,
                                                        }"
                                                <?php if ($user['type'] === 'root'): ?>
                                                title="Changer le groupe"
                                                <?php else: ?>
                                                disabled="disabled"
                                                title="Vous ne pouvez pas changer le groupe"
                                                <?php endif; ?>>
                                                <template x-for="(group, index) in groups" :key="index">
                                                    <option :value="group.id"
                                                        :selected="group.id == user.group_id"
                                                        x-text="group.name.charAt(0).toUpperCase() + group.name.slice(1)">
                                                    </option>
                                                </template>
                                            </select>
                                        </td>

                                        <!--  Status  -->
                                        <td class="px-4 py-3">
                                            <!-- Toggle Status avec select -->
                                            <select @change="toggleUserStatus(user, $event.target.value, $event)"
                                                :value="user.status"
                                                class="px-2.5 py-1.5 rounded text-xs font-medium transition-colors cursor-pointer border-0 focus:ring-2 focus:ring-offset-1"
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 focus:ring-green-500': user.status === 'active',
                                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 focus:ring-red-500': user.status === 'suspended',
                                                    'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 focus:ring-orange-500': user.status === 'expired'
                                                }" title="Changer le statut">
                                                <option value="active" :selected="user.status == 'active'">✓ Actif
                                                </option>
                                                <option value="suspended" :selected="user.status == 'suspended'">⊘
                                                    Suspendu</option>
                                                <option value="expired" :selected="user.status == 'expired'">⏱ Expiré
                                                </option>
                                            </select>
                                        </td>

                                        <!-- Date d'expiration avec durée restante -->
                                        <td class="px-4 py-3">
                                            <div class="relative" x-data="{ showPicker: false }">
                                                <button @click="showPicker = !showPicker"
                                                    x-data="{ remaining: getTimeRemaining(user.expires_at) }"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg transition-all group relative hover:scale-105"
                                                    :class="remaining.color.includes('red') ? 
                                                        'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30' : 
                                                        remaining.color.includes('orange') ? 
                                                        'bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:hover:bg-orange-900/30' :
                                                        remaining.color.includes('yellow') ? 
                                                        'bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/20 dark:hover:bg-yellow-900/30' :
                                                        'bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600'"
                                                    :title="'Cliquer pour modifier - ' + formatDate(user.expires_at)">

                                                    <!-- Icône indicatrice -->
                                                    <span x-show="remaining.icon" class="text-sm" x-text="remaining.icon"></span>

                                                    <!-- Texte de durée -->
                                                    <span :class="remaining.color" class="font-medium" x-text="remaining.text"></span>

                                                    <!-- Icône de modification au survol -->
                                                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="remaining.color" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Date Picker -->
                                                <div x-show="showPicker" @click.away="showPicker = false"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute z-100 mt-1 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl"
                                                    style="display: none;"
                                                    x-data="{ tempDate: formatDateForInput(user.expires_at) }">
                                                    <div class="space-y-2">
                                                        <label
                                                            class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            Nouvelle date d'expiration
                                                        </label>
                                                        <input type="date" x-model="tempDate" :min="getTodayDate()"
                                                            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                                        <button
                                                            @click="updateExpiryDate(user, tempDate, () => showPicker = false)"
                                                            class="w-full px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                                                            Mettre à jour
                                                        </button>
                                                        <button @click="showPicker = false"
                                                            class="w-full px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                                            Annuler
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Dernière connexion -->
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900 dark:text-white" x-text="formatRelativeDate(user.last_login_at)"></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(user.last_login_at)"></div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-1">
                                                <!-- View -->
                                                <button @click="viewUser(user)"
                                                    class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 rounded transition-colors"
                                                    title="Voir les détails">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                        <path fill-rule="evenodd"
                                                            d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>

                                                <!-- Edit -->
                                                <button @click="editUser(user)"
                                                    class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 rounded transition-colors"
                                                    title="Modifier">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Delete -->
                                                <button @click="$store.confirmRemoveModal.open(user, 'user')"
                                                    class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded transition-colors"
                                                    title="Supprimer">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="$store.userPolicyModal.open(user.id, user.username, 'user')"
                                                    class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                                    title="Appliquer des politiques">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="w-5 h-5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4 6h16M4 12h16M4 18h16M8 6v4M16 12v4M10 18v-4" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div x-show="filteredUsers.length === 0 && !loading"
                            class="flex-1 flex flex-col items-center justify-center py-16 px-4">
                            <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-gray-100">Aucun utilisateur
                                trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de
                                recherche.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div x-show="totalPages > 1 && !loading"
                        class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <span class="font-semibold" x-text="startIndex + 1"></span>-<span class="font-semibold" x-text="endIndex"></span>
                                sur <span class="font-semibold" x-text="globalusers.total_users"></span> utilisateur(s)
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
                                        class="px-3 py-1.5 text-xs rounded transition-colors font-medium min-w-8"
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
                            <span x-show="filteredUsers.length !== users.length">
                                Affichage de <span class="font-semibold text-purple-600 dark:text-purple-400" x-text="filteredUsers.length"></span>
                                sur <span class="font-semibold" x-text="globalusers.total_users"></span> utilisateur(s)
                            </span>
                        </div>
                        <div class="flex gap-3">

                            <!-- Bouton Nouvel utilisateur -->
                            <button @click="openAddUserModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-medium transition-colors whitespace-nowrap">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                </svg>
                                Nouvel utilisateur
                            </button>
                            <button @click="loadUsers()" :disabled="loading"
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
        <!-- Modal Détails Utilisateur -->
        <div x-show="detailsModalOpen" x-cloak @keydown.escape.window="detailsModalOpen = false"
            class="fixed inset-0 z-[60] overflow-y-auto" style="display: none;">

            <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm" @click="detailsModalOpen = false"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div x-show="detailsModalOpen" x-transition
                    class="relative w-full max-w-3xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700"
                    @click.stop>

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">Détails de l'utilisateur</h3>
                            <button @click="detailsModalOpen = false" class="text-white/80 hover:text-white p-1">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 max-h-[70vh] overflow-y-auto" x-show="selectedUser">
                        <!-- En-tête utilisateur -->
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="relative">
                                <div class="w-16 h-16 rounded-full bg-linear-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                    <span class="text-white font-bold text-xl" x-text="getInitials(selectedUser?.username)"></span>
                                </div>
                                <div x-show="selectedUser?.is_online" class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-3 border-white dark:border-gray-800 rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white" x-text="selectedUser?.username"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedUser?.fullname || 'Nom non renseigné'"></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="getStatusColor(selectedUser?.account_status)" x-text="selectedUser?.account_status === 'active' ? '✓ Actif' : selectedUser?.account_status === 'suspended' ? '⊘ Suspendu' : '⏱ Expiré'"></span>
                                    <span x-show="selectedUser?.is_online" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        🟢 En ligne
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Informations générales -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Groupe</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedUser?.group_name || 'Sans groupe'"></div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">ID Utilisateur</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedUser?.id"></div>
                            </div>
                        </div>

                        <!-- Statistiques de session -->
                        <div class="mb-6">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                </svg>
                                Statistiques de session
                            </h5>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-800">
                                    <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">Total sessions</div>
                                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="selectedUser?.total_sessions || 0"></div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800">
                                    <div class="text-xs text-green-700 dark:text-green-300 mb-1">Sessions actives</div>
                                    <div class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="selectedUser?.active_sessions || 0"></div>
                                </div>
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border border-purple-200 dark:border-purple-800">
                                    <div class="text-xs text-purple-700 dark:text-purple-300 mb-1">Temps total</div>
                                    <div class="text-lg font-bold text-purple-900 dark:text-purple-100" x-text="formatDuration(selectedUser?.total_time || 0)"></div>
                                </div>
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 border border-indigo-200 dark:border-indigo-800">
                                    <div class="text-xs text-indigo-700 dark:text-indigo-300 mb-1">Moyenne par session</div>
                                    <div class="text-lg font-bold text-indigo-900 dark:text-indigo-100" x-text="formatDuration(Math.round(selectedUser?.avg_session_time || 0))"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques de consommation -->
                        <div class="mb-6">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                </svg>
                                Consommation de données
                            </h5>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-lg p-3 border border-cyan-200 dark:border-cyan-800">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="text-xs text-cyan-700 dark:text-cyan-300">Download</div>
                                    </div>
                                    <div class="text-lg font-bold text-cyan-900 dark:text-cyan-100" x-text="formatBytes(selectedUser?.total_upload || 0)"></div>
                                </div>
                                <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg p-3 border border-teal-200 dark:border-teal-800">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="text-xs text-teal-700 dark:text-teal-300">Upload</div>
                                    </div>
                                    <div class="text-lg font-bold text-teal-900 dark:text-teal-100" x-text="formatBytes(selectedUser?.total_download || 0)"></div>
                                </div>
                                <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-3 border border-pink-200 dark:border-pink-800">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-3.5 h-3.5 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="text-xs text-pink-700 dark:text-pink-300">Total</div>
                                    </div>
                                    <div class="text-lg font-bold text-pink-900 dark:text-pink-100" x-text="formatBytes(selectedUser?.total_consumption || 0)"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations de connexion -->
                        <div class="mb-6">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
                                </svg>
                                Connexion actuelle
                            </h5>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Dernière connexion</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatRelativeDate(selectedUser?.last_login_at)"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Adresse IP actuelle</span>
                                    <span class="text-sm font-mono font-medium text-gray-900 dark:text-white" x-text="selectedUser?.current_ip || '-'"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Adresse MAC</span>
                                    <span class="text-sm font-mono font-medium text-gray-900 dark:text-white" x-text="selectedUser?.current_mac || '-'"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Appareils uniques</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedUser?.unique_devices || 0"></span>
                                </div>
                                <div x-show="selectedUser?.is_online && selectedUser?.current_session_duration" class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Durée session actuelle</span>
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400" x-text="formatDuration(selectedUser?.current_session_duration || 0)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-xl border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-end">
                            <button @click="detailsModalOpen = false"
                                class="px-4 py-2 text-sm bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors font-medium">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Modification Utilisateur -->
        <div x-show="editModalOpen" x-cloak @keydown.escape.window="editModalOpen = false"
            class="fixed inset-0 z-60 overflow-y-auto" style="display: none;">
            <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm" @click="editModalOpen = false"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div x-show="editModalOpen" x-transition
                    class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700"
                    @click.stop>

                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Modifier l'utilisateur</h3>
                        <button @click="editModalOpen = false"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveUserEdit()" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom
                                complet</label>
                            <input type="text" x-model="editForm.fullname"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', ['input' => 'fullname']) ?>

                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom
                                d'utilisateur</label>
                            <input type="text" x-model="editForm.username"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', ['input' => 'username']) ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de
                                passe</label>
                            <input type="password" x-model="editForm.password"
                                placeholder="Laisser vide pour ne pas modifier"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Laisser vide si vous ne souhaitez
                                pas changer le mot de passe</p>
                            <?php $view->inc('components', 'admin/input.error.php', ['input' => 'password']) ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date
                                d'expiration</label>
                            <input type="date" x-model="editForm.expires_at" :min="getTodayDate()"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', ['input' => 'expires_at']) ?>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                            <select x-model="editForm.status"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                <option value="active">Actif</option>
                                <option value="suspended">Suspendu</option>
                                <option value="expired">Expiré</option>
                            </select>
                            <?php $view->inc('components', 'admin/input.error.php', ['input' => 'status']) ?>
                        </div>
                    </form>

                    <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                        <button @click="editModalOpen = false" type="button"
                            class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors font-medium">
                            Annuler
                        </button>
                        <button @click="saveUserEdit()" type="button" :disabled="editLoading"
                            class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50">
                            <span x-show="!editLoading">Enregistrer</span>
                            <span x-show="editLoading">Enregistrement...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Modal Ajout des utilisateurs -->
    <div x-data x-show="$store.adduserModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Enhanced Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.adduserModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.adduserModal.isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Créer un utilisateur',
                    'subtitle' => 'Ajouter un nouveau utilisateur du portail captive',
                    'close' => '$store.adduserModal.close()',
                ]); ?>

                <!-- Form with Enhanced Styling -->
                <form @submit.prevent="$store.adduserModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message with Enhanced Styling -->
                    <div x-show="$store.adduserModal.errors.general ?? $store.adduserModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.adduserModal.errors.message"></span>
                    </div>

                    <!-- Première ligne : Nom complet et Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nom complet -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Nom complet
                            </label>
                            <div class="relative">
                                <input type="text" x-model="$store.adduserModal.formData.fullname"
                                    :class="$store.adduserModal.errors.fullname ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le nom complet">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.adduserModal.formData.fullname" class="w-4 h-4 text-green-500"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.adduserModal.errors.fullname"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.adduserModal.errors.fullname"></span>
                            </p>
                        </div>
                        <!-- Nom d'utilsateur -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Nom d'utilisateur
                            </label>
                            <div class="relative">
                                <input type="text" x-model="$store.adduserModal.formData.username"
                                    :class="$store.adduserModal.errors.username ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le nom complet">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.adduserModal.formData.username" class="w-4 h-4 text-green-500"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.adduserModal.errors.username"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.adduserModal.errors.username"></span>
                            </p>
                        </div>

                    </div>

                    <!-- Deuxième ligne : Mot de passe et groupe -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mot de passe -->
                        <div class="space-y-2" x-data="{ showPassword: false }">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Mot de passe <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                    x-model="$store.adduserModal.formData.password"
                                    :class="$store.adduserModal.errors.password ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Minimum 6 caractères">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd"
                                            d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                                            clip-rule="evenodd" />
                                        <path
                                            d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="$store.adduserModal.errors.password"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.adduserModal.errors.password"></span>
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fa fa-users text-gray-700 dark:text-gray-300"></i>&nbsp;
                                Groupe du compte
                            </label><select x-model="$store.adduserModal.formData.group" :class="$store.adduserModal.errors.group
                                ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20'
                                : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <template x-for="(group, index) in $store.adduserModal.groups" :key="index">
                                    <option :value="group.id"
                                        x-text="group.name.charAt(0).toUpperCase() + group.name.slice(1)"></option>
                                </template>
                            </select>

                            <p x-show="$store.adduserModal.errors.group"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.adduserModal.errors.group"></span>
                            </p>
                        </div>
                    </div>
                    <!-- Troisème ligne : Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Status du compte
                            </label>
                            <select x-model="$store.adduserModal.formData.status"
                                :class="$store.adduserModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active" selected>Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <p x-show="$store.adduserModal.errors.status"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.adduserModal.errors.status"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button" @click="$store.adduserModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit" :disabled="$store.adduserModal.loading"
                            class="flex-1 px-6 py-3 bg-linear-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.adduserModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="$store.adduserModal.loading ? 'Création...' : 'Créer'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-data x-show="$store.userPolicyModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed z-50 inset-0 overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.userPolicyModal.close()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.userPolicyModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- En-tête -->
                <div class="bg-linear-to-r from-blue-600 to-purple-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-4">
                            <div>
                                <h3 class="text-2xl font-bold">Politiques - <span class="text-orange-300" x-text="$store.userPolicyModal.username"></span></h3>
                            </div>
                        </div>
                        <button @click="$store.userPolicyModal.close()"
                            class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="inline-flex items-center text-orange-600 dark:text-orange-400">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Une politique 'Spéciale' à la priorité maximale
                    </span>
                </div>
                <!-- Indicateur de chargement -->
                <div x-show="$store.userPolicyModal.isLoading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">Chargement des politiques...</p>
                </div>

                <!-- Liste des politiques -->
                <div x-show="!$store.userPolicyModal.isLoading" class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <template x-for="(policy, index) in $store.userPolicyModal.policies" :key="policy.id">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between">
                                <!-- Nom et checkbox -->
                                <div class="flex items-center space-x-4 flex-1">
                                    <input
                                        type="checkbox"
                                        :checked="policy.applied"
                                        @change="$store.userPolicyModal.togglePolicy(policy.id, $event.target.checked)"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <div class="flex-1">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="policy.name"></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 flex-1"
                                    x-show="$store.userPolicyModal.type == 'user'">
                                    <label :for="'special' + policy.id">Politique spéciale</label>
                                    <input
                                        type="checkbox"
                                        :checked="policy?.is_special"
                                        @change="$store.userPolicyModal.toggleSpecialPolicy(policy.id, $event.target.checked)"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"></p>
                                    </div>
                                </div>

                                <!-- Bouton d'affichage des détails -->
                                <button
                                    @click="$store.userPolicyModal.toggleDetails(policy.id)"
                                    class="ml-4 px-3 py-1.5 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors flex items-center space-x-1">
                                    <svg class="w-4 h-4" :class="{'rotate-180': $store.userPolicyModal.expandedPolicies.includes(policy.id)}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <span x-text="$store.userPolicyModal.expandedPolicies.includes(policy.id) ? 'Masquer' : 'Détails'"></span>
                                </button>
                            </div>

                            <!-- Détails de la politique  -->
                            <div x-show="$store.userPolicyModal.expandedPolicies.includes(policy.id)"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                x-transition:leave-end="opacity-0 transform -translate-y-2"
                                class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 space-y-3">

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ID</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="policy.id"></p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="policy.description || 'Aucune description'"></p>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': policy.status === 'active',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': policy.status === 'inactive',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': policy.status !== 'active' && policy.status !== 'inactive'
                                        }"
                                        x-text="policy.status"></span>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Expire le</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="policy.expires_at || 'Sans expiration'"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Message si aucune politique -->
                    <div x-show="!$store.userPolicyModal.policies || $store.userPolicyModal.policies.length === 0"
                        class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">Aucune politique disponible</p>
                    </div>
                </div>

                <!-- Pied de page avec boutons d'action -->
                <div x-show="!$store.userPolicyModal.isLoading" class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-between items-center">
                        <!-- Indicateur de modifications -->
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span x-show="$store.userPolicyModal.hasChanges()" class="inline-flex items-center text-orange-600 dark:text-orange-400">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                Modifications non sauvegardées
                            </span>
                        </div>

                        <!-- Boutons -->
                        <div class="flex space-x-3">
                            <button @click="$store.userPolicyModal.close()"
                                class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                                Annuler
                            </button>
                            <button
                                @click="$store.userPolicyModal.submitForm()"
                                :disabled="!$store.userPolicyModal.hasChanges() || $store.userPolicyModal.isLoading"
                                :class="{
                                    'opacity-50 cursor-not-allowed': !$store.userPolicyModal.hasChanges() || $store.userPolicyModal.isLoading,
                                    'hover:bg-blue-700': $store.userPolicyModal.hasChanges() && !$store.userPolicyModal.isLoading
                                }"
                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg transition-colors flex items-center space-x-2">
                                <svg x-show="$store.userPolicyModal.isLoading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Enregistrer les modifications</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>