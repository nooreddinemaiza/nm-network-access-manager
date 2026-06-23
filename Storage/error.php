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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
            position: relative;
            padding: 20px;
        }

        .background-animation {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            top: 0;
            left: 0;
        }

        .circuit-line {
            position: absolute;
            background: rgba(102, 126, 234, 0.4);
            animation: circuit-flow 4s linear infinite;
        }

        .circuit-line.horizontal {
            height: 2px;
            width: 250px;
        }

        .circuit-line.vertical {
            width: 2px;
            height: 250px;
        }

        .circuit-line:nth-child(1) {
            top: 10%;
            left: -250px;
            animation-delay: 0s;
        }

        .circuit-line:nth-child(2) {
            top: 30%;
            right: -250px;
            animation-delay: 1s;
            animation-direction: reverse;
        }

        .circuit-line:nth-child(3) {
            top: 50%;
            left: -250px;
            animation-delay: 2s;
        }

        .circuit-line:nth-child(4) {
            top: 70%;
            right: -250px;
            animation-delay: 3s;
            animation-direction: reverse;
        }

        .circuit-line:nth-child(5) {
            left: 15%;
            top: -250px;
            animation-delay: 0.5s;
            animation-name: circuit-flow-vertical;
        }

        .circuit-line:nth-child(6) {
            right: 25%;
            top: -250px;
            animation-delay: 1.5s;
            animation-name: circuit-flow-vertical;
        }

        .circuit-line:nth-child(7) {
            left: 60%;
            top: -250px;
            animation-delay: 2.5s;
            animation-name: circuit-flow-vertical;
        }

        .circuit-line:nth-child(8) {
            right: 40%;
            top: -250px;
            animation-delay: 3.5s;
            animation-name: circuit-flow-vertical;
        }

        .circuit-node {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #667eea;
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
            animation: pulse-node 2s ease-in-out infinite;
        }

        .circuit-node:nth-child(9) {
            top: 15%;
            left: 20%;
            animation-delay: 0.5s;
        }

        .circuit-node:nth-child(10) {
            top: 35%;
            right: 15%;
            animation-delay: 1.5s;
        }

        .circuit-node:nth-child(11) {
            top: 55%;
            left: 30%;
            animation-delay: 2.5s;
        }

        .circuit-node:nth-child(12) {
            top: 75%;
            right: 25%;
            animation-delay: 3.5s;
        }

        .circuit-node:nth-child(13) {
            top: 25%;
            left: 50%;
            animation-delay: 1s;
        }

        .circuit-node:nth-child(14) {
            top: 65%;
            right: 45%;
            animation-delay: 2s;
        }

        @keyframes circuit-flow {
            0% {
                opacity: 0;
                transform: translateX(-100%);
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translateX(200%);
            }
        }

        @keyframes circuit-flow-vertical {
            0% {
                opacity: 0;
                transform: translateY(-100%);
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translateY(200%);
            }
        }

        @keyframes pulse-node {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
            }
            50% {
                transform: scale(1.5);
                box-shadow: 0 0 35px rgba(102, 126, 234, 1);
            }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .logo-icon svg {
            width: 45px;
            height: 45px;
            stroke: white;
            fill: none;
            stroke-width: 2;
        }

        h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 15px;
        }

        #portalMessage {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            font-size: 15px;
            font-weight: 500;
            display: none;
            line-height: 1.5;
        }

        #portalMessage:not(:empty) {
            display: block;
        }

        #portalMessage:before {
            content: '⚠ ';
            font-size: 16px;
            margin-right: 5px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
        }

        .checkbox-wrapper label {
            margin: 0;
            font-weight: 400;
            font-size: 14px;
            color: #4a5568;
            cursor: pointer;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: inherit;
        }

        button[type="submit"]:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        button[type="submit"]:hover:before {
            left: 100%;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #718096;
            font-size: 13px;
        }

        .footer-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-text a:hover {
            color: #764ba2;
        }

        /* Responsive */
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }

            .login-card {
                padding: 30px 25px;
            }

            h1 {
                font-size: 24px;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
            }

            .logo-icon svg {
                width: 38px;
                height: 38px;
            }

            .subtitle {
                font-size: 14px;
            }

            input[type="text"],
            input[type="password"] {
                padding: 12px 14px;
                font-size: 14px;
            }

            button[type="submit"] {
                padding: 12px;
                font-size: 15px;
            }
        }

        @media (max-width: 400px) {
            .login-card {
                padding: 25px 20px;
            }

            h1 {
                font-size: 22px;
            }

            .logo-icon {
                width: 60px;
                height: 60px;
            }

            .logo-icon svg {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="background-animation">
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line horizontal"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-line vertical"></div>
        <div class="circuit-node"></div>
        <div class="circuit-node"></div>
        <div class="circuit-node"></div>
        <div class="circuit-node"></div>
        <div class="circuit-node"></div>
        <div class="circuit-node"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <h1>Bienvenue</h1>
                <p class="subtitle">Connectez-vous pour accéder au réseau</p>
            </div>

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

                <button type="submit" name="accept">Se connecter</button>
            </form>

            <div class="footer-text">
                Besoin d'aide ? <a href="#">Contactez le support</a>
            </div>
        </div>
    </div>
</body>
</html>