<?php

namespace Core\Helper;

use Core\Routing\Http\Response;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

class Helper
{


    public static function getServerTime()
    {
        $serverTime = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        return Response::json(['time' => $serverTime, 'success' => true]);
    }
    public static function remainingTime(?string $expiresAt): string
    {
        $expiresAt = new DateTime($expiresAt);
        $now = new DateTime();

        if ($expiresAt <= $now) {
            return 'Expiré';
        }

        $i = $now->diff($expiresAt);

        return sprintf(
            '%d j %d h %d min %d s',
            $i->days,
            $i->h,
            $i->i,
            $i->s
        );
    }
    public static function isToday(string $dateTime, ?string $timezone = null): bool
    {
        // Gestion du fuseau horaire (optionnel mais recommandé)
        $tz = $timezone ? new DateTimeZone($timezone) : new DateTimeZone(date_default_timezone_get());

        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime, $tz);
        if ($date === false) {
            return false; // format invalide
        }

        $today = new DateTime('now', $tz);

        return $date->format('Y-m-d');
    }
    /**
     * Vérifie si une date d'expiration est dépassée.
     *
     * @param string $expirationDate Format attendu : Y-m-d H:i:s
     * @return bool
     */
    public static function isExpired(string $expirationDate): bool
    {
        try {
            $expiration = new DateTime($expirationDate);
            $now = new DateTime();

            return $now > $expiration;
        } catch (Exception $e) {
            return false;
        }
    }
    public static function print_r(mixed $data, bool $exit = true, string $title = 'Debug Output')
    {
        echo '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body class="bg-linear-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-linear-to-r from-blue-600 to-purple-600 rounded-t-xl p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <h1 class="text-2xl font-bold text-white">' . htmlspecialchars($title) . '</h1>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-medium">
                            ' . gettype($data) . '
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm">
                            ' . date('H:i:s') . '
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="bg-gray-800 rounded-b-xl shadow-2xl overflow-hidden border-2 border-gray-700">
                <div class="p-6">
                    <pre class="text-sm text-gray-100 font-mono leading-relaxed overflow-x-auto whitespace-pre-wrap wrap-break-words">'
            . htmlspecialchars(print_r($data, true)) .
            '</pre>
                </div>
                
                <!-- Footer -->
                <div class="bg-gray-900/50 border-t border-gray-700 px-6 py-4">
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-gray-400">
                            <span class="font-semibold text-blue-400">File:</span> 
                            ' . htmlspecialchars(debug_backtrace()[0]['file'] ?? 'Unknown') . '
                        </div>
                        <div class="text-gray-400">
                            <span class="font-semibold text-purple-400">Line:</span> 
                            ' . (debug_backtrace()[0]['line'] ?? 'N/A') . '
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-4 flex justify-end space-x-3">
                <button onclick="copyToClipboard()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span>Copier</span>
                </button>
            </div>
        </div>
        
        <script>
            function copyToClipboard() {
                const preElement = document.querySelector("pre");
                const text = preElement.textContent;
                
                navigator.clipboard.writeText(text).then(() => {
                    const button = event.target.closest("button");
                    const originalHTML = button.innerHTML;
                    button.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Copié!</span>
                    `;
                    button.classList.add("bg-green-600");
                    button.classList.remove("bg-blue-600", "hover:bg-blue-700");
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove("bg-green-600");
                        button.classList.add("bg-blue-600", "hover:bg-blue-700");
                    }, 2000);
                });
            }
        </script>
    </body>
    </html>
    ';

        if ($exit) {
            exit;
        }
    }
}
