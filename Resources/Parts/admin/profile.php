<div class="relative z-50">
    <!-- Modal Affichage -->
    <div x-show="profileView" x-transition class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center overflow-y-auto" x-cloak
        style="display: none;">
        <div @click.away="closeProfile()" class="bg-white dark:bg-black bg-opacity-60 text-gray-800 dark:text-gray-200 rounded-t-2xl shadow-xl w-full max-w-4xl overflow-hidden">
            <!-- Header -->
            <?= $view->inc('components', 'admin/modal.header.php', [
                'title' => 'Votre profil',
                'subtitle' => '',
                'close' => 'closeProfile()',
            ]); ?>
            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-20">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
                    <span>Chargement des données...</span>
                </div>
            </div>

            <!-- Content -->
            <div x-show="!loading && userData" class="flex flex-col md:flex-row">
                <!-- Sidebar -->
                <div class="bg-gray-50 dark:bg-gray-800 p-6 w-full md:w-1/3 text-center">
                    <div class="relative w-24 h-24 mx-auto mb-3">
                        <img :src="'/Assets/images/profile/user-default.png'" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg">
                    </div>
                    <h3 class="font-bold text-lg mb-2" x-text="userData?.fullname"></h3>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-center gap-2">
                            <i class="fas fa-envelope text-blue-500"></i>
                            <span class="truncate" x-text="userData?.email"></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-xs uppercase font-medium" x-text="userData?.type || 'Utilisateur'"></span>
                    </div>
                </div>
                <!-- Détails -->
                <div class="p-6 w-full md:w-2/3">
                    <h4 class="text-lg font-semibold mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Informations
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">ID Utilisateur</p>
                            <p class="font-medium text-base text-blue-600" x-text="'#' + userData?.id"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Membre depuis</p>
                            <p class="font-medium text-base" x-text="formatDate(userData?.created_at)"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div x-show="!loading && userData" class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                <button @click="closeProfile()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times mr-1"></i>Fermer
                </button>
                <button @click="openEditModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-edit mr-1"></i>Modifier
                </button>
                <button @click="openResetModal()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                    <i class="fas fa-lock mr-1"></i>Mot de passe
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Modification -->
    <div x-show="openEdit" x-transition class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center" x-cloak
        style="display: none;">
        <div @click.away="closeEditModal()" class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                    Modifier le profil
                </h2>
                <button @click="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Messages -->
            <div x-show="editMessage.show" class="mb-4">
                <div :class="editMessage.type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700'" class="border px-4 py-3 rounded">
                    <i :class="editMessage.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'" class="mr-2"></i>
                    <span x-text="editMessage.text"></span>
                </div>
            </div>

            <form @submit.prevent="submitEdit()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-medium">
                            <i class="fas fa-user mr-1 text-blue-500"></i>
                            Nom complet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="editForm.fullname" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" required>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'fullname']) ?>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium">
                            <i class="fas fa-envelope mr-1 text-blue-500"></i>
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" x-model="editForm.email" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200" required>
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'email']) ?>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium">
                            <i class="fas fa-user mr-1 text-blue-500"></i>
                            Username
                        </label>
                        <input type="text" x-model="editForm.username" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        <?php $view->inc('components', 'admin/input.error.php', ['input' => 'username']) ?>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" @click="closeEditModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white px-6 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" :disabled="editLoading" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-save mr-1"></i>
                        <span>Enregistrer</span>
                        <i x-show="editLoading" class="fas fa-spinner fa-spin ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Réinitialisation -->
    <div x-show="openReset" x-transition class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center" x-cloak
        style="display: none;">
        <div @click.away="closeResetModal()" class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-key text-orange-500 mr-2"></i>
                    Réinitialiser le mot de passe
                </h2>
                <button @click="closeResetModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Messages -->
            <div x-show="resetMessage.show" class="mb-4">
                <div :class="resetMessage.type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700'" class="border px-4 py-3 rounded">
                    <i :class="resetMessage.type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'" class="mr-2"></i>
                    <span x-text="resetMessage.text"></span>
                </div>
            </div>

            <form @submit.prevent="submitReset()">
                <!-- Nouveau mot de passe -->
                <div class="mb-4">
                    <label for="newPassword" class="block text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-1"></i>Nouveau mot de passe
                    </label>
                    <div class="relative">
                        <input
                            id="newPassword"
                            :type="showNewPassword ? 'text' : 'password'"
                            x-model="resetForm.password"
                            required
                            minlength="8"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-gray-800"
                            placeholder="Entrez le nouveau mot de passe">
                        <button
                            type="button"
                            @click="showNewPassword = !showNewPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 caractères</p>
                </div>

                <!-- Confirmation du mot de passe -->
                <div class="mb-6">
                    <label for="confirmPassword" class="block text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-1"></i>Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <input
                            id="confirmPassword"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            x-model="resetForm.password_confirmation"
                            required
                            class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white dark:bg-gray-800"
                            placeholder="Confirmez le mot de passe">
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                    <p x-show="resetForm.password && resetForm.password_confirmation && resetForm.password !== resetForm.password_confirmation"
                        class="text-xs text-red-500 mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Les mots de passe ne correspondent pas
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeResetModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="resetLoading || resetForm.password !== resetForm.password_confirmation || !resetForm.password"
                        class="bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-check mr-1"></i>
                        <span>Réinitialiser</span>
                        <i x-show="resetLoading" class="fas fa-spinner fa-spin ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>