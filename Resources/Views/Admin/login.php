<?php
$view->layout('layouts', 'main.php');
$view->section("content")
?>
<div class="flex mt-6 mb-6 pt-4 pb-6 items-center justify-center dark:bg-gray-900">

    <div class="w-full max-w-md border border-blue-500 shadow-blue-300 dark:border-blue-950 dark:shadow-blue-950 rounded-lg shadow-lg p-8" x-data="{
        <?= $view->e($type_name ?? 'email') ?>: '<?= $view->e($old[$type_name ?? 'email'] ?? '') ?>',
        type:'<?= $view->e($type ?? 'email') ?>',
        password: '',
        rememberMe: false,
        showPassword: false,
        emailTouched: false,
        passwordTouched: false,
        get emailValid() {
        <?php if ($view->e($type ?? 'email') == 'email'): ?>
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email);
        <?php else: ?>
            return true;
        <?php endif; ?>
        },
        get passwordValid() {
            return this.password.length >= 6;
        },
        get formValid() {
            return this.emailValid && this.passwordValid;
        }
        }">
        <div style="display: block;max-width: 75px;margin: 0 auto;">
            <?= $logo_url ?>
        </div>
        <h1 class="text-2xl font-bold text-center mb-6">
            <?= $view->e($title ?? 'Connexion Administrateur') ?>
        </h1>

        <?php if (!empty($errors)): ?>
            <!-- Messages d'erreur globaux -->
            <?php
            $hasGlobalError = isset($errors['global']) || isset($errors['credentials']) || isset($errors['status']) || isset($errors['csrf']);
            $isBlocked = isset($errors['global']) && is_array($errors['global'])
            ?>

            <?php if ($hasGlobalError): ?>
                <div class="<?= $isBlocked ? 'bg-red-100 border-red-500' : 'bg-red-100 border border-red-400' ?> text-red-700 rounded mb-4 p-4">
                    <div class="flex items-start">
                        <?php if ($isBlocked): ?>
                            <svg class="w-6 h-6 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.351-.166-2A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        <?php endif; ?>
                        <div class="flex-1">
                            <?php if ($isBlocked): ?>
                                <h3 class="font-bold text-red-900 text-lg mb-2">⚠️ Compte temporairement bloqué</h3>
                            <?php endif; ?>

                            <?php foreach (['global', 'credentials', 'status', 'csrf'] as $errorType): ?>
                                <?php if (isset($errors[$errorType])): ?>
                                    <?php $messages = is_array($errors[$errorType]) ? $errors[$errorType] : [$errors[$errorType]]; ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <p class="<?= $isBlocked ? 'text-base' : '' ?>"><?= $view->e(is_array($msg) ? implode(',', $msg) : $msg) ?></p>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($isBlocked): ?>
                                <div class="mt-3 pt-3 border-t border-red-300">
                                    <p class="text-sm text-red-600">
                                        💡 <strong>Conseil:</strong> Vérifiez votre <?= $type_label ?? 'email' ?> et mot de passe, puis réessayez après le délai.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <?php if (!$isBlocked ?? true): ?>
            <form method="post" action="<?= $view->e($action ?? '/dashboard/login') ?>"
                @submit="emailTouched = true; passwordTouched = true;"
                x-cloak>

                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= $old["csrf_token"] ?? $csrf_token ?>">

                <!-- Champ Email -->
                <div class="mb-4">
                    <label for="email" class="block  text-gray-900 dark:text-white font-medium mb-2">
                        <?= $view->e($type_label ?? 'Adresse e-mail') ?>
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="email"
                        type="<?= $view->e($type ?? 'email') ?>"
                        required
                        name="<?= $view->e($type_name ?? 'email') ?>"
                        x-model="<?= $view->e($type_name ?? 'email') ?>"
                        @blur="emailTouched = true"
                        :class="{
                            'border-green-500 focus:ring-green-300': emailTouched && emailValid,
                            'border-red-500 focus:ring-red-300': emailTouched && !emailValid,
                            'border-gray-300': !emailTouched
                        }"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 text-slate-800 transition"
                        placeholder=<?= $view->e($type_name ?? "admin@example.com") ?>
                        autocomplete="<?= $view->e($type ?? 'email') ?>">

                    <?php if (isset($errors[$type_name ?? 'email'])): ?>
                        <!-- Erreur de validation côté serveur -->
                        <?php $emailErrors = is_array($errors[$type_name ?? 'email']) ? $errors[$type_name ?? 'email'] : [$errors[$type_name ?? 'email']]; ?>
                        <?php foreach ($emailErrors as $error): ?>
                            <p class="text-red-600 text-sm mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <?= $view->e($error) ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <template x-if="emailTouched && !emailValid">
                        <p class="text-red-600 text-sm mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <?= $view->e($type_notification ?? "Veuillez saisir une adresse e-mail valide.") ?>

                        </p>
                    </template>

                    <!-- Indicateur de validation -->
                    <template x-if="emailTouched && emailValid">
                        <p class="text-green-600 text-sm mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <?= $view->e($type_name ?? "Email") ?> valide
                        </p>
                    </template>
                </div>

                <!-- Champ Mot de passe -->
                <div class="mb-4">
                    <label for="password" class="block  text-gray-900 dark:text-white font-medium mb-2">
                        Mot de passe
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            x-model="password"
                            @blur="passwordTouched = true"
                            :class="{
                                'border-green-500 focus:ring-green-300': passwordTouched && passwordValid,
                                'border-red-500 focus:ring-red-300': passwordTouched && !passwordValid,
                                'border-gray-300': !passwordTouched
                            }"
                            class="w-full px-4 py-2 pr-12 border rounded-lg focus:outline-none focus:ring-2 text-slate-800 transition"
                            placeholder="••••••••"
                            autocomplete="current-password">

                        <!-- Bouton afficher/masquer mot de passe -->
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition"
                            tabindex="-1">
                            <!-- Icône œil barré (masqué) -->
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                            </svg>
                            <!-- Icône œil ouvert (visible) -->
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Erreur de validation côté serveur -->
                    <?php if (isset($errors['password'])): ?>
                        <?php $passwordErrors = is_array($errors['password']) ? $errors['password'] : [$errors['password']]; ?>
                        <?php foreach ($passwordErrors as $error): ?>
                            <p class="text-red-600 text-sm mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <?= $view->e($error) ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Erreur de validation côté client -->
                    <template x-if="passwordTouched && !passwordValid">
                        <p class="text-red-600 text-sm mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Le mot de passe doit contenir au moins 6 caractères.
                        </p>
                    </template>

                    <!-- Indicateur de validation -->
                    <template x-if="passwordTouched && passwordValid">
                        <p class="text-green-600 text-sm mt-1 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Mot de passe valide
                        </p>
                    </template>
                </div>
                <!-- Bouton de soumission -->
                <button
                    type="submit"
                    :disabled="!formValid"
                    :class="{
                        'bg-blue-600 hover:bg-blue-700 cursor-pointer': formValid,
                        'bg-gray-400 cursor-not-allowed': !formValid
                    }"
                    class="w-full text-white py-3 rounded-lg font-medium transition duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Se connecter
                </button>
            </form>

            <!-- Message d'aide -->
            <div class="mt-6 text-center text-sm  text-gray-600 dark:text-white ">
                <p>Première connexion ? Contactez l'administrateur système.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$view->endSection();

$view->section('footer');
$view->inc('partials', 'footer.php', []);
$view->endSection();
?>