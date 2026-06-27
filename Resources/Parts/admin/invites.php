<div class="relative z-50">
    <!-- Modals liste de liens d'invitation -->
    <div x-data="linksModal()" x-show="isOpen" x-cloak @keydown.escape.window="closeModal()"
        class="fixed inset-0 overflow-y-auto" style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm transition-opacity" @click="closeModal()">
        </div>

        <!-- Modal Liste des liens -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-7xl bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 max-h-[95vh] flex flex-col"
                @click.stop>

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => '<i class="fa fa-link"></i><h2 class="text-lg font-bold"> Liste des liens</h2>',
                    'subtitle' => '',
                    'close' => 'closeModal()',
                ]); ?>

                <!-- Statistiques compactes -->
                <div class="px-4 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900/50 dark:to-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-6 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 dark:text-gray-400">Total:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="stats.total"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Actifs:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400" x-text="stats.active"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Inactifs:</span>
                            <span class="font-semibold text-red-600 dark:text-red-400" x-text="stats.inactive"></span>
                        </div>
                    </div>
                </div>

                <!-- Filtres compacts -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                        <!-- Recherche -->
                        <div class="flex-1 min-w-0">
                            <div class="relative">
                                <input type="text" x-model="searchTerm" @input="applyFilters()"
                                    placeholder="Rechercher group..."
                                    class="w-full px-3 py-2 pl-9 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                                <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Filtres groupés -->
                        <div class="flex flex-wrap gap-2">
                            <!-- Filtre Groupe -->
                            <select x-model="selectedGroup" @change="applyFilters()"
                                class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-gray-100 min-w-[120px]">
                                <option value="all">Tous groupes</option>
                                <template x-for="(group, index) in groups" :key="index">
                                    <option :value="group.name"
                                        x-text="group.name.charAt(0).toUpperCase() + group.name.slice(1)"></option>
                                </template>
                            </select>

                            <!-- Filtre Status -->
                            <select x-model="selectedStatus" @change="applyFilters()"
                                class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-gray-100 min-w-[120px]">
                                <option value="all">Tous statuts</option>
                                <option value="active">Actif</option>
                                <option value="expired">Expiré</option>
                                <option value="revoked">Révoqué</option>
                            </select>

                            <!-- Bouton Nouveau lien -->
                            <button @click="$store.addLinkModal.open();closeModal();"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-medium transition-colors whitespace-nowrap">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                </svg>
                                Nouveau lien
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div class="flex-1 overflow-hidden flex flex-col">
                    <!-- Loading -->
                    <div x-show="loading" class="flex-1 flex items-center justify-center">
                        <div class="text-center">
                            <div
                                class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-blue-600 border-t-transparent">
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Chargement...</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div x-show="!loading" class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Groupe
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Créateur
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Utilisations
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Expiration
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(link, idx) in paginatedlinks" :key="`link-${link.id}-${idx}`">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900 dark:text-gray-100 truncate block"
                                                x-text="link.group || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900 dark:text-gray-100 truncate block"
                                                x-text="link.creator || '-'"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm">
                                                <span :class="getUsageColor(link)" class="font-medium"
                                                    x-text="link.uses"></span>
                                                <span class="text-gray-400">/</span>
                                                <span class="text-gray-600 dark:text-gray-400"
                                                    x-text="link.max_uses || '∞'"></span>
                                            </div>
                                            <template x-if="link.max_uses && link.uses >= link.max_uses">
                                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                    Limite atteinte
                                                </span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div x-data="{ remaining: getTimeRemaining(link.expires_at) }">
                                                <span class="text-sm font-medium"
                                                    :class="remaining.color"
                                                    x-text="remaining.text"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <select
                                                x-model="link.status"
                                                @change="changeStatus(link, $event.target.value)"
                                                class="text-xs px-2 py-1 rounded-full border-0 font-medium focus:ring-2 focus:ring-offset-1 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-wait"
                                                :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 focus:ring-green-500': link.status === 'active',
                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 focus:ring-red-500': link.status === 'expired',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400 focus:ring-gray-500': link.status === 'revoked'
                                            }">
                                                <option value="active">Actif</option>
                                                <option value="expired">Expiré</option>
                                                <option value="revoked">Révoqué</option>
                                            </select>
                                            <template x-if="updatingStatus[link.id]">
                                                <svg class="inline-block ml-1 w-3 h-3 animate-spin text-gray-600" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </template>
                                        </td>
                                        <!-- Actions -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-1">
                                                <!-- Visit -->
                                                <a :href="link.visit"
                                                    class="p-1.5 text-gray-600 cursor-pointer hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 rounded transition-colors"
                                                    title="Ouvrir le lien"
                                                    target="_blank">
                                                    <i class="fa fa-external-link-alt text-sm"></i>
                                                </a>

                                                <!-- View -->
                                                <button @click="viewlink(link)"
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
                                                <button @click="editlink(link)"
                                                    class="p-1.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 rounded transition-colors"
                                                    title="Modifier">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Delete -->
                                                <button @click="$store.confirmRemoveModal.open(link, 'link')"
                                                    class="p-1.5 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded transition-colors"
                                                    title="Supprimer">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div x-show="filteredlinks.length === 0 && !loading"
                            class="flex-1 flex flex-col items-center justify-center py-16 px-4">
                            <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <h3 class="mt-4 text-base font-medium text-gray-900 dark:text-gray-100">Aucun lien trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de
                                recherche.</p>
                        </div>
                    </div>

                </div>
                <!-- Pagination compacte -->
                <div x-show="totalPages > 1 && !loading"
                    class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <span class="font-semibold" x-text="startIndex + 1"></span>-<span class="font-semibold"
                                x-text="endIndex"></span> sur <span class="font-semibold"
                                x-text="filteredlinks.length"></span>
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
                               'bg-blue-600 text-white hover:bg-blue-700' : 
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

                <!-- Footer compact -->
                <div
                    class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-xl">
                    <div class="flex justify-end items-center gap-3">
                        <button @click="loadlinks()"
                            class="px-4 py-2 text-sm bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-500 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                            </svg>
                            Actualiser
                        </button>
                        <button @click="closeModal()"
                            class="px-4 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors font-medium">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals ajout de lien d'invitation -->
    <div x-data x-show="$store.addLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Enhanced Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.addLinkModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.addLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Créer un lien d\'invitation',
                    'subtitle' => 'Ajouter un nouveau lien d\'invitation pour le portail captif',
                    'close' => '$store.addLinkModal.close()',
                ]); ?>

                <!-- Form with Enhanced Styling -->
                <form @submit.prevent="$store.addLinkModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message with Enhanced Styling -->
                    <div x-show="$store.addLinkModal.errors.general || $store.addLinkModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.addLinkModal.errors.message || $store.addLinkModal.errors.general"></span>
                    </div>

                    <!-- Première ligne : Max utilisations et Date d'expiration -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Max utilisations -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Max utilisations <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                    x-model.number="$store.addLinkModal.formData.max_uses"
                                    min="1"
                                    step="1"
                                    :class="$store.addLinkModal.errors.max_uses ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Ex: 10">
                            </div>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'max_uses',
                                'errors' => '$store.addLinkModal.errors'
                            ]) ?>
                        </div>

                        <!-- Date et heure d'expiration -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Date et heure d'expiration <span class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="datetime-local"
                                x-model="$store.addLinkModal.formData.expires_at"
                                :min="$store.addLinkModal.getTodayDateTime()"
                                :class="$store.addLinkModal.errors.expires_at ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'expires_at',
                                'errors' => '$store.addLinkModal.errors'
                            ]) ?>
                        </div>
                    </div>

                    <!-- Deuxième ligne : Groupe et Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fa fa-users text-gray-700 dark:text-gray-300"></i>&nbsp;
                                Groupe du compte <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select x-model="$store.addLinkModal.formData.group"
                                :class="$store.addLinkModal.errors.group
                                    ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20'
                                    : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <template x-for="(group, index) in $store.addLinkModal.groups" :key="index">
                                    <option :value="group.id"
                                        x-text="group.name.charAt(0).toUpperCase() + group.name.slice(1)"></option>
                                </template>
                            </select>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'group',
                                'errors' => '$store.addLinkModal.errors'
                            ]) ?>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Status du lien <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select x-model="$store.addLinkModal.formData.status"
                                :class="$store.addLinkModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active">Actif</option>
                                <option value="expired">expiré</option>
                                <option value="revoked">revoqué</option>
                            </select>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'status',
                                'errors' => '$store.addLinkModal.errors'
                            ]) ?>
                        </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button" @click="$store.addLinkModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit" :disabled="$store.addLinkModal.loading"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.addLinkModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="$store.addLinkModal.loading ? 'Création...' : 'Créer le lien'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal d'affichage des détails du lien -->
    <div x-data x-show="$store.viewLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.viewLinkModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.viewLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Détails du lien d\'invitation',
                    'subtitle' => 'Informations complètes du lien',
                    'close' => '$store.viewLinkModal.close()',
                ]); ?>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Lien avec bouton de copie -->
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2  flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                            </svg>
                            Lien d'invitation
                        </label>
                        <div class="flex gap-2">
                            <input type="text"
                                :value="$store.viewLinkModal.linkData.visit"
                                readonly
                                class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-gray-100 font-mono">
                            <button @click="$store.viewLinkModal.copyLink()"
                                class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z" />
                                </svg>
                                Copier
                            </button>
                        </div>
                    </div>

                    <!-- Informations principales -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Groupe -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <i class="fa fa-users mr-2"></i>
                                Groupe
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.linkData.group || '-'"></span>
                            </div>
                        </div>

                        <!-- Créateur -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                Créateur
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.linkData.creator || '-'"></span>
                            </div>
                        </div>

                        <!-- Token -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                                </svg>
                                Token
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100 font-mono text-sm break-all" x-text="$store.viewLinkModal.linkData.token"></span>
                            </div>
                        </div>

                        <!-- Utilisations -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Utilisations
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100">
                                    <span x-text="$store.viewLinkModal.linkData.uses"></span> /
                                    <span x-text="$store.viewLinkModal.linkData.max_uses"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Status
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span :class="{
                                'text-green-600 dark:text-green-400': $store.viewLinkModal.linkData.status === 'Actif',
                                'text-red-600 dark:text-red-400': $store.viewLinkModal.linkData.status === 'Expiré',
                                'text-orange-600 dark:text-orange-400': $store.viewLinkModal.linkData.status === 'Révoqué'
                            }" class="font-semibold" x-text="$store.viewLinkModal.linkData.status"></span>
                            </div>
                        </div>

                        <!-- Date d'expiration -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Date d'expiration
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.formatDate($store.viewLinkModal.linkData.expires_at)"></span>
                            </div>
                        </div>

                        <!-- Dernière utilisation -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                Dernière utilisation
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.formatDate($store.viewLinkModal.linkData.last_used_at)"></span>
                            </div>
                        </div>

                        <!-- Date de création -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Créé le
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.formatDate($store.viewLinkModal.linkData.created_at)"></span>
                            </div>
                        </div>

                        <!-- Date de mise à jour -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                </svg>
                                Mis à jour le
                            </label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <span class="text-gray-900 dark:text-gray-100" x-text="$store.viewLinkModal.formatDate($store.viewLinkModal.linkData.updated_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 rounded-b-2xl">
                    <div class="flex justify-end gap-3">
                        <button @click="$store.viewLinkModal.close()"
                            class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition-colors">
                            Fermer
                        </button>
                        <button @click="$store.editLinkModal.open($store.viewLinkModal.linkData)"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            Modifier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification du lien -->
    <div x-data x-show="$store.editLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.editLinkModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.editLinkModal.isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Modifier le lien d\'invitation',
                    'subtitle' => 'Modifier la date d\'expiration et le status',
                    'close' => '$store.editLinkModal.close()',
                ]); ?>

                <!-- Form -->
                <form @submit.prevent="$store.editLinkModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message -->
                    <div x-show="$store.editLinkModal.errors.general || $store.editLinkModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.editLinkModal.errors.message || $store.editLinkModal.errors.general"></span>
                    </div>

                    <!-- Informations non modifiables -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>Groupe:</strong> <span x-text="$store.editLinkModal.formData.group"></span><br>
                            <strong>Utilisations:</strong>
                            <span x-text="$store.editLinkModal.formData.uses"></span> /
                            <span x-text="$store.editLinkModal.formData.max_uses"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date d'expiration -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Date et heure d'expiration <span class="text-red-500 ml-1">*</span>
                            </label>
                            <input type="datetime-local"
                                x-model="$store.editLinkModal.formData.expires_at"
                                :min="$store.editLinkModal.getTodayDateTime()"
                                :class="$store.editLinkModal.errors.expires_at ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'expires_at',
                                'errors' => '$store.editLinkModal.errors'
                            ]) ?>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Status du lien <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select x-model="$store.editLinkModal.formData.status"
                                :class="$store.editLinkModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active">Actif</option>
                                <option value="expired">Expiré</option>
                                <option value="revoked">Révoqué</option>
                            </select>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'status',
                                'errors' => '$store.editLinkModal.errors'
                            ]) ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Max utilisations -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Max utilisations <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                    x-model.number="$store.editLinkModal.formData.max_uses"
                                    :min="$store.editLinkModal.formData.max_uses"
                                    step="1"
                                    :class="$store.editLinkModal.errors.max_uses ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Ex: 10">
                            </div>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'max_uses',
                                'errors' => '$store.editLinkModal.errors'
                            ]) ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button" @click="$store.editLinkModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit" :disabled="$store.editLinkModal.loading"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.editLinkModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="$store.editLinkModal.loading ? 'Modification...' : 'Modifier'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>