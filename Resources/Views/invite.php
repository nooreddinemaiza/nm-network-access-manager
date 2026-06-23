<?php

use Core\Helper\Helper;

$view->layout('layouts', 'main.php');

$view->section('meta');
?>

<meta name="invite-csrf" content="<?= $csrf_token ?>">
<link rel="shortcut icon" href="/assets/images/favicon.ico" type="image/x-icon">
<?php
$view->endSection();

$view->section('content');

// Formatter la date d'expiration
$expiresAt = '';
if (!isset($data['exception']) && isset($invite['expires_at'])) {
    $remaining =  Helper::remainingTime($invite['expires_at']);
}
?>

<div x-data="pageData()" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex justify-between items-center">
            <h1 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-900 dark:text-white">
                Portail Captive - Invitation au Groupe
            </h1>
            <button
                @click="toggleDarkMode"
                class="p-2 sm:p-2.5 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                aria-label="Basculer le mode sombre">
                <svg x-show="!darkMode" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="darkMode" class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 md:py-12">
        <?php if (isset($data['exception'])): ?>
            <!-- Affichage de l'exception -->
            <div class="flex items-center justify-center min-h-[60vh] sm:min-h-[70vh]">
                <div
                    x-data="{ show: false }"
                    x-init="setTimeout(() => show = true, 100)"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="w-full max-w-md">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                        <!-- Barre supérieure -->
                        <div class="h-1 bg-orange-500"></div>

                        <div class="p-6 sm:p-8">
                            <!-- Icône -->
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Titre -->
                            <h3 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-4 text-center">
                                Invitation non disponible
                            </h3>

                            <!-- Message d'erreur -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 mb-6 border border-gray-200 dark:border-gray-700">
                                <p class="text-sm sm:text-base text-gray-700 dark:text-gray-300 text-center">
                                    <?= htmlspecialchars($data['exception']) ?>
                                </p>
                            </div>

                            <!-- Message de contact -->
                            <div class="flex items-start space-x-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs sm:text-sm text-blue-800 dark:text-blue-200">
                                    Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur du groupe pour obtenir un nouveau lien d'invitation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- En-tête d'information du groupe -->
            <div
                x-data="{ show: false }"
                x-init="setTimeout(() => show = true, 100)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mb-6 sm:mb-8">
                <div class="max-w-2xl mx-auto bg-blue-600 dark:bg-blue-700 rounded-lg p-6 sm:p-8 text-white shadow-md">
                    <div class="flex items-start space-x-4 mb-4 sm:mb-6">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-medium opacity-90 mb-1">Vous êtes invité à rejoindre</p>
                            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold break-words">
                                <?= htmlspecialchars($invite['group']) ?>
                                <?php if (!empty($invite['description'])): ?>
                                    <span class="text-lg">
                                        ( <?= htmlspecialchars($invite['description']) ?> )
                                    </span>
                                <?php endif; ?>
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="bg-white/20 rounded-lg px-3 py-2.5 flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full flex-shrink-0"></div>
                            <span class="font-medium truncate"><?= $invite['uses'] ?> / <?= $invite['max_uses'] ?> utilisations</span>
                        </div>
                        <div class="bg-white/20 rounded-lg px-3 py-2.5 flex items-center space-x-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-medium text-xs sm:text-sm">Expire dans: <span id="remaining"><?= $remaining ?></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulaire d'invitation -->
            <div
                x-data="inviteForm()"
                x-init="setTimeout(() => show = true, 200)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="max-w-md mx-auto">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                    <h3 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6 text-center">
                        Créer votre compte
                    </h3>

                    <form @submit.prevent="submitForm" class="space-y-5">
                        <!-- Nom complet -->
                        <div x-data="{ focused: false }">
                            <label
                                for="fullname"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Nom complet</span>
                                </span>
                            </label>
                            <input
                                type="text"
                                id="fullname"
                                x-model="formData.fullname"
                                @focus="focused = true"
                                @blur="focused = false"
                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
                                :class="{ 'border-red-500 dark:border-red-500': errors.fullname }"
                                placeholder="Entrez votre nom complet">
                            <p
                                x-show="errors.fullname"
                                x-transition
                                class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span x-text="errors.fullname"></span>
                            </p>
                        </div>

                        <!-- Nom d'utilisateur -->
                        <div x-data="{ focused: false }">
                            <label
                                for="username"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                    <span>Nom d'utilisateur</span>
                                </span>
                            </label>
                            <input
                                type="text"
                                id="username"
                                x-model="formData.username"
                                @focus="focused = true"
                                @blur="focused = false"
                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
                                :class="{ 'border-red-500 dark:border-red-500': errors.username }"
                                placeholder="Choisissez un nom d'utilisateur">
                            <p
                                x-show="errors.username"
                                x-transition
                                class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span x-text="errors.username"></span>
                            </p>
                        </div>

                        <!-- Mot de passe -->
                        <div x-data="{ focused: false, showPassword: false }">
                            <label
                                for="password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Mot de passe</span>
                                </span>
                            </label>
                            <div class="relative">
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    x-model="formData.password"
                                    @focus="focused = true"
                                    @blur="focused = false"
                                    class="w-full px-4 py-3 pr-11 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
                                    :class="{ 'border-red-500 dark:border-red-500': errors.password }"
                                    placeholder="Créez un mot de passe sécurisé">
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p
                                x-show="errors.password"
                                x-transition
                                class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span x-text="errors.password"></span>
                            </p>
                        </div>

                        <!-- Message d'erreur général -->
                        <div
                            x-show="generalError"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg p-4 flex items-start space-x-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p x-text="generalError" class="text-sm text-red-800 dark:text-red-200 font-medium"></p>
                        </div>

                        <!-- Bouton de soumission -->
                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-full py-3 sm:py-3.5 px-6 bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md flex items-center justify-center space-x-2">
                            <span x-show="!loading" class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>Rejoindre le groupe</span>
                            </span>
                            <span x-show="loading" class="flex items-center space-x-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Traitement...</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php
$view->endSection();
$view->section('scripts');

use Core\Helper\AssetHelper;

echo AssetHelper::scripts([
    'scripts:main.js',
    'scripts:invite.js',
]);

$view->endSection();

?>