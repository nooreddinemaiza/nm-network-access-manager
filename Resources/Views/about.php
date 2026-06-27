<?php

use Core\Helper\Helper;

$view->layout('layouts', 'main.php');

$view->section('meta');
?>
<link rel="shortcut icon" href="/assets/images/logo.png" type="image/x-icon">
<?php
$view->endSection();

$view->section('content');
?>
<div class="min-h-screen bg-white dark:bg-zinc-900 overflow-hidden flex items-center justify-center p-4">
    <div class="w-full" style="max-width: 760px;">
        <!-- En-tête -->
        <div style="text-align:center;">
            <div style="display: block;max-width: 75px;margin: 0 auto;">
                <?= $logo_url ?>
            </div>
            <h2 style="font-size:2rem;font-weight:800;
                               ">
                <?= $name ?>
            </h2>
            <p class="m-2 mx-auto w-full text-black dark:text-amber-50">
                Plateforme de gestion centralisée des utilisateurs FreeRADIUS.
                Avant de démarrer, lisez attentivement les pré-requis ci-dessous.
            </p>
        </div>
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
                <h3 class="m-0 font-bold text-black dark:text-amber-50">
                    À propos de la plateforme
                </h3>
            </div>
            <p class="m-2 text-black dark:text-amber-50">
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
    </div>
</div>
<?php
$view->inc('partials', 'footer.php', []);
$view->endSection();
?>