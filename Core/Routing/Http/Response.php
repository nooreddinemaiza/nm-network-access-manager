<?php

namespace Core\Routing\Http;

use Core\File;
use Core\Helper\Data;



class Response
{
    protected mixed $content;
    protected int $statusCode = 200;
    protected string $statusText = 'OK';
    protected Data $headers;
    protected Data $cookies;
    protected string $version = '1.1';
    protected ?string $charset = 'UTF-8';

    // Status codes constants
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;
    public const HTTP_EARLY_HINTS = 103;
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_IM_USED = 226;
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_RESERVED = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENTLY_REDIRECT = 308;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_REQUEST_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_I_AM_A_TEAPOT = 418;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_TOO_EARLY = 425;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->headers = Data::create($this->normalizeHeaders($headers));
        $this->cookies = Data::create();

        // Set default headers
        if (!$this->hasHeader('Content-Type')) {
            $this->setHeader('Content-Type', 'text/html; charset=' . $this->charset);
        }
    }

    /**
     * Factory method for creating Response instances
     */
    public static function create(mixed $content = '', int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }

    /**
     * Create a JSON response
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $response = new self('', $status, $headers);
        return $response->setJson($data);
    }

    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $response = new self('', $status, $headers);
        return $response->setHeader('Location', $url);
    }


    /**
     * Create a view response (HTML)
     */
    public static function view(string $content, int $status = 200, array $headers = []): self
    {
        $response = new self($content, $status, $headers);
        return $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }
    /**
     * Crée une réponse à partir d'un fichier de vue en utilisant la classe File
     * 
     * @param string $label Le label du dossier contenant le fichier
     * @param string $filename Le nom du fichier de vue
     * @param int $status Code HTTP
     * @param array $headers Headers supplémentaires
     * @return self
     * @throws \InvalidArgumentException Si le fichier n'existe pas
     */
    public static function viewFile(string $label, string $filename, int $status = 200, array $headers = []): self
    {
        try {
            if (!File::exists($label, $filename)) {
                $filePath = !File::getPath($label, $filename);
                throw new \InvalidArgumentException("View file not found: {$filePath}");
            }

            $content = File::read($label, $filename);
            $response = self::view($content, $status, $headers);

            // Ajout du Content-Type si non spécifié
            if (!$response->hasHeader('Content-Type')) {
                $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
            }

            return $response;
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Crée une réponse à partir d'un fichier en utilisant la classe File
     * 
     * @param string $label Le label du dossier contenant le fichier
     * @param string $filename Le nom du fichier
     * @param string|null $contentType Type MIME (auto-détecté si null)
     * @param array $headers Headers supplémentaires
     * @return self
     * @throws \InvalidArgumentException Si le fichier n'existe pas
     */
    public static function file(string $label, string $filename, ?string $contentType = null, array $headers = []): self
    {
        try {
            if (!File::exists($label, $filename)) {
                $filePath = File::getPath($label, $filename);
                throw new \InvalidArgumentException("File not found: {$filePath}");
            }

            $content = File::read($label, $filename);
            $filePath = File::getPath($label, $filename);

            if ($contentType === null) {
                $mimeType = File::getInfo($label, $filename)['mime_type'] ?? null;
                $contentType = $mimeType ?: 'application/octet-stream';
            }

            $headers = array_merge([
                'Content-Type' => $contentType,
                'Content-Length' => File::getSize($label, $filename)
            ], $headers);

            return new self($content, 200, $headers);
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }
    /**
     * Normalize headers array
     */
    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[$this->normalizeHeaderName($key)] = $value;
        }
        return $normalized;
    }

    /**
     * Normalize header name
     */
    protected function normalizeHeaderName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower($name))));
    }

    // === Content Methods ===

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setJson(mixed $data): self
    {
        $this->content = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->setHeader('Content-Type', 'application/json; charset=' . $this->charset);
        return $this;
    }

    public function appendContent(mixed $content): self
    {
        $this->content .= $content;
        return $this;
    }

    public function prependContent(mixed $content): self
    {
        $this->content = $content . $this->content;
        return $this;
    }

    // === Status Methods ===

    public function setStatusCode(int $code, string $text = null): self
    {
        $this->statusCode = $code;
        $this->statusText = $text ?: (self::$statusTexts[$code] ?? 'Unknown');
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getStatusText(): string
    {
        return $this->statusText;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function isError(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }

    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isEmpty(): bool
    {
        return in_array($this->statusCode, [204, 304]);
    }

    // === Header Methods ===

    public function setHeader(string $key, mixed $value): self
    {
        $this->headers->set($this->normalizeHeaderName($key), $value);
        return $this;
    }

    public function addHeader(string $key, mixed $value): self
    {
        $key = $this->normalizeHeaderName($key);
        $existing = $this->headers->get($key);

        if ($existing === null) {
            $this->headers->set($key, $value);
        } elseif (is_array($existing)) {
            $existing[] = $value;
            $this->headers->set($key, $existing);
        } else {
            $this->headers->set($key, [$existing, $value]);
        }

        return $this;
    }

    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers->get($this->normalizeHeaderName($key), $default);
    }

    public function hasHeader(string $key): bool
    {
        return $this->headers->has($this->normalizeHeaderName($key));
    }

    public function removeHeader(string $key): self
    {
        $this->headers->remove($this->normalizeHeaderName($key));
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    public function headers(): Data
    {
        return $this->headers;
    }

    // === Cookie Methods ===

    public function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $cookie = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ];

        $this->cookies->set($name, $cookie);
        return $this;
    }

    public function expireCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    public function getCookie(string $name): mixed
    {
        return $this->cookies->get($name);
    }

    public function hasCookie(string $name): bool
    {
        return $this->cookies->has($name);
    }

    public function removeCookie(string $name): self
    {
        $this->cookies->remove($name);
        return $this;
    }

    public function getCookies(): array
    {
        return $this->cookies->all();
    }

    public function cookies(): Data
    {
        return $this->cookies;
    }

    // === Content Type Helpers ===

    public function withContentType(string $contentType): self
    {
        return $this->setHeader('Content-Type', $contentType);
    }

    public function asJson(): self
    {
        return $this->setHeader('Content-Type', 'application/json; charset=' . $this->charset);
    }

    public function asXml(): self
    {
        return $this->setHeader('Content-Type', 'application/xml; charset=' . $this->charset);
    }

    public function asHtml(): self
    {
        return $this->setHeader('Content-Type', 'text/html; charset=' . $this->charset);
    }

    public function asText(): self
    {
        return $this->setHeader('Content-Type', 'text/plain; charset=' . $this->charset);
    }

    // === Cache Methods ===

    public function setMaxAge(int $seconds): self
    {
        return $this->setHeader('Cache-Control', 'max-age=' . $seconds);
    }

    public function setPrivate(): self
    {
        return $this->setHeader('Cache-Control', 'private');
    }

    public function setPublic(): self
    {
        return $this->setHeader('Cache-Control', 'public');
    }

    public function setNoCache(): self
    {
        return $this->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function setEtag(string $etag, bool $weak = false): self
    {
        $etag = $weak ? 'W/"' . $etag . '"' : '"' . $etag . '"';
        return $this->setHeader('ETag', $etag);
    }

    public function setLastModified(\DateTimeInterface $date): self
    {
        return $this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
    }

    public function setExpires(\DateTimeInterface $date): self
    {
        return $this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
    }

    // === Security Headers ===

    public function withSecurityHeaders(): self
    {
        return $this
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function withCors(array $origins = ['*'], array $methods = ['GET', 'POST'], array $headers = []): self
    {
        $this->setHeader('Access-Control-Allow-Origin', implode(', ', $origins));
        $this->setHeader('Access-Control-Allow-Methods', implode(', ', $methods));

        if (!empty($headers)) {
            $this->setHeader('Access-Control-Allow-Headers', implode(', ', $headers));
        }

        return $this;
    }

    // === Response Preparation ===

    public function prepare(): self
    {
        // Set content length if not already set
        if (!$this->hasHeader('Content-Length') && !$this->hasHeader('Transfer-Encoding')) {
            $content = $this->getContent();
            if ($content !== null) {
                if (is_array($content)) {
                    $contentToSend = reset($content);
                } else {
                    $contentToSend = $content;
                }

                $this->setHeader('Content-Length', strlen((string)$contentToSend));
            }
        }

        // Remove content for certain status codes
        if ($this->isEmpty() || $this->statusCode < 200) {
            $this->setContent('');
            $this->removeHeader('Content-Type');
            $this->removeHeader('Content-Length');
        }

        return $this;
    }

    // === Send Response ===

    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public function sendHeaders(): self
    {
        if (headers_sent()) {
            return $this;
        }

        // Send status line
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        // Send headers
        foreach ($this->headers->all() as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header($name . ': ' . $v, false);
                }
            } else {
                header($name . ': ' . $value);
            }
        }

        // Send cookies
        foreach ($this->cookies->all() as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        return $this;
    }

    public function sendContent(): self
    {
        if (is_array($this->content)) {
            // Si c'est un tableau, prendre le premier élément
            $contentToSend = reset($this->content);
        } else {
            // Sinon utiliser le contenu tel quel
            $contentToSend = $this->content;
        }
        echo  $contentToSend;

        return $this;
    }

    // === Utility Methods ===

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    // === Magic Methods ===

    public function __toString(): string
    {
        return (string)$this->content;
    }

    public function __debugInfo(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'statusText' => $this->statusText,
            'headers' => $this->headers->all(),
            'cookies' => $this->cookies->all(),
            'content' => $this->content,
            'charset' => $this->charset,
            'version' => $this->version
        ];
    }

    // === Static Helpers ===

    public static function getStatusTxt(int $code): string
    {
        return self::$statusTexts[$code] ?? 'Unknown';
    }

    public static function isValidStatusCode(int $code): bool
    {
        return isset(self::$statusTexts[$code]);
    }
}
