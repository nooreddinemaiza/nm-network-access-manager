<?php

namespace App\Controllers;

use Core\File;
use Core\Logger;
use Core\Helper\Data;
use App\Models\DnsResolve;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;
use Core\Routing\RouteException;

class DnsResolveControllerR extends Controller
{

    /** @var string Fichier de token de validation */
    private const TOKEN_FILE = 'cron_secret.php';
    /** @var int Fenêtre de déduplication en heures */
    private const DEDUP_WINDOW_HOURS = 5;

    /** @var string Chemin du fichier de log DNS */
    private string $logFile = '/var/log/pfsense-dns.log';

    /** @var DnsResolve Modèle de base de données */
    private DnsResolve $model;

    /** @var bool Mode debug */
    private bool $debug = false;

    private array $technicalInfrastructure = [
        // CDN et réseaux de livraison
        'akamai',
        'cloudflare',
        'fastly',
        'cdn77',
        'keycdn',
        'stackpath',
        'cloudfront',
        'azureedge',
        'gstatic',
        'googleusercontent',
        'cdn',
        'mozilla.com',
        'tailscale.com',
        'fallback.',
        'brave.com',
        'events.',
        // Services Google (infrastructure)
        'googlesyndication.com',
        'google',
        'gvt1.com',
        'gvt2.com',
        'gvt3.com',
        'ggpht',
        'googlevideo',
        'ytimg',
        'google-analytics',
        'googletagmanager',
        'updates.',
        // Services Apple (infrastructure)
        'aaplimg',
        'mzstatic',
        'apple-dns',
        'apple-cloudkit',

        // Services Microsoft (infrastructure)
        'msft',
        'msecnd',
        'trafficmanager',
        'azurefd',
        'office365.com',
        'microsoftonline.com',
        'sfx.ms',
        'msedge.net',
        'microsoft.com',
        'office.com',
        'office.net',
        'windows.com',
        'appcenter.ms',
        'hisavana.com',
        'taboola.com',
        'opensignal.com',
        'nist.gov',
        'apple.com',
        'apple.news',
        'icloud.com',
        'tplinkcloud.com',
        // Amazon AWS
        'amazonaws.com',
        'cloudfront.net',
        's3.',
        'aws',

        // Services Meta/Facebook (infrastructure)
        'fbsbx.com',
        'facebook.net',
        'cdninstagram.com',

        // DNS et réseau
        'dns',
        'ntp.',
        'arpa',
        'quad9.net',

        // Certificats SSL
        'ocsp.',
        'crl.',
        'pki.',
        'lencr.org',
        'digicert',
        'letsencrypt',

        // Mise à jour système
        'windowsupdate',
        'ubuntu.com',
        'snapcraft.io',

        // Moteurs de recherche (infrastructure)
        'bing.com',
        'google.com',
        'duckduckgo.com',
        'google.cn',
        'google.us',
        'google.ru',
        'playstore',
        'unpkg.com',
        'jquery',
        'kaspersky',
        'ns1p.net',
        'byteoversea',
        'btloader',
        'indexww.com',


        // Services mail/messaging (infrastructure)
        'live.com',
        'live.net',
        'whatsapp.net',
        'msn.com',
        'safebrowsing.apple',
        'wifi.com',
        'ista.ma',
        'xn--8hb.xn--8hbae',
        'adjust.',
        'fbpigeon.com',
        'mattel',
        'ahagamecenter.com',
        'eagllwin',
        'heytapmobi.com',
        'bidmachine.io',
        'hpcnt.com',
        'mintegral.net',
        'branch.io',
        'adtraffic',
        'beepityping.com'

    ];

    // =========================================================================
    // FILTRES - NIVEAU 2 : TRACKING & PUBLICITÉ
    // =========================================================================

    private array $trackingAndAds = [
        // Tracking général
        'analytics',
        'tracking',
        'sweatco.in',
        'thecatmachine.com',
        'metric',
        'telemetry',
        'beacon',
        'crashlytics.com',
        'sentry.io',
        'datadoghq.com',
        'newrelic',
        'mtg',
        'vivo',
        'mi.com',
        'samsung',
        'xiaomi',


        // Publicité
        'ads',
        'ad.',
        'doubleclick',
        'adsrvr',
        'adnxs',
        'pubmatic.com',
        'adform.net',
        'adkernel.com',
        'adgrx.com',
        'advolve.io',
        'tribalfusion.com',
        'mgid.com',
        '4dsply.com',
        'popcash.net',
        'sharethrough',
        'adtidy',

        // Analytics commercial
        'scorecardresearch.com',
        'quantserve.com',
        'mixpanel',
        'amplitude',
        'appsflyer',
        'mparticle.com',
        'clevertap-prod.com',
        'onetrust.io',

        // Réseaux publicitaires mobiles
        'heytapdl.com',
        'applovin.com',
        'applvn.com',
        'pangle.io',
        'inner-active.mobi',
        'vungle.com',
        'moloco.com',
        'inmobi.com',
        'startappservice.com',
        'unrulymedia.com',
        'openx.net',
        'rtbhouse.com',
        'onaudience.com',
        'blismedia.com',
        'gammaplatform.com',
        '1rx.io',
        'simpli.fi',
        'erne.co',
        'crwdcntrl.net',
        'aditude.io',
        'prebid-server.com',
        'confiant-integrations.net',
        'miui.com',
        'ipify.org',
        'shalltry.com',
        'dbankcloud',
        'coloros.com',
        'sc-gw.com',
        'unisoc.com',
        'bingapis.com',
        'graph.facebook.com',
        'z-m-gateway.facebook.com',
    ];

    // =========================================================================
    // FILTRES - NIVEAU 3 : SDKs MOBILES

    private array $mobileSdksAndApis = [
        // SDKs Android/iOS
        'app-measurement.com',
        'android.com',
        'gos-gsp.io',
        'heytapmobile.com',
        '3gppnetwork.org',

        // SDKs fabricants
        'samsungcloud.com',
        'samsunghealth.com',
        'samsungdive.com',
        'samsung-dict.com',
        'samsungconsent.com',
        'samsungpositioning.com',
        'samsungosp.com',

        // Services chinois (infrastructure)
        'qq.com',
        'baidu.com',
        'alibaba.com',
        '360safe.com',
        '360.com',
        '360totalsecurity.com',
        'volces.com',
        'fengkongcloud.com',

        // TikTok infrastructure
        'tiktokpangle.',
        'tiktokcdn-us.',
        'tiktokv.',
        'tiktokglobalshopv.',

        // Géolocalisation
        'virtualearth.net',
        'here.com',

        // Autres SDKs
        'nelreports.net',
        'snplow.net',
        'scene7.com',
        'gdflpr.com',
        'gdfsnt.com',
        'gist.build',
        'snapkit.com',
        'skype.com',
    ];

    // =========================================================================
    // FILTRES - NIVEAU 4 : PATTERNS TECHNIQUES
    // =========================================================================

    private array $technicalPatterns = [
        // Préfixes techniques
        'detectportal.',
        'service-',
        '.data.',
        'websocket',
        'sockjs',
        'api.',
        'api-',
        'apis.',
        'json',
        'sdk',
        'gateway.',
        'proxy.',
        'graph.',
        'edge.',
        'cdn.',
        'static.',
        'assets.',
        'media.',
        'content.',
        'img.',
        'image.',
        'js.',
        'css.',
        'font.',
        'fonts.',
        'resource.',
        'widget.',
        'embed.',
        'player.',

        // Services système
        'mail.',
        'smtp.',
        'imap.',
        'pop.',
        'mx.',
        'spf.',
        'dkim.',
        'update.',
        'download.',
        'upload.',
        'sync.',

        // Environnements
        'dev.',
        'test.',
        'stage.',
        'staging.',
        'sandbox.',
        'qa.',

        // Réseau local
        'localhost',
        'local',
        'lan',
        'internal',
        'intranet',
    ];
    private array $notAllowedIps = [
        '192.168.0.21',
        '127.0.0.1',
        // '192.168.0.22',
        '192.168.0.92'
    ];
    // =========================================================================
    // LISTE BLANCHE : SITES RÉELS VISITABLES
    // =========================================================================

    private array $whitelistedDomains = [
        // Réseaux sociaux
        'facebook.com',
        'instagram.com',
        'twitter.com',
        'linkedin.com',
        'snapchat.com',
        'discord.com',
        'discord.gg',
        'reddit.com',
        'whatsapp.com',
        'tiktok.com',

        // Messagerie web
        'outlook.com',
        'gmail.com',
        'yahoo.com',

        // Streaming & Media
        'youtube.com',
        'netflix.com',
        'spotify.com',
        'twitch.tv',
        'disney.com',
        'primevideo.com',

        // E-commerce
        'amazon.com',
        'ebay.com',
        'temu.com',
        'walmart.com',
        'uber.com',
        'shein.com',
        'wish.com',

        // Finance & Crypto
        'paypal.com',
        'binance.com',
        'coinbase.com',
        'kraken.com',
        'metamask.io',
        'pancakeswap.finance',
        'gate.io',
        'kucoin.com',
        'huobi.com',
        'bithumb.com',
        'bitstamp.net',
        'changelly.com',
        'ledger.com',
        'exodus.com',
        'poloniex.com',

        // Banques
        'citibank.com',
        'citigroup.com',
        'ing.com',
        'db.com',

        // Tech & Productivité
        'openai.com',
        'chatgpt.com',
        'deepseek.com',
        'claude.ai',
        'notion.so',
        'canva.com',
        'adobe.com',
        'adobedc',
        'wordpress.com',
        'github.com',
        'stackoverflow.com',

        // Éducation
        'duolingo.com',
        'lingq.com',
        'efp-ofpft.com',
        'coursera.org',
        'udemy.com',
        'khanacademy.org',

        // Jeux
        'chess.com',
        'steam.',
        'epic.',
        'gamepass.com',
        'roblox.com',

        // Actualités Maroc
        'hespress.com',
        'le360.ma',
        'medias24.com',

        // E-commerce Maroc
        'avito.ma',
        'jumia.ma',
        'marjane.ma',

        // Services cloud
        'dropbox.com',
        'drive.google.com',
        'onedrive.com',

        // Sécurité
        'virustotal.com',
        'kaspersky.com',
        'avast.com',
        'avg.com',
        'malwarebytes.org',
        'eset.com',

        // Communication
        'webex.com',
        'zoom.us',
        'teams.microsoft.com',

        // Utilitaires
        'speedtest.net',
        'send-anywhere.com',

        // Raccourcisseurs URL
        'bit.ly',
        'goo.gl',
        't.co',

        // Navigateurs
        'opera.com',
        'brave.com',
        'firefox.com',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->model = new DnsResolve();

        // Mode debug basé sur l'environnement
        $this->debug = defined('APP_DEBUG') && APP_DEBUG === true;
    }

    // =========================================================================
    // MÉTHODES DE FILTRAGE
    // =========================================================================

    /**
     * Vérifie si un domaine est dans la liste blanche
     */
    private function isWhitelisted(string $domain): bool
    {
        $lowerDomain = strtolower($domain);

        foreach ($this->whitelistedDomains as $whitelisted) {
            $whitelisted = strtolower($whitelisted);

            // Match exact ou sous-domaine
            if (
                $lowerDomain === $whitelisted ||
                str_ends_with($lowerDomain, '.' . $whitelisted)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte si un domaine correspond à un pattern technique
     */
    private function isTechnicalDomain(string $domain, array $filters): bool
    {
        $lowerDomain = strtolower($domain);

        foreach ($filters as $pattern) {
            if (str_contains($lowerDomain, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte les structures de domaines suspectes
     */
    private function hasSuspiciousStructure(string $domain): bool
    {
        // Trop de chiffres (APIs générées)
        $digitCount = preg_match_all('/\d/', $domain);
        if ($digitCount > 5) {
            return true;
        }

        // Domaine principal très court avec chiffres
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            $mainPart = $parts[0];
            if (strlen($mainPart) < 4 && preg_match('/\d/', $mainPart)) {
                return true;
            }
        }

        // Patterns suspects
        $suspiciousPatterns = [
            '-prod.',
            '-api.',
            '-cdn.',
            '-edge.',
            '-sdk.',
            '-analytics.',
            '-tracking.',
            '-ad.',
            '-ads.',
            'telemetry',
            'metrics',
            'reporting',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($domain, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalise un domaine (garde seulement domaine.tld)
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain, '.'));
        $parts = explode('.', $domain);
        $count = count($parts);

        return $count >= 2
            ? $parts[$count - 2] . '.' . $parts[$count - 1]
            : $domain;
    }

    // =========================================================================
    // PARSING DU FICHIER LOG
    // =========================================================================

    /**
     * Parse le fichier de log DNS et applique tous les filtres
     * 
     * @return array [visits, stats]
     * @throws \RuntimeException Si le fichier n'est pas accessible
     */
    private function parseLogFile(): array
    {
        // Vérification de l'existence du fichier
        if (!file_exists($this->logFile)) {
            throw new \RuntimeException("Fichier log introuvable : {$this->logFile}");
        }

        if (!is_readable($this->logFile)) {
            throw new \RuntimeException("Fichier log non lisible : {$this->logFile}");
        }

        $handle = @fopen($this->logFile, 'r');
        if (!$handle) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier log : {$this->logFile}");
        }

        $visits = [];
        $dedup = [];
        $dedupWindow = self::DEDUP_WINDOW_HOURS * 3600;

        $stats = [
            'total_lines' => 0,
            'matched_lines' => 0,
            'filtered_non_a' => 0,
            'filtered_technical' => 0,
            'filtered_tracking' => 0,
            'filtered_sdk' => 0,
            'filtered_patterns' => 0,
            'filtered_suspicious' => 0,
            'filtered_duplicate' => 0,
            'whitelisted_kept' => 0,
            'unknown_kept' => 0,
        ];

        $this->log("Début du parsing du fichier DNS...");

        // Pattern regex pour les logs DNS PFSense
        $pattern = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}).*info: (\d{1,3}(?:\.\d{1,3}){3}) ([\w\.\-]+)\. (\w+) IN$/';

        while (($line = fgets($handle)) !== false) {
            $stats['total_lines']++;

            // Parse de la ligne
            if (!preg_match($pattern, $line, $matches)) {
                continue;
            }

            $stats['matched_lines']++;

            $timestamp = $matches[1];
            $sourceIp = $matches[2];
            $rawDomain = rtrim($matches[3], '.');
            $queryType = $matches[4];
            // Filtre 1 : Type de requête (seulement A = IPv4)
            if ($queryType !== 'A') {
                $stats['filtered_non_a']++;
                continue;
            }

            // Normalisation du domaine
            $domain = $this->normalizeDomain($rawDomain);

            // Filtre 2 : Détection intelligente
            $isWhitelisted = $this->isWhitelisted($domain);

            if (in_array($sourceIp, $this->notAllowedIps)) {
                continue;
            }

            $currentTime = strtotime($timestamp);


            if (!$isWhitelisted) {
                // Vérifier chaque catégorie de filtre
                if ($this->isTechnicalDomain($rawDomain, $this->technicalInfrastructure)) {
                    $stats['filtered_technical']++;
                    continue;
                }

                if ($this->isTechnicalDomain($rawDomain, $this->trackingAndAds)) {
                    $stats['filtered_tracking']++;
                    continue;
                }

                if ($this->isTechnicalDomain($rawDomain, $this->mobileSdksAndApis)) {
                    $stats['filtered_sdk']++;
                    continue;
                }

                if ($this->isTechnicalDomain($rawDomain, $this->technicalPatterns)) {
                    $stats['filtered_patterns']++;
                    continue;
                }

                if ($this->hasSuspiciousStructure($rawDomain)) {
                    $stats['filtered_suspicious']++;
                    continue;
                }

                $stats['unknown_kept']++;
            } else {
                $stats['whitelisted_kept']++;
            }


            if (isset($dedup[$sourceIp][$domain])) {
                $lastVisit = $dedup[$sourceIp][$domain];
                if (($currentTime - $lastVisit) < $dedupWindow) {
                    $stats['filtered_duplicate']++;
                    continue;
                }
            }

            // Enregistrer la visite
            $dedup[$sourceIp][$domain] = $currentTime;

            $visits[] = [
                'source_ip' => $sourceIp,
                'domain' => $domain,
                'query_type' => $queryType,
                'logged_at' => date('Y-m-d H:i:s', $currentTime),
                // 'is_whitelisted' => $isWhitelisted ? 1 : 0,
            ];
        }

        fclose($handle);

        $this->log(sprintf(
            "Parsing terminé : %d lignes analysées, %d visites conservées",
            $stats['total_lines'],
            count($visits)
        ));

        return [$visits, $stats];
    }

    // =========================================================================
    // MÉTHODES PUBLIQUES (API)
    // =========================================================================

    /**
     * Stocke les visites DNS en base de données
     * 
     * @return array|null Résultat de l'opération ou null en cas d'erreur
     */
    public function store()
    {
        // Parse le fichier log
        list($visits, $stats) = $this->parseLogFile();

        if (empty($visits)) {
            $this->log("Aucune visite à enregistrer", 'warning');
            return false;
        }

        // Stockage en base de données
        $result = $this->model->store($visits);
        if (!$result) {
            throw new ConnectionException('Une erreur est survenue lors de la mise à jour des enregistrement!');
        }
        $this->log(sprintf(
            "Stockage réussi : %d visites enregistrées",
            count($visits)
        ));

        return $result;
    }
    public function updateRecords(Request $request): Response
    {
        try {
            $data = Data::create($request->all())->only([
                'job',
                'token',
                'date',
                'lines',
                'file_size',
                'timestamp'
            ]);

            if (!File::exists('file', self::TOKEN_FILE)) {
                throw new ValidationException(message: 'Le token de securite est invalide ou Fichier introuvable!');
            }
            $token = File::require('file', self::TOKEN_FILE);
            $token = $token['token'];

            $errors = $data->validate([
                'job'       => 'required|same:dns-daily-sync',
                'token'      => 'required|same:' . $token,
                'date'      => 'required|date',
                'lines'     => 'required|integer|min:0',
                'file_size' => 'required|integer|min:0',
                'timestamp' => 'required'
            ]);

            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $result = $this->store($data);

            if (!$result) {
                throw new ConnectionException();
            }

            return Response::json([
                'success' => true,
                'message' => sprintf(
                    '%d entrées DNS traitées avec succès',
                    $data->get('lines')
                )
            ]);
        } catch (ValidationException $e) {

            $this->log(
                "Validation échouée (cron DNS) : " . json_encode($e->getErrors()),
                'warning'
            );

            return RouteException::handleUnauthorized($request);
        } catch (ConnectionException $e) {

            $this->log(
                "Erreur de connexion lors du traitement cron DNS",
                'message'
            );
            return Response::json([
                'success' => false,
                'message' => 'Pas de traitement a faire!'
            ]);
        } catch (\Throwable $e) {

            $this->log(
                "cron DNS : " . $e->getMessage(),
                'critical'
            );

            return Response::json([
                'success' => false,
                'message'   => 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Analyse les logs et retourne les statistiques sans stocker
     * 
     * @return array Statistiques détaillées
     */
    public function analyze(): array
    {
        try {
            list($visits, $stats) = $this->parseLogFile();

            // Calculs supplémentaires
            $uniqueIps = array_unique(array_column($visits, 'source_ip'));
            $uniqueDomains = array_unique(array_column($visits, 'domain'));

            $topDomains = $this->getTopDomains($visits, 20);
            $topVisitors = $this->getTopVisitors($visits, 10);

            return [
                'success' => true,
                'stats' => array_merge($stats, [
                    'total_visits' => count($visits),
                    'unique_ips' => count($uniqueIps),
                    'unique_domains' => count($uniqueDomains),
                ]),
                'top_domains' => $topDomains,
                'top_visitors' => $topVisitors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retourne les domaines les plus visités
     * 
     * @param array $visits Liste des visites
     * @param int $limit Nombre de résultats
     * @return array Top domaines avec statistiques
     */
    private function getTopDomains(array $visits, int $limit = 20): array
    {
        $domainStats = [];

        foreach ($visits as $visit) {
            $domain = $visit['domain'];

            if (!isset($domainStats[$domain])) {
                $domainStats[$domain] = [
                    'domain' => $domain,
                    'visits' => 0,
                    'unique_ips' => [],
                    // 'is_whitelisted' => $visit['is_whitelisted'],
                ];
            }

            $domainStats[$domain]['visits']++;
            $domainStats[$domain]['unique_ips'][$visit['source_ip']] = true;
        }

        // Ajouter le comptage des IPs uniques
        foreach ($domainStats as &$stat) {
            $stat['unique_visitors'] = count($stat['unique_ips']);
            unset($stat['unique_ips']);
        }

        // Trier par nombre de visites
        usort($domainStats, fn($a, $b) => $b['visits'] <=> $a['visits']);

        return array_slice($domainStats, 0, $limit);
    }

    /**
     * Retourne les visiteurs les plus actifs
     * 
     * @param array $visits Liste des visites
     * @param int $limit Nombre de résultats
     * @return array Top visiteurs avec statistiques
     */
    private function getTopVisitors(array $visits, int $limit = 10): array
    {
        $visitorStats = [];

        foreach ($visits as $visit) {
            $ip = $visit['source_ip'];

            if (!isset($visitorStats[$ip])) {
                $visitorStats[$ip] = [
                    'ip' => $ip,
                    'visits' => 0,
                    'unique_domains' => [],
                ];
            }

            $visitorStats[$ip]['visits']++;
            $visitorStats[$ip]['unique_domains'][$visit['domain']] = true;
        }

        // Ajouter le comptage des domaines uniques
        foreach ($visitorStats as &$stat) {
            $stat['unique_domains'] = count($stat['unique_domains']);
        }

        // Trier par nombre de visites
        usort($visitorStats, fn($a, $b) => $b['visits'] <=> $a['visits']);

        return array_slice($visitorStats, 0, $limit);
    }

    // =========================================================================
    // UTILITAIRES
    // =========================================================================

    /**
     * Logging simple
     * 
     * @param string $message Message à logger
     * @param string $level Niveau de log (info, warning, error)
     */
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->debug) {
            Logger::$level("DnsResolveController: {$message}");
        }
    }

    /**
     * Active ou désactive le mode debug
     * 
     * @param bool $debug Mode debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * Ajoute un domaine à la liste blanche
     * 
     * @param string $domain Domaine à ajouter
     */
    public function addToWhitelist(string $domain): void
    {
        if (!in_array($domain, $this->whitelistedDomains)) {
            $this->whitelistedDomains[] = strtolower($domain);
        }
    }

    /**
     * Ajoute un pattern de filtrage
     * 
     * @param string $pattern Pattern à ajouter
     * @param string $category Catégorie (technical, tracking, sdk, patterns)
     */
    public function addFilter(string $pattern, string $category = 'technical'): void
    {
        $pattern = strtolower($pattern);

        switch ($category) {
            case 'tracking':
                if (!in_array($pattern, $this->trackingAndAds)) {
                    $this->trackingAndAds[] = $pattern;
                }
                break;
            case 'sdk':
                if (!in_array($pattern, $this->mobileSdksAndApis)) {
                    $this->mobileSdksAndApis[] = $pattern;
                }
                break;
            case 'patterns':
                if (!in_array($pattern, $this->technicalPatterns)) {
                    $this->technicalPatterns[] = $pattern;
                }
                break;
            default:
                if (!in_array($pattern, $this->technicalInfrastructure)) {
                    $this->technicalInfrastructure[] = $pattern;
                }
        }
    }
}
