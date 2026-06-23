<?php

namespace Core;

/**
 * Classe de gestion des logs de l'application
 * 
 * Permet de journaliser les événements de l'application avec :
 * - Rotation automatique des fichiers de logs (taille max 10MB)
 * - Nettoyage automatique des vieux logs (> 2 jours)
 * - Limitation du nombre de fichiers de backup (max 5)
 * - Cache des messages pour éviter les doublons
 * - Cooldown entre les messages identiques (10s)
 * - Durée de vie maximale des messages en cache (1h)
 * - Limitation de la taille des messages (255 caractères)
 */

class Logger
{
    private static $logFile;
    private static $messageCooldown = 240;
    private static $maxMessageLifetime = 3600;
    private static $maxMessageLength = 255;
    private static $messagesCache = [];
    private static $messageTimestamp = [];
    private static $messagesOccurrences = [];

    // Nouvelles constantes pour la gestion des logs
    private static $maxFileSize = 10 * 1024 * 1024; // 10 MB en octets
    private static $maxLogAge = 172800; // 2 jours en secondes
    private static $maxBackupFiles = 5; // Nombre maximum de fichiers de backup

    // Initialisation du fichier de log avec vérification de rotation
    private static function initLogFile(): void
    {
        if (self::$logFile === null) {
            try {
                self::$logFile = File::getPath('log', 'app.log');
                self::checkLogRotation();
            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de l'initialisation du fichier de log : " . $e->getMessage());
            }
        }
    }

    // Vérifie si une rotation des logs est nécessaire
    private static function checkLogRotation(): void
    {
        try {
            if (!file_exists(self::$logFile)) {
                return;
            }

            $needRotation = false;

            // Vérifier la taille du fichier
            if (filesize(self::$logFile) > self::$maxFileSize) {
                $needRotation = true;
            }

            // Vérifier l'âge des logs
            self::cleanOldLogs();

            // Si rotation nécessaire, effectuer la rotation
            if ($needRotation) {
                self::rotateLogFile();
            }
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de la vérification de rotation des logs : " . $e->getMessage());
        }
    }

    // Nettoie les vieilles lignes de log
    private static function cleanOldLogs(): void
    {
        if (!file_exists(self::$logFile)) {
            return;
        }

        try {
            $tempFile = self::$logFile . '.tmp';
            $currentTime = time();
            $handle = fopen(self::$logFile, 'r');
            $tempHandle = fopen($tempFile, 'w');

            while (($line = fgets($handle)) !== false) {
                // Extraire la date du log (format: [dd:mm:HH:ii])
                if (preg_match('/\[([\d:]+)\]/', $line, $matches)) {
                    $logDate = \DateTime::createFromFormat('d:m:H:i', $matches[1]);
                    if ($logDate) {
                        $logTimestamp = $logDate->getTimestamp();
                        // Garder uniquement les logs plus récents que maxLogAge
                        if (($currentTime - $logTimestamp) < self::$maxLogAge) {
                            fwrite($tempHandle, $line);
                        }
                    }
                }
            }

            fclose($handle);
            fclose($tempHandle);

            // Remplacer l'ancien fichier par le nouveau
            rename($tempFile, self::$logFile);
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors du nettoyage des vieux logs : " . $e->getMessage());
        }
    }

    // Effectue la rotation du fichier de log
    private static function rotateLogFile(): void
    {
        try {
            $logDir = dirname(self::$logFile);
            $baseLogFile = basename(self::$logFile);

            // Supprimer le plus vieux backup si nécessaire
            if (file_exists($logDir . '/' . $baseLogFile . '.' . self::$maxBackupFiles)) {
                unlink($logDir . '/' . $baseLogFile . '.' . self::$maxBackupFiles);
            }

            // Rotation des fichiers existants
            for ($i = self::$maxBackupFiles - 1; $i >= 1; $i--) {
                $oldFile = $logDir . '/' . $baseLogFile . '.' . $i;
                $newFile = $logDir . '/' . $baseLogFile . '.' . ($i + 1);
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }

            // Déplacer le fichier actuel
            if (file_exists(self::$logFile)) {
                rename(self::$logFile, $logDir . '/' . $baseLogFile . '.1');
            }

            // Créer un nouveau fichier de log vide
            touch(self::$logFile);
            chmod(self::$logFile, 0644);
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de la rotation du fichier de log : " . $e->getMessage());
        }
    }

    // Méthode pour forcer une rotation des logs
    public static function forceRotation(): void
    {
        self::initLogFile();
        self::rotateLogFile();
    }
    // Sanitize le message pour éviter les caractères spéciaux dans le log
    private static function sanitizeMessage(string $message): string
    {
        return substr($message, 0, self::$maxMessageLength);
    }

    // Vérifie si un message est soumis à une limite de fréquence
    private static function isRateLimited(string $message): bool
    {
        self::initLogFile();
        $currentTimestamp = time();
        if (!isset(self::$messagesCache[$message]) || (self::$messagesCache[$message] < $currentTimestamp - self::$messageCooldown)) {
            self::$messagesCache[$message] = $currentTimestamp;
            return false;
        }
        return true;
    }

    // Mise à jour des logs dans le fichier
    private static function updateLog(string $level, string $message): void
    {
        $message = self::sanitizeMessage($message);
        if (self::isRateLimited($message)) {
            return; // Si le message est limité, on l'ignore
        }
        self::initLogFile();
        $currentTimestamp = time();
        $formattedDate = (new \DateTime())->format('d:m:H:i'); // Format jour:mois:heure:minute

        // Calculer le nombre d'occurrences
        if (!isset(self::$messagesOccurrences[$message])) {
            self::$messagesOccurrences[$message] = [
                'level' => $level,
                'count' => 1,
                'times' => [$formattedDate]
            ];
        } else {
            self::$messagesOccurrences[$message]['count']++;
            self::$messagesOccurrences[$message]['times'][] = $formattedDate;
        }

        // Formatage du message à ajouter dans le log
        $logMessage = self::formatLogMessage($message, $level);

        // Écriture dans le fichier de log
        try {
            file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de l'écriture dans le fichier de log : " . $e->getMessage());
        }

        self::$messageTimestamp[$message] = $currentTimestamp;
    }

    // Formatte le message pour l'ajouter au log selon les règles définies
    private static function formatLogMessage(string $message, string $level): string
    {
        $data = self::$messagesOccurrences[$message];
        $count = $data['count'];
        $times = implode(' | ', $data['times']);
        $formattedMessage = '';

        if ($count > 1) {
            // Si le message est répété, format selon les règles
            if (count($data['times']) === 1) {
                $formattedMessage = "[{$times}] -> [{$level}] -> {$count} -> {$message}";
            } else {
                $formattedMessage = "[{$times}] -> [{$level}] -> {$count} -> {$message}";
            }
        } else {
            $formattedMessage = "[{$times}] -> [{$level}] -> {$message}";
        }

        return $formattedMessage . PHP_EOL;
    }

    // Nettoyer les logs expirés
    public static function cleanExpiredLogs(): void
    {
        $currentTimestamp = time();
        foreach (self::$messagesOccurrences as $message => $data) {
            if ($currentTimestamp - self::$messageTimestamp[$message] > self::$maxMessageLifetime) {
                unset(self::$messagesOccurrences[$message]);
            }
        }
    }

    // Récupérer les logs d'un message spécifique
    public static function getLogs(string $message): array
    {
        return isset(self::$messagesOccurrences[$message]) ? self::$messagesOccurrences[$message] : [];
    }

    // Fonction pour obtenir le nombre d'occurrences d'un message
    public static function getOccurrenceCount(string $message): int
    {
        return isset(self::$messagesOccurrences[$message]) ? self::$messagesOccurrences[$message]['count'] : 0;
    }

    public static function filterLogs(?string $dateRange = null, ?string $level = null, ?string $searchText = null): array
    {
        self::initLogFile();

        $logContents = file_get_contents(self::$logFile);
        $lines = explode(PHP_EOL, $logContents);
        $filteredLogs = [];

        // Calcul des dates limites en fonction de la période demandée
        $now = new \DateTime();
        $startDate = null;

        switch ($dateRange) {
            case "today":
                $startDate = $now->format("d:m:Y"); // Ex: 20:03:2025
                break;
            case "week":
                $startDate = (clone $now)->modify("-7 days")->format("d:m:Y");
                break;
            case "month":
                $startDate = (clone $now)->modify("-30 days")->format("d:m:Y");
                break;
        }

        foreach ($lines as $line) {
            if (empty($line)) continue;

            // Expression régulière pour extraire les parties du log
            if (preg_match('/^\[(\d{2}):(\d{2}):(\d{2}):(\d{2})\] -> \[(\w+)\] -> (.+)$/', $line, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $logTime = $matches[4];
                $logLevel = $matches[5];
                $message = $matches[6];

                // Reformater la date extraite
                $logDate = "$day:$month:$year";

                // Vérifier si la date du log est dans la plage demandée
                $matchesDate = ($startDate === null || $logDate >= $startDate);
                $matchesLevel = ($level === null || stripos($logLevel, $level) !== false);
                $matchesText = ($searchText === null || stripos($message, $searchText) !== false);

                if ($matchesDate && $matchesLevel && $matchesText) {
                    $filteredLogs[] = $line;
                }
            }
        }
        return $filteredLogs;
    }

    // Fonction pour gérer les différents niveaux de log
    public static function info(string $message): void
    {
        self::updateLog("INFO", $message);
    }

    public static function warning(string $message, ?array $plus = []): void
    {
        if ($plus) $message = $message . implode(',', $plus);
        self::updateLog("WARNING", $message);
    }

    public static function error(string $message, ?array $plus = []): void
    {
        if ($plus) $message = $message . implode(',', $plus);
        self::updateLog("ERROR", $message);
    }

    public static function debug(string $message, ?array $plus = []): void
    {
        if ($plus) $message = $message . implode(',', $plus);
        self::updateLog("DEBUG", $message);
    }

    public static function critical(string $message, ?array $plus = []): void
    {
        if ($plus) $message = $message . implode(',', $plus);
        self::updateLog("CRITICAL", $message);
    }

    /**
     * Exporte le fichier de log sous forme de document HTML
     * 
     * @param string|null $title Titre du document HTML
     * @param string|null $dateFormat Format de date pour filtrer les logs (optionnel)
     * @param string|null $level Niveau de log pour filtrer les logs (optionnel)
     * @param string|null $searchText Texte à rechercher dans les logs (optionnel)
     * @param bool $includeStyles Inclure les styles CSS (défaut: true)
     * @return string Document HTML contenant les logs
     */
    public static function exportToHtml(?string $title = 'Logs de l\'application', ?string $dateFormat = null, ?string $level = null, ?string $searchText = null, bool $includeStyles = true)
    {
        self::initLogFile();

        // Filtrer les logs selon les critères
        $logs = self::filterLogs($dateFormat, $level, $searchText);

        // Générer le code CSS pour le document HTML
        $css = $includeStyles ? '
        <style>
            body {
                font-family: "Poppins", sans-serif;
                margin: 0;
                padding: 20px;
                background: #f4f7f6;
                color: #333;
            }
            h1 {
                color: #222;
                text-align: center;
            }
            .container {
                max-width: 900px;
                margin: 20px auto;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .log-container {
                overflow-x: auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background: #007bff;
                color: white;
            }
            .info { background-color: #d9edf7; }
            .warning { background-color: #fcf8e3; }
            .error { background-color: #f2dede; }
            .debug { background-color: #d1ecf1; }
            .critical { background-color: #f8d7da; }
            .timestamp {
                text-align: center;
                color: #666;
                font-size: 0.9em;
                margin-bottom: 10px;
            }
            .filters {
                background: #e9ecef;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
            }
            .filters ul {
                list-style: none;
                padding: 0;
            }
            .filters li {
                display: inline;
                margin-right: 10px;
                font-weight: bold;
            }
            @media (max-width: 600px) {
                th, td {
                    padding: 8px;
                    font-size: 14px;
                }
            }
        </style>' : '';

        // Générer l'entête du document HTML
        $html = '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        ' . $css . '
    </head>
    <body>
        <div class="container">
            <h1>' . htmlspecialchars($title) . '</h1>
            <div class="timestamp">Généré le ' . date('d/m/Y à H:i:s') . '</div>
            <div class="filters">
                <strong>Filtres appliqués:</strong>
                <ul>
                    ' . ($dateFormat ? '<li>Date: ' . htmlspecialchars($dateFormat) . '</li>' : '<li>Date: Tous</li>') . '
                    ' . ($level ? '<li>Niveau: ' . htmlspecialchars($level) . '</li>' : '<li>Niveau: Tous</li>') . '
                    ' . ($searchText ? '<li>Recherche: ' . htmlspecialchars($searchText) . '</li>' : '<li>Recherche: Aucune</li>') . '
                </ul>
            </div>
            <div class="log-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date<span style="font-size:12px">(j:m:h:min)</span></th>
                            <th>Niveau</th>
                            <th>Occurr.</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($logs as $log) {
            if (empty($log)) continue;

            if (preg_match('/\[([\d:]+)\] -> \[(.*?)\]( -> (\d+))?( -> (.*))?/', $log, $matches)) {
                $date = $matches[1];
                $logLevel = $matches[2];
                $count = isset($matches[4]) ? $matches[4] : 1;
                $message = isset($matches[6]) ? $matches[6] : (isset($matches[5]) ? substr($matches[5], 4) : '');
                $levelClass = strtolower($logLevel);

                $html .= '
                    <tr class="' . $levelClass . '">
                        <td>' . htmlspecialchars($date) . '</td>
                        <td class="' . $levelClass . '">' . htmlspecialchars($logLevel) . '</td>
                        <td>' . $count . '</td>
                        <td>' . htmlspecialchars_decode($message) . '</td>
                    </tr>';
            }
        }

        $html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </body>
    </html>';
        echo $html;
    }
}
