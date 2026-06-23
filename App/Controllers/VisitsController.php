<?php


namespace App\Controllers;

use Core\Helper\Data;
use Core\System\CSRF;
use App\Models\Statistic;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Controllers\Controller;
use Core\Exception\CSRFExeption;
use Core\Exception\ConnectionException;
use Core\System\Session;

class StatisticController extends Controller
{

    // Fenêtre de déduplication : 5 heures
    private const DEDUP_WINDOW = 5;
    private $logFile = '/var/log/pfsense-dns.log';
    /**
     * ========================================================================
     * FILTRES NIVEAU 1 : INFRASTRUCTURE TECHNIQUE (CDN, API, Services Cloud)
     * ========================================================================
     */
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

        // Services Google (infrastructure)
        'googleapis',
        'gvt1.com',
        'gvt2.com',
        'gvt3.com',
        'ggpht',
        'googlevideo',
        'ytimg',
        'google-analytics',
        'googletagmanager',

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

        // Amazon AWS
        'amazonaws.com',
        'cloudfront.net',
        's3.',
        'aws',

        // Services meta/Facebook (infrastructure)
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

        // Moteurs de recherche
        'bing.com',
        'google.com',
        'duckduckgo.com',
        'google.cn',
        'google.us',

        //mails
        'live.com',
        'whatsapp.net',
        'msn.com',
        'safebrowsing.apple',
        'wifi.com',
        'ista.ma'
    ];

    /**
     * ========================================================================
     * FILTRES NIVEAU 2 : TRACKING, ANALYTICS, PUBLICITÉ
     * ========================================================================
     */
    private array $trackingAndAds = [
        // Tracking général
        'analytics',
        'tracking',
        'metric',
        'telemetry',
        'beacon',
        'crashlytics.com',
        'sentry.io',
        'datadoghq.com',
        'newrelic',

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
    ];

    /**
     * ========================================================================
     * FILTRES NIVEAU 3 : SDKs MOBILES ET APIs TIERCES
     * ========================================================================
     */
    private array $mobileSdksAndApis = [
        // SDKs Android/iOS
        'app-measurement.com',
        'android.com',
        'gos-gsp.io',
        'heytapmobile.com',
        '3gppnetwork.org',

        // SDKs spécifiques fabricants
        'samsungcloud.com',
        'samsunghealth.com',
        'samsungdive.com',
        'samsung-dict.com',
        'samsungconsent.com',
        'samsungpositioning.com',
        'samsungosp.com',
        'xiaomi.com',

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
        'tiktokpangle.us',
        'tiktokcdn-us.com',
        'tiktokcdn-eu.com',
        'tiktokv.com',
        'tiktokglobalshopv.com',

        // Services de géolocalisation
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
    ];

    /**
     * ========================================================================
     * FILTRES NIVEAU 4 : DOMAINES TECHNIQUES (PATTERNS)
     * ========================================================================
     */
    private array $technicalPatterns = [
        // Préfixes techniques
        'api.',
        'sdk.',
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

    /**
     * ========================================================================
     * LISTE BLANCHE : DOMAINES À TOUJOURS GARDER (sites réels)
     * ========================================================================
     */
    private array $whitelistedDomains = [
        // Réseaux sociaux (pages visitables)
        'facebook.com',
        'instagram.com',
        'twitter.com',
        'linkedin.com',
        'snapchat.com',
        'discord.com',
        'discord.gg',
        'reddit.com',
        'whatsapp.com', // Interface web

        // Messagerie web
        'office.com',
        'outlook.com',
        'gmail.com',
        'yahoo.com',

        // Streaming & Media
        'youtube.com',
        'netflix.com',
        'spotify.com',
        'twitch.tv',
        'disney.com',

        // E-commerce
        'amazon.com',
        'ebay.com',
        'alibaba.com',
        'temu.com',
        'walmart.com',
        'uber.com',

        // Finance (sites visitables)
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

        // Banques (portails web)
        'citibank.com',
        'citigroup.com',
        'ing.com',
        'db.com',

        // Tech & Productivité
        'openai.com',
        'chatgpt.com',
        'deepseek.com',
        'notion.so',
        'canva.com',
        'adobe.com',
        'wordpress.com',

        // Éducation & Apprentissage
        'duolingo.com',
        'lingq.com',
        'efp-ofppt.com',

        // Jeux
        'chess.com',
        'steam.',
        'epic.',
        'gamepass.com',

        // Actualités (Maroc)
        'hespress.com',

        // Services cloud (interfaces web)
        'dropbox.com',
        'drive.google.com',
        'onedrive.com',

        // Sécurité (sites visitables)
        'virustotal.com',
        'kaspersky.com',
        'avast.com',
        'avg.com',
        'malwarebytes.org',
        'eset.com',

        // Communication
        'skype.com',
        'webex.com',
        'zoom.us',
        'teams.microsoft.com',

        // Utilitaires
        'speedtest.net',
        'send-anywhere.com',

        // Raccourcisseurs d'URL (indiquent visite intentionnelle)
        'bit.ly',
        'goo.gl',


        // Autres
        'opera.com',
        'brave.com',
        'me.com',
    ];


    /**
     * Vérifie si un domaine est dans la liste blanche
     */
    private function isWhitelisted(string $domain, array $whitelist): bool
    {
        $lowerDomain = strtolower($domain);

        foreach ($whitelist as $whitelisted) {
            if (
                strtolower($whitelisted) === $lowerDomain ||
                str_ends_with($lowerDomain, '.' . strtolower($whitelisted))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte si un domaine est technique/non-visitable
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
     * Détection avancée : domaines suspects par structure
     */
    private function hasSuspiciousStructure(string $domain): bool
    {
        // Domaines avec beaucoup de chiffres (APIs générées)
        $digitCount = preg_match_all('/\d/', $domain);
        if ($digitCount > 5) {
            return true;
        }

        // Domaines très courts sans TLD connu (ex: "1rx.io", "sc-gw.com")
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            $mainPart = $parts[0];
            // Domaine principal < 4 caractères avec chiffres
            if (strlen($mainPart) < 4 && preg_match('/\d/', $mainPart)) {
                return true;
            }
        }

        // Domaines avec patterns suspects
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
     * Filtre principal : décide si un domaine doit être conservé
     */
    private function shouldKeepDomain(
        string $domain,
        array $whitelist,
        array $techInfra,
        array $tracking,
        array $sdks,
        array $patterns
    ): bool {
        // 1. Liste blanche prioritaire
        if (isWhitelisted($domain, $whitelist)) {
            return true;
        }

        // 2. Filtres techniques
        if (
            isTechnicalDomain($domain, $techInfra) ||
            isTechnicalDomain($domain, $tracking) ||
            isTechnicalDomain($domain, $sdks) ||
            isTechnicalDomain($domain, $patterns)
        ) {
            return false;
        }

        // 3. Structure suspecte
        if (hasSuspiciousStructure($domain)) {
            return false;
        }

        // 4. Par défaut : conserver (domaines inconnus potentiellement intéressants)
        return true;
    }

    /**
     * Normalise un domaine
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain, '.'));
        $parts = explode('.', $domain);
        $count = count($parts);

        return $count >= 2 ? $parts[$count - 2] . '.' . $parts[$count - 1] : $domain;
    }

    /**
     * Parse le fichier de log avec filtrage intelligent
     */
    private function parseLogFile(): array
    {
        $whitelist = $this->whitelistedDomains;
        $techInfra = $this->technicalInfrastructure;
        $tracking = $this->trackingAndAds;
        $sdks = $this->mobileSdksAndApis;
        $patterns = $this->technicalPatterns;
        $dedupWindow = self::DEDUP_WINDOW * 3600;
        $handle = fopen($this->logFile, 'r');
        if (!$handle) {
            die("❌ Impossible d'ouvrir le fichier log : $this->logFile\n");
        }

        $visits = [];
        $stats = [
            'total_lines' => 0,
            'matched_lines' => 0,
            'filtered_non_A' => 0,
            'filtered_technical' => 0,
            'filtered_tracking' => 0,
            'filtered_sdk' => 0,
            'filtered_patterns' => 0,
            'filtered_suspicious' => 0,
            'whitelisted_kept' => 0,
            'unknown_kept' => 0,
        ];

        $dedup = [];

        echo "🔍 Analyse du fichier de log avec filtrage intelligent...\n";

        while (($line = fgets($handle)) !== false) {
            $stats['total_lines']++;

            if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}).*info: (\d{1,3}(?:\.\d{1,3}){3}) ([\w\.\-]+)\. (\w+) IN$/', $line, $matches)) {
                $stats['matched_lines']++;

                $timestamp = $matches[1];
                $sourceIp = $matches[2];
                $rawDomain = rtrim($matches[3], '.');
                $queryType = $matches[4];

                // Filtre 1 : Type de requête
                if ($queryType !== 'A') {
                    $stats['filtered_non_A']++;
                    continue;
                }

                // Normalisation
                $domain = normalizeDomain($rawDomain);

                // Filtre 2 : Détection intelligente
                $isWhitelisted = isWhitelisted($domain, $whitelist);

                if (!$isWhitelisted) {
                    // Vérifier chaque type de filtre pour les stats
                    if (isTechnicalDomain($domain, $techInfra)) {
                        $stats['filtered_technical']++;
                        continue;
                    }
                    if (isTechnicalDomain($domain, $tracking)) {
                        $stats['filtered_tracking']++;
                        continue;
                    }
                    if (isTechnicalDomain($domain, $sdks)) {
                        $stats['filtered_sdk']++;
                        continue;
                    }
                    if (isTechnicalDomain($domain, $patterns)) {
                        $stats['filtered_patterns']++;
                        continue;
                    }
                    if (hasSuspiciousStructure($domain)) {
                        $stats['filtered_suspicious']++;
                        continue;
                    }

                    // Domaine inconnu conservé
                    $stats['unknown_kept']++;
                } else {
                    $stats['whitelisted_kept']++;
                }

                // Déduplication temporelle
                $currentTime = strtotime($timestamp);

                if (isset($dedup[$sourceIp][$domain])) {
                    $lastVisit = $dedup[$sourceIp][$domain];
                    if (($currentTime - $lastVisit) < $dedupWindow) {
                        continue;
                    }
                }

                $dedup[$sourceIp][$domain] = $currentTime;

                $visits[] = [
                    'source_ip' => $sourceIp,
                    'domain' => $domain,
                    'query_type' => $queryType,
                    'logged_at' => date('Y-m-d H:i:s', $currentTime),
                    'is_whitelisted' => $isWhitelisted ? 1 : 0,
                ];
            }
        }

        fclose($handle);

        return [$visits, $stats];
    }
    /**
     * Export CSV amélioré
     */
    public function exportResult()
    {
        $visits = $this->parseLogFile();
        $result = [];
        foreach ($visits as $visit) {
            $result[] = [
                $visit['source_ip'],
                $visit['domain'],
                $visit['query_type'],
                $visit['logged_at'],
            ];
        }
        return $result;
    }

    public function store()
    {
        $domains = $this->exportResult();
        if (!$domains) {
            return;
        }
        $this->model->store($domains);
    }
}
