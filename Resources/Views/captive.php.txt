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
            align-items: center;
            justify-content: center;
            background-color: #0a0e27;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .circuit-line {
            position: absolute;
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            animation: pulse 3s ease-in-out infinite;
        }

        .circuit-line.horizontal {
            height: 2px;
            width: 100%;
            left: 0;
        }

        .circuit-line.vertical {
            width: 2px;
            height: 100%;
            top: 0;
        }

        .circuit-line:nth-child(1) { top: 20%; animation-delay: 0s; }
        .circuit-line:nth-child(2) { top: 50%; animation-delay: 1s; }
        .circuit-line:nth-child(3) { top: 80%; animation-delay: 2s; }
        .circuit-line:nth-child(4) { left: 20%; animation-delay: 0.5s; }
        .circuit-line:nth-child(5) { left: 50%; animation-delay: 1.5s; }
        .circuit-line:nth-child(6) { left: 80%; animation-delay: 2.5s; }

        @keyframes pulse {
            0%, 100% { opacity: 0.1; }
            50% { opacity: 0.4; }
        }

        /* Floating Particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #3b82f6;
            border-radius: 50%;
            box-shadow: 0 0 10px #3b82f6;
            animation: float 15s linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        /* Grid Background */
        .grid-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .login-container {
            width: 100%;
            max-width: 28rem;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(31, 41, 55, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.2);
            padding: 2rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
            position: relative;
            animation: cardEntrance 0.8s ease-out;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Corner Decorations */
        .login-card::before,
        .login-card::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid #3b82f6;
            animation: cornerPulse 2s ease-in-out infinite;
        }

        .login-card::before {
            top: -1px;
            left: -1px;
            border-right: none;
            border-bottom: none;
            border-top-left-radius: 1rem;
        }

        .login-card::after {
            bottom: -1px;
            right: -1px;
            border-left: none;
            border-top: none;
            border-bottom-right-radius: 1rem;
        }

        @keyframes cornerPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; box-shadow: 0 0 20px #3b82f6; }
        }

        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.25rem;
            position: relative;
        }

        .logo-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
        }

        /* Animated Ring Around Logo */
        .logo-ring {
            position: absolute;
            top: -10px;
            left: -10px;
            width: 140px;
            height: 140px;
            border: 2px solid transparent;
            border-top-color: #3b82f6;
            border-right-color: #3b82f6;
            border-radius: 50%;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-ring::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            right: 5px;
            bottom: 5px;
            border: 2px solid transparent;
            border-bottom-color: #8b5cf6;
            border-left-color: #8b5cf6;
            border-radius: 50%;
            animation: rotate 2s linear infinite reverse;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            position: relative;
            z-index: 2;
            animation: logoFloat 3s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.5));
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            animation: titleGlow 2s ease-in-out infinite;
        }

        @keyframes titleGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
            50% { text-shadow: 0 0 20px rgba(59, 130, 246, 0.8); }
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
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        #portalMessage:not(:empty) {
            display: block;
        }

        #portalMessage:before {
            content: '⚠ ';
            font-size: 1rem;
            margin-right: 0.3125rem;
            animation: blink 1s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            color: #e5e7eb;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: color 0.3s;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 0.5rem;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6, #3b82f6);
            background-size: 200% 200%;
            opacity: 0;
            transition: opacity 0.3s;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .input-wrapper:focus-within::before {
            opacity: 1;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #4b5563;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: rgba(55, 65, 81, 0.8);
            color: white;
            font-family: inherit;
            position: relative;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            background: rgba(55, 65, 81, 1);
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: #9ca3af;
        }

        /* Input Icon Animation */
        .form-group:focus-within label {
            color: #3b82f6;
            transform: translateX(5px);
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-size: 200% 200%;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            animation: gradientShift 3s ease infinite;
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        button[type="submit"]:hover::before {
            width: 300px;
            height: 300px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
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

            .logo-wrapper {
                width: 100px;
                height: 100px;
            }

            .logo {
                width: 100px;
                height: 100px;
            }

            .logo-ring {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="grid-bg"></div>
    <div class="animated-bg">
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-line vertical"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo-wrapper">
                    <div class="logo-ring"></div>
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
                    <div class="input-wrapper">
                        <input type="text" id="auth_user" name="auth_user" required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auth_pass">Mot de passe</label>
                    <div class="input-wrapper">
                        <input type="password" id="auth_pass" name="auth_pass" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" name="accept" value="Login">Se connecter</button>
            </form>
        </div>
    </div>

    <script>
        // Generate floating particles
        const particleCount = 30;
        const animatedBg = document.querySelector('.animated-bg');
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            animatedBg.appendChild(particle);
        }

        // Add input focus effect
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>