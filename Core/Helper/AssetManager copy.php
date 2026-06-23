<?php

declare(strict_types=1);

namespace Core\Helper;

use Core\File;
use Core\Logger;
use Core\Routing\Http\Response;
use Core\Routing\RouteException;

/**
 * Gestionnaire d'assets avec encodage optimisé et service sécurisé
 * 
 * Gère le service des fichiers statiques avec :
 * - Encodage UTF-8 correct pour les fichiers texte
 * - Compression gzip/brotli intelligente
 * - Cache HTTP optimisé avec ETag et Last-Modified
 * - Sécurité renforcée
 * - Support des range requests
 */
class AssetManager
{
    private static array $config = [
        'cache_max_age' => 31536000, // 1 an
        'enable_compression' => true,
        'enable_etag' => true,
        'enable_range_requests' => true,
        'compression_level' => 6,
        'compression_threshold' => 1024, // Ne compresse que si > 1KB
        'allowed_extensions' => [
            'css',
            'js',
            'json',
            'xml',
            'txt',
            'md',
            'png',
            'jpg',
            'jpeg',
            'gif',
            'svg',
            'webp',
            'avif',
            'ico',
            'woff',
            'woff2',
            'ttf',
            'eot',
            'otf',
            'pdf',
            'zip',
            'mp4',
            'webm',
            'mp3',
            'wav'
        ],
        'mime_types' => [
            // Texte avec charset UTF-8 explicite
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            'txt' => 'text/plain; charset=utf-8',
            'md' => 'text/markdown; charset=utf-8',

            // Images (pas de charset)
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml; charset=utf-8',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',

            // Fonts (pas de charset)
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',

            // Autres
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav'
        ],
        'text_extensions' => ['css', 'js', 'json', 'xml', 'txt', 'md', 'svg'],
        'compressible_extensions' => ['css', 'js', 'json', 'xml', 'txt', 'md', 'svg', 'html']
    ];

    public static function init(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Sert un asset avec encodage et optimisations
     */
    public static function serve(string $label, string $filename): Response
    {
        try {
            // Validation et sécurité
            $validationResult = self::validateAssetRequest($label, $filename);
            if ($validationResult !== null) {
                return $validationResult;
            }

            $filePath = File::getPath($label, $filename);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Vérification des conditions de cache
            $cacheResult = self::checkCacheConditions($filePath);
            if ($cacheResult !== null) {
                return $cacheResult;
            }

            // Lecture et encodage du fichier
            $content = self::readAndEncodeFile($filePath, $extension);
            if ($content === null) {
                return Response::create('Error reading file', 500);
            }

            // Création de la réponse
            $response = self::createAssetResponse($content, $extension, $filePath);

            // Gestion des range requests
            if (self::$config['enable_range_requests']) {
                $rangeResponse = self::handleRangeRequest($response, $content);
                if ($rangeResponse !== null) {
                    return $rangeResponse;
                }
            }
            return $response;
        } catch (\Exception $e) {
            return RouteException::handleInternalServerError(exception: $e);
        }
    }

    /**
     * Valide la requête d'asset
     */
    private static function validateAssetRequest(string $label, string $filename): ?Response
    {
        // Validation de l'extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!self::isAllowedExtension($extension)) {
            Logger::warning('Blocked asset request - forbidden extension');
            return Response::create('Forbidden', 403);
        }

        // Validation de sécurité du chemin
        if (self::containsPathTraversal($filename)) {
            Logger::warning('Blocked asset request - path traversal attempt');
            return Response::create('Forbidden', 403);
        }

        // Vérification de l'existence
        if (!File::exists($label, $filename)) {
            return RouteException::handleAssetNotFound();
        }

        return null;
    }

    /**
     * Vérifie les conditions de cache HTTP
     */
    private static function checkCacheConditions(string $filePath): ?Response
    {
        $fileTime = filemtime($filePath);
        $etag = self::generateETag($filePath, $fileTime);

        // Vérification If-None-Match (ETag)
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $clientEtag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            if ($clientEtag === $etag) {
                $response = Response::create('', 304);
                self::setCacheHeaders($response, $fileTime, $etag);
                return $response;
            }
        }

        // Vérification If-Modified-Since
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($clientTime >= $fileTime) {
                $response = Response::create('', 304);
                self::setCacheHeaders($response, $fileTime, $etag);
                return $response;
            }
        }

        return null;
    }

    /**
     * Lit et encode correctement un fichier
     */
    private static function readAndEncodeFile(string $filePath, string $extension): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        // Traitement spécial pour les fichiers texte
        if (self::isTextFile($extension)) {
            // Vérifier si le contenu est déjà en UTF-8 valide
            if (!mb_check_encoding($content, 'UTF-8')) {
                // Détection de l'encodage avec plus d'options
                $encoding = mb_detect_encoding($content, [
                    'UTF-8',
                    'ISO-8859-1',
                    'ISO-8859-15',
                    'Windows-1252',
                    'CP1252'
                ], true);

                if ($encoding && $encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                } else {
                    // Dernière tentative avec iconv si mb échoue
                    $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $content);
                    if ($converted !== false) {
                        $content = $converted;
                    }
                }
            }

            // Pour les CSS, ajouter @charset si nécessaire
            if ($extension === 'css') {
                $content = self::ensureCssCharset($content);
            }
        }

        return $content;
    }

    /**
     * S'assure que le CSS a le bon @charset
     */
    private static function ensureCssCharset(string $content): string
    {
        // Supprimer tout @charset existant
        $content = preg_replace('/^\s*@charset\s+["\'][^"\']*["\'];\s*/i', '', $content);

        // Ajouter @charset UTF-8 au début si le CSS contient des caractères non-ASCII
        if (preg_match('/[^\x00-\x7F]/', $content)) {
            $content = "@charset \"UTF-8\";\n" . $content;
        }

        return $content;
    }

    /**
     * Crée la réponse HTTP pour l'asset
     */
    private static function createAssetResponse(string $content, string $extension, string $filePath): Response
    {
        $fileTime = filemtime($filePath);
        $etag = self::generateETag($filePath, $fileTime);
        $mimeType = self::getMimeType($extension);

        // Compression si applicable
        $compressedContent = self::compressContent($content, $extension);
        $finalContent = $compressedContent['content'];
        $isCompressed = $compressedContent['compressed'];

        // Création de la réponse
        $response = Response::create($finalContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($finalContent),
            'Accept-Ranges' => 'bytes'
        ]);

        // Headers de cache
        self::setCacheHeaders($response, $fileTime, $etag);

        // Headers de compression
        if ($isCompressed) {
            $response->setHeader('Content-Encoding', $compressedContent['encoding']);
            $response->setHeader('Vary', 'Accept-Encoding');
        }

        // Headers de sécurité
        self::setSecurityHeaders($response, $extension);

        return $response;
    }

    /**
     * Gère la compression du contenu
     */
    private static function compressContent(string $content, string $extension): array
    {
        if (
            !self::$config['enable_compression'] ||
            !self::shouldCompress($extension) ||
            strlen($content) < self::$config['compression_threshold']
        ) {
            return ['content' => $content, 'compressed' => false];
        }

        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

        if (strpos($acceptEncoding, 'gzip') !== false) {
            $compressed = gzencode($content, self::$config['compression_level']);
            if ($compressed !== false) {
                return [
                    'content' => $compressed,
                    'compressed' => true,
                    'encoding' => 'gzip'
                ];
            }
        }

        return ['content' => $content, 'compressed' => false];
    }

    /**
     * Gère les range requests pour les gros fichiers
     */
    private static function handleRangeRequest(Response $response, string $content): ?Response
    {
        if (!isset($_SERVER['HTTP_RANGE'])) {
            return null;
        }

        $range = $_SERVER['HTTP_RANGE'];
        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return null;
        }

        $start = (int)$matches[1];
        $end = $matches[2] ? (int)$matches[2] : strlen($content) - 1;
        $length = $end - $start + 1;

        if ($start > $end || $start >= strlen($content)) {
            return Response::create('Range not satisfiable', 416);
        }

        $partialContent = substr($content, $start, $length);

        return Response::create($partialContent, 206, [
            'Content-Range' => "bytes {$start}-{$end}/" . strlen($content),
            'Content-Length' => $length,
            'Content-Type' => $response->getHeader('Content-Type')
        ]);
    }

    /**
     * Configure les headers de cache
     */
    private static function setCacheHeaders(Response $response, int $fileTime, string $etag): void
    {
        $maxAge = self::$config['cache_max_age'];
        $expires = new \DateTime();
        $expires->setTimestamp(time() + $maxAge);

        $response
            ->setHeader('Cache-Control', "public, max-age={$maxAge}, immutable")
            ->setHeader('Expires', $expires->format('D, d M Y H:i:s') . ' GMT')
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $fileTime) . ' GMT');

        if (self::$config['enable_etag']) {
            $response->setHeader('ETag', '"' . $etag . '"');
        }
    }

    /**
     * Configure les headers de sécurité
     */
    private static function setSecurityHeaders(Response $response, string $extension): void
    {
        // Désactiver le sniffing MIME
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // Headers spécifiques par type
        switch ($extension) {
            case 'js':
                $response->setHeader('X-Content-Type-Options', 'nosniff');
                break;
            case 'css':
                // Force le navigateur à interpréter comme CSS
                $response->setHeader('X-Content-Type-Options', 'nosniff');
                // Éviter les problèmes CORS pour les fonts
                $response->setHeader('Access-Control-Allow-Origin', '*');
                break;
            case 'svg':
                $response->setHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'");
                break;
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'otf':
            case 'eot':
                // Headers CORS pour les fonts
                $response->setHeader('Access-Control-Allow-Origin', '*');
                $response->setHeader('Access-Control-Allow-Methods', 'GET');
                $response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
                break;
        }
    }

    /**
     * Génère un ETag robuste
     */
    private static function generateETag(string $filePath, int $fileTime): string
    {
        $fileSize = filesize($filePath);
        return md5("{$filePath}-{$fileTime}-{$fileSize}");
    }

    /**
     * Vérifie si c'est un fichier texte
     */
    private static function isTextFile(string $extension): bool
    {
        return in_array($extension, self::$config['text_extensions']);
    }

    /**
     * Vérifie si le fichier doit être compressé
     */
    private static function shouldCompress(string $extension): bool
    {
        return in_array($extension, self::$config['compressible_extensions']);
    }

    /**
     * Vérifie la tentative de path traversal
     */
    private static function containsPathTraversal(string $filename): bool
    {
        return strpos($filename, '..') !== false ||
            strpos($filename, '\\') !== false ||
            strpos($filename, chr(0)) !== false;
    }

    /**
     * Vérifie si une extension est autorisée
     */
    private static function isAllowedExtension(string $extension): bool
    {
        return in_array($extension, self::$config['allowed_extensions']);
    }

    /**
     * Obtient le type MIME pour une extension
     */
    private static function getMimeType(string $extension): string
    {
        return self::$config['mime_types'][$extension] ?? 'application/octet-stream';
    }

    /**
     * Log du service d'asset
     */
    private static function logAssetServed(string $label, string $filename, int $size): void
    {
        Logger::info('Asset served successfully');
    }

    /**
     * Formate les octets en format lisible
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // === Méthodes d'aide pour la génération HTML ===

    /**
     * Génère l'URL d'un asset avec versioning
     */
    public static function url(string $label, string $filename, bool $versioned = true): string
    {
        $baseUrl = self::getBaseUrl();
        $assetPath = "Assets/{$label}/{$filename}";

        if ($versioned && File::exists($label, $filename)) {
            $filePath = File::getPath($label, $filename);
            $version = filemtime($filePath);
            $assetPath .= "?v={$version}";
        }

        return $baseUrl . '/' . $assetPath;
    }

    /**
     * Génère une balise CSS avec attributs optimisés
     */
    public static function css(string $label, string $filename, array $attributes = []): string
    {
        $url = self::url($label, $filename);
        $attrs = self::buildAttributes(array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'crossorigin' => 'anonymous'
        ], $attributes));

        return "<link{$attrs}>";
    }

    /**
     * Génère une balise JavaScript avec attributs optimisés
     */
    public static function js(string $label, string $filename, array $attributes = []): string
    {
        $url = self::url($label, $filename);
        $attrs = self::buildAttributes(array_merge([
            'src' => $url,
            'crossorigin' => 'anonymous'
        ], $attributes));

        return "<script{$attrs}></script>";
    }

    /**
     * Inline un asset avec encodage correct
     */
    public static function inline(string $label, string $filename): string
    {
        try {
            if (!File::exists($label, $filename)) {
                return '';
            }

            $filePath = File::getPath($label, $filename);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $content = self::readAndEncodeFile($filePath, $extension);

            if ($content === null) {
                return '';
            }

            switch ($extension) {
                case 'css':
                    // Nettoyer le @charset pour l'inline (pas nécessaire dans <style>)
                    $content = preg_replace('/^\s*@charset\s+["\'][^"\']*["\'];\s*/i', '', $content);
                    return "<style>{$content}</style>";
                case 'js':
                    return "<script>{$content}</script>";
                default:
                    return $content;
            }
        } catch (\Exception $e) {
            Logger::warning('Error inlining asset');
            return '';
        }
    }

    /**
     * Construit les attributs HTML
     */
    private static function buildAttributes(array $attributes): string
    {
        $attrs = '';
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $attrs .= " {$key}";
                }
            } else {
                $attrs .= " {$key}=\"" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "\"";
            }
        }
        return $attrs;
    }

    /**
     * Obtient l'URL de base
     */
    private static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }

    // === Méthodes utilitaires ===

    /**
     * Vérifie si un asset existe et est valide
     */
    public static function exists(string $label, string $filename): bool
    {
        return File::exists($label, $filename) &&
            self::isAllowedExtension(strtolower(pathinfo($filename, PATHINFO_EXTENSION))) &&
            !self::containsPathTraversal($filename);
    }

    /**
     * Obtient les informations complètes d'un asset
     */
    public static function getInfo(string $label, string $filename): array
    {
        if (!self::exists($label, $filename)) {
            return [];
        }

        $filePath = File::getPath($label, $filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $fileSize = filesize($filePath);
        $fileTime = filemtime($filePath);

        return [
            'path' => $filePath,
            'size' => $fileSize,
            'size_human' => self::formatBytes($fileSize),
            'modified' => $fileTime,
            'modified_date' => date('Y-m-d H:i:s', $fileTime),
            'extension' => $extension,
            'mime_type' => self::getMimeType($extension),
            'is_text' => self::isTextFile($extension),
            'is_compressible' => self::shouldCompress($extension),
            'url' => self::url($label, $filename, false),
            'versioned_url' => self::url($label, $filename, true),
            'etag' => self::generateETag($filePath, $fileTime)
        ];
    }
    /**
     * Sert un fichier XML avec optimisations spécifiques
     */
    public static function serveXml(string $label, string $filename, array $options = []): Response
    {
        try {
            // Options par défaut pour XML
            $defaultOptions = [
                'validate_xml' => true,
                'add_xml_declaration' => true,
                'pretty_format' => false,
                'enable_xslt_pi' => false,
                'custom_headers' => []
            ];

            $options = array_merge($defaultOptions, $options);

            // Validation de base
            $validationResult = self::validateAssetRequest($label, $filename);
            if ($validationResult !== null) {
                return $validationResult;
            }

            // Vérifier que c'est bien un fichier XML
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension !== 'xml') {
                return Response::create('Not an XML file', 400);
            }

            $filePath = File::getPath($label, $filename);

            // Vérification des conditions de cache
            $cacheResult = self::checkCacheConditions($filePath);
            if ($cacheResult !== null) {
                return $cacheResult;
            }

            // Lecture et traitement du XML
            $content = self::readAndProcessXml($filePath, $options);
            if ($content === null) {
                return Response::create('Error processing XML file', 500);
            }

            // Création de la réponse avec headers XML spécifiques
            $response = self::createXmlResponse($content, $filePath, $options);

            return $response;
        } catch (\Exception $e) {
            Logger::error('Error serving XML asset');
            return Response::create('Internal Server Error', 500);
        }
    }

    /**
     * Lit et traite un fichier XML avec validations
     */
    private static function readAndProcessXml(string $filePath, array $options): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        // Encodage UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $encoding = mb_detect_encoding($content, [
                'UTF-8',
                'ISO-8859-1',
                'Windows-1252'
            ], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
        }

        // Validation XML si demandée
        if ($options['validate_xml']) {
            $previousUseErrors = libxml_use_internal_errors(true);
            $dom = new \DOMDocument();

            if (!$dom->loadXML($content)) {
                $errors = libxml_get_errors();
                Logger::warning('Invalid XML file');
                libxml_clear_errors();
                libxml_use_internal_errors($previousUseErrors);
                return null;
            }

            libxml_use_internal_errors($previousUseErrors);

            // Reformatage si demandé
            if ($options['pretty_format']) {
                $dom->formatOutput = true;
                $content = $dom->saveXML();
            }
        }

        // Ajouter la déclaration XML si nécessaire
        if ($options['add_xml_declaration'] && !preg_match('/^\s*<\?xml/', $content)) {
            $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $content;
        }

        return $content;
    }

    /**
     * Crée la réponse HTTP pour le fichier XML
     */
    private static function createXmlResponse(string $content, string $filePath, array $options): Response
    {
        $fileTime = filemtime($filePath);
        $etag = self::generateETag($filePath, $fileTime);

        // Compression si applicable
        $compressedContent = self::compressContent($content, 'xml');
        $finalContent = $compressedContent['content'];
        $isCompressed = $compressedContent['compressed'];

        // Headers de base
        $headers = [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Content-Length' => strlen($finalContent),
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff'
        ];

        // Headers personnalisés
        if (!empty($options['custom_headers'])) {
            $headers = array_merge($headers, $options['custom_headers']);
        }

        // Création de la réponse
        $response = Response::create($finalContent, 200, $headers)->asXml();

        // Headers de cache
        self::setCacheHeaders($response, $fileTime, $etag);

        // Headers de compression
        if ($isCompressed) {
            $response->setHeader('Content-Encoding', $compressedContent['encoding']);
            $response->setHeader('Vary', 'Accept-Encoding');
        }

        // Headers de sécurité pour XML
        $response->setHeader('Content-Security-Policy', "default-src 'none'");

        // Permettre CORS si nécessaire (pour les APIs XML)
        if (isset($options['cors']) && $options['cors']) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        }

        return $response;
    }
}
