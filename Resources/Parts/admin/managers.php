<div class="relative z-50">
    <!-- Modal Ajout des Managers -->
    <div x-data x-show="$store.addManagerModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        x-cloak
        style="display: none;">

        <!-- Enhanced Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.addManagerModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.addManagerModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Créer un manager',
                    'subtitle' => 'Ajouter un nouveau membre à l\'équipe',
                    'close' => '$store.addManagerModal.close()',
                ]); ?>

                <!-- Form with Enhanced Styling -->
                <form @submit.prevent="$store.addManagerModal.submitForm()" class="p-6 space-y-6">

                    <!-- Error Message with Enhanced Styling -->
                    <div x-show="$store.addManagerModal.errors.general ?? $store.addManagerModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.addManagerModal.errors.message"></span>
                    </div>

                    <!-- Première ligne : Nom complet et Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nom complet -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                Nom complet <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-model="$store.addManagerModal.formData.fullname"
                                    :class="$store.addManagerModal.errors.fullname ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le nom complet">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.addManagerModal.formData.fullname" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.addManagerModal.errors.fullname"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addManagerModal.errors.fullname"></span>
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                Email <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="email"
                                    x-model="$store.addManagerModal.formData.email"
                                    :class="$store.addManagerModal.errors.email ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="exemple@email.com">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.addManagerModal.formData.email && $store.addManagerModal.formData.email.includes('@')" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.addManagerModal.errors.email"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addManagerModal.errors.email"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Deuxième ligne : Mot de passe -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mot de passe -->
                        <div class="space-y-2" x-data="{ showPassword: false }">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                Mot de passe <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                    x-model="$store.addManagerModal.formData.password"
                                    :class="$store.addManagerModal.errors.password ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                    class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Minimum 6 caractères">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="$store.addManagerModal.errors.password"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addManagerModal.errors.password"></span>
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Status du compte
                            </label>
                            <select x-model="$store.addManagerModal.formData.status"
                                :class="$store.addManagerModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active" selected>Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <p x-show="$store.addManagerModal.errors.status"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.addManagerModal.errors.status"></span>
                            </p>
                        </div>
                    </div>
                    <!-- Enhanced Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button"
                            @click="$store.addManagerModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit"
                            :disabled="$store.addManagerModal.loading"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.addManagerModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="$store.addManagerModal.loading ? 'Création...' : 'Créer'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modification des Managers -->
    <div x-data x-show="$store.editManagerModal.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">

        <!-- Enhanced Backdrop with Blur -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.editManagerModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.editManagerModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl relative border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Gradient Header -->
                <?= $view->inc('components', 'admin/modal.header.php', [
                    'title' => 'Modifier le compte',
                    'subtitle' => 'Mettre à jour les informations du compte',
                    'close' => '$store.editManagerModal.close()',
                ]); ?>
                <!-- Form with Enhanced Styling -->
                <form @submit.prevent="$store.editManagerModal.submitForm()" class="p-6 space-y-6">
                    <!-- Error Message with Enhanced Styling -->
                    <div x-show="$store.editManagerModal.errors.general ?? $store.editManagerModal.errors.message"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span x-text="$store.editManagerModal.errors.message || $store.editManagerModal.errors.general"></span>
                    </div>

                    <!-- Première ligne : Nom complet et Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nom complet -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                Nom complet <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-model="$store.editManagerModal.formData.fullname"
                                    :class="$store.editManagerModal.errors.fullname ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-amber-500 focus:ring-amber-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Entrez le nom complet">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.editManagerModal.formData.fullname" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.editManagerModal.errors.fullname"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.editManagerModal.errors.fullname"></span>
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                Email <span class="text-red-500 ml-1">*</span>
                            </label>
                            <div class="relative">
                                <input type="email"
                                    x-model="$store.editManagerModal.formData.email"
                                    :class="$store.editManagerModal.errors.email ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-amber-500 focus:ring-amber-500/20'"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="exemple@email.com">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg x-show="$store.editManagerModal.formData.email && $store.editManagerModal.formData.email.includes('@')" class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <p x-show="$store.editManagerModal.errors.email"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.editManagerModal.errors.email"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Deuxième ligne  -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mot de passe -->
                        <div class="space-y-2" x-data="{ showPassword: false }">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                Nouveau mot de passe <span class="text-gray-400 text-xs">(laisser vide pour ne pas changer)</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                    x-model="$store.editManagerModal.formData.password"
                                    :class="$store.editManagerModal.errors.password ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-amber-500 focus:ring-amber-500/20'"
                                    class="w-full px-4 py-3 pr-12 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Minimum 6 caractères si changé">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                                    </svg>
                                </button>
                            </div>
                            <p x-show="$store.editManagerModal.errors.password"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.editManagerModal.errors.password"></span>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Status du compte <span class="text-red-500 ml-1">*</span>
                            </label>
                            <select x-model="$store.editManagerModal.formData.status"
                                :class="$store.editManagerModal.errors.status ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500/20'"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border rounded-xl focus:outline-none focus:ring-4 transition-all duration-200 text-gray-900 dark:text-gray-100">
                                <option value="active" selected>Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                            <p x-show="$store.editManagerModal.errors.status"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform translate-y-1"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="$store.editManagerModal.errors.status"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Enhanced Action Buttons -->
                    <div class="flex space-x-3 pt-6">
                        <button type="button"
                            @click="$store.editManagerModal.close()"
                            class="flex-1 px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 border border-gray-200 dark:border-gray-600">
                            Annuler
                        </button>
                        <button type="submit"
                            :disabled="$store.editManagerModal.loading"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold transition-all duration-200 flex items-center justify-center transform hover:scale-105 disabled:transform-none shadow-lg hover:shadow-xl">
                            <svg x-show="$store.editManagerModal.loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="$store.editManagerModal.loading ? 'Modification...' : 'Modifier l\'utilisateur'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Liste des Managers -->
    <div x-data x-show="$store.managersListModal.isOpen"
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
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$store.managersListModal.close()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="$store.managersListModal.isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-6xl relative border border-gray-200 dark:border-gray-700 overflow-hidden max-h-[90vh] flex flex-col">

                <!-- Header -->
                <div class="bg-gradient-to-r text-white">

                    <?= $view->inc('components', 'admin/modal.header.php', [
                        'title' => 'Liste des Managers',
                        'subtitle' => 'Gérer les comptes managers',
                        'close' => '$store.managersListModal.close()',
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
                                    x-model="$store.managersListModal.searchTerm"
                                    @input="$store.managersListModal.filterManagers()"
                                    placeholder="Nom, email ou username..."
                                    class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100">
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="gap-4 mt-6">
                            <button
                                @click="$store.addManagerModal.open()"
                                class="group flex items-center rounded-xl py-3 pl-4 pr-3 text-base font-semibold text-blue-700 bg-white shadow hover:bg-blue-50 hover:text-blue-900 dark:text-blue-200 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white w-full transition-all duration-200 border border-blue-100 dark:border-gray-700">
                                <i class="fa fa-user-plus text-blue-400 dark:text-blue-300"></i> Nouveau Manager
                            </button>
                        </div>
                        <!-- Filtre par status -->
                        <div class="md:w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status
                            </label>
                            <select x-model="$store.managersListModal.selectedStatus"
                                @change="$store.managersListModal.filterManagers()"
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
                    <div x-show="$store.managersListModal.loading" class="flex items-center justify-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Table -->
                    <div x-show="!$store.managersListModal.loading" class="flex-1 overflow-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Manager
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
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
                                <template x-for="manager in $store.managersListModal.paginatedManagers" :key="manager.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center">
                                                        <span class="text-white font-semibold" x-text="manager.fullname.charAt(0).toUpperCase()"></span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="manager.fullname"></div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="manager.email"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="$store.managersListModal.getStatusClass(manager.status)"
                                                x-text="$store.managersListModal.getStatusText(manager.status)">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="$store.managersListModal.formatDate(manager.created_at)"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <!-- Toggle Status -->
                                                <button @click="$store.managersListModal.toggleStatus(manager.id, manager.status)"
                                                    :class="manager.status === 'active' ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300'"
                                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium transition-colors">
                                                    <span x-text="manager.status === 'active' ? 'Désactiver' : 'Activer'"></span>
                                                </button>

                                                <!-- View Details Button -->
                                                <button @click="$store.managersListModal.openProfile(manager)" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white" title="Voir les détails">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 3C5 3 1.73 7.11 1.13 8c-.27.39-.27.91 0 1.3C1.73 12.89 5 17 10 17s8.27-4.11 8.87-5c.27-.39.27-.91 0-1.3C18.27 7.11 15 3 10 3zm0 12c-3.87 0-7.16-3.13-7.82-4C2.84 10.13 6.13 7 10 7s7.16 3.13 7.82 4c-.66.87-3.95 4-7.82 4zm0-7a3 3 0 100 6 3 3 0 000-6z" />
                                                    </svg>
                                                </button>

                                                <!-- Edit Button -->
                                                <button @click="$store.editManagerModal.open(manager)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>

                                                <!-- Delete Button -->
                                                <button @click="$store.confirmRemoveModal.open(manager, 'manager')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
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
                        <div x-show="$store.managersListModal.filteredManagers.length === 0 && !$store.managersListModal.loading"
                            class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucun manager trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de recherche.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div x-show="$store.managersListModal.totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Affichage de <span x-text="(($store.managersListModal.currentPage - 1) * $store.managersListModal.itemsPerPage) + 1"></span> à
                                <span x-text="Math.min($store.managersListModal.currentPage * $store.managersListModal.itemsPerPage, $store.managersListModal.filteredManagers.length)"></span>
                                sur <span x-text="$store.managersListModal.filteredManagers.length"></span> résultats
                            </div>
                            <!-- Parties manquantes pour compléter le modal -->

                            <!-- Fin de la pagination (la partie coupée) -->
                            <div class="flex space-x-2">
                                <button @click="$store.managersListModal.goToPage($store.managersListModal.currentPage - 1)"
                                    :disabled="$store.managersListModal.currentPage === 1"
                                    :class="$store.managersListModal.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded transition-colors">
                                    Précédent
                                </button>

                                <!-- Numéros de pages -->
                                <template x-for="page in Array.from({length: $store.managersListModal.totalPages}, (_, i) => i + 1)" :key="page">
                                    <button @click="$store.managersListModal.goToPage(page)"
                                        :class="page === $store.managersListModal.currentPage ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                        class="px-3 py-1 text-sm rounded transition-colors"
                                        x-text="page">
                                    </button>
                                </template>

                                <button @click="$store.managersListModal.goToPage($store.managersListModal.currentPage + 1)"
                                    :disabled="$store.managersListModal.currentPage === $store.managersListModal.totalPages"
                                    :class="$store.managersListModal.currentPage === $store.managersListModal.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
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
                                <span x-text="$store.managersListModal.filteredManagers.length"></span> manager(s) affiché(s)
                            </div>
                            <div class="flex space-x-3">
                                <button @click="$store.managersListModal.loadManagers()"
                                    class="px-4 py-2 text-sm bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-500 transition-colors">
                                    <svg class="w-4 h-4 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                    </svg>
                                    Actualiser
                                </button>
                                <button @click="$store.managersListModal.close()"
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
    <!-- Modal Affichage -->
    <div x-show="$store.managersListModal.profileView" x-transition class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto" x-cloak
        style="display: none;">
        <div @click.away="$store.managersListModal.profileClose()" class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl shadow-xl w-full max-w-4xl overflow-hidden">
            <!-- Header -->
            <?= $view->inc('components', 'admin/modal.header.php', [
                'title' => 'Profil',
                'subtitle' => '',
                'close' => '$store.managersListModal.profileClose()',
            ]); ?>

            <!-- Content -->
            <div x-show="$store.managersListModal.manager" class="flex flex-col md:flex-row">
                <!-- Sidebar -->
                <div class="bg-gray-50 dark:bg-gray-800 p-6 w-full md:w-1/3 text-center">
                    <div class="relative w-24 h-24 mx-auto mb-3">
                        <img :src="'/Assets/images/user-default.png'" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                    </div>
                    <h3 class="font-bold text-lg mb-2" x-text="$store.managersListModal.manager?.fullname"></h3>

                    <div class="mt-4">
                        <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs uppercase font-medium" x-text="$store.managersListModal.manager?.type || 'Manager'"></span>
                    </div>
                </div>

                <!-- Détails -->
                <div class="p-6 w-full md:w-2/3">
                    <h4 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <p x-text="'Profil ' +$store.managersListModal.manager?.fullname"></p>

                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">ID Utilisateur</p>
                            <p class="font-medium text-base text-blue-600" x-text="'#' + $store.managersListModal.manager?.id"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Membre depuis</p>
                            <p class="font-medium text-base" x-text="$store.managersListModal.formatDate($store.managersListModal.manager?.created_at)"></p>
                        </div>
                        <template x-if="$store.managersListModal.manager?.last_login">
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <p class="text-sm text-gray-500 mb-1">Dernière connexion</p>
                                <p class="font-medium text-base" x-text="$store.managersListModal.formatDateTime($store.managersListModal.manager.last_login)"></p>
                            </div>
                        </template>

                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Status du compte</p>
                            <p class="font-medium text-base">
                                <template x-if="$store.managersListModal.manager?.status">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        Actif
                                    </span>
                                </template>
                                <template x-if="!$store.managersListModal.manager?.status">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle text-red-500 mr-1"></i>
                                        Inactif
                                    </span>
                                </template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div x-show="$store.managersListModal.manager" class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                <button @click="$store.editManagerModal.open($store.managersListModal.manager)"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Modifier
                </button>
                <button @click="$store.managersListModal.profileClose()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times mr-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>