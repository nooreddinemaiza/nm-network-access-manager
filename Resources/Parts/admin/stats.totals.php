<div x-data="dashboardStats()" class="p-3 sm:p-5 space-y-4 max-w-400 mx-auto">

    <!-- ─── Top Actions ─────────────────────────────────────── -->
    <div class="flex flex-wrap gap-2">
        <button
            @click="$store.adduserModal.open()" 
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 shadow-lg shadow-blue-600/20 dark:shadow-blue-500/20 hover:shadow-blue-600/30 dark:hover:shadow-blue-500/30">
            <i class="fa fa-user-plus text-xs"></i>
            <span>Nouveau Utilisateur</span>
        </button>
        <button
            @click="$store.addLinkModal.open()" 
            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 shadow-lg shadow-blue-600/20 dark:shadow-blue-500/20 hover:shadow-blue-600/30 dark:hover:shadow-blue-500/30">
            <i class="fa fa-link text-xs"></i>
            <span>Invitation</span>
        </button>
        <button @click="fetchStats()" :disabled="loading"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white cursor-pointer text-sm font-medium transition-all duration-200 disabled:opacity-50 ml-auto shadow-lg shadow-gray-200/20 dark:shadow-gray-700/20">
            <svg class="w-4 h-4" :class="{ 'spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span x-text="loading ? 'Actualisation…' : 'Actualiser'"></span>
        </button>
    </div>

    <!-- ─── Filters Bar ─────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-2">
        <button @click="showFilters = !showFilters"
            class="flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white text-sm font-medium transition-all duration-200 shadow-md"
            :class="hasActiveFilters() ? 'border-2 border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'border border-transparent'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            <span x-text="showFilters ? 'Masquer' : 'Filtres'"></span>
            <span x-show="hasActiveFilters()" class="w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
        </button>

        <!-- Raccourcis rapides -->
        <div class="flex gap-1.5">
            <button @click="setQuickRange(7)"
                :class="isQuickRange(7) ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-blue-500/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200">7j</button>
            <button @click="setQuickRange(30)"
                :class="isQuickRange(30) ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-blue-500/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200">30j</button>
            <button @click="setQuickRange(90)"
                :class="isQuickRange(90) ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-blue-500/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-2.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200">90j</button>
        </div>

        <!-- Reset -->
        <button x-show="hasActiveFilters()" @click="resetFilters()"
            class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs text-red-700 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-800 transition-all duration-200">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Réinitialiser
        </button>

        <span x-show="hasActiveFilters()" class="text-xs text-blue-600 dark:text-blue-400 italic">Données filtrées</span>
    </div>

    <!-- ─── Panneau filtres ──────────────────────────────────── -->
    <div x-show="showFilters" style="display:none"
        class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 space-y-4">

        <!-- Mode toggle -->
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Mode :</span>
            <button @click="dateMode = 'picker'; dateFrom = ''; dateTo = ''; fetchStats()"
                :class="dateMode === 'picker'
                ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-blue-500/30'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-200">
                Année / Mois
            </button>
            <button @click="dateMode = 'range'; selectedYear = 'all'; selectedMonth = 'all'; fetchStats()"
                :class="dateMode === 'range'
                ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-600/30 dark:shadow-blue-500/30'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all duration-200">
                Plage de dates
            </button>
        </div>

        <div class="flex flex-wrap gap-4 items-end">
            <!-- PICKER : Année + Mois -->
            <template x-if="dateMode === 'picker'">
                <div class="flex gap-3 flex-wrap">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium">Année</label>
                        <select x-model="selectedYear" @change="selectedMonth = 'all'; fetchStats()"
                            class="bg-gray-50 dark:bg-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 cursor-pointer transition-all duration-200">
                            <option value="all">Toutes</option>
                            <template x-for="y in availableYears" :key="y">
                                <option :value="String(y)" x-text="y"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium">Mois</label>
                        <select x-model="selectedMonth" @change="fetchStats()"
                            :disabled="selectedYear === 'all'"
                            class="bg-gray-50 dark:bg-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed transition-all duration-200">
                            <option value="all">Tous</option>
                            <template x-for="m in availableMonths" :key="m.value">
                                <option :value="String(m.value)" x-text="m.label"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </template>

            <!-- RANGE : Du … Au … -->
            <template x-if="dateMode === 'range'">
                <div class="flex gap-3 flex-wrap items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium">Du</label>
                        <input type="date" x-model="dateFrom"
                            @change="dateTo && fetchStats()"
                            class="bg-gray-50 dark:bg-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 transition-all duration-200">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-600 dark:text-gray-400 font-medium">Au</label>
                        <input type="date" x-model="dateTo" :min="dateFrom"
                            @change="dateFrom && fetchStats()"
                            class="bg-gray-50 dark:bg-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 transition-all duration-200">
                    </div>
                </div>
            </template>

            <!-- Groupe -->
            <div class="flex flex-col gap-1" x-show="groups && groups.length > 0">
                <label class="text-xs text-gray-600 dark:text-gray-400 font-medium">Groupe</label>
                <select x-model="selectedGroup" @change="fetchStats()"
                    class="bg-gray-50 dark:bg-gray-700 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 focus:outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-400/20 cursor-pointer transition-all duration-200">
                    <option value="all">Tous les groupes</option>
                    <template x-for="g in groups" :key="g.id">
                        <option :value="String(g.id)" x-text="g.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Résumé du filtre actif -->
        <div x-show="hasActiveFilters()" class="flex flex-wrap gap-2 pt-1 border-t border-gray-200 dark:border-gray-700">
            <span class="text-xs text-gray-500 dark:text-gray-400">Filtre actif :</span>
            <span x-show="selectedGroup !== 'all'" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"
                x-text="'Groupe : ' + (groups.find(g => String(g.id) === selectedGroup)?.name || selectedGroup)"></span>
            <span x-show="dateMode === 'picker' && selectedYear !== 'all'" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"
                x-text="selectedMonth !== 'all' ? selectedYear + ' / ' + selectedMonth : 'Année ' + selectedYear"></span>
            <span x-show="dateMode === 'range' && dateFrom && dateTo" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"
                x-text="dateFrom + ' → ' + dateTo"></span>
        </div>
    </div>

    <!-- ─── Stats Grid ──────────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-3">

        <!-- Utilisateurs -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 hover:shadow-blue-200/30 dark:hover:shadow-blue-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Utilisateurs</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.total_users || '0'"></div>
            <div class="flex gap-2 mt-1.5 flex-wrap">
                <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium" x-text="'✓ ' + (stats.active_users || 0)"></span>
                <span class="text-xs text-amber-600 dark:text-amber-400 font-medium" x-text="'⏸ ' + (stats.suspended_users || 0)"></span>
                <span class="text-xs text-red-600 dark:text-red-400 font-medium" x-text="'✕ ' + (stats.expired_users || 0)"></span>
            </div>
        </div>

        <!-- En ligne -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-cyan-400 dark:hover:border-cyan-500 transition-all duration-300 hover:shadow-cyan-200/30 dark:hover:shadow-cyan-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                    <div class="w-2.5 h-2.5 rounded-full bg-cyan-600 dark:bg-cyan-400 animate-pulse"></div>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">En ligne</div>
            <div class="text-2xl font-bold text-cyan-600 dark:text-cyan-400" x-text="stats.online_users || '0'"></div>
            <div class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="stats.total_unique_devices + ' appareils uniques'"></div>
        </div>

        <!-- Groupes -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-300 hover:shadow-indigo-200/30 dark:hover:shadow-indigo-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Groupes</div>
            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="stats.total_groups || '0'"></div>
        </div>

        <!-- Sessions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-sky-400 dark:hover:border-sky-500 transition-all duration-300 hover:shadow-sky-200/30 dark:hover:shadow-sky-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Sessions</div>
            <div class="text-2xl font-bold text-sky-600 dark:text-sky-400" x-text="stats.total_sessions || '0'"></div>
        </div>

        <!-- Durée totale -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 transition-all duration-300 hover:shadow-amber-200/30 dark:hover:shadow-amber-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Durée totale</div>
            <div class="text-base font-bold text-amber-600 dark:text-amber-400" x-text="formatDuration(stats.total_time_seconds)"></div>
        </div>

        <!-- Consommation -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-red-400 dark:hover:border-red-500 transition-all duration-300 hover:shadow-red-200/30 dark:hover:shadow-red-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Consommation</div>
            <div class="text-base font-bold text-red-600 dark:text-red-400" x-text="formatBytes(stats.total_consumption_bytes)"></div>
        </div>

        <!-- Download -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500 transition-all duration-300 hover:shadow-emerald-200/30 dark:hover:shadow-emerald-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Download</div>
            <div class="text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="formatBytes(stats.total_upload_bytes)"></div>
        </div>

        <!-- Upload -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-xl shadow-gray-200/50 dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-500 transition-all duration-300 hover:shadow-teal-200/30 dark:hover:shadow-teal-900/30 hover:-translate-y-0.5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-xl bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5">Upload</div>
            <div class="text-base font-bold text-teal-600 dark:text-teal-400" x-text="formatBytes(stats.total_download_bytes)"></div>
        </div>
    </div>

    <!-- ─── Top Consumer ────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30 rounded-2xl p-5 shadow-xl shadow-amber-200/20 dark:shadow-amber-900/20 border border-amber-200 dark:border-amber-800 transition-all duration-300 hover:shadow-amber-300/30 dark:hover:shadow-amber-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 min-w-max sm:min-w-0">
            <div class="flex items-center gap-3 shrink-0">
                <div class="relative">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 dark:from-amber-400 dark:to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/30 dark:shadow-amber-400/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="absolute -top-1.5 -right-1.5 text-sm leading-none">👑</div>
                </div>
                <div>
                    <div class="text-xs text-amber-600 dark:text-amber-400 font-medium">Top Consommateur</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="most_consumer.fullname || most_consumer.username || 'Sans Nom'"></div>
                </div>
            </div>

            <div class="w-px h-8 bg-amber-200 dark:bg-amber-800 hidden sm:block"></div>

            <div class="flex items-center gap-2 shrink-0">
                <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="formatBytes(most_consumer.total_consumption || 0)"></div>
                </div>
            </div>

            <div class="w-px h-8 bg-amber-200 dark:bg-amber-800 hidden sm:block"></div>

            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-mono" x-text="formatBytes(most_consumer.total_download || 0)"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-mono" x-text="formatBytes(most_consumer.total_upload || 0)"></span>
                </div>

                <div class="h-5 w-px bg-amber-200 dark:bg-amber-800 hidden sm:block"></div>

                <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="most_consumer.total_sessions"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">sessions</span>
                </div>

                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400" x-text="formatRelativeDate(most_consumer.last_activity_at)"></span>
                </div>

                <div class="px-2.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center gap-1">
                    <span class="text-gray-400 dark:text-gray-500">ID</span>
                    <span class="font-semibold" x-text="most_consumer.id"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Top Group ───────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-2xl p-5 shadow-xl shadow-blue-200/20 dark:shadow-blue-900/20 border border-blue-200 dark:border-blue-800 transition-all duration-300 hover:shadow-blue-300/30 dark:hover:shadow-blue-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 min-w-max sm:min-w-0">
            <div class="flex items-center gap-3 shrink-0">
                <div class="relative">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-400 dark:to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/30 dark:shadow-blue-400/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="absolute -top-1.5 -right-1.5 text-sm leading-none">⭐</div>
                </div>
                <div>
                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Top Groupe</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="most_group?.group_name || 'Sans Nom'"></div>
                </div>
            </div>

            <div class="w-px h-8 bg-blue-200 dark:bg-blue-800 hidden sm:block"></div>

            <div class="flex items-center gap-2 shrink-0">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="formatBytes(most_group?.total_consumption || 0)"></div>
                </div>
            </div>

            <div class="w-px h-8 bg-blue-200 dark:bg-blue-800 hidden sm:block"></div>

            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-mono" x-text="formatBytes(most_group?.total_download || 0)"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300 font-mono" x-text="formatBytes(most_group?.total_upload || 0)"></span>
                </div>

                <div class="h-5 w-px bg-blue-200 dark:bg-blue-800 hidden sm:block"></div>

                <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="most_group?.total_users"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">users</span>
                </div>

                <div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="most_group?.total_sessions"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">sessions</span>
                </div>

                <div class="px-2.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center gap-1">
                    <span class="text-gray-400 dark:text-gray-500">ID</span>
                    <span class="font-semibold" x-text="most_group?.group_id"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Top Site ─────────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-cyan-50 to-sky-50 dark:from-cyan-950/30 dark:to-sky-950/30 rounded-2xl p-5 shadow-xl shadow-cyan-200/20 dark:shadow-cyan-900/20 border border-cyan-200 dark:border-cyan-800 transition-all duration-300 hover:shadow-cyan-300/30 dark:hover:shadow-cyan-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 min-w-max sm:min-w-0">
            <div class="flex items-center gap-3 shrink-0">
                <div class="relative">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-cyan-500 to-sky-600 dark:from-cyan-400 dark:to-sky-500 flex items-center justify-center shadow-lg shadow-cyan-500/30 dark:shadow-cyan-400/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <div class="absolute -top-1.5 -right-1.5 text-sm leading-none">⭐</div>
                </div>
                <div>
                    <div class="text-xs text-cyan-600 dark:text-cyan-400 font-medium">Top Site</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="most_visited_site?.domain || 'Sans Nom'"></div>
                </div>
            </div>

            <div class="w-px h-8 bg-cyan-200 dark:bg-cyan-800 hidden sm:block"></div>

            <div class="flex items-center gap-2 shrink-0">
                <div class="w-9 h-9 rounded-xl bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Visites</div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white" x-text="most_visited_site?.total_visits"></div>
                </div>
            </div>
        </div>
    </div>

</div>