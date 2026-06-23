<?php

// $server_url = 'http://100.116.113.119';
$server_url = 'http://192.168.0.20';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion au Réseau</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f9fafb;
        }

        /* ── HEADER ── */
        header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .header-logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .header-logo span {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.01em;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        nav a {
            text-decoration: none;
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s, color 0.15s;
            white-space: nowrap;
        }

        nav a:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        nav a.active {
            background-color: #eff6ff;
            color: #2563eb;
        }

        /* Hamburger (mobile) */
        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 0.5rem;
            border: none;
            background: none;
        }

        .menu-toggle span {
            display: block;
            width: 22px;
            height: 2px;
            background: #374151;
            border-radius: 2px;
            transition: transform 0.2s, opacity 0.2s;
        }

        .mobile-nav {
            display: none;
            flex-direction: column;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 0.75rem 1.5rem 1rem;
            gap: 0.25rem;
        }

        .mobile-nav.open {
            display: flex;
        }

        .mobile-nav a {
            text-decoration: none;
            color: #4b5563;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            transition: background-color 0.15s, color 0.15s;
        }

        .mobile-nav a:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        /* ── MAIN CONTENT ── */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .dark body {
            background-color: #111827;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }

        .login-container {
            width: 100%;
            max-width: 28rem;
        }

        .login-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }

        .dark .login-card {
            background-color: #1f2937;
            border: 1px solid #374151;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .logo-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            fill: none;
            stroke-width: 2;
        }

        h1 {
            color: #111827;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .dark h1 {
            color: white;
        }

        #portalMessage {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 1rem 1.25rem;
            border-radius: 0.625rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            font-size: 0.9375rem;
            font-weight: 500;
            display: none;
            line-height: 1.5;
        }

        #portalMessage:not(:empty) {
            display: block;
        }

        #portalMessage:before {
            content: '⚠ ';
            font-size: 1rem;
            margin-right: 0.3125rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            color: #111827;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .dark label {
            color: white;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #f9fafb;
            color: #111827;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .dark input[type="text"],
        .dark input[type="password"] {
            background-color: #374151;
            border-color: #4b5563;
            color: white;
        }

        .dark input[type="text"]::placeholder,
        .dark input[type="password"]::placeholder {
            color: #9ca3af;
        }

        .dark input[type="text"]:focus,
        .dark input[type="password"]:focus {
            border-color: #3b82f6;
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.625rem;
            background-color: #4ade80;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: background-color 0.2s;
        }

        button[type="submit"]:hover {
            background-color: #22c55e;
        }

        .dark button[type="submit"] {
            background-color: #374151;
            color: #e5e7eb;
            border-color: #4b5563;
        }

        .dark button[type="submit"]:hover {
            background-color: #4b5563;
        }

        button[type="submit"]:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .login-card {
                padding: 1.5rem;
            }

            h1 {
                font-size: 1.25rem;
            }

            .logo-icon {
                width: 100px;
                height: 100px;
            }

            .logo-icon svg {
                width: 50px;
                height: 50px;
            }

            nav {
                display: none;
            }

            .menu-toggle {
                display: flex;
            }
        }
    </style>
</head>

<body>

    <!-- ── HEADER ── -->
    <header>
        <div class="header-inner">
            <!-- Logo / marque -->
            <a href="<?= $server_url ?>" class="header-logo">
                <img src="<?= $server_url ?>/Assets/images/OFPPT.png" alt="Logo OFPPT">
            </a>

            <!-- Navigation desktop -->
            <nav>
                <a href="<?= $server_url ?>">Accueil</a>
                <a href="<?= $server_url ?>/user/login">Votre espace</a>
            </nav>

            <!-- Bouton hamburger (mobile) -->
            <button class="menu-toggle" id="menuToggle" aria-label="Ouvrir le menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Navigation mobile -->
        <div class="mobile-nav" id="mobileNav">
            <a href="#">Accueil</a>
            <a href="#">Votre espace</a>
        </div>
    </header>

    <!-- ── CONTENU PRINCIPAL ── -->
    <main>
        <div class="login-container">
            <div class="login-card">
                <div class="logo-container">
                    <div class="logo">
                        <img src="<?= $server_url ?>/Assets/images/OFPPT.png" alt="Logo" class="logo">
                    </div>
                </div>

                <h1>Accéder au réseau</h1>

                <!-- Message d'erreur pfSense -->
                <div id="portalMessage">$PORTAL_MESSAGE$</div>

                <form name="login_form" method="post" action="$PORTAL_ACTION$">
                    <input name="redirurl" type="hidden" value="$PORTAL_REDIRURL$">

                    <div class="form-group">
                        <label for="auth_user">Nom d'utilisateur</label>
                        <input type="text" id="auth_user" name="auth_user" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="auth_pass">Mot de passe</label>
                        <input type="password" id="auth_pass" name="auth_pass" required autocomplete="current-password">
                    </div>

                    <button type="submit" name="accept" value="Login">Se connecter</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Toggle menu mobile
        const toggle = document.getElementById('menuToggle');
        const mobileNav = document.getElementById('mobileNav');

        toggle.addEventListener('click', () => {
            mobileNav.classList.toggle('open');
        });
    </script>

</body>

</html>