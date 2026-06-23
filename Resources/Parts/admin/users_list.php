<div x-data="siteStatsModal()" x-show="isOpen" x-cloak @keydown.escape.window="closeModal()"
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
                    <h2 class="text-lg font-bold">Statistiques sites</h2>',
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
                            (<span x-text="globalStats.total_sites"></span> sites,
                            <span class="text-green-600 dark:text-green-400" x-text="globalStats.online_sites"></span> en ligne)
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                        :class="{'rotate-180': showGlobalStats}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="showGlobalStats" x-collapse class="px-6 pb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Total sites -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-linear-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Total</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="globalStats.total_sites"></p>
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
                        <span x-show="searchTerm || onlineFilter !== 'all'"
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
                    </div>

                    <!-- Tri -->
                    <div class="flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 self-center">Trier par:</span>
                        <button @click="changeSortBy('domain')"
                            :class="sortBy === 'domain' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Domain
                            <svg x-show="sortBy === 'domain'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path x-show="sortOrder === 'asc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                <path x-show="sortOrder === 'desc'" d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z" />
                            </svg>
                        </button>
                        <button @click="changeSortBy('total_visits')"
                            :class="sortBy === 'total_visits' ? 'bg-purple-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium inline-flex items-center gap-1">
                            Visites
                            <svg x-show="sortBy === 'total_visits'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
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
                                    Domaine
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    visites
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Première visite
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                    Dernière visite
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(stat, idx) in paginatedStats" :key="`stat-${stat.id}-${idx}`">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <!-- Site -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <div class="w-10 h-10 rounded-full bg-linear-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                                    <span class="text-white font-bold text-sm" x-text="getInitials(stat.domain)"></span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="stat.domain"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Visites -->
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" x-text="stat.total_visits"></span>
                                    </td>

                                    <!-- First visit -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="stat.first_visit"></span>
                                    </td>

                                    <!-- Last visit -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="stat.last_visit"></span>
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
                            sur <span class="font-semibold" x-text="filteredStats.length"></span> Site(s)
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
                            sur <span class="font-semibold" x-text="stats.length"></span> Site(s)
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="$store.deleteStats.open({username:'site'},'sites')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded transition-colors"
                            title="Supprimer">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Supprimer
                        </button>
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