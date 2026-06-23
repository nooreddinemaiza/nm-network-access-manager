<div class="relative z-50">
    <!-- Modal Liste des Politiques -->
    <div x-data x-show="$store.policyListModal.isOpen"
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
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.policyListModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.policyListModal.isOpen"
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
                        'title' => 'Liste des Politiques',
                        'subtitle' => 'Gérer les Politiques',
                        'close' => '$store.policyListModal.close()',
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
                                    x-model="$store.policyListModal.searchTerm"
                                    @input="$store.policyListModal.filterPolicies()"
                                    placeholder="Nom, email ou username..."
                                    class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="gap-4 mt-6">
                            <button
                                @click="$store.addPolicyModal.open()"
                                class="group flex items-center rounded-xl py-3 pl-4 pr-3 text-base font-semibold text-blue-700 bg-white shadow hover:bg-blue-50 hover:text-blue-900 dark:text-blue-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white w-full transition-all duration-200 border border-blue-100 dark:border-gray-700">
                                <i class="fa fa-plus text-blue-400 dark:text-blue-300"></i> Politique
                            </button>
                        </div>
                        <!-- Filtre par status -->
                        <div class="md:w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select x-model="$store.policyListModal.selectedStatus"
                                @change="$store.policyListModal.filterPolicies()"
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
                    <div x-show="$store.policyListModal.loading" class="flex items-center justify-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Table -->
                    <div x-show="!$store.policyListModal.loading" class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Titre
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date d'expération
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="policy in $store.policyListModal.paginatedPolicies" :key="policy.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                x-text="policy.name">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                x-text="policy.description ?? '-Sans-'">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="$store.policyListModal.getStatusClass(policy.status)"
                                                x-text="$store.policyListModal.getStatusText(policy.status)">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="policy.expires_at ? $store.policyListModal.formatDate(policy.expires_at) : '-Sans-'"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <!-- Toggle Status -->
                                                <button @click="$store.policyListModal.toggleStatus(policy.id, policy.status)"
                                                    :class="policy.status === 'active' ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300'"
                                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium transition-colors">
                                                    <span x-text="policy.status === 'active' ? 'Désactiver' : 'Activer'"></span>
                                                </button>

                                                <!-- View Details Button -->
                                                <button @click="$store.viewPolicyModal.open(policy)" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white" title="Voir les détails">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 3C5 3 1.73 7.11 1.13 8c-.27.39-.27.91 0 1.3C1.73 12.89 5 17 10 17s8.27-4.11 8.87-5c.27-.39.27-.91 0-1.3C18.27 7.11 15 3 10 3zm0 12c-3.87 0-7.16-3.13-7.82-4C2.84 10.13 6.13 7 10 7s7.16 3.13 7.82 4c-.66.87-3.95 4-7.82 4zm0-7a3 3 0 100 6 3 3 0 000-6z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    @click="$store.policyItemModal.open(policy)"
                                                    class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                                    title="Attributs / Presets">
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

                                                <!-- Edit Button -->
                                                <button @click="$store.editPolicyModal.open(policy)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Delete Button -->
                                                <button @click="$store.confirmRemoveModal.open(policy, 'policy')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div x-show="$store.policyListModal.filteredPolicies.length === 0 && !$store.policyListModal.loading"
                            class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune politque trouvée</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de recherche.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div x-show="$store.policyListModal.totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Affichage de <span x-text="(($store.policyListModal.currentPage - 1) * $store.policyListModal.itemsPerPage) + 1"></span> à
                                <span x-text="Math.min($store.policyListModal.currentPage * $store.policyListModal.itemsPerPage, $store.policyListModal.filteredPolicies.length)"></span>
                                sur <span x-text="$store.policyListModal.filteredPolicies.length"></span> résultats
                            </div>
                            <!-- Parties manquantes pour compléter le modal -->

                            <!-- Fin de la pagination (la partie coupée) -->
                            <div class="flex space-x-2">
                                <button @click="$store.policyListModal.goToPage($store.policyListModal.currentPage - 1)"
                                    :disabled="$store.policyListModal.currentPage === 1"
                                    :class="$store.policyListModal.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                    Précédent
                                </button>

                                <!-- Numéros de pages -->
                                <template x-for="page in Array.from({length: $store.policyListModal.totalPages}, (_, i) => i + 1)" :key="page">
                                    <button @click="$store.policyListModal.goToPage(page)"
                                        :class="page === $store.policyListModal.currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                        class="px-3 py-1 text-sm rounded transition-colors"
                                        x-text="page">
                                    </button>
                                </template>

                                <button @click="$store.policyListModal.goToPage($store.policyListModal.currentPage + 1)"
                                    :disabled="$store.policyListModal.currentPage === $store.policyListModal.totalPages"
                                    :class="$store.policyListModal.currentPage === $store.policyListModal.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                    Suivant
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions en bas du modal -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="$store.policyListModal.filteredPolicies.length"></span> politique(s) affiché(s)
                            </div>
                            <div class="flex space-x-3">
                                <button @click="$store.policyListModal.loadPolicies()"
                                    class="px-4 py-2 text-sm bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-500 transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                    </svg>
                                    Actualiser
                                </button>
                                <button @click="$store.policyListModal.close()"
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
    <!-- Modal Ajout des Politiques -->
    <div x-data x-show="$store.addPolicyModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Enhanced Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.addPolicyModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.addPolicyModal.isOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Ajouter une nouvelle politique',
                    'subtitle' => '',
                    'close' => '$store.addPolicyModal.close()',
                ]); ?>

                <!-- Form with Enhanced Styling -->
                <form @submit.prevent="$store.addPolicyModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message with Enhanced Styling -->
                    <div x-show="$store.addPolicyModal.errors.general ?? $store.addPolicyModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.addPolicyModal.errors.message"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Titre -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Titre <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" x-model="$store.addPolicyModal.formData.name"
                                    :class="$store.addPolicyModal.errors.name ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le titre">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.addPolicyModal.formData.name" class="w-4 h-4 text-green-500"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.addPolicyModal.errors.name"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addPolicyModal.errors.name"></span>
                            </p>
                        </div>
                        <!-- description -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Description
                            </label>
                            <div class="relative">
                                <input type="text" x-model="$store.addPolicyModal.formData.description"
                                    :class="$store.addPolicyModal.errors.description ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le nom complet">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.addPolicyModal.formData.description" class="w-4 h-4 text-green-500"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.addPolicyModal.errors.description"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addPolicyModal.errors.description"></span>
                            </p>
                        </div>

                    </div>

                    <!-- Troisème ligne : Status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date et heure d'expiration -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Date et heure d'expiration
                            </label>
                            <input type="datetime-local"
                                x-model="$store.addPolicyModal.formData.expires_at"
                                :min="$store.addPolicyModal.getTodayDateTime()"
                                :class="$store.addPolicyModal.errors.expires_at ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'expires_at',
                                'errors' => '$store.addPolicyModal.errors'
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
                                Status du compte
                            </label>
                            <select x-model="$store.addPolicyModal.formData.status"
                                :class="$store.addPolicyModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active" selected>Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <p x-show="$store.addPolicyModal.errors.status"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addPolicyModal.errors.status"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button" @click="$store.addPolicyModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit" :disabled="$store.addPolicyModal.loading"
                            class="flex-1 px-6 py-3 bg-linear-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.addPolicyModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="$store.addPolicyModal.loading ? 'Création...' : 'Créer'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal de modification des Politiques -->
    <div x-data x-show="$store.editPolicyModal.isOpen" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" x-cloak style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.editPolicyModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.editPolicyModal.isOpen" x-transition:enter="transition ease-out duration-300"
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
                    'close' => '$store.editPolicyModal.close()',
                ]); ?>

                <!-- Form -->
                <form @submit.prevent="$store.editPolicyModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message -->
                    <div x-show="$store.editPolicyModal.errors.general || $store.editPolicyModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.editPolicyModal.errors.message || $store.editPolicyModal.errors.general"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Nom -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Nom <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-model="$store.editPolicyModal.formData.name"
                                    :class="$store.editPolicyModal.errors.name ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                            </div>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'name',
                                'errors' => '$store.editPolicyModal.errors'
                            ]) ?>
                        </div>
                        <!-- Description -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                Description
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-model="$store.editPolicyModal.formData.description"
                                    :class="$store.editPolicyModal.errors.description ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                            </div>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'description',
                                'errors' => '$store.editPolicyModal.errors'
                            ]) ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date d'expiration -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                Date et heure d'expiration
                            </label>
                            <input type="datetime-local"
                                x-model="$store.editPolicyModal.formData.expires_at"
                                :min="$store.editPolicyModal.getTodayDateTime()"
                                :class="$store.editPolicyModal.errors.expires_at ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'expires_at',
                                'errors' => '$store.editPolicyModal.errors'
                            ]) ?>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Status <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select x-model="$store.editPolicyModal.formData.status"
                                :class="$store.editPolicyModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <?php $view->inc('components', 'admin/input.error.php', [
                                'input' => 'status',
                                'errors' => '$store.editPolicyModal.errors'
                            ]) ?>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button" @click="$store.editPolicyModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit" :disabled="$store.editPolicyModal.loading"
                            class="flex-1 px-6 py-3 bg-linear-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.editPolicyModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="$store.editPolicyModal.loading ? 'Modification...' : 'Modifier'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modals affichage Politiques -->
    <div x-data x-show="$store.viewPolicyModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 overflow-y-auto"
        style="display: none;">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.viewPolicyModal.close()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.viewPolicyModal.isOpen"
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
                                <span class="text-3xl font-bold" x-text="$store.viewPolicyModal.policy?.id"></span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold" x-text="$store.viewPolicyModal.policy?.name"></h2>
                                <p class="text-blue-100 text-sm">Détails de la politique</p>
                            </div>
                        </div>
                        <button @click="$store.viewPolicyModal.close()"
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
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ID de la politique</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="$store.viewPolicyModal.policy?.id"></p>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="$store.viewPolicyModal.getStatusClass($store.viewPolicyModal.policy?.status)"
                                    x-text="$store.viewPolicyModal.getStatusText($store.viewPolicyModal.policy?.status)">
                                </span>
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Expiré le</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                x-text="!$store.viewPolicyModal.policy?.expires_at ? '-Sans-' : $store.viewPolicyModal.formatDateTime($store.viewPolicyModal.policy?.expires_at)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Créé le</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                x-text="$store.viewPolicyModal.formatDateTime($store.viewPolicyModal.policy?.created_at)"></p>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</label>
                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed"
                            x-text="$store.viewPolicyModal.policy?.description || 'Aucune description'"></p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex justify-end space-x-3">
                        <button @click="$store.editPolicyModal.open($store.viewPolicyModal.policy); $store.viewPolicyModal.close()"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            Modifier
                        </button>
                        <button @click="$store.viewPolicyModal.close()"
                            class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>