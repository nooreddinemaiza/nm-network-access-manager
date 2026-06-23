<?php

namespace Core\System;

/**
 * Classe de gestion des tokens CSRF
 * Utilise la classe Session pour la persistance
 */
class CSRF
{
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 heure

    /**
     * Génère un nouveau token CSRF et le stocke en session
     */
    public static function generateToken(): string
    {
        Session::start();

        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));

        Session::set(self::TOKEN_NAME, [
            'value' => $token,
            'created_at' => time()
        ]);

        return $token;
    }

    /**
     * Récupère le token CSRF actuel ou en génère un nouveau
     */
    public static function getToken(): string
    {
        Session::start();

        $tokenData = Session::get(self::TOKEN_NAME);

        // Générer un nouveau token si inexistant ou expiré
        if ($tokenData === null || self::isTokenExpired($tokenData)) {
            return self::generateToken();
        }

        return $tokenData['value'];
    }

    /**
     * Vérifie si le token a expiré
     */
    private static function isTokenExpired(array $tokenData): bool
    {
        if (!isset($tokenData['created_at'])) {
            return true;
        }

        return (time() - $tokenData['created_at']) > self::TOKEN_LIFETIME;
    }

    /**
     * Génère une balise meta pour le token CSRF
     */
    public static function getMetaTag(string $name = 'csrf-token'): string
    {
        $token = self::getToken();
        return sprintf(
            '<meta name="%s" content="%s">',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Génère un champ input hidden pour le token CSRF
     */
    public static function getInputField(string $name = 'csrf_token'): string
    {
        $token = self::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Retourne uniquement le token (string brut)
     */
    public static function getTokenString(): string
    {
        return self::getToken();
    }

    /**
     * Génère un token sous forme de tableau associatif
     */
    public static function getTokenArray(string $key = 'csrf_token'): array
    {
        return [$key => self::getToken()];
    }

    /**
     * Génère un token au format JSON
     */
    public static function getTokenJson(string $key = 'csrf_token'): string
    {
        return json_encode(self::getTokenArray($key));
    }

    /**
     * Vérifie si le token CSRF est valide
     */
    public static function validateToken($token): bool
    {
        if (!$token || !is_string($token)) {
            return false;
        }
        Session::start();

        $tokenData = Session::get(self::TOKEN_NAME);

        if ($tokenData === null) {
            return false;
        }

        // Vérifier l'expiration
        if (self::isTokenExpired($tokenData)) {
            return false;
        }

        // Vérification avec protection contre les timing attacks
        return hash_equals($tokenData['value'], $token);
    }

    /**
     * Vérifie le token depuis la requête
     */
    public static function validateRequest(string $fieldName = 'csrf_token'): bool
    {
        // Vérifier dans POST
        if (isset($_POST[$fieldName])) {
            return self::validateToken($_POST[$fieldName]);
        }

        // Vérifier dans les headers HTTP
        $headers = [
            'HTTP_X_CSRF_TOKEN',
            'HTTP_X_XSRF_TOKEN',
            'HTTP_CSRF_TOKEN'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return self::validateToken($_SERVER[$header]);
            }
        }

        // Vérifier dans GET (déconseillé mais possible)
        if (isset($_GET[$fieldName])) {
            return self::validateToken($_GET[$fieldName]);
        }

        return false;
    }

    /**
     * Régénère le token CSRF
     */
    public static function regenerateToken(): string
    {
        return self::generateToken();
    }

    /**
     * Supprime le token CSRF
     */
    public static function deleteToken(): void
    {
        Session::start();
        Session::delete(self::TOKEN_NAME);
    }

    /**
     * Vérifie la requête et lève une exception si invalide
     */
    public static function validateOrFail(string $fieldName = 'csrf_token', string $message = 'Invalid CSRF token'): void
    {
        if (!self::validateRequest($fieldName)) {
            http_response_code(403);
            throw new \RuntimeException($message);
        }
    }

    /**
     * Middleware pour valider automatiquement le CSRF
     */
    public static function middleware(string $fieldName = 'csrf_token'): bool
    {
        // Ne vérifier que pour les méthodes POST, PUT, PATCH, DELETE
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return self::validateRequest($fieldName);
        }

        return true; // GET et HEAD ne nécessitent pas de validation
    }

    /**
     * Récupère les informations du token
     */
    public static function getTokenInfo(): ?array
    {
        Session::start();

        $tokenData = Session::get(self::TOKEN_NAME);

        if ($tokenData === null) {
            return null;
        }

        return [
            'value' => $tokenData['value'],
            'created_at' => $tokenData['created_at'],
            'expires_at' => $tokenData['created_at'] + self::TOKEN_LIFETIME,
            'is_expired' => self::isTokenExpired($tokenData),
            'age' => time() - $tokenData['created_at'],
            'remaining' => max(0, self::TOKEN_LIFETIME - (time() - $tokenData['created_at']))
        ];
    }
}
