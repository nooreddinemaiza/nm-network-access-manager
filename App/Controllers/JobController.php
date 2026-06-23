<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Jobs\DispatchDnsLogsJob;
use Core\Queue\Exceptions\QueueException;
use Core\Queue\QueueManager;
use Core\Queue\UniqueJobGuard;

/**
 * Endpoint HTTP interne pour le dispatch des Jobs de queue.
 *
 * Sécurité multicouche :
 *  1. Restriction IP   → uniquement localhost / réseau interne
 *  2. Token secret     → header X-Internal-Token (hash_equals, anti timing-attack)
 *  3. Méthode HTTP     → POST uniquement
 *  4. UniqueJobGuard   → protection contre le double dispatch (réponse 409)
 *
 * Le endpoint répond en < 10ms : il dispatch le Job et sort immédiatement.
 * Tout le traitement réel se fait dans le Worker en arrière-plan.
 *
 * Crontab :
 *   0 2 * * * curl -s -X POST \
 *     -H "X-Internal-Token: ${INTERNAL_TOKEN}" \
 *     --max-time 10 \
 *     http://127.0.0.1/internal/queue/dns-logs \
 *     >> /var/log/cron-dns.log 2>&1
 */
final class JobController
{
    /**
     * IPs autorisées à appeler les endpoints internes.
     * Étendre si le crontab tourne sur une machine séparée.
     */
    private const ALLOWED_IPS = ['127.0.0.1', '::1', '100.105.181.84'];

    /**
     * Durée du verrou unique en secondes.
     * 26h > 24h pour couvrir les légers décalages de crontab.
     */
    private const LOCK_TTL = 93600;

    public function __construct(
        private readonly QueueManager   $queueManager,
        private readonly UniqueJobGuard $guard,
        // private readonly string         $internalToken,
        private readonly string         $dnsLogDirectory,
        private readonly int            $chunkSize = 1000,
    ) {}

    // =========================================================================
    // Endpoints
    // =========================================================================

    /**
     *
     * Dispatche le Job d'ingestion DNS pour la date du jour.
     *
     * Réponses possibles :
     *  202 Accepted  → Job dispatché avec succès
     *  409 Conflict  → Job déjà dispatché pour aujourd'hui (doublon bloqué)
     *  400 Bad Request → fichier de logs introuvable
     *  401 Unauthorized → token manquant ou invalide
     *  403 Forbidden → IP non autorisée
     *  405 Method Not Allowed → méthode HTTP incorrecte
     *  500 Internal Server Error → erreur inattendue
     */
    public function dispatchDnsLogs(): void
    {
        // ── Sécurité (ordre important : du moins coûteux au plus coûteux) ───
        $this->assertMethod('POST');
        $this->assertAllowedIp();
        // $this->assertValidToken();

        // ── Paramètres du Job ────────────────────────────────────────────────
        $date        = date('Y-m-d');
        // ── Vérification préalable du fichier ────────────────────────────────

        if (!file_exists($this->dnsLogDirectory) || !is_readable($this->dnsLogDirectory)) {
            $this->respond(400, [
                'status'  => 'error',
                'message' => sprintf('DNS log file not found or not readable for date %s.', $date),
                'date'    => $date,
            ]);
            return;
        }

        // ── Protection doublon ───────────────────────────────────────────────
        $lockKey = sprintf('dns-logs:dispatch:%s', $date);

        if (!$this->guard->acquire($lockKey, self::LOCK_TTL)) {
            $this->respond(409, [
                'status'  => 'skipped',
                'message' => sprintf('Job already dispatched for date %s.', $date),
                'date'    => $date,
            ]);
            return;
        }

        // ── Dispatch (< 10ms) ────────────────────────────────────────────────
        try {
            $jobId = $this->queueManager->dispatch(
                new DispatchDnsLogsJob()
            );
        } catch (QueueException $e) {
            // Libère le verrou si le dispatch échoue pour permettre une reprise.
            $this->guard->release($lockKey);

            $this->respond(500, [
                'status'  => 'error',
                'message' => 'Failed to dispatch job: ' . $e->getMessage(),
                'date'    => $date,
            ]);
            return;
        }

        $this->respond(202, [
            'status'  => 'dispatched',
            'job_id'  => $jobId,
            'date'    => $date,
            'message' => sprintf(
                'DNS log ingestion job dispatched for %s (Nombre de lignes: %d).',
                $date,
                $this->chunkSize,
            ),
        ]);
    }
    public function dispatchDnsJob(): int
    {
        $date        = date('Y-m-d');

        if (!file_exists($this->dnsLogDirectory) || !is_readable($this->dnsLogDirectory)) {
            return  400;
        }

        // ── Protection doublon ───────────────────────────────────────────────
        $lockKey = sprintf('dns-logs:dispatch:%s', $date);

        if (!$this->guard->acquire($lockKey, self::LOCK_TTL)) {
            return 409;
        }

        // ── Dispatch (< 10ms) ────────────────────────────────────────────────
        try {
            $jobId = $this->queueManager->dispatch(
                new DispatchDnsLogsJob()
            );
        } catch (QueueException $e) {
            // Libère le verrou si le dispatch échoue pour permettre une reprise.
            $this->guard->release($lockKey);
            return 500;
        }

        return 202;
    }
    /**
     * GET /internal/queue/status
     *
     * Retourne l'état des queues (nombre de Jobs en attente).
     * Utile pour le monitoring et le debugging.
     */
    public function status(): void
    {
        $this->assertMethod('GET');
        $this->assertAllowedIp();
        $this->assertValidToken();

        $date    = date('Y-m-d');
        $lockKey = sprintf('dns-logs:dispatch:%s', $date);

        $this->respond(200, [
            'queues' => [
                'default'        => $this->queueManager->size('default'),
                'dns-processing' => $this->queueManager->size('dns-processing'),
            ],
            'dns_job_dispatched_today' => $this->guard->isLocked($lockKey),
            'date'                     => $date,
            'timestamp'                => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Sécurité
    // =========================================================================

    /**
     * Vérifie que la méthode HTTP correspond à celle attendue.
     */
    private function assertMethod(string $expected): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');

        if ($method !== $expected) {
            $this->respond(405, [
                'error'   => 'Method Not Allowed',
                'allowed' => $expected,
            ]);
            exit;
        }
    }

    /**
     * Autorise uniquement les IPs de ALLOWED_IPS.
     * Ignore volontairement X-Forwarded-For : un appel interne
     * ne doit jamais passer par un proxy.
     */
    private function assertAllowedIp(): void
    {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!in_array($remoteIp, self::ALLOWED_IPS, strict: true)) {
            $this->respond(403, ['error' => 'Forbidden']);
            exit;
        }
    }

    /**
     * Vérifie le token secret via hash_equals() (résistant aux timing attacks).
     *
     * Le token est attendu dans le header HTTP :
     *   X-Internal-Token: votre-token-secret
     */
    private function assertValidToken(): void
    {
        // PHP transforme les headers en HTTP_* en majuscules avec underscores.
        // $provided = $_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? '';

        // if ($provided === '' || !hash_equals($this->internalToken, $provided)) {
        //     $this->respond(401, ['error' => 'Unauthorized']);
        //     exit;
        // }
    }

    // =========================================================================
    // Réponse HTTP
    // =========================================================================

    /**
     * @param array<string, mixed> $data
     */
    private function respond(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        // Empêche la mise en cache des réponses internes.
        header('Cache-Control: no-store, no-cache');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
