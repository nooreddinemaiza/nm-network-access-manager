<?php

use Core\Helper\AssetHelper;

$view->layout('layouts', 'main.php');
$view->section('styles');
?>
<style>
    /* ── Blobs animés ── */
    .hero-blob {
        position: absolute;
        border-radius: 60% 40% 70% 30% / 50% 60% 40% 50%;
        filter: blur(72px);
        opacity: 0.30;
        animation: morphBlob 12s ease-in-out infinite alternate;
        pointer-events: none;
    }

    @keyframes morphBlob {
        0% {
            border-radius: 60% 40% 70% 30% / 50% 60% 40% 50%;
            transform: rotate(0deg) scale(1);
        }

        100% {
            border-radius: 40% 60% 30% 70% / 60% 40% 70% 30%;
            transform: rotate(10deg) scale(1.10);
        }
    }

    .dark .hero-blob {
        opacity: 0.15;
    }

    /* ── Grille décorative ── */
    .deco-grid {
        background-image:
            linear-gradient(currentColor 1px, transparent 1px),
            linear-gradient(90deg, currentColor 1px, transparent 1px);
        background-size: 56px 56px;
    }

    /* ── Underline animé pour les liens nav ── */
    .nav-link {
        position: relative;
        padding-bottom: 2px;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 1px;
        background: currentColor;
        transition: width .3s ease;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    /* ── Cards ── */
    .card-lift {
        transition: transform .35s cubic-bezier(.25, .8, .25, 1), box-shadow .35s ease;
    }

    .card-lift:hover {
        transform: translateY(-6px);
        box-shadow: 0 24px 48px rgba(0, 0, 0, .10);
    }

    /* ── Entrées décalées ── */
    .fade-up {
        opacity: 0;
        animation: fadeUp .8s ease forwards;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(28px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .d1 {
        animation-delay: .10s;
    }

    .d2 {
        animation-delay: .25s;
    }

    .d3 {
        animation-delay: .40s;
    }

    .d4 {
        animation-delay: .55s;
    }

    .d5 {
        animation-delay: .75s;
    }

    /* ── Ticket / badge WiFi ── */
    .wifi-badge {
        background: repeating-linear-gradient(-45deg,
                transparent,
                transparent 6px,
                rgba(0, 0, 0, .04) 6px,
                rgba(0, 0, 0, .04) 12px);
    }

    .dark .wifi-badge {
        background: repeating-linear-gradient(-45deg,
                transparent,
                transparent 6px,
                rgba(255, 255, 255, .04) 6px,
                rgba(255, 255, 255, .04) 12px);
    }

    /* ── Signal WiFi animé ── */
    .signal-ring {
        position: absolute;
        border-radius: 50%;
        border: 2px solid;
        animation: signalPulse 2.5s ease-out infinite;
        opacity: 0;
    }

    .signal-ring:nth-child(2) {
        animation-delay: .6s;
    }

    .signal-ring:nth-child(3) {
        animation-delay: 1.2s;
    }

    @keyframes signalPulse {
        0% {
            transform: scale(.5);
            opacity: .7;
        }

        100% {
            transform: scale(2.5);
            opacity: 0;
        }
    }

    /* ── Steps connecteurs ── */
    .step-connector {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, #3D6B4F44, #C9A84C44);
    }

    .dark .step-connector {
        background: linear-gradient(90deg, #C9A84C44, #3D6B4F44);
    }
</style>
<?php
$view->endSection();
$view->section('content');
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<header class="fixed top-0 left-0 right-0 z-50 backdrop-blur-md
               border-b border-ink/10 dark:border-cream/10
               bg-amber-50 dark:bg-slate-800
               transition-colors duration-500">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between ">

        <!-- Logo -->
        <div class="w-8 h-8 bg-moss dark:bg-gold rounded-full flex items-center justify-center transition-all duration-300 group-hover:scale-110">
            <?= $logo_url ?>
        </div>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-8">
            <a href="#" class="nav-link text-sm font-medium text-ink/80 dark:text-cream/80
                               hover:text-ink dark:hover:text-cream transition-colors">Accueil</a>
            <a href="#fonctionnement" class="nav-link text-sm font-medium text-ink/80 dark:text-cream/80
                               hover:text-ink dark:hover:text-cream transition-colors">Comment ça marche</a>
            <a href="/about" class="nav-link text-sm font-medium text-ink/80 dark:text-cream/80
                               hover:text-ink dark:hover:text-cream transition-colors">À propos</a>
        </nav>

        <!-- Right controls -->
        <div class="flex items-center gap-3">
            <!-- CTA -->
            <a href="/user/login" class="hidden md:inline-flex items-center gap-1.5 px-4 py-2 rounded-full
                            bg-moss dark:bg-gold text-cream dark:text-ink text-sm font-medium
                            hover:opacity-90 transition-all duration-200 hover:scale-105">
                Votre espace
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>

            <!-- Mobile burger -->
            <button @click="menuOpen = !menuOpen"
                class="md:hidden p-1.5 rounded-md hover:bg-ink/10 dark:hover:bg-cream/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!menuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="menuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="menuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden border-t border-ink/10 dark:border-cream/10 px-6 py-4 flex flex-col gap-4">
        <a href="#" class="text-sm font-medium text-ink/80 dark:text-cream/80">Accueil</a>
        <a href="#fonctionnement" class="text-sm font-medium text-ink/80 dark:text-cream/80">Comment ça marche</a>
        <a href="#apropos" class="text-sm font-medium text-ink/80 dark:text-cream/80">À propos</a>
        <a href="/user/login" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full
                                bg-moss dark:bg-gold text-cream dark:text-ink text-sm font-medium w-fit">
            Votre espace
        </a>
    </div>
</header>


<!-- ═══════════════════ HERO ═══════════════════ -->
<main>
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20">

        <!-- Blobs -->
        <div class="hero-blob w-[520px] h-[520px] bg-moss dark:bg-gold -top-24 -left-32"></div>
        <div class="hero-blob w-80 h-80 bg-blush dark:bg-moss/50 bottom-16 -right-20" style="animation-delay:-4s"></div>
        <div class="hero-blob w-56 h-56 bg-gold/50 dark:bg-blush/20 top-12 right-1/3" style="animation-delay:-8s"></div>

        <!-- Grille déco -->
        <div class="absolute inset-0 deco-grid opacity-[0.03] dark:opacity-[0.05] text-ink dark:text-cream"></div>

        <div class="relative max-w-5xl mx-auto px-6 text-center">

            <?php if ($connected): ?>
                <!-- Badge statut -->
                <div class="fade-up d1 inline-flex items-center gap-2 px-3 py-1.5 mb-8 rounded-full
                    border border-ink/15 dark:border-cream/15
                    bg-cream/60 dark:bg-ink/60 backdrop-blur-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-moss dark:bg-gold opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-moss dark:bg-gold"></span>
                    </span>
                    <span class="text-xs font-medium text-ink/70 dark:text-cream/70 tracking-wide uppercase">
                        Réseau actif — Connexion disponible
                    </span>
                </div>
            <?php endif; ?>

            <!-- Titre -->
            <h1 class="fade-up d2 font-display text-5xl md:text-7xl lg:text-8xl font-bold
                   leading-[0.92] tracking-tight mb-6 text-ink dark:text-cream">
                Votre accès<br />
                <em class="not-italic text-moss dark:text-gold">WiFi sécurisé</em><br />
                en un clic
            </h1>
            <?php if ($sub_title): ?>
                <!-- Sous-titre -->
                <p class="fade-up d3 max-w-xl mx-auto text-base md:text-lg
                  text-ink/60 dark:text-cream/60 leading-relaxed mb-10">
                    <?php echo ($sub_title); ?>
                </p>
            <?php endif; ?>

            <!-- CTA pair -->
            <div class="fade-up d4 flex flex-col sm:flex-row items-center justify-center gap-4 mb-20">
                <a href="<?= "http://pfsense.idosr.net:8002/index.php?zone=ista" ?>" target="_blank"
                    class="group flex items-center gap-2 px-7 py-3.5 rounded-full
                      bg-ink dark:bg-cream text-cream dark:text-ink
                      font-medium text-sm hover:opacity-90 transition-all duration-200
                      hover:scale-105 shadow-lg shadow-ink/20 dark:shadow-cream/10">
                    Accéder au réseau
                    <span class="group-hover:translate-x-1 transition-transform duration-200">→</span>
                </a>
                <a href="#fonctionnement"
                    class="flex items-center gap-2 px-7 py-3.5 rounded-full
                      border border-ink/20 dark:border-cream/20
                      text-ink/70 dark:text-cream/70 font-medium text-sm
                      hover:bg-ink/5 dark:hover:bg-cream/5 transition-all duration-200">
                    Voir comment ça marche
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            </div>

            <div class="fade-up d5 flex justify-center">
                <div class="relative w-52 h-52 flex items-center justify-center">
                    <?= $logo_url ?>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════ COMMENT ÇA MARCHE ═══════════════════ -->
    <section id="fonctionnement" class="py-28 bg-blush/25 dark:bg-cream/5 transition-colors duration-500">
        <div class="max-w-5xl mx-auto px-6">

            <div class="text-center mb-16">
                <span class="text-xs font-medium tracking-widest uppercase text-moss dark:text-gold mb-3 block">
                    Processus
                </span>
                <h2 class="font-display text-4xl md:text-5xl font-bold text-ink dark:text-cream leading-tight">
                    Connectez-vous en <em class="not-italic text-moss dark:text-gold">3 étapes</em>
                </h2>
            </div>

            <!-- Steps -->
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6 md:gap-0">

                <!-- Étape 1 -->
                <div class="flex-1 flex flex-col items-center text-center px-4">
                    <div class="w-14 h-14 rounded-2xl bg-moss dark:bg-gold flex items-center justify-center mb-4 shadow-lg shadow-moss/25 dark:shadow-gold/25">
                        <svg class="w-6 h-6 text-cream dark:text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                    </div>
                    <div class="w-7 h-7 rounded-full border-2 border-moss dark:border-gold
                            flex items-center justify-center mb-3
                            text-xs font-bold text-moss dark:text-gold">1</div>
                    <h3 class="font-semibold text-sm text-ink dark:text-cream mb-2">Connectez-vous au WiFi</h3>
                    <p class="text-xs text-ink/55 dark:text-cream/55 leading-relaxed">
                        Choisissez le réseau INFO1 ou INFO2 dans la liste des réseaux disponibles sur votre appareil.
                    </p>
                </div>

                <div class="step-connector hidden md:block"></div>

                <!-- Étape 2 -->
                <div class="flex-1 flex flex-col items-center text-center px-4">
                    <div class="w-14 h-14 rounded-2xl bg-moss/80 dark:bg-gold/80 flex items-center justify-center mb-4 shadow-lg shadow-moss/20 dark:shadow-gold/20">
                        <svg class="w-6 h-6 text-cream dark:text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="w-7 h-7 rounded-full border-2 border-moss dark:border-gold
                            flex items-center justify-center mb-3
                            text-xs font-bold text-moss dark:text-gold">2</div>
                    <h3 class="font-semibold text-sm text-ink dark:text-cream mb-2">Identifiez-vous</h3>
                    <p class="text-xs text-ink/55 dark:text-cream/55 leading-relaxed">
                        Le portail captif s'ouvre automatiquement. Entrez vos identifiants ou créez un compte en quelques secondes.
                    </p>
                </div>

                <div class="step-connector hidden md:block"></div>

                <!-- Étape 3 -->
                <div class="flex-1 flex flex-col items-center text-center px-4">
                    <div class="w-14 h-14 rounded-2xl bg-moss/60 dark:bg-gold/60 flex items-center justify-center mb-4 shadow-lg shadow-moss/15 dark:shadow-gold/15">
                        <svg class="w-6 h-6 text-cream dark:text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="w-7 h-7 rounded-full border-2 border-moss dark:border-gold
                            flex items-center justify-center mb-3
                            text-xs font-bold text-moss dark:text-gold">3</div>
                    <h3 class="font-semibold text-sm text-ink dark:text-cream mb-2">Naviguez librement</h3>
                    <p class="text-xs text-ink/55 dark:text-cream/55 leading-relaxed">
                        Accès internet immédiat, consultez votre consommation depuis le tableau de bord personnel.
                    </p>
                </div>
            </div>
            <div class="mt-5 text-center">
                <p>
                    <span class="text-red">Pas de compte?</span>, Contactez l'administrateur
                </p>
            </div>
        </div>
    </section>


    <!-- ═══════════════════ CTA FINAL ═══════════════════ -->
    <section class="py-24 bg-ink dark:bg-gold/10 transition-colors duration-500 relative overflow-hidden">
        <div class="absolute inset-0 opacity-5"
            style="background: radial-gradient(ellipse at 50% 50%, #C9A84C 0%, transparent 70%);"></div>
        <div class="relative max-w-2xl mx-auto px-6 text-center">
            <h2 class="font-display text-4xl md:text-5xl font-bold text-cream dark:text-cream mb-4 leading-tight">
                Prêt à vous <em class="not-italic text-gold">connecter</em> ?
            </h2>
            <p class="text-cream/60 mb-8 leading-relaxed">
                Naviguer librement toute la journée
            </p>
            <a href="<?= "http://pfsense.idosr.net:8002/index.php?zone=ista" ?>"
                class="inline-flex items-center gap-2 px-7 py-4 rounded-full
                  bg-gold text-ink font-semibold text-sm
                  hover:bg-gold/90 hover:scale-105 transition-all duration-200
                  shadow-xl shadow-gold/30">
                Accéder au portail
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </section>
</main>


<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="bg-cream dark:bg-ink border-t border-ink/10 dark:border-cream/10 transition-colors duration-500">
    <div class="max-w-6xl mx-auto px-6 py-16">
        <!-- Bottom bar -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4
                    pt-8 border-t border-ink/10 dark:border-cream/10">
            <p class="text-xs text-ink/40 dark:text-cream/40">
                <?php if ($name): ?>
                    <span class="font-display font-bold text-ink dark:text-cream">
                        <?= $name . ". " ?>
                    </span>
                <?php endif; ?> Tous droits réservés.
            </p>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-moss dark:bg-gold animate-pulse"></span>
                <span class="text-xs text-ink/40 dark:text-cream/40">Réseau opérationnel</span>
            </div>
        </div>
    </div>
</footer>

<?php
$view->endSection();

$view->section('footer');
$view->inc('partials', 'footer.php');
$view->endSection();

$view->section('scripts');
echo AssetHelper::scripts(['scripts:main.js']);
$view->endSection();
