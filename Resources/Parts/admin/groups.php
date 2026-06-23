<div class="relative z-50">
    <!-- Modals liste groupes -->
    <div x-data x-show="$store.groupsListModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-cloak
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 overflow-y-auto"
        style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.groupsListModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.groupsListModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-6xl relative border border-gray-200 dark:border-gray-700 overflow-hidden max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="bg-linear-to-r text-white">

                    <?= $view->inc('components', 'admin/modal.header.php', [
                        'title' => 'Liste des groupes',
                        'subtitle' => 'Gérer les groupes',
                        'close' => '$store.groupsListModal.close()',
                    ]); ?>
                </div>

                <!-- Filtres -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Recherche -->
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Rechercher
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-model="$store.groupsListModal.searchTerm"
                                    @input="$store.groupsListModal.filterGroups()"
                                    placeholder="Nom, description..."
                                    class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="gap-4 mt-6">
                            <button
                                @click="$store.addGroupModal.open()"
                                class="group flex items-center rounded-xl py-3 pl-4 pr-3 text-base font-semibold text-blue-700 bg-white shadow hover:bg-blue-50 hover:text-blue-900 dark:text-blue-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white w-full transition-all duration-200 border border-blue-100 dark:border-gray-700">
                                <i class="fa fa-plus text-blue-400 dark:text-blue-300"></i>&nbsp; Nouveau
                            </button>
                        </div>
                        <!-- Filtre par status -->
                        <div class="md:w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select x-model="$store.groupsListModal.selectedStatus"
                                @change="$store.groupsListModal.filterGroups()"
                                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                <option value="all">Tous les status</option>
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contenu principal -->
                <div class="flex-1 overflow-hidden flex flex-col">
                    <!-- Loading -->
                    <div x-show="$store.groupsListModal.loading" class="flex items-center justify-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Table -->
                    <div x-show="!$store.groupsListModal.loading" class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Titre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Membres
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Manager
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Créé le
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(group, index) in $store.groupsListModal.paginatedGroups" :key="index">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-linear-to-r from-blue-500 to-purple-500 flex items-center justify-center">
                                                        <span class="text-white font-semibold" x-text="group.name.charAt(0).toUpperCase()"></span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="group.name"></div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="'ID: '+group.id"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                x-text="group.members + '/' + group.max_members">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select @change="$store.groupsListModal.changeModerator(group, $event.target.value, $event)"
                                                :value="group.moderator"
                                                class=" rounded text-xs font-medium transition-colors cursor-pointer"
                                                :class="{
                                                    'bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-300': group.moderator == '-Sans-',
                                                    'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-300': group.moderator != '-Sans-',
                                                }"
                                                title="Changer le manager">
                                                <template x-for="(moderator, index) in $store.groupsListModal.moderators" :key="index">
                                                    <option
                                                        :selected="moderator.fullname == group.moderator"
                                                        :value="moderator.id"
                                                        x-text="moderator.fullname.charAt(0).toUpperCase() + moderator.fullname.slice(1)"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="$store.groupsListModal.formatDate(group.created_at)"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <!-- View Details Button -->
                                                <button @click="$store.groupsListModal.openProfile(group)" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white" title="Voir les détails">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 3C5 3 1.73 7.11 1.13 8c-.27.39-.27.91 0 1.3C1.73 12.89 5 17 10 17s8.27-4.11 8.87-5c.27-.39.27-.91 0-1.3C18.27 7.11 15 3 10 3zm0 12c-3.87 0-7.16-3.13-7.82-4C2.84 10.13 6.13 7 10 7s7.16 3.13 7.82 4c-.66.87-3.95 4-7.82 4zm0-7a3 3 0 100 6 3 3 0 000-6z" />
                                                    </svg>
                                                </button>

                                                <!-- Edit Button -->
                                                <button @click="$store.editGroupModal.open(group)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Delete Button -->
                                                <button @click="$store.confirmRemoveModal.open(group, 'group', 'Supprimer les membre de ce groupe?')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>

                                                <button
                                                    @click="$store.userPolicyModal.open(group.id, group.name, 'group')"
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

                        <!-- Pagination -->
                        <div x-show="$store.groupsListModal.totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    Affichage de <span x-text="(($store.groupsListModal.currentPage - 1) * $store.groupsListModal.itemsPerPage) + 1"></span> à
                                    <span x-text="Math.min($store.groupsListModal.currentPage * $store.groupsListModal.itemsPerPage, $store.groupsListModal.filteredGroups.length)"></span>
                                    sur <span x-text="$store.groupsListModal.filteredGroups.length"></span> résultats
                                </div>

                                <!-- Fin de la pagination (la partie coupée) -->
                                <div class="flex space-x-2">
                                    <button @click="$store.groupsListModal.goToPage($store.groupsListModal.currentPage - 1)"
                                        :disabled="$store.groupsListModal.currentPage === 1"
                                        :class="$store.groupsListModal.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                        class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                        Précédent
                                    </button>

                                    <!-- Numéros de pages -->
                                    <template x-for="page in Array.from({length: $store.groupsListModal.totalPages}, (_, i) => i + 1)" :key="page">
                                        <button @click="$store.groupsListModal.goToPage(page)"
                                            :class="page === $store.groupsListModal.currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                            class="px-3 py-1 text-sm rounded transition-colors"
                                            x-text="page">
                                        </button>
                                    </template>

                                    <button @click="$store.groupsListModal.goToPage($store.groupsListModal.currentPage + 1)"
                                        :disabled="$store.groupsListModal.currentPage === $store.groupsListModal.totalPages"
                                        :class="$store.groupsListModal.currentPage === $store.groupsListModal.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                        class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                        Suivant
                                    </button>
                                </div>
                            </div>


                        </div>
                        <!-- Empty State -->
                        <div x-show="$store.groupsListModal.filteredGroups.length === 0 && !$store.groupsListModal.loading"
                            class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucun group trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de recherche.</p>
                        </div>
                    </div>
                    <!-- Actions en bas du modal -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="$store.groupsListModal.filteredGroups.length"></span> Groupe(s) affiché(s)
                            </div>
                            <div class="flex space-x-3">
                                <button @click="$store.groupsListModal.loadGroups()"
                                    class="px-4 py-2 text-sm bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-500 transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                    </svg>
                                    Actualiser
                                </button>
                                <button @click="$store.groupsListModal.close()"
                                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals affichage groupe -->
    <div x-data x-show="$store.groupViewModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.groupViewModal.close()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.groupViewModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <div class="bg-linear-to-r from-blue-600 to-purple-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-4">
                            <div class="h-16 w-16 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="text-3xl font-bold" x-text="$store.groupViewModal.group?.name?.charAt(0).toUpperCase()"></span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold" x-text="$store.groupViewModal.group?.name"></h2>
                                <p class="text-blue-100 text-sm">Détails du groupe</p>
                            </div>
                        </div>
                        <button @click="$store.groupViewModal.close()"
                            class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ID du groupe</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="$store.groupViewModal.group?.id"></p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nombre de membres</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                    x-text="$store.groupViewModal.group?.members || '0'"></span> /
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                                    x-text="$store.groupViewModal.group?.max_members || '0'"></span>
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Manager</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                    :class="$store.groupViewModal.group?.moderator === '-Sans-' ? 
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' : 
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'"
                                    x-text="$store.groupViewModal.group?.moderator"></span>
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Créé le</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                x-text="$store.groupViewModal.formatDateTime($store.groupViewModal.group?.created_at)"></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</label>
                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed"
                            x-text="$store.groupViewModal.group?.description || 'Aucune description'"></p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-end space-x-3">
                        <button @click="$store.editGroupModal.open($store.groupViewModal.group); $store.groupViewModal.close()"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            Modifier
                        </button>
                        <button @click="$store.groupViewModal.close()"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals édition groupe -->
    <div x-data x-show="$store.editGroupModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.editGroupModal.close()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.editGroupModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <div class="bg-linear-to-r from-blue-600 to-purple-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold">Modifier le groupe</h2>
                            <p class="text-blue-100 text-sm mt-1">Mettre à jour les informations du groupe</p>
                        </div>
                        <button @click="$store.editGroupModal.close()"
                            class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom du groupe <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            x-model="$store.editGroupModal.group.name"
                            placeholder="Entrez le nom du groupe"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100"
                            required>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'name', 'errors' => '$store.addGroupModal.errors']) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            x-model="$store.editGroupModal.group.description"
                            placeholder="Entrez la description du groupe"
                            rows="4"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100"
                            required></textarea>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'description', 'errors' => '$store.addGroupModal.errors']) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre de membres <span class="text-xs text-orange-600">(Optionnel, Par défaut 40 personnes)</span>
                        </label>
                        <input type="number"
                            x-model.number="$store.editGroupModal.group.max_members"
                            min="1"
                            step="1"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                            placeholder="Ex: 10">
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'max_members', 'errors' => '$store.editGroupModal.errors']) ?>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manager</label>
                        <select
                            x-model="$store.editGroupModal.group.moderator"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                            <template x-for="(moderator, index) in $store.groupsListModal.moderators" :key="index">
                                <option
                                    :selected="moderator.fullname == $store.editGroupModal.group.moderator"
                                    :value="moderator.id"
                                    x-text="moderator.fullname.charAt(0).toUpperCase() + moderator.fullname.slice(1)">
                                </option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-end space-x-3">
                        <button @click="$store.editGroupModal.close()"
                            :disabled="$store.editGroupModal.loading"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Annuler
                        </button>
                        <button @click="$store.editGroupModal.save()"
                            :disabled="$store.editGroupModal.loading"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!$store.editGroupModal.loading">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                                </svg>
                                Enregistrer
                            </span>
                            <span x-show="$store.editGroupModal.loading" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Enregistrement...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals ajout groupe -->
    <div x-data x-show="$store.addGroupModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.addGroupModal.close()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.addGroupModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <div class="bg-linear-to-r from-green-600 to-teal-600 text-white p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold">Créer un nouveau groupe</h2>
                            <p class="text-green-100 text-sm mt-1">Ajouter un groupe au système</p>
                        </div>
                        <button @click="$store.addGroupModal.close()"
                            class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nom du groupe <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            x-model="$store.addGroupModal.group.name"
                            placeholder="Entrez le nom du groupe"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            required>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'name', 'errors' => '$store.addGroupModal.errors']) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            x-model="$store.addGroupModal.group.description"
                            placeholder="Entrez la description du groupe"
                            rows="4"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100"
                            required></textarea>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'description', 'errors' => '$store.addGroupModal.errors']) ?>

                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre de membres <span class="text-xs text-orange-600">(Optionnel, Par défaut 40 personnes)</span>
                        </label>
                        <input type="number"
                            x-model.number="$store.addGroupModal.group.max_members"
                            min="1"
                            step="1"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                            placeholder="Ex: 10">
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'max_members', 'errors' => '$store.addGroupModal.errors']) ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manager</label>
                        <select
                            x-model="$store.addGroupModal.group.moderator"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-gray-100">
                            <option value="-1">Sélectionnez un manager (optionnel)</option>
                            <template x-for="(moderator, index) in $store.groupsListModal.moderators" :key="index">
                                <option
                                    :value="moderator.id"
                                    x-text="moderator.fullname.charAt(0).toUpperCase() + moderator.fullname.slice(1)">
                                </option>
                            </template>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vous pouvez assigner un manager plus tard</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-end space-x-3">
                        <button @click="$store.addGroupModal.close()"
                            :disabled="$store.addGroupModal.loading"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Annuler
                        </button>
                        <button @click="$store.addGroupModal.save()"
                            :disabled="$store.addGroupModal.loading"
                            class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!$store.addGroupModal.loading">
                                <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Créer le groupe
                            </span>
                            <span x-show="$store.addGroupModal.loading" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Création...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>