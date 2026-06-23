<?php
$total_steps = $total_steps ?? 2;
$titre = "";
switch ($step) {
    case 0:
        $titre = "Bienvenue & Pré-requis";
        break;
    case 1:
        $titre = "Configuration de la base de données";
        break;
    case 2:
        $titre = "Création de l'administrateur";
        break;
    case 15:
        $titre = "Vérification & Migration de la base de données";
        break;
    default:
        $titre = "Étape inconnue";
        break;
}
$view->layout('layouts', 'main.php');
$view->section('content');
?>

<style>
    /* ── étape 0 : variables & helpers ── */
    :root {
        --c-bg: #0d1117;
        --c-surface: #161b22;
        --c-border: #30363d;
        --c-accent: #3b82f6;
        --c-accent2: #8b5cf6;
        --c-green: #22c55e;
        --c-yellow: #f59e0b;
        --c-red: #ef4444;
        --c-text: #e6edf3;
        --c-muted: #8b949e;
        --c-code-bg: #1e2430;
    }

    .welcome-wrap * {
        box-sizing: border-box;
    }

    .welcome-wrap {
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        color: var(--c-text);
    }

    /* badge pill */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .65rem;
        border-radius: 9999px;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .03em;
        border: 1px solid;
    }

    .pill-blue {
        background: #1d3461;
        border-color: #3b82f6;
        color: #93c5fd;
    }

    .pill-green {
        background: #14532d;
        border-color: #22c55e;
        color: #86efac;
    }

    .pill-yellow {
        background: #451a03;
        border-color: #f59e0b;
        color: #fcd34d;
    }

    .pill-red {
        background: #450a0a;
        border-color: #ef4444;
        color: #fca5a5;
    }

    .pill-violet {
        background: #2e1065;
        border-color: #8b5cf6;
        color: #c4b5fd;
    }

    /* section card */
    .req-card {
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        border-radius: 1rem;
        padding: 1.4rem 1.6rem;
    }

    .req-card+.req-card {
        margin-top: 1rem;
    }

    /* code-inline */
    .ci {
        font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
        background: var(--c-code-bg);
        border: 1px solid var(--c-border);
        border-radius: .35rem;
        padding: .05rem .4rem;
        font-size: .78rem;
        color: #a5d6ff;
    }

    /* checklist row */
    .chk-row {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .6rem 0;
        border-bottom: 1px solid var(--c-border);
    }

    .chk-row:last-child {
        border-bottom: none;
    }

    .chk-icon {
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: .1rem;
    }

    /* accordion */
    details summary {
        cursor: pointer;
        list-style: none;
    }

    details summary::-webkit-details-marker {
        display: none;
    }

    details[open] .acc-arrow {
        transform: rotate(90deg);
    }

    .acc-arrow {
        transition: transform .2s;
    }

    /* progress dots */
    .step-dots {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .step-dot {
        width: .6rem;
        height: .6rem;
        border-radius: 50%;
        background: var(--c-border);
    }

    .step-dot.active {
        background: var(--c-accent);
    }

    .step-dot.done {
        background: var(--c-green);
    }

    /* CTA button */
    .btn-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        width: 100%;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        border-radius: 1rem;
        cursor: pointer;
        transition: opacity .15s, transform .1s;
    }

    .btn-cta:hover {
        opacity: .9;
        transform: translateY(-1px);
    }

    .btn-cta:active {
        transform: scale(.98);
    }
</style>

<div class="hero bg-gray-900 text-white py-16 px-6 text-center">
    <h1 class="text-4xl font-bold mb-4"><?= $view->e($title ?? "Configuration de l'interface de gestion web") ?></h1>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 flex items-center justify-center p-4"
        style="background: #0d1117 !important;">

        <div class="w-full" style="max-width: 760px;">

            <!-- ══════════════════════════════════════
                 ÉTAPE 0 — BIENVENUE & PRÉ-REQUIS
            ══════════════════════════════════════ -->
            <?php if ($step == 0): ?>
                <div class="welcome-wrap">

                    <!-- En-tête -->
                    <div style="text-align:center; padding: 2rem 0 1.5rem;">
                        <div style="display:inline-flex;align-items:center;gap:.6rem;
                                background:#161b22;border:1px solid #30363d;
                                border-radius:9999px;padding:.4rem 1rem;margin-bottom:1.2rem;">
                            <svg width="16" height="16" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944
                                     a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0
                                     5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03
                                     9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span style="font-size:.78rem;font-weight:600;color:#93c5fd;letter-spacing:.04em;">
                                INSTALLATION GUIDÉE
                            </span>
                        </div>

                        <h2 style="font-size:2rem;font-weight:800;
                               background:linear-gradient(135deg,#e6edf3,#8b5cf6);
                               -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                               margin:0 0 .6rem;">
                            Portail Captif Manager
                        </h2>
                        <p style="color:#8b949e;font-size:.95rem;max-width:480px;margin:0 auto;">
                            Plateforme de gestion centralisée des utilisateurs FreeRADIUS.
                            Avant de démarrer, lisez attentivement les pré-requis ci-dessous.
                        </p>
                    </div>

                    <!-- Steps overview -->
                    <div style="display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap;">
                        <?php
                        $steps_overview = [
                            ['0', 'Pré-requis',    'pill-blue'],
                            ['1', 'Base de données', 'pill-blue'],
                            ['1.5', 'Migration',      'pill-blue'],
                            ['2', 'Administrateur', 'pill-blue'],
                        ];
                        foreach ($steps_overview as $s):
                        ?>
                            <span class="pill <?= $s[2] ?>">
                                <span style="opacity:.6">Étape <?= $s[0] ?></span>
                                <?= $s[1] ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <form action="" method="POST" id="welcomeForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                        <?php if (!empty($errors['checking'])): ?>
                            <div class="req-card" style="border-color:#ef4444;margin-bottom:1rem;">
                                <?php foreach ($errors['checking'] as $e): ?>
                                    <p style="color:#fca5a5;font-size:.85rem;margin:.2rem 0;"><?= $e ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- ── 1. À propos de la plateforme ── -->
                        <div class="req-card" style="margin-bottom:1rem;">
                            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                                <div style="width:2rem;height:2rem;border-radius:.5rem;
                                        background:#1d3461;display:flex;align-items:center;
                                        justify-content:center;flex-shrink:0;">
                                    <svg width="16" height="16" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 style="font-size:1rem;font-weight:700;color:#e6edf3;margin:0;">
                                    À propos de la plateforme
                                </h3>
                            </div>
                            <p style="color:#8b949e;font-size:.88rem;line-height:1.65;margin:0 0 .8rem;">
                                Cette plateforme simplifie la gestion des utilisateurs d'un portail captif basé sur
                                <strong style="color:#93c5fd;">FreeRADIUS</strong>.
                                Sans elle, toute création ou modification d'utilisateur se fait manuellement dans la base de données.
                                Avec elle, vous pouvez :
                            </p>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem;">
                                <?php
                                $features = [
                                    ['👤', 'Créer des comptes permanents ou expirables'],
                                    ['🔐', 'Gérer les mots de passe chiffrés'],
                                    ['📂', 'Regrouper les utilisateurs en groupes'],
                                    ['⚙️', 'Appliquer des politiques d\'accès'],
                                    ['📊', 'Consulter les statistiques (upload, download, temps de connexion)'],
                                    ['🌐', 'Visualiser les sites visités (via logs DNS)'],
                                    ['👥', 'Gérer des modérateurs par groupe'],
                                    ['🔑', 'Un administrateur root unique avec droits totaux'],
                                ];
                                foreach ($features as $f): ?>
                                    <div style="display:flex;align-items:flex-start;gap:.5rem;
                                        background:#0d1117;border:1px solid #21262d;
                                        border-radius:.6rem;padding:.55rem .75rem;">
                                        <span style="font-size:.9rem;flex-shrink:0;"><?= $f[0] ?></span>
                                        <span style="color:#8b949e;font-size:.8rem;line-height:1.5;"><?= $f[1] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ── 2. Pré-requis système ── -->
                        <div class="req-card" style="margin-bottom:1rem;">
                            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                                <div style="width:2rem;height:2rem;border-radius:.5rem;
                                        background:#2e1065;display:flex;align-items:center;
                                        justify-content:center;flex-shrink:0;">
                                    <svg width="16" height="16" fill="none" stroke="#8b5cf6" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18
                                             m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />
                                    </svg>
                                </div>
                                <h3 style="font-size:1rem;font-weight:700;color:#e6edf3;margin:0;">
                                    Pré-requis système
                                </h3>
                                <span class="pill pill-yellow">À vérifier avant de continuer</span>
                            </div>

                            <div class="chk-row">
                                <div class="chk-icon" style="background:#14532d;">
                                    <svg width="12" height="12" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:.87rem;font-weight:600;color:#e6edf3;">
                                        FreeRADIUS installé et configuré
                                    </p>
                                    <p style="margin:.2rem 0 0;font-size:.8rem;color:#8b949e;">
                                        La plateforme s'appuie sur la base de données de FreeRADIUS.
                                        FreeRADIUS doit être opérationnel avant de démarrer la configuration.
                                    </p>
                                </div>
                            </div>

                            <div class="chk-row">
                                <div class="chk-icon" style="background:#14532d;">
                                    <svg width="12" height="12" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:.87rem;font-weight:600;color:#e6edf3;">
                                        Tables FreeRADIUS créées dans la base de données
                                    </p>
                                    <p style="margin:.2rem 0 0;font-size:.8rem;color:#8b949e;">
                                        Les tables suivantes doivent exister.
                                        Leur absence <strong style="color:#fca5a5;">bloquera la migration</strong> à l'étape suivante.
                                    </p>
                                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem;">
                                        <?php foreach (
                                            [
                                                'nas',
                                                'nasreload',
                                                'radacct',
                                                'radcheck',
                                                'radgroupcheck',
                                                'radgroupreply',
                                                'radpostauth',
                                                'radreply',
                                                'radusergroup'
                                            ] as $t
                                        ): ?>
                                            <span class="ci"><?= $t ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="chk-row">
                                <div class="chk-icon" style="background:#451a03;">
                                    <svg width="12" height="12" fill="none" stroke="#f59e0b" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                                             1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464
                                             0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:.87rem;font-weight:600;color:#e6edf3;">
                                        Droits de l'utilisateur de base de données
                                    </p>
                                    <p style="margin:.2rem 0 0;font-size:.8rem;color:#8b949e;">
                                        L'utilisateur DB doit avoir les droits <span class="ci">CREATE TABLE</span>,
                                        <span class="ci">CREATE VIEW</span>, <span class="ci">CREATE PROCEDURE</span>
                                        et <span class="ci">CREATE EVENT</span> pour que la migration réussisse.
                                    </p>
                                </div>
                            </div>

                            <div class="chk-row">
                                <div class="chk-icon" style="background:#451a03;">
                                    <svg width="12" height="12" fill="none" stroke="#f59e0b" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667
                                             1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464
                                             0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <p style="margin:0;font-size:.87rem;font-weight:600;color:#e6edf3;">
                                        Permissions Apache sur les fichiers de la plateforme
                                    </p>
                                    <p style="margin:.2rem 0 0;font-size:.8rem;color:#8b949e;">
                                        L'utilisateur <span class="ci">www-data</span> (ou <span class="ci">apache</span>)
                                        doit pouvoir lire et écrire dans les fichiers
                                        <span class="ci">.env</span>, les logs et les fichiers de configuration.
                                    <div>
                                        <h3 class="m-2">
                                            Exemple de commades à executer :
                                        </h3>
                                        <p class="ci m-2">sudo chown -R www-data:www-data /var/www/html/</p>
                                        <p class="ci m-2">
                                            sudo chmod -R 2775 /var/www/html/
                                        </p>
                                    </div>

                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ── 3. Configuration FreeRADIUS ── -->
                        <details class="req-card" style="margin-bottom:1rem;">
                            <summary>
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:.75rem;">
                                        <div style="width:2rem;height:2rem;border-radius:.5rem;
                                                background:#0c1a2e;display:flex;align-items:center;
                                                justify-content:center;flex-shrink:0;">
                                            <svg width="16" height="16" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724
                                                     1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37
                                                     2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756
                                                     2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94
                                                     1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572
                                                     1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724
                                                     0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724
                                                     1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924
                                                     0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31
                                                     2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <span style="font-size:1rem;font-weight:700;color:#e6edf3;">
                                            Configuration FreeRADIUS requise
                                        </span>
                                    </div>
                                    <svg class="acc-arrow" width="16" height="16" fill="none" stroke="#8b949e"
                                        stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </summary>

                            <div style="margin-top:1rem;border-top:1px solid #30363d;padding-top:1rem;
                                    display:flex;flex-direction:column;gap:.85rem;">

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#93c5fd;">
                                        1. Activer le module SQL dans FreeRADIUS
                                    </p>
                                    <p style="margin:0;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        Assurez-vous que FreeRADIUS utilise le module SQL pointant vers
                                        <strong style="color:#e6edf3;">la même base de données</strong> que celle configurée ici.
                                    </p>
                                </div>

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#93c5fd;">
                                        2. Utiliser les vues de la plateforme
                                    </p>
                                    <p style="margin:0 0 .6rem;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        Par défaut FreeRADIUS lit <span class="ci">radcheck</span> et <span class="ci">radreply</span>.
                                        Après la migration, changez ces paramètres dans
                                        <span class="ci">/etc/chemin_vers_freeradius/mods-enabled/sql</span> :
                                    </p>
                                    <div style="font-family:monospace;font-size:.78rem;
                                            background:#161b22;border:1px solid #30363d;
                                            border-radius:.5rem;padding:.7rem .9rem;color:#a5d6ff;">
                                        authcheck_table = <span style="color:#86efac;">radcheck_view</span><br>
                                        authreply_table = <span style="color:#86efac;">radreply_view</span>
                                    </div>
                                </div>

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#93c5fd;">
                                        3. Activer CHAP pour le déchiffrement des mots de passe
                                    </p>
                                    <p style="margin:0;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        La plateforme chiffre tous les mots de passe. FreeRADIUS et pfSense doivent
                                        utiliser <strong style="color:#e6edf3;">CHAP</strong> pour les déchiffrer correctement.
                                    </p>
                                </div>
                            </div>
                        </details>

                        <!-- ── 4. Statistiques sites visités (DNS) ── -->
                        <details class="req-card" style="margin-bottom:1rem;">
                            <summary>
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:.75rem;">
                                        <div style="width:2rem;height:2rem;border-radius:.5rem;
                                                background:#0c2318;display:flex;align-items:center;
                                                justify-content:center;flex-shrink:0;">
                                            <svg width="16" height="16" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9
                                                     0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657
                                                     0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                        </div>
                                        <span style="font-size:1rem;font-weight:700;color:#e6edf3;">
                                            Statistiques DNS / Sites visités
                                        </span>
                                        <span class="pill pill-green">Optionnel</span>
                                    </div>
                                    <svg class="acc-arrow" width="16" height="16" fill="none" stroke="#8b949e"
                                        stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </summary>

                            <div style="margin-top:1rem;border-top:1px solid #30363d;padding-top:1rem;
                                    display:flex;flex-direction:column;gap:.85rem;">

                                <p style="margin:0;font-size:.83rem;color:#8b949e;line-height:1.65;">
                                    La fonctionnalité <strong style="color:#e6edf3;">Sites visités</strong>
                                    repose sur les logs DNS envoyés par pfSense (ou votre serveur DNS) au serveur web.
                                    Elle est <strong style="color:#86efac;">optionnelle</strong> — le reste de la plateforme fonctionne sans elle.
                                </p>

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#86efac;">
                                        Script <span class="ci">dns_extractor.sh</span>
                                    </p>
                                    <p style="margin:0;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        Extrait les logs DNS des journaux système Linux et les archive par date.
                                    </p>
                                </div>

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#86efac;">
                                        Script <span class="ci">dns-daily-sync.sh</span> — planifié en cron
                                    </p>
                                    <p style="margin:0 0 .5rem;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        Traite les logs quotidiennement et génère <span class="ci">pfsense_dns_today.log</span>,
                                        lisible par Apache.
                                        Ce script envoie aussi des requêtes HTTP pour déclencher les workers de la plateforme.
                                        Modifiez l'URL dans le script :
                                    </p>
                                    <div style="font-family:monospace;font-size:.78rem;
                                            background:#161b22;border:1px solid #30363d;
                                            border-radius:.5rem;padding:.6rem .9rem;color:#a5d6ff;">
                                        API_URL="http://<span style="color:#fcd34d;">votre-domaine-ou-ip</span>/cron/update-log"
                                    </div>
                                </div>

                                <div style="background:#0d1117;border:1px solid #21262d;border-radius:.6rem;padding:.9rem 1rem;">
                                    <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:600;color:#86efac;">
                                        Accès au fichier de logs par Apache
                                    </p>
                                    <p style="margin:0;font-size:.8rem;color:#8b949e;line-height:1.6;">
                                        Le fichier <span class="ci">pfsense_dns_today.log</span> doit être accessible
                                        en lecture par l'utilisateur Apache. La plateforme traite ce fichier
                                        par lots via un système de <strong style="color:#e6edf3;">Job Workers</strong>.
                                    </p>
                                </div>
                            </div>
                        </details>

                        <!-- ── Résumé étapes ── -->
                        <div class="req-card" style="margin-bottom:1.5rem;">
                            <p style="font-size:.8rem;font-weight:700;color:#8b949e;
                                  letter-spacing:.07em;text-transform:uppercase;margin:0 0 .9rem;">
                                Déroulement de l'installation
                            </p>
                            <div style="display:flex;flex-direction:column;gap:.5rem;">
                                <?php
                                $install_steps = [
                                    ['0', 'Présentation & pré-requis', '(cette page)', 'pill-blue'],
                                    ['1', 'Connexion à la base de données', 'Renseignez les identifiants MySQL/MariaDB', 'pill-blue'],
                                    ['1.5', 'Vérification & Migration', 'Contrôle des tables RADIUS, création des entités applicatives', 'pill-violet'],
                                    ['2', 'Compte administrateur', 'Création du compte root unique', 'pill-blue'],
                                ];
                                foreach ($install_steps as $s): ?>
                                    <div style="display:flex;align-items:flex-start;gap:.75rem;
                                        padding:.55rem .7rem;border-radius:.6rem;
                                        background:#0d1117;border:1px solid #21262d;">
                                        <span class="pill <?= $s[3] ?>" style="flex-shrink:0;margin-top:.1rem;">
                                            Étape <?= $s[0] ?>
                                        </span>
                                        <div>
                                            <p style="margin:0;font-size:.85rem;font-weight:600;color:#e6edf3;"><?= $s[1] ?></p>
                                            <p style="margin:.15rem 0 0;font-size:.78rem;color:#8b949e;"><?= $s[2] ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- CTA -->
                        <button type="submit" class="btn-cta" form="welcomeForm">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            Démarrer la configuration
                        </button>

                        <!-- Auteur -->
                        <div style="text-align:center;margin-top:1.5rem;padding-bottom:.5rem;">
                            <p style="font-size:.75rem;color:#30363d;">
                                Développé par
                                <span style="color:#8b949e;font-weight:600;">Nour-eddine MAIZA</span>
                                — Développeur web &amp; Administrateur systèmes et réseaux
                            </p>
                        </div>

                    </form>
                </div>
                <!-- FIN ÉTAPE 0 -->

            <?php else: ?>
                <!-- ══════════════════════════════════════
                 ÉTAPES 1, 1.5, 2 — formulaires existants
            ══════════════════════════════════════ -->
                <div class="w-full max-w-2xl mx-auto">
                    <div class="bg-white/70 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl shadow-blue-500/10 p-8">

                        <?php if ($message == 1): ?>
                            <div class="text-center">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-2">Configuration terminée</h3>
                                <p class="text-slate-600">La configuration est terminée ou l'étape est inconnue.</p>
                            </div>

                        <?php else: ?>
                            <!-- Header -->
                            <div class="text-center mb-8">
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent mb-2">
                                    <?= $titre ?>
                                </h1>
                                <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mx-auto"></div>
                            </div>

                            <!-- Error message -->
                            <?php if (!empty($errors['checking'])): ?>
                                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 rounded-r-xl">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75
                                                     1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11
                                                     13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <?php foreach ($errors['checking'] as $error) : ?>
                                            <div class="">
                                                <p class="text-sm text-red-700 font-medium"><?= $error ?? "" ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Form -->
                            <form action="" method="POST" class="space-y-6" id="configForm"
                                x-data="formConfig()" @submit.prevent="validateForm">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">

                                <?php if ($step == 1): ?>
                                    <!-- Étape 1 : Base de données -->
                                    <div class="space-y-6">
                                        <?php
                                        $fields = [
                                            ['db_host', 'Hôte', '127.0.0.1', 'text', 'required', 'server'],
                                            ['db_port', 'Port', '3306', 'text', 'required', 'hashtag'],
                                            ['db_database', 'Nom de la base', '', 'text', 'required', 'database'],
                                            ['db_username', 'Utilisateur', '', 'text', 'required', 'user'],
                                            ['db_password', 'Mot de passe', '', 'password', '', 'lock-closed'],
                                            ['db_charset', 'Encodage', 'utf8mb4', 'text', '', 'globe'],
                                        ];
                                        ?>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <?php foreach ($fields as $field): ?>
                                                <?php
                                                [$id, $label, $placeholder, $type, $required, $icon] = $field;
                                                $value = htmlspecialchars($_POST[$id] ?? $placeholder, ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <div class="group <?= in_array($id, ['db_charset', 'db_collation', 'db_prefix']) ? 'md:col-span-2' : '' ?>">
                                                    <label for="<?= $id ?>" class="block text-sm font-semibold text-slate-700 mb-2">
                                                        <?= $label ?><?= $required ? ' <span class="text-red-500 ml-1">*</span>' : '' ?>
                                                    </label>
                                                    <div class="relative">
                                                        <input
                                                            type="<?= $id === 'db_password' ? 'password' : $type ?>"
                                                            id="<?= $id ?>"
                                                            name="<?= $id ?>"
                                                            x-model="<?= str_replace('.', '_', $id) ?>"
                                                            <?php if ($required): ?>@blur="validateField('<?= $id ?>')" <?php endif; ?>
                                                            value="<?= $value ?>"
                                                            class="w-full px-4 py-3 pl-12 bg-slate-50 border-2 border-slate-200 rounded-2xl
                                                               focus:outline-none focus:border-blue-500 focus:bg-white transition-all
                                                               duration-200 text-slate-800 placeholder-slate-400"
                                                            placeholder="<?= $placeholder ?>"
                                                            <?= $required ?>
                                                            <?php if ($id === 'db_password'): ?>
                                                            x-bind:type="showDbPassword ? 'text' : 'password'"
                                                            <?php endif; ?> />
                                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <?php
                                                                $icons = [
                                                                    'server'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>',
                                                                    'hashtag'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>',
                                                                    'database'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>',
                                                                    'user'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>',
                                                                    'lock-closed' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>',
                                                                    'globe'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>',
                                                                ];
                                                                echo $icons[$icon] ?? $icons['server'];
                                                                ?>
                                                            </svg>
                                                        </div>
                                                        <?php if ($id === 'db_password'): ?>
                                                            <button type="button" @click="showDbPassword = !showDbPassword"
                                                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                                                <svg x-show="!showDbPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                </svg>
                                                                <svg x-show="showDbPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                                                                </svg>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (isset($errors[$id])): ?>
                                                        <div class="text-left">
                                                            <?php foreach ($errors[$id] as $error) { ?>
                                                                <p class="text-xs text-red-500 mt-1"><?= $error ?></p>
                                                            <?php } ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p x-show="errors['<?= $id ?>']" x-text="errors['<?= $id ?>']"
                                                        class="text-xs text-red-500 mt-1"></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                <?php elseif ($step == 2): ?>
                                    <!-- Étape 2 : Administrateur -->
                                    <div class="space-y-6" x-data="adminConfig()">
                                        <!-- fullname -->
                                        <div class="group">
                                            <label for="fullname" class="block text-sm font-semibold text-slate-700 mb-2">
                                                Nom complet <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="text" id="fullname" name="fullname" x-model="fullname"
                                                    @blur="validateField('fullname')"
                                                    value="<?= htmlspecialchars($_POST["fullname"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                                    required
                                                    class="w-full px-4 py-3 pl-12 bg-slate-50 border-2 border-slate-200
                                                          rounded-2xl focus:outline-none focus:border-blue-500 focus:bg-white
                                                          transition-all duration-200 text-slate-800 placeholder-slate-400"
                                                    placeholder="Ex. Noureddine Ma.." />
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879
                                                             1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <?php if (isset($errors['fullname'])): ?>
                                                <?php foreach ($errors['fullname'] as $error) { ?>
                                                    <p class="text-xs text-red-500 mt-1"><?= $error ?></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                            <p x-show="errors.fullname" x-text="errors.fullname" class="text-xs text-red-500 mt-1"></p>
                                        </div>

                                        <!-- email -->
                                        <div class="group">
                                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                                                Email <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="email" id="email" name="email" x-model="email"
                                                    @blur="validateField('email')"
                                                    value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                                    required
                                                    class="w-full px-4 py-3 pl-12 bg-slate-50 border-2 border-slate-200
                                                          rounded-2xl focus:outline-none focus:border-blue-500 focus:bg-white
                                                          transition-all duration-200 text-slate-800 placeholder-slate-400"
                                                    placeholder="Ex. admin@example.com" />
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M16 12a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <?php if (isset($errors['email'])): ?>
                                                <?php foreach ($errors['email'] as $error) { ?>
                                                    <p class="text-xs text-red-500 mt-1"><?= $error ?></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                            <p x-show="errors.email" x-text="errors.email" class="text-xs text-red-500 mt-1"></p>
                                        </div>

                                        <!-- password -->
                                        <div class="group">
                                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                                                Mot de passe <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <div class="relative">
                                                <input x-bind:type="showPassword ? 'text' : 'password'"
                                                    id="password" name="password" x-model="password"
                                                    @blur="validateField('password')"
                                                    value="<?= htmlspecialchars($_POST["password"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                                    required
                                                    class="w-full px-4 py-3 pl-12 pr-12 bg-slate-50 border-2 border-slate-200
                                                          rounded-2xl focus:outline-none focus:border-blue-500 focus:bg-white
                                                          transition-all duration-200 text-slate-800 placeholder-slate-400"
                                                    placeholder="Mot de passe sécurisé" />
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2
                                                             2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </div>
                                                <button type="button" @click="showPassword = !showPassword"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <?php if (isset($errors['password'])): ?>
                                                <?php foreach ($errors['password'] as $error) { ?>
                                                    <p class="text-xs text-red-500 mt-1"><?= $error ?></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                            <p x-show="errors.password" x-text="errors.password" class="text-xs text-red-500 mt-1"></p>
                                        </div>

                                        <!-- password_confirm -->
                                        <div class="group">
                                            <label for="password_confirm" class="block text-sm font-semibold text-slate-700 mb-2">
                                                Retapez le mot de passe <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <div class="relative">
                                                <input x-bind:type="showPasswordConfirm ? 'text' : 'password'"
                                                    id="password_confirm" name="password_confirm" x-model="passwordConfirm"
                                                    @blur="validateField('password_confirm')"
                                                    value="<?= htmlspecialchars($_POST["password_confirm"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                                                    required
                                                    class="w-full px-4 py-3 pl-12 pr-12 bg-slate-50 border-2 border-slate-200
                                                          rounded-2xl focus:outline-none focus:border-blue-500 focus:bg-white
                                                          transition-all duration-200 text-slate-800 placeholder-slate-400"
                                                    placeholder="Retapez le mot de passe" />
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2
                                                             2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                </div>
                                                <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                                                    <svg x-show="!showPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <svg x-show="showPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <?php if (isset($errors['password_confirm'])): ?>
                                                <?php foreach ($errors['password_confirm'] as $error) { ?>
                                                    <p class="text-xs text-red-500 mt-1"><?= $error ?></p>
                                                <?php } ?>
                                            <?php endif; ?>
                                            <p x-show="errors.password_confirm" x-text="errors.password_confirm" class="text-xs text-red-500 mt-1"></p>
                                        </div>
                                    </div>

                                    <script>
                                        function adminConfig() {
                                            return {
                                                fullname: '<?= htmlspecialchars($_POST["fullname"] ?? "", ENT_QUOTES, "UTF-8") ?>',
                                                email: '<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>',
                                                password: '<?= htmlspecialchars($_POST["password"] ?? "", ENT_QUOTES, "UTF-8") ?>',
                                                passwordConfirm: '<?= htmlspecialchars($_POST["password_confirm"] ?? "", ENT_QUOTES, "UTF-8") ?>',
                                                showPassword: false,
                                                showPasswordConfirm: false,
                                                errors: {},
                                                validateField(field) {
                                                    let isValid = true;
                                                    if (field === 'fullname' && !this.fullname) {
                                                        this.errors.fullname = "Le nom complet est requis";
                                                        isValid = false;
                                                    }
                                                    if (field === 'email' && !this.email) {
                                                        this.errors.email = "L'email est requis";
                                                        isValid = false;
                                                    } else if (field === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                                                        this.errors.email = "Adresse email invalide";
                                                        isValid = false;
                                                    }
                                                    if (field === 'password' && !this.password) {
                                                        this.errors.password = "Le mot de passe est requis";
                                                        isValid = false;
                                                    } else if (field === 'password' && this.password.length < 8) {
                                                        this.errors.password = "Minimum 8 caractères";
                                                        isValid = false;
                                                    }
                                                    if (field === 'password_confirm' && this.password !== this.passwordConfirm) {
                                                        this.errors.password_confirm = "Les mots de passe ne correspondent pas";
                                                        isValid = false;
                                                    }
                                                    return isValid;
                                                }
                                            }
                                        }
                                    </script>

                                <?php elseif ($step == 15):
                                    $preview = $data['data']['preview'] ?? null;
                                ?>
                                    <!-- Étape 1.5 : Migration -->
                                    <div class="space-y-6">
                                        <?php if ($preview): ?>
                                            <!-- Tables RADIUS -->
                                            <div class="rounded-2xl border-2 <?= $preview['radius']['complete'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' ?> p-5">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <?php if ($preview['radius']['complete']): ?>
                                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </div>
                                                        <h3 class="font-semibold text-green-800">Tables RADIUS détectées</h3>
                                                    <?php else: ?>
                                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </div>
                                                        <h3 class="font-semibold text-red-800">Tables RADIUS manquantes — migration bloquée</h3>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!$preview['radius']['complete']): ?>
                                                    <p class="text-sm text-red-700 mb-3">
                                                        Ces tables doivent être créées par FreeRADIUS <strong>avant</strong> de continuer.
                                                    </p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($preview['radius']['missing'] as $t): ?>
                                                            <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-mono rounded-lg border border-red-200">
                                                                <?= htmlspecialchars($t) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($preview['radius']['found'] as $t): ?>
                                                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-mono rounded-lg border border-green-200">
                                                                <?= htmlspecialchars($t) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($preview['radius']['complete']): ?>
                                                <?php if (!empty($preview['app_tables']['to_create'])): ?>
                                                    <div class="rounded-2xl border-2 border-blue-200 bg-blue-50 p-5">
                                                        <div class="flex items-center gap-3 mb-4">
                                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </div>
                                                            <h3 class="font-semibold text-blue-800">
                                                                <?= count($preview['app_tables']['to_create']) ?> table(s) à créer
                                                            </h3>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <?php foreach ($preview['app_tables']['to_create'] as $t): ?>
                                                                <span class="px-3 py-1 bg-white text-blue-700 text-xs font-mono rounded-lg border border-blue-200">
                                                                    <?= htmlspecialchars($t) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($preview['app_views']['to_create'])): ?>
                                                    <div class="rounded-2xl border-2 border-violet-200 bg-violet-50 p-5">
                                                        <div class="flex items-center gap-3 mb-4">
                                                            <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center flex-shrink-0">
                                                                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                            </div>
                                                            <h3 class="font-semibold text-violet-800">
                                                                <?= count($preview['app_views']['to_create']) ?> vue(s) à créer
                                                            </h3>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <?php foreach ($preview['app_views']['to_create'] as $v): ?>
                                                                <span class="px-3 py-1 bg-white text-violet-700 text-xs font-mono rounded-lg border border-violet-200">
                                                                    <?= htmlspecialchars($v) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (empty($preview['app_tables']['to_create']) && empty($preview['app_views']['to_create'])): ?>
                                                    <div class="rounded-2xl border-2 border-green-200 bg-green-50 p-5 text-center">
                                                        <p class="text-green-700 font-medium">✅ Toutes les tables et vues sont déjà en place.</p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-slate-500 text-sm text-center">Impossible de charger l'aperçu du schéma.</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Bouton conditionnel migration -->
                                    <?php $hide_default_button = true; ?>
                                    <div class="pt-6">
                                        <?php if ($preview && $preview['radius']['complete']): ?>
                                            <button type="submit"
                                                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700
                                                       hover:to-purple-700 text-white font-semibold py-4 px-6 rounded-2xl
                                                       transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]
                                                       shadow-lg hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8
                                                             4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                                    </svg>
                                                    <span>Lancer la migration</span>
                                                </div>
                                            </button>
                                        <?php else: ?>
                                            <a href="/installation/migration"
                                                class="block w-full text-center bg-gradient-to-r from-slate-600 to-slate-700
                                                  hover:from-slate-700 hover:to-slate-800 text-white font-semibold py-4 px-6
                                                  rounded-2xl transition-all duration-200 transform hover:scale-[1.02] shadow-lg">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11
                                                             11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    <span>Vérifier à nouveau</span>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (($step ?? 1) !== 15 && empty($hide_default_button)): ?>
                                    <div class="pt-6">
                                        <button type="submit"
                                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700
                                                   hover:to-purple-700 text-white font-semibold py-4 px-6 rounded-2xl
                                                   transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]
                                                   shadow-lg hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span>Continuer</span>
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Footer info -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-slate-500">
                            Étape <?= ($step != 15 ? $step : '1.5') ?> sur <?= $total_steps ?>
                        </p>
                    </div>
                </div>
            <?php endif; /* fin else (steps 1/1.5/2) */ ?>

        </div>
    </div>
</div>

<script>
    function formConfig() {
        return {
            showDbPassword: false,
            errors: {},
            db_host: '<?= htmlspecialchars($_POST["db_host"]     ?? "127.0.0.1", ENT_QUOTES, "UTF-8") ?>',
            db_port: '<?= htmlspecialchars($_POST["db_port"]     ?? "3306",      ENT_QUOTES, "UTF-8") ?>',
            db_database: '<?= htmlspecialchars($_POST["db_database"] ?? "",          ENT_QUOTES, "UTF-8") ?>',
            db_username: '<?= htmlspecialchars($_POST["db_username"] ?? "",          ENT_QUOTES, "UTF-8") ?>',
            db_password: '<?= htmlspecialchars($_POST["db_password"] ?? "",          ENT_QUOTES, "UTF-8") ?>',
            db_charset: '<?= htmlspecialchars($_POST["db_charset"]  ?? "utf8mb4",   ENT_QUOTES, "UTF-8") ?>',

            validateField(field) {
                let isValid = true;
                if (field === 'db_host' && !this.db_host) {
                    this.errors.db_host = "L'hôte est requis";
                    isValid = false;
                }
                if (field === 'db_port' && !this.db_port) {
                    this.errors.db_port = "Le port est requis";
                    isValid = false;
                }
                if (field === 'db_database' && !this.db_database) {
                    this.errors.db_database = "Le nom de la base est requis";
                    isValid = false;
                }
                if (field === 'db_username' && !this.db_username) {
                    this.errors.db_username = "Le nom d'utilisateur est requis";
                    isValid = false;
                }
                return isValid;
            },

            validateForm() {
                let isValid = true;
                this.errors = {};
                <?php if ($step == 1): ?>
                    if (!this.db_host) {
                        this.errors.db_host = "L'hôte est requis";
                        isValid = false;
                    }
                    if (!this.db_port) {
                        this.errors.db_port = "Le port est requis";
                        isValid = false;
                    }
                    if (!this.db_database) {
                        this.errors.db_database = "Le nom de la base est requis";
                        isValid = false;
                    }
                    if (!this.db_username) {
                        this.errors.db_username = "Le nom d'utilisateur est requis";
                        isValid = false;
                    }
                <?php endif; ?>
                if (isValid) {
                    document.getElementById('configForm').submit();
                }
            }
        }
    }
</script>

<?php
$view->endSection();
$view->section('footer');
$view->inc('partials', 'footer.php', []);
$view->endSection();
$view->section('scripts');
$view->endSection();
?>