<?php

$view->layout('layouts', 'error.php'); ?>
<title><?= $title ?></title>
<?php
$view->section('meta');
$view->endSection();

$view->section('content');

if ($error == 404): ?>
    <!-- Page 404 - Non trouvé -->
    <div id="page-404" class="error-page min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-search text-6xl text-indigo-500 mb-4"></i>
                <h1 class="text-8xl font-bold text-indigo-600 mb-2">404</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?= $title ?? "Page non trouvée" ?></h2>
                <p class="text-gray-600 mb-8"><?= $message ?? "Désolé, la page que vous recherchez n'existe pas ou a été déplacée." ?></p>
            </div>
        </div>
    </div>
<?php elseif ($error == 500): ?>
    <!-- Page 500 - Erreur serveur -->
    <div id="page-500" class="error-page  min-h-screen bg-gradient-to-br from-red-50 to-pink-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-server text-6xl text-red-500 mb-4"></i>
                <h1 class="text-8xl font-bold text-red-600 mb-2">500</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Erreur serveur interne</h2>
                <p class="text-gray-600 mb-8">Une erreur s'est produite sur nos serveurs. Nous travaillons à résoudre le problème.</p>
            </div>
        </div>
    </div>
<?php elseif ($error == 403): ?>
    <!-- Page 403 - Accès refusé -->
    <div id="page-403" class="error-page  min-h-screen bg-gradient-to-br from-yellow-50 to-orange-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-lock text-6xl text-yellow-600 mb-4"></i>
                <h1 class="text-8xl font-bold text-yellow-600 mb-2">403</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Accès refusé</h2>
                <p class="text-gray-600 mb-8">Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.</p>
            </div>
        </div>
    </div>
<?php elseif ($error == 503): ?>
    <!-- Page 503 - Service indisponible -->
    <div id="page-503" class="error-page  min-h-screen bg-gradient-to-br from-purple-50 to-indigo-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-tools text-6xl text-purple-500 mb-4"></i>
                <h1 class="text-8xl font-bold text-purple-600 mb-2">503</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Service indisponible</h2>
                <p class="text-gray-600 mb-8">Le service est temporairement indisponible pour maintenance. Merci de réessayer plus tard.</p>
            </div>
            <div class="bg-white rounded-lg p-4 mb-6 border border-purple-200">
                <div class="flex items-center justify-center text-purple-600">
                    <i class="fas fa-clock mr-2"></i>
                    <span class="text-sm font-medium">Maintenance en cours...</span>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($error == 401): ?>
    <!-- Page 401 - Non autorisé -->
    <div id="page-401" class="error-page  min-h-screen bg-gradient-to-br from-gray-50 to-blue-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-user-slash text-6xl text-gray-500 mb-4"></i>
                <h1 class="text-8xl font-bold text-gray-600 mb-2">401</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Non autorisé</h2>
                <p class="text-gray-600 mb-8">
                    Vous devez vous
                    <a href="/login" class="text-blue-600 hover:underline font-semibold transition-colors duration-200">connecter</a>
                    pour accéder à cette page.
                </p>
            </div>
        </div>
    </div>
<?php elseif ($error == "desactive_account"): ?>
    <!-- Page Compte non activé -->
    <div id="page-non-active-user" class="error-page min-h-screen bg-gradient-to-br from-yellow-50 to-orange-100 flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <i class="fas fa-user-clock text-6xl text-yellow-600 mb-4"></i>
                <h1 class="text-6xl font-bold text-yellow-600 mb-2">Compte non activé</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Votre compte est désactivé</h2>
                <p class="text-gray-600 mb-8">
                    Veuillez contacter votre administrateur<br>
                </p>
            </div>
        </div>
    </div>
<?php elseif ($error == "invite"): ?>
    <div class="flex items-center justify-center min-h-[calc(100vh-80px)] px-4 py-12">

        <div
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 100)"
            x-show="show"
            x-transition:enter="transition ease-out duration-700 transform"
            x-transition:enter-start="opacity-0 scale-90 -translate-y-8"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            class="w-full max-w-lg">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 transition-all duration-500">
                <!-- Barre supérieure orange -->
                <div class="h-2 bg-gradient-to-r from-orange-500 via-orange-400 to-orange-500"></div>

                <div class="p-10 text-center">
                    <!-- Icône animée -->
                    <div class="relative inline-flex mb-6">
                        <div class="absolute inset-0 bg-orange-500/20 rounded-full animate-ping"></div>
                        <div class="relative w-20 h-20 bg-gradient-to-br from-orange-100 to-orange-50 dark:from-orange-900/30 dark:to-orange-800/20 rounded-full flex items-center justify-center ring-4 ring-orange-500/20">
                            <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Titre -->
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Invitation non disponible
                    </h3>

                    <!-- Message d'erreur -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6 mb-6 border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                            <?= htmlspecialchars($data['message']) ?>
                        </p>
                    </div>

                    <!-- Séparateur -->
                    <div class="flex items-center justify-center mb-6">
                        <div class="h-px bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-600 to-transparent w-full"></div>
                    </div>

                    <!-- Message de contact -->
                    <div class="flex items-start space-x-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-5 border border-blue-200 dark:border-blue-800">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-blue-800 dark:text-blue-200 text-left leading-relaxed">
                            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur du groupe pour obtenir un nouveau lien d'invitation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif;

$view->endSection();
$view->section('footer');
$view->inc('partials', 'footer.php', []);
$view->endSection();
$view->section('scripts');

$view->endSection();
?>