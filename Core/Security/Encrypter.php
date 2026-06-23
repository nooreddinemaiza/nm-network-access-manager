<?php

namespace Core\Security;

use Core\File;
use Exception;
use RuntimeException;
use InvalidArgumentException;

class Encrypter
{
    private string $encryptionKey;
    private const CIPHER_METHOD = 'aes-256-gcm';
    private const KEY_LENGTH = 32;
    private const TAG_LENGTH = 16;

    public function __construct(?string $key = null, ?bool $return_key = true)
    {
        if ($key === null) {
            $this->encryptionKey = $this->secret_key($return_key);
        } else {
            if (strlen($key) !== self::KEY_LENGTH) {
                throw new InvalidArgumentException('La clé doit faire exactement 32 bytes');
            }
            $this->encryptionKey = $key;
        }

        if (!in_array(self::CIPHER_METHOD, openssl_get_cipher_methods())) {
            throw new RuntimeException('La méthode de chiffrement AES-256-GCM n\'est pas disponible');
        }
    }

    public function generateKey(): string
    {
        return random_bytes(self::KEY_LENGTH);
    }

    private function secret_key(?bool $return_key = true): string
    {
        // ✅ On bypasse File::require pour éviter le cache
        // et on lit directement le fichier pour avoir la valeur fraîche
        $content = [];

        if (File::exists('config', 'secret.php')) {
            // Cache désactivé : la clé peut venir d'être écrite
            // dans cette même exécution
            $content = File::require('config', 'secret.php', false);
        }

        if (
            is_array($content)
            && !empty($content['secret'])
            && is_string($content['secret'])
        ) {
            $decoded = base64_decode($content['secret'], true);

            // Clé valide : bonne longueur, décodage réussi
            if ($decoded !== false && strlen($decoded) === self::KEY_LENGTH) {
                return $decoded;
            }
        }

        // Génère une nouvelle clé
        $key     = $this->generateKey();
        $safeKey = base64_encode($key);

        // ✅ Heredoc NOWDOC (marqueur entre apostrophes) : aucune interpolation,
        // aucun caractère spécial ne peut casser le contenu du fichier
        $fileContent = <<<'ENDOFFILE'
<?php
// Ne pas modifier ce fichier. Il est généré automatiquement.
// La clé est encodée en base64 — ne jamais changer sa valeur.
return [
    'secret' => '
ENDOFFILE;
        $fileContent .= $safeKey . "'\n];\n";

        File::write('config', 'secret.php', $fileContent);

        // ✅ On vide le cache File pour que le prochain require
        // relise le fichier depuis le disque
        File::clearCache('config', 'secret.php');

        return $return_key ? $key : '';
    }
    /**
     * Chiffre une donnée
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Les données à chiffrer ne peuvent pas être vides');
        }

        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Échec du chiffrement');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Déchiffre une donnée
     */
    public function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            throw new InvalidArgumentException('Les données à déchiffrer ne peuvent pas être vides');
        }

        $data = base64_decode($encryptedData, true);
        if ($data === false) {
            throw new InvalidArgumentException('Format de données invalide');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);

        if (strlen($data) < $ivLength + self::TAG_LENGTH) {
            throw new InvalidArgumentException('Données chiffrées trop courtes');
        }

        $iv         = substr($data, 0, $ivLength);
        $tag        = substr($data, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($data, $ivLength + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Échec du déchiffrement ou données corrompues');
        }

        return $plaintext;
    }

    /**
     * Hash un mot de passe utilisateur (bcrypt — irréversible)
     * 
     * ✅ FIX 3 : Pour les mots de passe utilisateurs, on NE DOIT PAS chiffrer
     * (réversible). On utilise un hash à sens unique : si la DB est compromise,
     * les mots de passe restent protégés.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Vérifie un mot de passe utilisateur contre son hash bcrypt
     */
    public static function verifyHashedPassword(string $password, string $hash): bool
    {
        if (empty($password) || empty($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * Vérifie si un mot de passe correspond à une valeur CHIFFRÉE (AES)
     * À utiliser uniquement pour des données qui nécessitent d'être déchiffrées
     * (ex: mot de passe de base de données, token d'API, etc.)
     * NE PAS utiliser pour les mots de passe des comptes utilisateurs.
     */
    public function verifyEncryptedSecret(string $plaintext, string $encryptedValue): bool
    {
        if (empty($plaintext) || empty($encryptedValue)) {
            return false;
        }

        try {
            $decrypted = $this->decrypt($encryptedValue);
            return hash_equals($decrypted, $plaintext);
        } catch (Exception) {
            return false;
        }
    }

    // --- Le reste de vos méthodes, inchangées ---

    public static function generateStrongPassword(
        int $length = 16,
        bool $includeUppercase = true,
        bool $includeLowercase = true,
        bool $includeNumbers = true,
        bool $includeSpecial = true,
        bool $avoidAmbiguous = false
    ): string {
        if ($length < 12) {
            throw new InvalidArgumentException('La longueur minimale est 12 caractères');
        }
        if (!$includeUppercase && !$includeLowercase && !$includeNumbers && !$includeSpecial) {
            throw new InvalidArgumentException('Au moins un type de caractère doit être activé');
        }

        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers   = '0123456789';
        $special   = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        if ($avoidAmbiguous) {
            $uppercase = str_replace(['O', 'I'], '', $uppercase);
            $lowercase = str_replace(['l', 'o'], '', $lowercase);
            $numbers   = str_replace(['0', '1'], '', $numbers);
            $special   = str_replace(['|', '`'], '', $special);
        }

        $charPool = '';
        $required = [];

        if ($includeUppercase) {
            $charPool .= $uppercase;
            $required[] = $uppercase;
        }
        if ($includeLowercase) {
            $charPool .= $lowercase;
            $required[] = $lowercase;
        }
        if ($includeNumbers) {
            $charPool .= $numbers;
            $required[] = $numbers;
        }
        if ($includeSpecial) {
            $charPool .= $special;
            $required[] = $special;
        }

        $poolLength = strlen($charPool);
        $password   = '';

        foreach ($required as $chars) {
            $password .= $chars[self::secureRandomInt(0, strlen($chars) - 1)];
        }

        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $charPool[self::secureRandomInt(0, $poolLength - 1)];
        }

        return str_shuffle($password);
    }

    private static function secureRandomInt(int $min, int $max): int
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Min doit être inférieur ou égal à Max');
        }
        try {
            return random_int($min, $max);
        } catch (Exception $e) {
            throw new RuntimeException('Impossible de générer un nombre aléatoire sécurisé', 0, $e);
        }
    }

    public static function evaluatePasswordStrength(string $password): array
    {
        $score    = 0;
        $feedback = [];
        $length   = strlen($password);

        if ($length >= 16) {
            $score += 30;
        } elseif ($length >= 12) {
            $score += 20;
            $feedback[] = 'Augmentez la longueur à 16+ caractères pour plus de sécurité';
        } elseif ($length >= 8) {
            $score += 10;
            $feedback[] = 'Le mot de passe est trop court (minimum recommandé : 12 caractères)';
        } else {
            $feedback[] = 'Le mot de passe est beaucoup trop court';
        }

        $hasLower   = preg_match('/[a-z]/', $password);
        $hasUpper   = preg_match('/[A-Z]/', $password);
        $hasNumber  = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

        $score += ($hasLower + $hasUpper + $hasNumber + $hasSpecial) * 10;

        if (!$hasLower)   $feedback[] = 'Ajoutez des lettres minuscules';
        if (!$hasUpper)   $feedback[] = 'Ajoutez des lettres majuscules';
        if (!$hasNumber)  $feedback[] = 'Ajoutez des chiffres';
        if (!$hasSpecial) $feedback[] = 'Ajoutez des caractères spéciaux';

        if (!preg_match('/(.)\1{2,}/', $password)) {
            $score += 15;
        } else {
            $feedback[] = 'Évitez les répétitions de caractères';
        }

        $commonSequences  = ['123', 'abc', 'qwerty', 'azerty', 'password', 'admin'];
        $hasCommonSequence = false;
        foreach ($commonSequences as $seq) {
            if (stripos($password, $seq) !== false) {
                $hasCommonSequence = true;
                break;
            }
        }
        if (!$hasCommonSequence) {
            $score += 15;
        } else {
            $feedback[] = 'Évitez les séquences communes (123, abc, qwerty, etc.)';
        }

        if ($score >= 80)     $strengthLevel = 'Très fort';
        elseif ($score >= 60) $strengthLevel = 'Fort';
        elseif ($score >= 40) $strengthLevel = 'Moyen';
        elseif ($score >= 20) $strengthLevel = 'Faible';
        else                  $strengthLevel = 'Très faible';

        return [
            'score'          => $score,
            'strength_level' => $strengthLevel,
            'feedback'       => empty($feedback) ? ['Excellent mot de passe !'] : $feedback,
        ];
    }

    public function exportKey(): string
    {
        return base64_encode($this->encryptionKey);
    }

    public static function fromBase64Key(string $base64Key): self
    {
        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== self::KEY_LENGTH) {
            throw new InvalidArgumentException('Clé base64 invalide');
        }
        return new self($key);
    }

    public function encryptBatch(array $dataArray): array
    {
        $encrypted = [];
        foreach ($dataArray as $key => $value) {
            $encrypted[$key] = $this->encrypt($value);
        }
        return $encrypted;
    }

    public function decryptBatch(array $encryptedArray): array
    {
        $decrypted = [];
        foreach ($encryptedArray as $key => $value) {
            $decrypted[$key] = $this->decrypt($value);
        }
        return $decrypted;
    }

    public static function generateUuid64(): string
    {
        $hex = bin2hex(random_bytes(8));
        return sprintf('%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4));
    }

    public static function radiusCryptPassword(string $password): string
    {
        $salt = '$6$' . bin2hex(random_bytes(16)) . '$';
        return crypt($password, $salt);
    }

    public static function verifyRadiusPassword(string $password, string $hashedPassword): bool
    {
        return hash_equals($hashedPassword, crypt($password, $hashedPassword));
    }
}
