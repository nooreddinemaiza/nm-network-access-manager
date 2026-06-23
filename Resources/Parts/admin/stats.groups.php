<div x-data="groupStatsModal()" x-show="isOpen" x-cloak @keydown.escape.window="closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
    <!-- Modal Liste Groupe -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="isOpen" x-transition
            class="relative w-full max-w-7xl bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-h-[95vh] flex flex-col"
            @click.stop>

            <!-- Header -->
            <?= $view->inc('components', 'admin/modal.header.php', [
                'title' => '<svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                    <h2 class="text-lg font-bold">Statistiques des Groupes</h2>',
                'subtitle' => '',
                'close' => 'closeModal()',
            ]); ?>

            <!-- Statistiques Globales -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-900/50 dark:to-gray-800/50">
                <button @click="showGlobalStats = !showGlobalStats"
                    class="w-full px-6 py-3 flex items-center justify-between hover:bg-indigo-100/50 dark:hover:bg-gray-800/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Statistiques Globales</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            (<span x-text="globalStats.total_groups"></span> groupes,
                            <span x-text="globalStats.total_users"></span> users,
                            <span class="text-green-600 dark:text-green-400" x-text="globalStats.is_online"></span> en ligne)
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                        :class="{'rotate-180': showGlobalStats}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="showGlobalStats" x-collapse class="px-6 pb-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <!-- Stats cards - voir fichier séparé pour le code complet -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Groupes</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="globalStats.total_groups"></p>
                                </div>
                            </div>
                        </div>
                        <!-- Ajouter les autres stats de la même manière -->
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
                    </div>
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                        :class="{'rotate-180': showFilters}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="showFilters" x-collapse class="px-6 pb-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                        <div class="relative">
                            <input type="text" x-model="searchTerm" @input="applyFilters()"
                                placeholder="Rechercher un groupe..."
                                class="w-full px-3 py-2 pl-9 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">
                            <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <input type="number" x-model="minUsers" @input="applyFilters()"
                            placeholder="Min utilisateurs"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">

                        <input type="number" x-model="maxUsers" @input="applyFilters()"
                            placeholder="Max utilisateurs"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">

                        <select x-model="onlineFilter" @change="applyFilters()"
                            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 dark:text-gray-100">
                            <option value="all">Tous</option>
                            <option value="has_online">Avec users en ligne</option>
                            <option value="no_online">Sans users en ligne</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 self-center">Trier par:</span>
                        <button @click="changeSortBy('total_users')"
                            :class="sortBy === 'total_users' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium">
                            Utilisateurs
                        </button>
                        <button @click="changeSortBy('total_time')"
                            :class="sortBy === 'total_time' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium">
                            Temps total
                        </button>
                        <button @click="changeSortBy('total_consumption')"
                            :class="sortBy === 'total_consumption' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium">
                            Consommation
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
                <div x-show="loading" class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-indigo-600 border-t-transparent"></div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Chargement...</p>
                    </div>
                </div>

                <div x-show="!loading" class="flex-1 overflow-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Groupe</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Utilisateurs</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sessions</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Temps total</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Consommation</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="(stat, idx) in paginatedStats" :key="`group-${stat.group_id}-${idx}`">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center"
                                                :class="getGroupColor(idx)">
                                                <span class="text-white font-bold text-sm" x-text="getInitials(stat.group_name)"></span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="stat.group_name"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    ID: <span x-text="stat.group_id"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="stat.total_users"></span>
                                            <div class="flex gap-2 text-xs">
                                                <span class="text-green-600 dark:text-green-400" x-text="stat.active_users + ' actifs'"></span>
                                                <span x-show="stat.is_online > 0" class="text-emerald-600 dark:text-emerald-400" x-text="stat.is_online + ' en ligne'"></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="stat.total_sessions"></span>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-medium" :class="getTimeColor(stat.total_time)" x-text="formatDuration(stat.total_time)"></div>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="relative inline-block group">
                                            <div class="text-sm font-medium cursor-help" :class="getConsumptionColor(stat.total_consumption)" x-text="formatBytes(stat.total_consumption)"></div>

                                            <!-- Tooltip -->
                                            <div class="absolute bottom-full right-0 mb-2 hidden group-hover:block z-10 w-48">
                                                <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg p-3 border border-gray-700">
                                                    <div class="space-y-1.5">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-400">Download:</span>
                                                            <span class="font-semibold text-cyan-400" x-text="formatBytes(stat.total_upload)"></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-400">Upload:</span>
                                                            <span class="font-semibold text-teal-400" x-text="formatBytes(stat.total_download)"></span>
                                                        </div>
                                                        <div class="flex justify-between pt-1.5 border-t border-gray-600">
                                                            <span class="text-gray-300">Total:</span>
                                                            <span class="font-bold text-pink-400" x-text="formatBytes(stat.total_consumption)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <!-- View -->
                                        <button @click="viewDetails(stat)"
                                            class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 rounded transition-colors"
                                            title="Voir les détails">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd"
                                                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <!-- Delete -->
                                        <button @click="$store.deleteStats.open(stat,'groups')"
                                            class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded transition-colors"
                                            title="Supprimer">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="filteredStats.length === 0 && !loading" class="flex-1 flex flex-col items-center justify-center py-16 px-4">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-gray-100">Aucun groupe trouvé</h3>
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
                            Affichage de <span class="font-semibold text-indigo-600 dark:text-indigo-400" x-text="filteredStats.length"></span>
                            sur <span class="font-semibold" x-text="stats.length"></span> groupe(s)
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
                            class="px-4 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded transition-colors font-medium">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails Groupe -->
    <div x-show="detailsModalOpen" x-cloak @keydown.escape.window="detailsModalOpen = false"
        class="fixed inset-0 z-[60] overflow-y-auto" style="display: none;">

        <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm" @click="detailsModalOpen = false"></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="detailsModalOpen" x-transition
                class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700"
                @click.stop>

                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Détails du Groupe</h3>
                        <button @click="detailsModalOpen = false" class="text-white/80 hover:text-white p-1">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 max-h-[70vh] overflow-y-auto" x-show="selectedGroup">
                    <!-- En-tête du groupe -->
                    <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white" x-text="selectedGroup?.group_name"></h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ID: <span class="font-mono" x-text="selectedGroup?.group_id"></span>
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                    </svg>
                                    <span x-text="selectedGroup?.total_users"></span> utilisateurs
                                </span>
                                <span x-show="selectedGroup?.is_online > 0" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    🟢 <span x-text="selectedGroup?.is_online + ' en ligne'"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques utilisateurs -->
                    <div class="mb-6">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                            Statistiques des utilisateurs
                        </h5>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-800">
                                <div class="text-xs text-blue-700 dark:text-blue-300 mb-1">Total</div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="selectedGroup?.total_users || 0"></div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800">
                                <div class="text-xs text-green-700 dark:text-green-300 mb-1">Actifs</div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="selectedGroup?.active_users || 0"></div>
                                <div class="text-xs text-green-600 dark:text-green-400 mt-1" x-show="selectedGroup?.total_users > 0">
                                    <span x-text="Math.round((selectedGroup?.active_users / selectedGroup?.total_users) * 100)"></span>%
                                </div>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3 border border-emerald-200 dark:border-emerald-800">
                                <div class="text-xs text-emerald-700 dark:text-emerald-300 mb-1">En ligne</div>
                                <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100" x-text="selectedGroup?.is_online || 0"></div>
                                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1" x-show="selectedGroup?.total_users > 0">
                                    <span x-text="Math.round((selectedGroup?.is_online / selectedGroup?.total_users) * 100)"></span>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques de sessions -->
                    <div class="mb-6">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
                            </svg>
                            Activité et sessions
                        </h5>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-purple-700 dark:text-purple-300 mb-1">Total sessions</div>
                                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100" x-text="selectedGroup?.total_sessions || 0"></div>
                                    </div>
                                    <svg class="w-8 h-8 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-xs text-purple-600 dark:text-purple-400 mt-2" x-show="selectedGroup?.total_users > 0">
                                    Moy: <span x-text="Math.round((selectedGroup?.total_sessions / selectedGroup?.total_users) * 10) / 10"></span> sessions/user
                                </div>
                            </div>

                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-orange-700 dark:text-orange-300 mb-1">Temps total</div>
                                        <div class="text-lg font-bold text-orange-900 dark:text-orange-100" x-text="formatDuration(selectedGroup?.total_time || 0)"></div>
                                    </div>
                                    <svg class="w-8 h-8 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-xs text-orange-600 dark:text-orange-400 mt-2" x-show="selectedGroup?.total_users > 0">
                                    Moy: <span x-text="formatDuration(Math.round(selectedGroup?.avg_time_per_user || 0))"></span>/user
                                </div>
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
                            <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-lg p-4 border border-cyan-200 dark:border-cyan-800">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-xs text-cyan-700 dark:text-cyan-300">Download</div>
                                </div>
                                <div class="text-lg font-bold text-cyan-900 dark:text-cyan-100" x-text="formatBytes(selectedGroup?.total_upload || 0)"></div>
                                <div class="text-xs text-cyan-600 dark:text-cyan-400 mt-1" x-show="selectedGroup?.total_users > 0">
                                    Moy: <span x-text="formatBytes(Math.round(selectedGroup?.total_upload / selectedGroup?.total_users))"></span>/user
                                </div>
                            </div>

                            <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg p-4 border border-teal-200 dark:border-teal-800">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-xs text-teal-700 dark:text-teal-300">Upload</div>
                                </div>
                                <div class="text-lg font-bold text-teal-900 dark:text-teal-100" x-text="formatBytes(selectedGroup?.total_download || 0)"></div>
                                <div class="text-xs text-teal-600 dark:text-teal-400 mt-1" x-show="selectedGroup?.total_users > 0">
                                    Moy: <span x-text="formatBytes(Math.round(selectedGroup?.total_download / selectedGroup?.total_users))"></span>/user
                                </div>
                            </div>

                            <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-4 border border-pink-200 dark:border-pink-800">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-xs text-pink-700 dark:text-pink-300">Total</div>
                                </div>
                                <div class="text-lg font-bold text-pink-900 dark:text-pink-100" x-text="formatBytes(selectedGroup?.total_consumption || 0)"></div>
                                <div class="text-xs text-pink-600 dark:text-pink-400 mt-1" x-show="selectedGroup?.total_users > 0">
                                    Moy: <span x-text="formatBytes(Math.round(selectedGroup?.total_consumption / selectedGroup?.total_users))"></span>/user
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ratios et moyennes -->
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Métriques par utilisateur</h5>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Sessions/User</div>
                                <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedGroup?.total_users > 0 ? Math.round((selectedGroup?.total_sessions / selectedGroup?.total_users) * 10) / 10 : 0"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Temps/User</div>
                                <div class="text-sm font-bold text-purple-600 dark:text-purple-400" x-text="formatDuration(Math.round(selectedGroup?.avg_time_per_user || 0))"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Conso/User</div>
                                <div class="text-sm font-bold text-pink-600 dark:text-pink-400" x-text="formatBytes(Math.round(selectedGroup?.avg_consumption_per_user || 0))"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Taux actifs</div>
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                    <span x-text="selectedGroup?.total_users > 0 ? Math.round((selectedGroup?.active_users / selectedGroup?.total_users) * 100) : 0"></span>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 rounded-b-xl border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-end">
                        <button @click="detailsModalOpen = false"
                            class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>