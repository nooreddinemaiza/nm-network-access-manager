<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DNSLogProcessor;
use App\Services\DnsLogRepository;
use Core\Database\Database;
use Core\Helper\FileReader;
use Core\Helper\FileWriter;
use Core\Helper\LineFilter;
use Core\Logger;
use Core\Queue\Job;

/**
 * Job de traitement d'un chunk de logs DNS.
 *
 * Chaque instance traite exactement $limit lignes à partir de $offset
 * dans le fichier de logs. Les chunks sont indépendants et peuvent
 * être exécutés en parallèle par plusieurs Workers.
 *
 * Flux :
 *   1. Lit $limit lignes à partir de $offset dans le fichier
 *   2. Passe les lignes brutes au DnsLogProcessor (parse, filtre, normalise)
 *   3. Passe les entrées traitées au DnsLogRepository (INSERT OR UPDATE)
 */
final class ProcessDnsLogsChunkJob extends Job
{
    protected int    $maxAttempts = 3;
    protected int    $timeout     = 300;
    protected int|array $backoff     = [30, 60, 120];
    protected string $onQueue     = 'dns-processing';

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
        'microsoft.',
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

    public function __construct(
        /** Date du fichier de logs (Y-m-d), utilisée pour la clé d'idempotence. */
        private readonly string $date,

        /** Chemin absolu vers le fichier de logs DNS. */
        private string $logFilePath,

        /** Numéro de ligne de début (0-based). */
        private readonly int    $offset,

        /** Nombre de lignes à lire. */
        private readonly int    $limit,

        /** Index du chunk courant (pour le logging). */
        private readonly int    $chunkIndex,

        /** Nombre total de chunks (pour le logging). */
        private readonly int    $totalChunks,
    ) {}

    // -------------------------------------------------------------------------
    // JobInterface
    // -------------------------------------------------------------------------

    public function handle(): void
    {
        $lines = $this->readChunk();

        if (empty($lines)) {
            return; // Chunk vide (ex: fin de fichier atteinte avant offset).
        }

        $processor  = $this->resolveProcessor()
            ->onlyQueryTypes(['A', 'AAAA']);
        $repository = $this->resolveRepository();

        // 1. Traitement : parse + filtre + normalisation
        $entries = $processor->processChunk($lines, $this->date);

        if (empty($entries)) {
            return; // Toutes les lignes du chunk ont été filtrées.
        }

        // 2. Persistance : INSERT OR UPDATE en base
        $repository->persist($entries);
    }

    public function failed(\Throwable $e): void
    {
        // Le chunk a définitivement échoué après 3 tentatives.
        // On loggue mais on ne libère PAS le verrou global :
        // les autres chunks peuvent toujours réussir.
        Logger::warning(sprintf(
            '[DnsLogs] Chunk %d/%d for date %s failed permanently after %d attempts: %s',
            $this->chunkIndex + 1,
            $this->totalChunks,
            $this->date,
            $this->maxAttempts,
            $e->getMessage(),
        ));
    }

    private function readChunk(): array
    {
        FileWriter::writeLog('test.log', json_encode([
            'logFilePath' => basename($this->logFilePath),
            'limit' => $this->offset,
            'offset' => ($this->offset + $this->limit),
        ]), 'Debug');
        $lineFilter = LineFilter::new()
            ->startsWith($this->date)
            ->excludes($this->technicalInfrastructure)
            ->excludes($this->trackingAndAds)
            ->excludes($this->mobileSdksAndApis)
            ->excludes($this->technicalPatterns)
            ->excludes($this->notAllowedIps)
            ->compile();
        $lines = FileReader::readRangeFiltered(
            'log',
            basename($this->logFilePath),
            $this->offset,
            ($this->offset + $this->limit),
            $lineFilter
        );
        $lines = FileReader::toArray($lines);
        return $lines;
    }
    // -------------------------------------------------------------------------
    // Résolution des dépendances
    // -------------------------------------------------------------------------

    /**
     * Résout le DnsLogProcessor depuis le container de l'application.
     * À adapter selon votre container IoC.
     */
    private function resolveProcessor(): DNSLogProcessor
    {
        $processor = (new DNSLogProcessor());
        return $processor;
    }

    private function resolveRepository(): DnsLogRepository
    {
        // return app()->get(DnsLogRepository::class);
        return new DnsLogRepository(new Database());
    }
}
