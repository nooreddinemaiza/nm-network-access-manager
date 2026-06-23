<div
    x-show="$store.policyItemModal.isOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    style="display: none;">

    <div x-show="$store.policyItemModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @click.away="$store.policyItemModal.close()"
        x-cloak
        class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Modifier les attributs
            </h2>
            <button @click="$store.policyItemModal.close()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation des onglets -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-8 px-6 overflow-x-auto" aria-label="Tabs">
                <template x-for="(tab, index) in $store.policyItemModal.tabs" :key="index">
                    <button @click="$store.policyItemModal.activeTab = index"
                        :class="$store.policyItemModal.activeTab === index ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all"
                        x-text="tab.name">
                    </button>
                </template>
            </nav>
        </div>

        <!-- Contenu des onglets -->
        <div class="flex-1 p-6 overflow-y-auto">
            <form @submit.prevent="$store.policyItemModal.submitForm" class="space-y-8">
                <!-- Onglet Général -->
                <div x-show="$store.policyItemModal.activeTab === 0" class="space-y-8">

                    <!-- Durée max de session -->
                    <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Durée max de session
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Valeur (minutes)
                                </label>
                                <input type="number"
                                    placeholder="Ex: 120"
                                    x-model.number="$store.policyItemModal.formData.max_session.value"
                                    :class="$store.policyItemModal.hasTabError('max_session') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Priorité
                                </label>
                                <input type="number"
                                    placeholder="Ex: 1"
                                    x-model.number="$store.policyItemModal.formData.max_session.priority"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    État
                                </label>
                                <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                    <input type="checkbox"
                                        x-model="$store.policyItemModal.formData.max_session.enabled"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                </label>
                            </div>
                        </div>
                        <p x-show="$store.policyItemModal.hasTabError('max_session')"
                            x-text="$store.policyItemModal.getError('max_session')"
                            x-transition
                            class="text-red-500 text-sm mt-3 flex items-center gap-2">
                        </p>
                    </div>

                    <!-- Durée max d'inactivité -->
                    <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Durée max d'inactivité
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Valeur (minutes)
                                </label>
                                <input type="number"
                                    placeholder="Ex: 30"
                                    x-model.number="$store.policyItemModal.formData.max_inactive.value"
                                    :class="$store.policyItemModal.hasTabError('max_inactive') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Priorité
                                </label>
                                <input type="number"
                                    placeholder="Ex: 1"
                                    x-model.number="$store.policyItemModal.formData.max_inactive.priority"
                                    :class="$store.policyItemModal.hasTabError('max_inactive') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    État
                                </label>
                                <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                    <input type="checkbox"
                                        x-model="$store.policyItemModal.formData.max_inactive.enabled"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                </label>
                            </div>
                        </div>
                        <p x-show="$store.policyItemModal.hasTabError('max_inactive')"
                            x-text="$store.policyItemModal.getError('max_inactive')"
                            x-transition
                            class="text-red-500 text-sm mt-3 flex items-center gap-2">
                        </p>
                    </div>

                    <!-- Sessions simultanées -->
                    <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Sessions simultanées
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Valeur (nombre)
                                </label>
                                <input type="number"
                                    placeholder="Ex: 5"
                                    x-model.number="$store.policyItemModal.formData.sessions.value"
                                    :class="$store.policyItemModal.hasTabError('sessions') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Priorité
                                </label>
                                <input type="number"
                                    placeholder="Ex: 1"
                                    x-model.number="$store.policyItemModal.formData.sessions.priority"
                                    :class="$store.policyItemModal.hasTabError('sessions') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    État
                                </label>
                                <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                    <input type="checkbox"
                                        x-model="$store.policyItemModal.formData.sessions.enabled"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                </label>
                            </div>
                        </div>
                        <p x-show="$store.policyItemModal.hasTabError('sessions')"
                            x-text="$store.policyItemModal.getError('sessions')"
                            x-transition
                            class="text-red-500 text-sm mt-3 flex items-center gap-2">
                        </p>
                    </div>

                    <!-- Intervalle accounting -->
                    <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Intervalle accounting <span class="text-sm">(Période de mise à jour)</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Valeur (secondes)
                                </label>
                                <input type="number"
                                    placeholder="Ex: 60"
                                    x-model.number="$store.policyItemModal.formData.accounting.value"
                                    :class="$store.policyItemModal.hasTabError('accounting') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Priorité
                                </label>
                                <input type="number"
                                    placeholder="Ex: 1"
                                    x-model.number="$store.policyItemModal.formData.accounting.priority"
                                    :class="$store.policyItemModal.hasTabError('accounting') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                    class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    État
                                </label>
                                <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                    <input type="checkbox"
                                        x-model="$store.policyItemModal.formData.accounting.enabled"
                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                </label>
                            </div>
                        </div>
                        <p x-show="$store.policyItemModal.hasTabError('accounting')"
                            x-text="$store.policyItemModal.getError('accounting')"
                            x-transition
                            class="text-red-500 text-sm mt-3 flex items-center gap-2">
                        </p>
                    </div>

                </div>
                <?php if (false): ?>
                    <!-- Onglet Débit -->
                    <div x-show="$store.policyItemModal.activeTab === 1" class="space-y-8">
                        <div class="rounded-lg p-5 border bg-yellow-400 border-gray-200 dark:border-gray-700">
                            <p class="text-black">
                                Cette section est en cours de test, elle n'a aucun effet!
                            </p>
                        </div>
                        <!-- Max-Upload -->
                        <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Max-Upload
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Valeur (Mo)
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 2"
                                        x-model.number="$store.policyItemModal.formData.max_upload.value"
                                        :class="$store.policyItemModal.hasTabError('max_upload') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Priorité
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 1"
                                        x-model.number="$store.policyItemModal.formData.max_upload.priority"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        État
                                    </label>
                                    <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                        <input type="checkbox"
                                            x-model="$store.policyItemModal.formData.max_upload.enabled"
                                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                    </label>
                                </div>
                            </div>
                            <p x-show="$store.policyItemModal.hasTabError('max_upload')"
                                x-text="$store.policyItemModal.getError('max_upload')"
                                x-transition
                                class="text-red-500 text-sm mt-3 flex items-center gap-2">
                            </p>
                        </div>
                        <!-- Max-Download -->
                        <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Max-Download
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Valeur (Mo)
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 20"
                                        x-model.number="$store.policyItemModal.formData.max_download.value"
                                        :class="$store.policyItemModal.hasTabError('max_download') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Priorité
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 1"
                                        x-model.number="$store.policyItemModal.formData.max_download.priority"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        État
                                    </label>
                                    <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                        <input type="checkbox"
                                            x-model="$store.policyItemModal.formData.max_download.enabled"
                                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                    </label>
                                </div>
                            </div>
                            <p x-show="$store.policyItemModal.hasTabError('max_download')"
                                x-text="$store.policyItemModal.getError('max_download')"
                                x-transition
                                class="text-red-500 text-sm mt-3 flex items-center gap-2">
                            </p>
                        </div>

                        <!-- Max-Consommation -->
                        <div class="rounded-lg p-5 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Max-Consommation
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Valeur (Mo)
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 20000"
                                        x-model.number="$store.policyItemModal.formData.max_consommation.value"
                                        :class="$store.policyItemModal.hasTabError('max_consommation') ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Priorité
                                    </label>
                                    <input type="number"
                                        placeholder="Ex: 1"
                                        x-model.number="$store.policyItemModal.formData.max_consommation.priority"
                                        class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-colors focus:ring-2 focus:border-transparent outline-none">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        État
                                    </label>
                                    <label class="flex items-center gap-3 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 cursor-pointer transition-colors">
                                        <input type="checkbox"
                                            x-model="$store.policyItemModal.formData.max_consommation.enabled"
                                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activé</span>
                                    </label>
                                </div>
                            </div>
                            <p x-show="$store.policyItemModal.hasTabError('max_consommation')"
                                x-text="$store.policyItemModal.getError('max_consommation')"
                                x-transition
                                class="text-red-500 text-sm mt-3 flex items-center gap-2">
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Footer avec boutons d'action -->
        <div class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex space-x-3">
                <button @click="$store.policyItemModal.prevTab()"
                    x-show="$store.policyItemModal.activeTab > 0"
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Précédent
                    </span>
                </button>
                <button @click="$store.policyItemModal.nextTab()"
                    x-show="$store.policyItemModal.activeTab < $store.policyItemModal.tabs.length - 1"
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <span class="flex items-center gap-2">
                        Suivant
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                </button>
            </div>

            <div class="flex space-x-3">
                <button @click="$store.policyItemModal.cancel()"
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 transition-all">
                    Annuler
                </button>
                <button @click="$store.policyItemModal.submitForm()"
                    :disabled="$store.policyItemModal.isLoading"
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm hover:shadow-md">
                    <span x-show="!$store.policyItemModal.isLoading">Modifier</span>
                    <span x-show="$store.policyItemModal.isLoading" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Modification...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>