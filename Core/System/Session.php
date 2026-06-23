<?php

namespace Core\System;

use RuntimeException;

/**
 * Classe de gestion des sessions
 * Point central pour toute gestion de session dans l'application
 */
class Session
{
    private static bool $started = false;
    private static array $config = [
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
        'name' => 'APP_SESSION',
        'regenerate_interval' => 300, // Régénération ID toutes les 5 min
        'remember_me_duration' => 2592000, // 30 jours
    ];

    /**
     * Démarre la session avec une configuration personnalisée
     */
    public static function start(array $config = []): bool
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        self::$config = array_merge(self::$config, $config);

        // Définir le nom de session
        session_name(self::$config['name']);

        // Configuration des cookies de session
        session_set_cookie_params([
            'lifetime' => self::$config['lifetime'],
            'path' => self::$config['path'],
            'domain' => self::$config['domain'],
            'secure' => self::$config['secure'],
            'httponly' => self::$config['httponly'],
            'samesite' => self::$config['samesite']
        ]);

        self::$started = session_start();

        // Initialiser le timestamp de création si nécessaire
        if (!self::has('_created_at')) {
            self::set('_created_at', time());
        }

        // Initialiser le timestamp de dernière activité
        if (!self::has('_last_activity')) {
            self::set('_last_activity', time());
        }

        return self::$started;
    }

    /**
     * Vérifie si la session est démarrée
     */
    public static function isStarted(): bool
    {
        return self::$started || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Définit une valeur en session
     */
    public static function set(string $key, $value): void
    {
        self::start();

        // Gestion des clés avec notation pointée (ex: user.name)
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $current = &$_SESSION;

            foreach ($keys as $i => $k) {
                if ($i === count($keys) - 1) {
                    $current[$k] = $value;
                } else {
                    if (!isset($current[$k]) || !is_array($current[$k])) {
                        $current[$k] = [];
                    }
                    $current = &$current[$k];
                }
            }
        } else {
            $_SESSION[$key] = $value;
        }

        self::updateActivity();
    }

    /**
     * Récupère une valeur de la session
     */
    public static function get(string $key, $default = null)
    {
        self::start();

        // Gestion des clés avec notation pointée
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $current = $_SESSION;

            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    return $default;
                }
                $current = $current[$k];
            }

            return $current;
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe en session
     */
    public static function has(string $key): bool
    {
        self::start();

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $current = $_SESSION;

            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    return false;
                }
                $current = $current[$k];
            }

            return true;
        }

        return isset($_SESSION[$key]);
    }

    /**
     * Supprime une valeur de la session
     */
    public static function delete(string $key): void
    {
        self::start();

        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $current = &$_SESSION;
            $lastKey = array_pop($keys);

            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    return;
                }
                $current = &$current[$k];
            }

            unset($current[$lastKey]);
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Récupère toutes les données de session
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    /**
     * Vide complètement la session
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * Détruit complètement la session
     */
    public static function destroy(): bool
    {
        self::start();
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        self::$started = false;
        return session_destroy();
    }

    /**
     * Régénère l'ID de session (sécurité)
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::start();
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Messages flash (affichés une seule fois)
     */
    public static function flash(string $key, $value = null)
    {
        self::start();

        if ($value === null) {
            $flash = self::get('_flash.' . $key);
            self::delete('_flash.' . $key);
            return $flash;
        }

        self::set('_flash.' . $key, $value);
    }

    /**
     * Vérifie si un message flash existe
     */
    public static function hasFlash(string $key): bool
    {
        self::start();
        return self::has('_flash.' . $key);
    }

    /**
     * Ajoute un message flash au tableau de messages
     */
    public static function addFlash(string $type, string $message): void
    {
        self::start();
        $messages = self::get('_flash_messages.' . $type, []);
        $messages[] = $message;
        self::set('_flash_messages.' . $type, $messages);
    }

    /**
     * Récupère tous les messages flash d'un type
     */
    public static function getFlashMessages(string $type): array
    {
        self::start();
        $messages = self::get('_flash_messages.' . $type, []);
        self::delete('_flash_messages.' . $type);
        return $messages;
    }

    /**
     * Récupère l'ID de session actuel
     */
    public static function getId(): string
    {
        self::start();
        return session_id();
    }

    /**
     * Définit un ID de session personnalisé
     */
    public static function setId(string $id): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Cannot set session ID when session is active');
        }
        session_id($id);
    }

    /**
     * Récupère le nom de la session
     */
    public static function getName(): string
    {
        return session_name();
    }

    /**
     * Met à jour le timestamp de dernière activité
     */
    private static function updateActivity(): void
    {
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Vérifie si la session a expiré (timeout d'inactivité)
     */
    public static function hasExpired(int $timeout = 1800): bool
    {
        self::start();

        $lastActivity = self::get('_last_activity', 0);

        if ($lastActivity === 0) {
            return false;
        }

        return (time() - $lastActivity) > $timeout;
    }

    /**
     * Récupère l'âge de la session en secondes
     */
    public static function getAge(): int
    {
        self::start();
        $createdAt = self::get('_created_at', time());
        return time() - $createdAt;
    }

    /**
     * Sauvegarde des données temporaires avec expiration
     */
    public static function setTemp(string $key, $value, int $ttl = 300): void
    {
        self::start();
        self::set('_temp.' . $key, [
            'value' => $value,
            'expires_at' => time() + $ttl
        ]);
    }

    /**
     * Récupère une donnée temporaire
     */
    public static function getTemp(string $key, $default = null)
    {
        self::start();

        $temp = self::get('_temp.' . $key);

        if ($temp === null) {
            return $default;
        }

        if (time() > $temp['expires_at']) {
            self::delete('_temp.' . $key);
            return $default;
        }

        return $temp['value'];
    }

    /**
     * Nettoie les données temporaires expirées
     */
    public static function cleanTempData(): void
    {
        self::start();

        $tempData = self::get('_temp', []);

        foreach ($tempData as $key => $data) {
            if (isset($data['expires_at']) && time() > $data['expires_at']) {
                self::delete('_temp.' . $key);
            }
        }
    }

    /**
     * Récupère les informations de la session
     */
    public static function info(): array
    {
        self::start();

        return [
            'id' => self::getId(),
            'name' => self::getName(),
            'age' => self::getAge(),
            'created_at' => self::get('_created_at'),
            'last_activity' => self::get('_last_activity'),
            'cookie_params' => session_get_cookie_params()
        ];
    }

    /**
     * Sauvegarde et ferme la session (libère le fichier de session)
     */
    public static function save(): void
    {
        if (self::isStarted()) {
            session_write_close();
            self::$started = false;
        }
    }

    /**
     * Vérifie et restaure une session depuis le cookie "Remember Me"
     */
    public static function checkRememberMe(callable $getUserCallback): bool
    {
        self::start();

        if (self::isAuthenticated()) {
            return true;
        }

        if (!isset($_COOKIE['remember_me'])) {
            return false;
        }

        $parts = explode(':', $_COOKIE['remember_me'], 2);
        if (count($parts) !== 2) {
            self::deleteRememberMeCookie();
            return false;
        }

        [$userId, $token] = $parts;
        $hashedToken = hash('sha256', $token);

        // Récupérer l'utilisateur via le callback fourni
        $user = $getUserCallback($userId);

        if (!$user) {
            self::deleteRememberMeCookie();
            return false;
        }

        // Valider le token stocké
        $storedToken = $user['remember_token'] ?? null;
        if (!$storedToken || !hash_equals($storedToken, $hashedToken)) {
            self::deleteRememberMeCookie();
            return false;
        }

        // Restaurer la session
        self::login($userId, $user, true);
        return true;
    }

    /**
     * Connecte un utilisateur
     */
    public static function login(
        int|string $userId,
        array $userData = [],
        bool $remember = false,
        string $client_ip = "",
        string $client_agent = ""
    ): void {
        self::start();

        // Régénérer l'ID de session pour prévenir la fixation de session
        self::regenerate(true);

        // Stocker les informations d'authentification
        self::set('_auth.authenticated', true);
        self::set('_auth.user_id', $userId);
        self::set('_auth.user_type', $userData['type']);
        self::set('_auth.login_time', time());
        self::set('_auth.ip_address', $client_ip);
        self::set('_auth.user_agent',  $client_agent);

        // Stocker les données utilisateur
        if (!empty($userData)) {
            self::set('_auth.user_data', $userData);
        }

        // Gestion du "Se souvenir de moi"
        if ($remember) {
            self::setRememberMeCookie($userId);
        }
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        self::start();

        // Supprimer le cookie "Remember Me"
        self::deleteRememberMeCookie();

        // Supprimer toutes les données d'authentification
        self::delete('_auth');

        // Régénérer l'ID de session
        self::regenerate(true);
    }
    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public static function getUserType(): int|string|null
    {
        self::start();
        return self::get('_auth.user_type');
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return self::get('_auth.authenticated', false) === true;
    }

    /**
     * Récupère l'ID de l'utilisateur connecté
     */
    public static function getUserId(): int|string|null
    {
        self::start();
        return self::get('_auth.user_id');
    }

    /**
     * Récupère les données de l'utilisateur connecté
     */
    public static function getUserData(string $key = null, $default = null)
    {
        self::start();

        if ($key === null) {
            return self::get('_auth.user_data', []);
        }

        return self::get('_auth.user_data.' . $key, $default);
    }
    /**
     * Définit un cookie "Remember Me"
     */
    private static function setRememberMeCookie(int|string $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Stocker le token haché en session pour validation ultérieure
        self::set('_auth.remember_token', $hashedToken);

        // Définir le cookie avec le token
        setcookie(
            'remember_me',
            $userId . ':' . $token,
            time() + self::$config['remember_me_duration'],
            self::$config['path'],
            self::$config['domain'],
            self::$config['secure'],
            true // httponly
        );
    }
    /**
     * Supprime le cookie "Remember Me"
     */
    private static function deleteRememberMeCookie(): void
    {
        if (isset($_COOKIE['remember_me'])) {
            setcookie(
                'remember_me',
                '',
                time() - 3600,
                self::$config['path'],
                self::$config['domain'],
                self::$config['secure'],
                true
            );
        }
        self::delete('_auth.remember_token');
    }
    /**
     * Incrémente un compteur de tentatives (limitation)
     */
    public static function incrementAttempt(string $key, int $maxAttempts = 5, int $window = 900): array
    {
        self::start();

        $attempts = self::get('_attempts.' . $key, [
            'count' => 0,
            'first_attempt' => time(),
            'blocked_until' => 0
        ]);

        // Vérifier si le blocage est actif
        if ($attempts['blocked_until'] > time()) {
            return [
                'blocked' => true,
                'remaining_time' => $attempts['blocked_until'] - time(),
                'attempts' => $attempts['count']
            ];
        }

        // Réinitialiser si la fenêtre de temps est dépassée
        if ((time() - $attempts['first_attempt']) > $window) {
            $attempts = [
                'count' => 1,
                'first_attempt' => time(),
                'blocked_until' => 0
            ];
        } else {
            $attempts['count']++;

            // Bloquer si le maximum de tentatives est atteint
            if ($attempts['count'] >= $maxAttempts) {
                $attempts['blocked_until'] = time() + $window;
            }
        }

        self::set('_attempts.' . $key, $attempts);

        return [
            'blocked' => $attempts['blocked_until'] > time(),
            'remaining_time' => max(0, $attempts['blocked_until'] - time()),
            'attempts' => $attempts['count']
        ];
    }

    /**
     * Réinitialise un compteur de tentatives
     */
    public static function resetAttempts(string $key): void
    {
        self::start();
        self::delete('_attempts.' . $key);
    }
}
