<?php

declare(strict_types=1);

namespace Core\Helper;

use Core\File;
use Core\Logger;
use RuntimeException;
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


// ========================================
// MÉTHODES D'UPLOAD
// ========================================

    /**
     * Upload un fichier avec validation et sécurité renforcée
     * 
     * @param array $file Tableau $_FILES['field_name']
     * @param string $label Type de destination (images, scripts, styles, etc.)
     * @param array $options Options d'upload
     * @return array Résultat de l'upload avec informations du fichier
     * @throws RuntimeException En cas d'erreur
     * 
     * Exemple d'utilisation:
     * $result = AssetManager::upload($_FILES['avatar'], 'images', [
     *     'max_size' => 5242880, // 5MB
     *     'allowed_types' => ['jpg', 'png', 'webp'],
     *     'rename' => 'hash', // 'hash', 'original', ou une chaîne personnalisée
     *     'overwrite' => false,
     *     'optimize_image' => true
     * ]);
     */
    public static function upload(array $file, string $label, array $options = []): array
    {
        try {
            // Options par défaut
            $defaultOptions = [
                'max_size' => 10485760, // 10MB par défaut
                'allowed_types' => null, // null = utilise allowed_extensions de la config
                'rename' => 'hash', // 'hash', 'original', ou string personnalisé
                'overwrite' => false,
                'optimize_image' => false,
                'validate_image' => true,
                'create_thumbnail' => false,
                'thumbnail_size' => [150, 150],
                'sanitize_filename' => true
            ];

            $options = array_merge($defaultOptions, $options);

            // Validation initiale du fichier uploadé
            $validationResult = self::validateUploadedFile($file, $options);
            if (!$validationResult['success']) {
                Logger::warning('Upload validation failed: ' . $validationResult['error']);
                throw new RuntimeException($validationResult['error']);
            }

            // Extraction des informations du fichier
            $fileInfo = self::extractFileInfo($file);
            $extension = $fileInfo['extension'];

            // Vérification de l'extension autorisée
            if (!self::isUploadExtensionAllowed($extension, $options['allowed_types'])) {
                Logger::warning("Upload blocked - forbidden extension: {$extension}");
                throw new RuntimeException("Type de fichier non autorisé: {$extension}");
            }

            // Validation spécifique pour les images
            if (self::isImageExtension($extension) && $options['validate_image']) {
                if (!self::validateImageFile($file['tmp_name'])) {
                    Logger::warning('Upload blocked - invalid image file');
                    throw new RuntimeException('Le fichier image est invalide ou corrompu');
                }
            }

            // Génération du nom de fichier final
            $finalFilename = self::generateUploadFilename(
                $fileInfo['name'],
                $extension,
                $options['rename'],
                $options['sanitize_filename']
            );

            // Vérification de l'existence si overwrite est false
            if (!$options['overwrite'] && File::exists($label, $finalFilename)) {
                // Ajouter un suffixe unique
                $finalFilename = self::makeFilenameUnique($label, $finalFilename);
            }

            // Déplacement du fichier
            $destinationPath = File::getPath($label, $finalFilename);

            // Création du répertoire si nécessaire
            $dir = dirname($destinationPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                Logger::error("Failed to move uploaded file to: {$destinationPath}");
                throw new RuntimeException('Impossible de sauvegarder le fichier uploadé');
            }

            // Permissions sécurisées
            chmod($destinationPath, 0644);

            // Post-traitement pour les images
            $thumbnailPath = null;
            if (self::isImageExtension($extension)) {
                if ($options['optimize_image']) {
                    self::optimizeImage($destinationPath, $extension);
                }

                if ($options['create_thumbnail']) {
                    $thumbnailPath = self::createThumbnail(
                        $destinationPath,
                        $label,
                        $finalFilename,
                        $options['thumbnail_size']
                    );
                }
            }

            Logger::info("File uploaded successfully: {$finalFilename}");

            // Retour des informations
            return [
                'success' => true,
                'filename' => $finalFilename,
                'original_name' => $fileInfo['name'],
                'path' => $destinationPath,
                'size' => filesize($destinationPath),
                'size_human' => self::formatBytes(filesize($destinationPath)),
                'extension' => $extension,
                'mime_type' => mime_content_type($destinationPath),
                'url' => self::url($label, $finalFilename),
                'thumbnail' => $thumbnailPath,
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            Logger::error('Upload failed: ' . $e->getMessage());

            // Nettoyage en cas d'erreur
            if (isset($destinationPath) && file_exists($destinationPath)) {
                unlink($destinationPath);
            }

            throw $e;
        }
    }

    /**
     * Upload multiple fichiers en une seule opération
     * 
     * @param array $files Tableau de fichiers ($_FILES)
     * @param string $label Type de destination
     * @param array $options Options d'upload
     * @return array Tableau des résultats pour chaque fichier
     */
    public static function uploadMultiple(array $files, string $label, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                // Vérifier si c'est un tableau de fichiers multiples normalisé
                if (isset($file['name']) && is_array($file['name'])) {
                    // Normaliser le format $_FILES pour fichiers multiples
                    $normalizedFiles = self::normalizeFilesArray($file);
                    foreach ($normalizedFiles as $i => $normalizedFile) {
                        try {
                            $results[] = self::upload($normalizedFile, $label, $options);
                        } catch (\Exception $e) {
                            $errors[] = [
                                'index' => "{$index}_{$i}",
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                } else {
                    // Fichier unique déjà au bon format
                    $results[] = self::upload($file, $label, $options);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => count($results) > 0,
            'uploaded' => count($results),
            'failed' => count($errors),
            'files' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Valide un fichier uploadé
     */
    private static function validateUploadedFile(array $file, array $options): array
    {
        // Vérification de la structure du tableau
        if (!isset($file['tmp_name']) || !isset($file['error']) || !isset($file['size'])) {
            return ['success' => false, 'error' => 'Structure de fichier invalide'];
        }

        // Vérification des erreurs d'upload PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::getUploadErrorMessage($file['error'])];
        }

        // Vérification que c'est un vrai fichier uploadé
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'Fichier non uploadé via HTTP POST'];
        }

        // Vérification de la taille
        if ($file['size'] > $options['max_size']) {
            $maxSizeHuman = self::formatBytes($options['max_size']);
            return ['success' => false, 'error' => "Fichier trop volumineux (max: {$maxSizeHuman})"];
        }

        // Vérification que le fichier n'est pas vide
        if ($file['size'] === 0) {
            return ['success' => false, 'error' => 'Fichier vide'];
        }

        return ['success' => true];
    }

    /**
     * Extrait les informations d'un fichier uploadé
     */
    private static function extractFileInfo(array $file): array
    {
        $pathInfo = pathinfo($file['name']);

        return [
            'name' => $pathInfo['filename'],
            'extension' => strtolower($pathInfo['extension'] ?? ''),
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime' => $file['type'],
            'tmp_name' => $file['tmp_name']
        ];
    }

    /**
     * Vérifie si une extension est autorisée pour l'upload
     */
    private static function isUploadExtensionAllowed(string $extension, ?array $allowedTypes): bool
    {
        if ($allowedTypes === null) {
            return self::isAllowedExtension($extension);
        }

        return in_array($extension, $allowedTypes);
    }

    /**
     * Vérifie si c'est une extension d'image
     */
    private static function isImageExtension(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']);
    }

    /**
     * Valide qu'un fichier est bien une image valide
     */
    private static function validateImageFile(string $tmpPath): bool
    {
        // Vérifier avec getimagesize
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            return false;
        }

        // Vérifier le type MIME
        $allowedMimes = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF,
            IMAGETYPE_WEBP
        ];

        return in_array($imageInfo[2], $allowedMimes);
    }

    /**
     * Génère un nom de fichier sécurisé pour l'upload
     */
    private static function generateUploadFilename(
        string $originalName,
        string $extension,
        string $renameMode,
        bool $sanitize
    ): string {
        switch ($renameMode) {
            case 'hash':
                return md5(uniqid($originalName, true) . random_bytes(8)) . '.' . $extension;

            case 'original':
                if ($sanitize) {
                    return self::sanitizeFilename($originalName) . '.' . $extension;
                }
                return $originalName . '.' . $extension;

            default:
                // Mode personnalisé
                if ($sanitize) {
                    $renameMode = self::sanitizeFilename($renameMode);
                }
                return $renameMode . '.' . $extension;
        }
    }

    /**
     * Nettoie un nom de fichier
     */
    private static function sanitizeFilename(string $filename): string
    {
        // Supprimer l'extension si présente
        $filename = pathinfo($filename, PATHINFO_FILENAME);

        // Remplacer les caractères spéciaux
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Supprimer les underscores multiples
        $filename = preg_replace('/_+/', '_', $filename);

        // Trim
        $filename = trim($filename, '_-');

        // Limiter la longueur
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        return $filename ?: 'file';
    }

    /**
     * Rend un nom de fichier unique en ajoutant un suffixe
     */
    private static function makeFilenameUnique(string $label, string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $basename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        $counter = 1;
        $newFilename = $filename;

        while (File::exists($label, $newFilename)) {
            $newFilename = $basename . '_' . $counter . '.' . $extension;
            $counter++;

            // Sécurité : éviter une boucle infinie
            if ($counter > 9999) {
                $newFilename = $basename . '_' . uniqid() . '.' . $extension;
                break;
            }
        }

        return $newFilename;
    }

    /**
     * Optimise une image (compression)
     */
    private static function optimizeImage(string $path, string $extension): bool
    {
        try {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($path);
                    if ($image) {
                        imagejpeg($image, $path, 85); // 85% qualité
                        imagedestroy($image);
                        return true;
                    }
                    break;

                case 'png':
                    $image = imagecreatefrompng($path);
                    if ($image) {
                        imagepng($image, $path, 6); // Compression 6
                        imagedestroy($image);
                        return true;
                    }
                    break;

                case 'webp':
                    $image = imagecreatefromwebp($path);
                    if ($image) {
                        imagewebp($image, $path, 85);
                        imagedestroy($image);
                        return true;
                    }
                    break;
            }
        } catch (\Exception $e) {
            Logger::warning('Image optimization failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Crée une miniature d'une image
     */
    private static function createThumbnail(
        string $sourcePath,
        string $label,
        string $filename,
        array $size
    ): ?string {
        try {
            $pathInfo = pathinfo($filename);
            $thumbnailName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $thumbnailPath = File::getPath($label, $thumbnailName);

            list($width, $height) = getimagesize($sourcePath);
            list($newWidth, $newHeight) = $size;

            // Calculer les dimensions en gardant le ratio
            $ratio = min($newWidth / $width, $newHeight / $height);
            $finalWidth = (int)($width * $ratio);
            $finalHeight = (int)($height * $ratio);

            // Créer la miniature
            $thumb = imagecreatetruecolor($finalWidth, $finalHeight);

            $extension = strtolower($pathInfo['extension']);
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $source = imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $source = imagecreatefrompng($sourcePath);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    break;
                case 'gif':
                    $source = imagecreatefromgif($sourcePath);
                    break;
                case 'webp':
                    $source = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    return null;
            }

            if (!$source) {
                return null;
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $finalWidth, $finalHeight, $width, $height);

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumb, $thumbnailPath, 85);
                    break;
                case 'png':
                    imagepng($thumb, $thumbnailPath, 6);
                    break;
                case 'gif':
                    imagegif($thumb, $thumbnailPath);
                    break;
                case 'webp':
                    imagewebp($thumb, $thumbnailPath, 85);
                    break;
            }

            imagedestroy($source);
            imagedestroy($thumb);

            return $thumbnailName;
        } catch (\Exception $e) {
            Logger::warning('Thumbnail creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalise le tableau $_FILES pour les uploads multiples
     */
    private static function normalizeFilesArray(array $files): array
    {
        $normalized = [];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $normalized[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
        }

        return $normalized;
    }

    /**
     * Récupère le message d'erreur d'upload PHP
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite upload_max_filesize du php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite MAX_FILE_SIZE du formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
        ];

        return $messages[$errorCode] ?? 'Erreur d\'upload inconnue';
    }

// ========================================
// MÉTHODES DE DOWNLOAD
// ========================================

    /**
     * Force le téléchargement d'un fichier
     * 
     * @param string $label Type de fichier
     * @param string $filename Nom du fichier
     * @param array $options Options de download
     * @return Response
     * 
     * Exemple d'utilisation:
     * return AssetManager::download('images', 'photo.jpg', [
     *     'download_name' => 'ma-photo.jpg',
     *     'inline' => false,
     *     'speed_limit' => 1048576 // 1MB/s
     * ]);
     */
    public static function download(string $label, string $filename, array $options = []): Response
    {
        try {
            // Options par défaut
            $defaultOptions = [
                'download_name' => null, // Nom personnalisé pour le téléchargement
                'inline' => false, // false = téléchargement, true = affichage dans le navigateur
                'speed_limit' => null, // Limite de vitesse en bytes/sec (null = illimité)
                'resume_support' => true, // Support de la reprise de téléchargement
                'log_download' => true
            ];

            $options = array_merge($defaultOptions, $options);

            // Validation
            if (!File::exists($label, $filename)) {
                Logger::warning("Download failed - file not found: {$filename}");
                return RouteException::handleAssetNotFound();
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!self::isAllowedExtension($extension)) {
                Logger::warning("Download blocked - forbidden extension: {$extension}");
                return Response::create('Forbidden', 403);
            }

            $filePath = File::getPath($label, $filename);
            $fileSize = filesize($filePath);
            $downloadName = $options['download_name'] ?? $filename;

            // Log du téléchargement
            if ($options['log_download']) {
                self::logDownload($label, $filename, $fileSize);
            }

            // Gestion de la reprise de téléchargement (Range Request)
            if ($options['resume_support'] && isset($_SERVER['HTTP_RANGE'])) {
                return self::handleRangeDownload($filePath, $fileSize, $downloadName, $extension, $options);
            }

            // Téléchargement complet
            return self::handleFullDownload($filePath, $fileSize, $downloadName, $extension, $options);
        } catch (\Exception $e) {
            Logger::error('Download failed: ' . $e->getMessage());
            return RouteException::handleInternalServerError(exception: $e);
        }
    }

    /**
     * Gère un téléchargement complet
     */
    private static function handleFullDownload(
        string $filePath,
        int $fileSize,
        string $downloadName,
        string $extension,
        array $options
    ): Response {
        $mimeType = self::getMimeType($extension);
        $disposition = $options['inline'] ? 'inline' : 'attachment';

        // Lecture du fichier
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('Erreur lors de la lecture du fichier');
        }

        // Si limite de vitesse, utiliser un stream
        if ($options['speed_limit'] !== null && !$options['inline']) {
            return self::streamDownload($filePath, $fileSize, $downloadName, $mimeType, $disposition, $options['speed_limit']);
        }

        // Création de la réponse
        $response = Response::create($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => "{$disposition}; filename=\"{$downloadName}\"",
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Pragma' => 'public',
            'Accept-Ranges' => 'bytes'
        ]);

        // Headers de sécurité
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    /**
     * Gère un téléchargement avec range (reprise)
     */
    private static function handleRangeDownload(
        string $filePath,
        int $fileSize,
        string $downloadName,
        string $extension,
        array $options
    ): Response {
        $range = $_SERVER['HTTP_RANGE'];

        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return Response::create('Invalid range', 416);
        }

        $start = (int)$matches[1];
        $end = $matches[2] ? (int)$matches[2] : $fileSize - 1;

        if ($start > $end || $start >= $fileSize) {
            return Response::create('Range not satisfiable', 416, [
                'Content-Range' => "bytes */{$fileSize}"
            ]);
        }

        $length = $end - $start + 1;
        $mimeType = self::getMimeType($extension);
        $disposition = $options['inline'] ? 'inline' : 'attachment';

        // Lecture de la portion demandée
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new RuntimeException('Impossible d\'ouvrir le fichier');
        }

        fseek($handle, $start);
        $content = fread($handle, $length);
        fclose($handle);

        return Response::create($content, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Disposition' => "{$disposition}; filename=\"{$downloadName}\"",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    /**
     * Stream un fichier avec limite de vitesse
     */
    private static function streamDownload(
        string $filePath,
        int $fileSize,
        string $downloadName,
        string $mimeType,
        string $disposition,
        int $speedLimit
    ): Response {
        // Créer une réponse callback qui va streamer le fichier
        $callback = function () use ($filePath, $speedLimit) {
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                throw new RuntimeException('Impossible d\'ouvrir le fichier');
            }

            $chunkSize = 8192; // 8KB chunks
            $sleepTime = ($chunkSize / $speedLimit) * 1000000; // microsecondes

            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();

                if ($speedLimit > 0) {
                    usleep((int)$sleepTime);
                }
            }

            fclose($handle);
        };

        // Pour un stream, on retourne une réponse avec les headers appropriés
        // et on laisse le callback s'exécuter
        $response = Response::create('', 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => "{$disposition}; filename=\"{$downloadName}\"",
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
            'Pragma' => 'public',
            'Accept-Ranges' => 'bytes',
            'X-Content-Type-Options' => 'nosniff'
        ]);

        // Note: Dans une vraie implémentation, il faudrait gérer le streaming
        // différemment selon votre framework. Ceci est une approximation.
        ob_start();
        $callback();
        $content = ob_get_clean();

        return $response->setContent($content);
    }

    /**
     * Télécharge un fichier en tant que ZIP
     * 
     * @param array $files Tableau de ['label' => 'filename'] ou [['label' => '', 'filename' => '']]
     * @param string $zipName Nom du fichier ZIP
     * @return Response
     */
    public static function downloadAsZip(array $files, string $zipName = 'download.zip'): Response
    {
        try {
            if (!class_exists('ZipArchive')) {
                throw new RuntimeException('Extension ZipArchive non disponible');
            }

            $tempZip = tempnam(sys_get_temp_dir(), 'zip');
            $zip = new \ZipArchive();

            if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Impossible de créer le fichier ZIP');
            }

            $addedFiles = 0;
            foreach ($files as $key => $value) {
                // Support de deux formats: ['label' => 'filename'] ou [['label' => '', 'filename' => '']]
                if (is_array($value)) {
                    $label = $value['label'];
                    $filename = $value['filename'];
                } else {
                    $label = $key;
                    $filename = $value;
                }

                if (File::exists($label, $filename)) {
                    $filePath = File::getPath($label, $filename);
                    $zip->addFile($filePath, $filename);
                    $addedFiles++;
                }
            }

            if ($addedFiles === 0) {
                $zip->close();
                unlink($tempZip);
                throw new RuntimeException('Aucun fichier valide à ajouter au ZIP');
            }

            $zip->close();

            // Lecture du ZIP
            $zipContent = file_get_contents($tempZip);
            $zipSize = filesize($tempZip);

            // Nettoyage
            unlink($tempZip);

            Logger::info("ZIP download created with {$addedFiles} files: {$zipName}");

            return Response::create($zipContent, 200, [
                'Content-Type' => 'application/zip',
                'Content-Length' => $zipSize,
                'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'public'
            ]);
        } catch (\Exception $e) {
            Logger::error('ZIP download failed: ' . $e->getMessage());
            return Response::create('Error creating ZIP file', 500);
        }
    }

    /**
     * Log un téléchargement
     */
    private static function logDownload(string $label, string $filename, int $size): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        Logger::info("File downloaded: {$filename}", [
            'label' => $label,
            'size' => $size,
            'size_human' => self::formatBytes($size),
            'ip' => $ip,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * Obtient les statistiques de téléchargement d'un fichier
     * (Nécessite une table de logs ou un système de tracking)
     */
    public static function getDownloadStats(string $label, string $filename): array
    {
        // Cette méthode pourrait être étendue pour interroger une base de données
        // de logs de téléchargement si vous en avez une

        if (!File::exists($label, $filename)) {
            return [];
        }

        $info = self::getInfo($label, $filename);

        return [
            'filename' => $filename,
            'size' => $info['size'],
            'size_human' => $info['size_human'],
            'last_modified' => $info['modified_date'],
            'url' => self::url($label, $filename),
            // Ici, vous pourriez ajouter:
            // 'download_count' => ...,
            // 'last_download' => ...,
            // 'total_bandwidth' => ...
        ];
    }
}
