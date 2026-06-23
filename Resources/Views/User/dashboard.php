<?php

use Core\Helper\AssetHelper;


$view->layout('layouts', 'main.php');

// ── Helpers ─────────────────────────────────────────────────────────────────

function fmt_bytes(int $bytes, int $dec = 2): string
{
    if ($bytes <= 0) return '0 o';
    $u = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $i = min((int) floor(log($bytes, 1024)), count($u) - 1);
    return number_format($bytes / (1024 ** $i), $dec) . ' ' . $u[$i];
}

function fmt_duration(int $secs): string
{
    if ($secs <= 0) return '0s';
    $h = intdiv($secs, 3600);
    $m = intdiv($secs % 3600, 60);
    $s = $secs % 60;
    if ($h > 0) return "{$h}h {$m}m";
    if ($m > 0) return "{$m}m {$s}s";
    return "{$s}s";
}

function fmt_relative(string $dt): string
{
    $d = time() - strtotime($dt);
    return match (true) {
        $d < 60     => 'À l\'instant',
        $d < 3600   => 'Il y a ' . intdiv($d, 60) . ' min',
        $d < 86400  => 'Il y a ' . intdiv($d, 3600) . 'h',
        $d < 604800 => 'Il y a ' . intdiv($d, 86400) . 'j',
        default     => date('d/m/Y', strtotime($dt)),
    };
}

// ── Données (injectées par le contrôleur) ────────────────────────────────────
$user = $user ?? [
    'id'                       => 2,
    'username'                 => 'noureddine',
    'fullname'                 => 'Noureddine M',
    'account_status'           => 'active',
    'expires_at'               => null,
    'created_at'               => '2026-01-20 03:00:02',
    'total_sessions'           => 120,
    'total_time'               => 564840,
    'total_download'           => 2849208001,
    'total_upload'             => 17615488964,
    'total_consumption'        => 20464696965,
    'unique_devices'           => 3,
    'current_session_duration' => 52738,
    'current_mac'              => 'ee:26:6a:49:87:af',
    'current_ip'               => $client_ip ?? '192.168.0.195',
    'is_online'                => 1,
    'active_sessions'          => 1,
    'last_login_at'            => '2026-05-19 10:40:45',
    'group_name'               => 'VIP',
    'group_id'                 => 4,
];

// ── Calculs ──────────────────────────────────────────────────────────────────
$is_online      = (bool) $user['is_online'];
$is_active      = $user['account_status'] === 'active';
$total_dl       = fmt_bytes((int) $user['total_download']);
$total_ul       = fmt_bytes((int) $user['total_upload']);
$total_conso    = fmt_bytes((int) $user['total_consumption']);
$total_time_fmt = fmt_duration((int) $user['total_time']);
$session_fmt    = fmt_duration((int) $user['current_session_duration']);
$last_login_rel = $user['last_login_at'] ? fmt_relative($user['last_login_at']) : '—';
$member_since   = $user['created_at']   ? date('d M Y', strtotime($user['created_at'])) : '—';

$dl_bytes = (int) $user['total_download'];
$ul_bytes = (int) $user['total_upload'];
$total_io = $dl_bytes + $ul_bytes ?: 1;
$dl_pct   = round($dl_bytes / $total_io * 100);
$ul_pct   = 100 - $dl_pct;

$avg_secs  = $user['total_sessions'] > 0 ? intdiv((int)$user['total_time'], (int)$user['total_sessions']) : 0;
$avg_bytes = $user['total_sessions'] > 0 ? intdiv((int)$user['total_consumption'], (int)$user['total_sessions']) : 0;

$initials = mb_substr(implode('', array_map(
    fn($w) => mb_strtoupper(mb_substr($w, 0, 1)),
    explode(' ', trim($user['fullname']))
)), 0, 2);

$view->section('styles');
?>
<style>
    /* ═══════════════════════════════════════
       PALETTE SLATE / TECHNIQUE
       --c-bg       fond principal
       --c-surface  cartes
       --c-border   bordures
       --c-muted    texte secondaire
       --c-accent   bleu électrique
       --c-green    online / actif
       --c-amber    upload / warning
    ═══════════════════════════════════════ */
    :root {
        --c-bg: #0c0e14;
        --c-surface: #111520;
        --c-surf2: #161b28;
        --c-border: #1e2535;
        --c-border2: #252d40;
        --c-muted: #4a5578;
        --c-dim: #6b7a9f;
        --c-text: #c8d0e8;
        --c-bright: #e8edf8;
        --c-accent: #3b82f6;
        --c-accent2: #60a5fa;
        --c-green: #22c55e;
        --c-amber: #f59e0b;
        --c-red: #ef4444;
        --c-vip: #a78bfa;
    }

    /* Force dark même si le layout applique light */
    .dash-root {
        background: var(--c-bg);
        color: var(--c-text);
        min-height: 100vh;
        font-family: 'DM Sans', sans-serif;
    }

    /* ── Scanline texture ── */
    .dash-root::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        background: repeating-linear-gradient(0deg,
                transparent,
                transparent 2px,
                rgba(0, 0, 0, .08) 2px,
                rgba(0, 0, 0, .08) 4px);
        pointer-events: none;
    }

    /* ── Layout ── */
    .dash-wrap {
        position: relative;
        z-index: 1;
    }

    /* ── Header ── */
    .dash-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        background: rgba(12, 14, 20, .90);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--c-border);
    }

    /* ── Cards ── */
    .d-card {
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        border-radius: 6px;
        transition: border-color .2s, box-shadow .2s;
    }

    .d-card:hover {
        border-color: var(--c-border2);
        box-shadow: 0 0 0 1px var(--c-border2), 0 8px 32px rgba(0, 0, 0, .4);
    }

    .d-card-accent {
        background: var(--c-surf2);
        border: 1px solid var(--c-accent);
        border-radius: 6px;
        box-shadow: 0 0 24px rgba(59, 130, 246, .08), inset 0 0 32px rgba(59, 130, 246, .03);
    }

    /* ── Section labels ── */
    .d-label {
        font-size: .6rem;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--c-muted);
    }

    /* ── Valeurs principales ── */
    .d-value {
        font-family: 'Fraunces', serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--c-bright);
        line-height: 1;
    }

    .d-value-sm {
        font-family: 'Fraunces', serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--c-bright);
        line-height: 1.1;
    }

    /* ── Mono ── */
    .mono {
        font-family: 'Courier New', monospace;
        font-size: .78rem;
        letter-spacing: .04em;
    }

    /* ── Accent bleu ── */
    .txt-accent {
        color: var(--c-accent2);
    }

    .txt-green {
        color: var(--c-green);
    }

    .txt-amber {
        color: var(--c-amber);
    }

    .txt-vip {
        color: var(--c-vip);
    }

    .txt-muted {
        color: var(--c-muted);
    }

    .txt-dim {
        color: var(--c-dim);
    }

    .txt-bright {
        color: var(--c-bright);
    }

    /* ── Barres ── */
    .bar-track {
        height: 3px;
        background: var(--c-border2);
        border-radius: 2px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 1.4s cubic-bezier(.4, 0, .2, 1);
    }

    .bar-accent {
        background: var(--c-accent);
    }

    .bar-amber {
        background: var(--c-amber);
    }

    .bar-green {
        background: var(--c-green);
    }

    /* ── Online pulse ── */
    .online-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--c-green);
        box-shadow: 0 0 0 0 rgba(34, 197, 94, .6);
        animation: onpulse 2s infinite;
    }

    @keyframes onpulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(34, 197, 94, .5);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(34, 197, 94, 0);
        }
    }

    .offline-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--c-muted);
    }

    /* ── Badge groupe VIP ── */
    .badge-vip {
        background: rgba(167, 139, 250, .12);
        border: 1px solid rgba(167, 139, 250, .3);
        color: var(--c-vip);
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 3px;
    }

    .badge-active {
        background: rgba(34, 197, 94, .10);
        border: 1px solid rgba(34, 197, 94, .25);
        color: var(--c-green);
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 3px;
    }

    .badge-inactive {
        background: rgba(239, 68, 68, .10);
        border: 1px solid rgba(239, 68, 68, .25);
        color: var(--c-red);
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        padding: 2px 8px;
        border-radius: 3px;
    }

    /* ── Séparateur ── */
    .d-sep {
        border-color: var(--c-border);
    }

    /* ── Entrées ── */
    .fade-up {
        opacity: 0;
        animation: fadeUp .5s ease forwards;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .d1 {
        animation-delay: .04s
    }

    .d2 {
        animation-delay: .09s
    }

    .d3 {
        animation-delay: .14s
    }

    .d4 {
        animation-delay: .19s
    }

    .d5 {
        animation-delay: .24s
    }

    .d6 {
        animation-delay: .30s
    }

    .d7 {
        animation-delay: .36s
    }

    /* ── Timer ── */
    #live-timer,
    #live-timer-2 {
        font-variant-numeric: tabular-nums;
    }

    /* ── Section bande ── */
    .section-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .section-bar::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--c-border);
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--c-border2);
        border-radius: 2px;
    }

    /* ── Boutons ── */
    .btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: 1px solid var(--c-border2);
        border-radius: 4px;
        font-size: .75rem;
        font-weight: 600;
        color: var(--c-dim);
        transition: all .2s;
    }

    .btn-ghost:hover {
        border-color: var(--c-accent);
        color: var(--c-accent2);
    }

    .btn-danger-ghost {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: 1px solid rgba(239, 68, 68, .3);
        border-radius: 4px;
        font-size: .75rem;
        font-weight: 600;
        color: var(--c-red);
        transition: all .2s;
    }

    .btn-danger-ghost:hover {
        background: rgba(239, 68, 68, .08);
    }

    /* ── Glow ligne session active ── */
    .session-glow {
        box-shadow: inset 3px 0 0 var(--c-green);
    }
</style>
<?php $view->endSection(); ?>

<?php $view->section('content'); ?>

<div class="dash-root">
    <div class="dash-wrap">

        <!-- ═══════════ HEADER ═══════════ -->
        <header class="dash-header">
            <div style="max-width:1280px;margin:0 auto;padding:0 24px;height:52px;display:flex;align-items:center;justify-content:space-between;gap:16px;">

                <!-- Logo -->
                <a href="/" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
                    <div style="width:28px;height:28px;background:var(--c-accent);border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M1.5 8.5C5.5 4.5 10.5 2.5 12 2.5s6.5 2 10.5 6" stroke="white" stroke-width="2.2" stroke-linecap="round" />
                            <path d="M4.5 12C7.5 9 10 8 12 8s4.5 1 7.5 4" stroke="white" stroke-width="2.2" stroke-linecap="round" />
                            <path d="M7.5 15.5C9.5 13.5 10.8 13 12 13s2.5.5 4.5 2.5" stroke="white" stroke-width="2.2" stroke-linecap="round" />
                            <circle cx="12" cy="19" r="1.5" fill="white" />
                        </svg>
                    </div>
                    <?php if ($app_name): ?>
                        <span style="font-family:'Fraunces',serif;font-weight:700;font-size:1rem;color:var(--c-bright);letter-spacing:-.01em;"><?= $app_name ?></span>
                    <?php endif; ?>

                    <span style="font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--c-muted);padding:1px 6px;border:1px solid var(--c-border2);border-radius:2px;">PORTAL</span>
                </a>

                <!-- Nav -->
                <nav style="display:flex;align-items:center;gap:4px;">
                    <a href="/dashboard" style="padding:4px 12px;font-size:.75rem;font-weight:600;color:var(--c-accent2);background:rgba(59,130,246,.10);border:1px solid rgba(59,130,246,.2);border-radius:3px;text-decoration:none;">Dashboard</a>
                    <a href="/" style="padding:4px 12px;font-size:.75rem;font-weight:500;color:var(--c-dim);text-decoration:none;border-radius:3px;transition:color .2s;" onmouseover="this.style.color='var(--c-text)'" onmouseout="this.style.color='var(--c-dim)'">Accueil</a>
                </nav>

                <!-- Right -->
                <div style="display:flex;align-items:center;gap:12px;">

                    <!-- Identité -->
                    <div style="display:flex;align-items:center;gap:10px;padding-left:12px;border-left:1px solid var(--c-border);">
                        <div style="position:relative;flex-shrink:0;">
                            <div style="width:30px;height:30px;border-radius:4px;background:var(--c-accent);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;font-family:'Fraunces',serif;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <?php if ($is_online): ?>
                                <span style="position:absolute;bottom:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:var(--c-green);border:2px solid var(--c-bg);"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p style="font-size:.75rem;font-weight:700;color:var(--c-bright);line-height:1;"><?= htmlspecialchars($user['fullname']) ?></p>
                            <p style="font-size:.65rem;color:var(--c-muted);margin-top:2px;">@<?= htmlspecialchars($user['username']) ?></p>
                        </div>
                    </div>

                    <a href="/" class="btn-danger-ghost">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Quitter
                    </a>
                </div>
            </div>
        </header>


        <!-- ═══════════ CONTENU ═══════════ -->
        <div style="max-width:1280px;margin:0 auto;padding:72px 24px 80px;">


            <!-- ── Topbar identité ────────────────────────────────────────────── -->
            <div class="fade-up d1" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:28px;padding-bottom:20px;border-bottom:1px solid var(--c-border);">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:6px;background:var(--c-accent);display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-size:1.1rem;font-weight:800;color:#fff;">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <h1 style="font-family:'Fraunces',serif;font-size:1.4rem;font-weight:700;color:var(--c-bright);line-height:1;">
                                <?= htmlspecialchars($user['fullname']) ?>
                            </h1>
                            <span class="badge-vip">★ <?= htmlspecialchars($user['group_name']) ?></span>
                            <?php if ($is_active): ?>
                                <span class="badge-active">ACTIF</span>
                            <?php else: ?>
                                <span class="badge-inactive">INACTIF</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:.72rem;color:var(--c-muted);">
                            @<?= htmlspecialchars($user['username']) ?>
                            &nbsp;·&nbsp; Membre depuis <?= $member_since ?>
                            &nbsp;·&nbsp; Dernière connexion : <?= $last_login_rel ?>
                        </p>
                    </div>
                </div>

                <!-- Statut réseau -->
                <div style="display:flex;align-items:center;gap:6px;padding:6px 14px;border:1px solid <?= $is_online ? 'rgba(34,197,94,.3)' : 'var(--c-border)' ?>;border-radius:4px;background:<?= $is_online ? 'rgba(34,197,94,.06)' : 'transparent' ?>;">
                    <?php if ($is_online): ?>
                        <div class="online-dot"></div>
                        <span style="font-size:.7rem;font-weight:700;color:var(--c-green);letter-spacing:.06em;text-transform:uppercase;">EN LIGNE</span>
                    <?php else: ?>
                        <div class="offline-dot"></div>
                        <span style="font-size:.7rem;font-weight:600;color:var(--c-muted);letter-spacing:.06em;text-transform:uppercase;">HORS LIGNE</span>
                    <?php endif; ?>
                </div>
            </div>


            <!-- ── Session active ─────────────────────────────────────────────── -->
            <?php if ($is_online): ?>
                <div class="fade-up d2 d-card session-glow" style="margin-bottom:20px;padding:16px 20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <div class="d-label" style="margin-bottom:6px;">SESSION ACTIVE</div>
                            <div style="display:flex;flex-wrap:wrap;gap:20px;">
                                <div>
                                    <span style="font-size:.68rem;color:var(--c-muted);">Durée</span><br>
                                    <span id="live-timer" style="font-family:'Fraunces',serif;font-size:1.1rem;font-weight:700;color:var(--c-green);"><?= $session_fmt ?></span>
                                </div>
                                <div>
                                    <span style="font-size:.68rem;color:var(--c-muted);">IP</span><br>
                                    <span class="mono txt-accent"><?= htmlspecialchars($client_ip) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="<?= $portal_link ?>" class="btn-danger-ghost">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 101.06 1.06M12 3v9" />
                        </svg>
                        Terminer
                    </a>
                </div>
            <?php endif; ?>

            <!-- ── Alerte expiration ──────────────────────────────────────────── -->
            <?php if ($user['expires_at']):
                $exp_ts   = strtotime($user['expires_at']);
                $exp_diff = $exp_ts - time();
                $exp_days = max(0, intdiv($exp_diff, 86400));
                $exp_c    = $exp_days <= 3 ? 'rgba(239,68,68,.15)' : 'rgba(245,158,11,.08)';
                $exp_bc   = $exp_days <= 3 ? 'rgba(239,68,68,.4)'  : 'rgba(245,158,11,.3)';
                $exp_tc   = $exp_days <= 3 ? 'var(--c-red)'        : 'var(--c-amber)';
            ?>
                <div class="fade-up d2" style="margin-bottom:20px;padding:12px 20px;border-radius:5px;background:<?= $exp_c ?>;border:1px solid <?= $exp_bc ?>;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <svg width="14" height="14" fill="none" stroke="<?= $exp_tc ?>" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        <span style="font-size:.75rem;font-weight:600;color:<?= $exp_tc ?>;">
                            Compte expirant <?= $exp_days === 0 ? "aujourd'hui" : "dans {$exp_days} jour" . ($exp_days > 1 ? 's' : '') ?>
                            &nbsp;—&nbsp; <?= date('d/m/Y à H:i', $exp_ts) ?>
                        </span>
                    </div>
                    <a href="/renouveler" style="font-size:.72rem;font-weight:700;color:<?= $exp_tc ?>;text-decoration:underline;white-space:nowrap;">Renouveler →</a>
                </div>
            <?php endif; ?>


            <!-- ── Section label KPI ─────────────────────────────────────────── -->
            <div class="section-bar fade-up d3">
                <span class="d-label">STATISTIQUES GLOBALES</span>
            </div>

            <!-- ── KPI 4 colonnes ────────────────────────────────────────────── -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin-bottom:20px;">

                <!-- Sessions -->
                <div class="d-card fade-up d3" style="padding:18px 20px;">
                    <div class="d-label" style="margin-bottom:12px;">SESSIONS TOTALES</div>
                    <div class="d-value"><?= number_format($user['total_sessions']) ?></div>
                    <div style="margin-top:8px;display:flex;align-items:center;gap:6px;">
                        <div class="bar-track" style="flex:1;">
                            <div class="bar-fill bar-accent" style="width:<?= min(100, ($user['total_sessions'] / 200) * 100) ?>%;"></div>
                        </div>
                        <span style="font-size:.65rem;color:var(--c-muted);">/ 200</span>
                    </div>
                </div>

                <!-- Temps total -->
                <div class="d-card fade-up d3" style="padding:18px 20px;">
                    <div class="d-label" style="margin-bottom:12px;">TEMPS CONNECTÉ</div>
                    <div class="d-value"><?= $total_time_fmt ?></div>
                    <div style="margin-top:8px;">
                        <span style="font-size:.68rem;color:var(--c-muted);">Moy. / session : </span>
                        <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);"><?= fmt_duration($avg_secs) ?></span>
                    </div>
                </div>

                <!-- Consommation -->
                <div class="d-card fade-up d4" style="padding:18px 20px;">
                    <div class="d-label" style="margin-bottom:12px;">DONNÉES TRANSFÉRÉES</div>
                    <div class="d-value"><?= $total_conso ?></div>
                    <div style="margin-top:8px;">
                        <span style="font-size:.68rem;color:var(--c-muted);">Moy. / session : </span>
                        <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);"><?= fmt_bytes($avg_bytes) ?></span>
                    </div>
                </div>

                <!-- Appareils -->
                <div class="d-card fade-up d4" style="padding:18px 20px;">
                    <div class="d-label" style="margin-bottom:12px;">APPAREILS UNIQUES</div>
                    <div class="d-value"><?= (int) $user['unique_devices'] ?></div>
                    <div style="margin-top:8px;display:flex;gap:4px;">
                        <?php for ($i = 0; $i < max(5, (int)$user['unique_devices'] + 2); $i++): ?>
                            <div style="width:18px;height:18px;border-radius:2px;background:<?= $i < (int)$user['unique_devices'] ? 'var(--c-accent)' : 'var(--c-border2)' ?>;"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>


            <!-- ── Section label Trafic ───────────────────────────────────────── -->
            <div class="section-bar fade-up d4">
                <span class="d-label">TRAFIC RÉSEAU</span>
            </div>

            <!-- ── Trafic + Infos réseau ──────────────────────────────────────── -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">

                <!-- DL / UL -->
                <div class="d-card fade-up d5" style="padding:20px 22px;">

                    <!-- Download -->
                    <div style="margin-bottom:20px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <div style="display:flex;align-items:center;gap:7px;">
                                <svg width="13" height="13" fill="none" stroke="var(--c-accent2)" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);letter-spacing:.04em;">DOWNLOAD</span>
                            </div>
                            <span style="font-family:'Fraunces',serif;font-size:1.05rem;font-weight:700;color:var(--c-accent2);"><?= $total_dl ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-accent" style="width:<?= $dl_pct ?>%;"></div>
                        </div>
                        <div style="margin-top:4px;font-size:.62rem;color:var(--c-muted);"><?= $dl_pct ?>% du trafic total</div>
                    </div>

                    <!-- Upload -->
                    <div style="margin-bottom:20px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <div style="display:flex;align-items:center;gap:7px;">
                                <svg width="13" height="13" fill="none" stroke="var(--c-amber)" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);letter-spacing:.04em;">UPLOAD</span>
                            </div>
                            <span style="font-family:'Fraunces',serif;font-size:1.05rem;font-weight:700;color:var(--c-amber);"><?= $total_ul ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-amber" style="width:<?= $ul_pct ?>%;"></div>
                        </div>
                        <div style="margin-top:4px;font-size:.62rem;color:var(--c-muted);"><?= $ul_pct ?>% du trafic total</div>
                    </div>

                    <!-- Total -->
                    <div style="padding-top:16px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;">
                        <span class="d-label">TOTAL COMBINÉ</span>
                        <span style="font-family:'Fraunces',serif;font-size:1.1rem;font-weight:700;color:var(--c-bright);"><?= $total_conso ?></span>
                    </div>
                </div>

                <!-- Infos réseau + profil -->
                <div style="display:flex;flex-direction:column;gap:12px;">

                    <!-- Réseau actuel -->
                    <div class="d-card fade-up d5" style="padding:18px 20px;">
                        <div class="d-label" style="margin-bottom:14px;">RÉSEAU ACTUEL</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <div style="font-size:.62rem;color:var(--c-muted);margin-bottom:4px;">ADRESSE IP</div>
                                <div class="mono txt-accent" style="font-size:.85rem;font-weight:700;"><?= htmlspecialchars($client_ip ?: '—') ?></div>
                            </div>
                            <div>
                                <div style="font-size:.62rem;color:var(--c-muted);margin-bottom:4px;">SESSIONS ACTIVES</div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-family:'Fraunces',serif;font-size:1.1rem;font-weight:700;color:var(--c-bright);"><?= (int)$user['active_sessions'] ?></span>
                                    <?php if ((int)$user['active_sessions'] > 0): ?>
                                        <div class="online-dot" style="width:6px;height:6px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($is_online): ?>
                                <div style="grid-column:1/-1;">
                                    <div style="font-size:.62rem;color:var(--c-muted);margin-bottom:4px;">DURÉE SESSION</div>
                                    <div id="live-timer-2" style="font-family:'Fraunces',serif;font-size:1.05rem;font-weight:700;color:var(--c-green);"><?= $session_fmt ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profil -->
                    <div class="d-card fade-up d6" style="padding:18px 20px;flex:1;">
                        <div class="d-label" style="margin-bottom:14px;">PROFIL COMPTE</div>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:.7rem;color:var(--c-muted);">Groupe</span>
                                <span class="badge-vip">★ <?= htmlspecialchars($user['group_name']) ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:.7rem;color:var(--c-muted);">Statut</span>
                                <span class="<?= $is_active ? 'badge-active' : 'badge-inactive' ?>"><?= strtoupper($user['account_status']) ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:.7rem;color:var(--c-muted);">Membre depuis</span>
                                <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);"><?= $member_since ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:.7rem;color:var(--c-muted);">Dernière co.</span>
                                <span style="font-size:.72rem;font-weight:600;color:var(--c-dim);"><?= $last_login_rel ?></span>
                            </div>
                            <?php if ($user['expires_at']): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <span style="font-size:.7rem;color:var(--c-muted);">Expiration</span>
                                    <span style="font-size:.72rem;font-weight:600;color:var(--c-red);"><?= date('d/m/Y', strtotime($user['expires_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ── Section label Performances ────────────────────────────────── -->
            <div class="section-bar fade-up d6">
                <span class="d-label">PERFORMANCES</span>
            </div>

            <!-- ── Métriques moyennes ─────────────────────────────────────────── -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:12px;">

                <div class="d-card fade-up d6" style="padding:16px 20px;">
                    <div class="d-label" style="margin-bottom:10px;">MOY. DURÉE / SESSION</div>
                    <div class="d-value-sm"><?= fmt_duration($avg_secs) ?></div>
                </div>

                <div class="d-card fade-up d6" style="padding:16px 20px;">
                    <div class="d-label" style="margin-bottom:10px;">MOY. DONNÉES / SESSION</div>
                    <div class="d-value-sm"><?= fmt_bytes($avg_bytes) ?></div>
                </div>

                <div class="d-card fade-up d7" style="padding:16px 20px;">
                    <div class="d-label" style="margin-bottom:10px;">RATIO UL / DL</div>
                    <div class="d-value-sm">
                        <span style="color:var(--c-amber);"><?= $ul_pct ?>%</span>
                        <span style="font-size:1rem;color:var(--c-muted);"> / </span>
                        <span class="txt-accent"><?= $dl_pct ?>%</span>
                    </div>
                </div>

                <div class="d-card fade-up d7" style="padding:16px 20px;">
                    <div class="d-label" style="margin-bottom:10px;">DERNIER LOGIN</div>
                    <div class="d-value-sm"><?= $last_login_rel ?></div>
                    <div style="margin-top:6px;font-size:.65rem;color:var(--c-muted);"><?= $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : '—' ?></div>
                </div>
            </div>


        </div><!-- /wrap -->
    </div><!-- /dash-root -->


    <!-- ═══════════ TIMER JS ═══════════ -->
    <?php if ($is_online): ?>
        <script>
            (function() {
                let s = <?= (int)$user['current_session_duration'] ?>;
                const els = document.querySelectorAll('#live-timer,#live-timer-2');

                function fmt(n) {
                    const h = Math.floor(n / 3600),
                        m = Math.floor((n % 3600) / 60),
                        r = n % 60;
                    return h > 0 ? `${h}h ${String(m).padStart(2,'0')}m` : `${m}m ${String(r).padStart(2,'0')}s`;
                }
                setInterval(() => {
                    s++;
                    els.forEach(e => {
                        if (e) e.textContent = fmt(s);
                    });
                }, 1000);
            })();
        </script>
    <?php endif; ?>

    <?php
    $view->endSection();

    $view->section('footer');
    $view->inc('partials', 'footer.php');
    $view->endSection();

    $view->section('scripts');
    echo AssetHelper::scripts(['scripts:main.js']);
    $view->endSection();
