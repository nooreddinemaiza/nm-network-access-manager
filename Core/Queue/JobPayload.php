<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Queue\Contracts\JobInterface;
use Core\Queue\Exceptions\QueueException;

/**
 * Représente un Job sérialisé prêt à être stocké dans un driver de queue.
 *
 * Le champ `status` est explicite et persisté en base :
 *   pending    → Job en attente d'être traité
 *   processing → Job réservé par un Worker (en cours d'exécution)
 *   done       → Job terminé avec succès (supprimé de la table après acknowledge)
 *   failed     → Job définitivement échoué (déplacé dans failed_jobs)
 */
final class JobPayload
{
    // -------------------------------------------------------------------------
    // Constantes de statut
    // -------------------------------------------------------------------------

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE       = 'done';
    public const STATUS_FAILED     = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_DONE,
        self::STATUS_FAILED,
    ];

    /** Classes autorisées à être désérialisées. Null = toutes autorisées. */
    private static ?array $allowedClasses = null;

    private function __construct(
        public readonly string  $id,
        public readonly string  $jobClass,
        public readonly string  $queue,
        public readonly string  $encodedData,
        public readonly int     $attempts,
        public readonly int     $maxAttempts,
        public readonly int     $timeout,
        public readonly int     $availableAt,
        public readonly int     $createdAt,
        public readonly string  $status       = self::STATUS_PENDING,
        public readonly ?string $reservedAt   = null,
        public readonly ?string $failedReason = null,
    ) {
        if (!in_array($this->status, self::VALID_STATUSES, strict: true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid job status "%s". Valid values: %s.',
                $this->status,
                implode(', ', self::VALID_STATUSES),
            ));
        }
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    /**
     * Crée un payload depuis un objet Job.
     *
     * @throws QueueException
     */
    public static function fromJob(JobInterface $job, int $delaySeconds = 0): self
    {
        $class = get_class($job);

        self::assertAllowedClass($class);

        $encoded = self::encode($job);
        $now     = time();

        return new self(
            id:          self::generateId(),
            jobClass:    $class,
            queue:       $job->queue(),
            encodedData: $encoded,
            attempts:    0,
            maxAttempts: $job->maxAttempts(),
            timeout:     $job->timeout(),
            availableAt: $now + $delaySeconds,
            createdAt:   $now,
            status:      self::STATUS_PENDING,
        );
    }

    /**
     * Recrée un payload depuis un tableau de données brutes (lecture driver).
     *
     * @param array<string, mixed> $raw
     *
     * @throws QueueException
     */
    public static function fromRaw(array $raw): self
    {
        $required = [
            'id', 'job_class', 'queue', 'encoded_data',
            'attempts', 'max_attempts', 'timeout',
            'available_at', 'created_at', 'status',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $raw)) {
                throw new QueueException(
                    sprintf('Malformed job payload: missing field "%s".', $field)
                );
            }
        }

        self::assertAllowedClass($raw['job_class']);

        return new self(
            id:           (string)  $raw['id'],
            jobClass:     (string)  $raw['job_class'],
            queue:        (string)  $raw['queue'],
            encodedData:  (string)  $raw['encoded_data'],
            attempts:     (int)     $raw['attempts'],
            maxAttempts:  (int)     $raw['max_attempts'],
            timeout:      (int)     $raw['timeout'],
            availableAt:  (int)     $raw['available_at'],
            createdAt:    (int)     $raw['created_at'],
            status:       (string)  $raw['status'],
            reservedAt:   isset($raw['reserved_at'])   ? (string) $raw['reserved_at']   : null,
            failedReason: isset($raw['failed_reason']) ? (string) $raw['failed_reason'] : null,
        );
    }

    // -------------------------------------------------------------------------
    // Désérialisation
    // -------------------------------------------------------------------------

    /**
     * Reconstruit l'objet Job depuis le payload encodé.
     *
     * @throws QueueException
     */
    public function resolveJob(): JobInterface
    {
        self::assertAllowedClass($this->jobClass);
        
        if (!class_exists($this->jobClass)) {
            throw new QueueException(
                sprintf('Job class "%s" does not exist.', $this->jobClass)
            );
        }

        if (!is_subclass_of($this->jobClass, JobInterface::class)) {
            throw new QueueException(
                sprintf('Class "%s" does not implement JobInterface.', $this->jobClass)
            );
        }

        return self::decode($this->encodedData, $this->jobClass);
    }

    // -------------------------------------------------------------------------
    // Mutations (immutabilité — retourne de nouvelles instances)
    // -------------------------------------------------------------------------

    public function withStatus(string $status): self
    {
        return new self(
            id:           $this->id,
            jobClass:     $this->jobClass,
            queue:        $this->queue,
            encodedData:  $this->encodedData,
            attempts:     $this->attempts,
            maxAttempts:  $this->maxAttempts,
            timeout:      $this->timeout,
            availableAt:  $this->availableAt,
            createdAt:    $this->createdAt,
            status:       $status,
            reservedAt:   $this->reservedAt,
            failedReason: $this->failedReason,
        );
    }

    public function incrementAttempts(): self
    {
        return new self(
            id:           $this->id,
            jobClass:     $this->jobClass,
            queue:        $this->queue,
            encodedData:  $this->encodedData,
            attempts:     $this->attempts + 1,
            maxAttempts:  $this->maxAttempts,
            timeout:      $this->timeout,
            availableAt:  $this->availableAt,
            createdAt:    $this->createdAt,
            status:       $this->status,
            reservedAt:   $this->reservedAt,
            failedReason: $this->failedReason,
        );
    }

    public function withFailedReason(string $reason): self
    {
        return new self(
            id:           $this->id,
            jobClass:     $this->jobClass,
            queue:        $this->queue,
            encodedData:  $this->encodedData,
            attempts:     $this->attempts,
            maxAttempts:  $this->maxAttempts,
            timeout:      $this->timeout,
            availableAt:  $this->availableAt,
            createdAt:    $this->createdAt,
            status:       self::STATUS_FAILED,
            reservedAt:   $this->reservedAt,
            failedReason: $reason,
        );
    }

    // -------------------------------------------------------------------------
    // Sérialisation vers le driver
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'job_class'     => $this->jobClass,
            'queue'         => $this->queue,
            'encoded_data'  => $this->encodedData,
            'attempts'      => $this->attempts,
            'max_attempts'  => $this->maxAttempts,
            'timeout'       => $this->timeout,
            'available_at'  => $this->availableAt,
            'created_at'    => $this->createdAt,
            'status'        => $this->status,
            'reserved_at'   => $this->reservedAt,
            'failed_reason' => $this->failedReason,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }

    public function isAvailable(): bool
    {
        return $this->availableAt <= time();
    }

    // -------------------------------------------------------------------------
    // Allowlist de classes (sécurité)
    // -------------------------------------------------------------------------

    /**
     * @param class-string[]|null $classes
     */
    public static function setAllowedClasses(?array $classes): void
    {
        self::$allowedClasses = $classes;
    }

    // -------------------------------------------------------------------------
    // Encode / decode
    // -------------------------------------------------------------------------

    /**
     * Sérialise un Job en JSON+base64.
     *
     * Extraction des propriétés dans le scope de l'objet.
     * Compatible PHP 8.2+ — ne dépend pas de setAccessible().
     *
     * @throws QueueException
     */
    private static function encode(JobInterface $job): string
    {
        $data = self::extractProperties($job);
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return base64_encode($json);
    }

    /**
     * Reconstruit un Job depuis son payload encodé.
     *
     * Stratégie :
     *  1. Décode le JSON → tableau associatif ['prop' => valeur]
     *  2. Lit les paramètres du constructeur via ReflectionConstructor
     *  3. Mappe chaque paramètre depuis le tableau (par nom)
     *  4. Instancie via newInstanceArgs() — aucun setAccessible() nécessaire
     *
     * Prérequis : les paramètres du constructeur portent les mêmes noms
     * que les propriétés du Job (convention readonly PHP 8.2).
     *
     * @param class-string $class
     * @throws QueueException
     */
    private static function decode(string $encodedData, string $class): JobInterface
    {
        $json = base64_decode($encodedData, strict: true);

        if ($json === false) {
            throw new QueueException('Failed to base64-decode job payload.');
        }

        $data        = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        $reflection  = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        // Pas de constructeur → instanciation sans arguments.
        if ($constructor === null) {
            /** @var JobInterface */
            return $reflection->newInstance();
        }

        // Mappe les arguments dans l'ordre du constructeur.
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new QueueException(sprintf(
                    'Cannot reconstruct job "%s": missing constructor parameter "$%s" in payload.',
                    $class,
                    $name,
                ));
            }
        }

        /** @var JobInterface */
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Extrait toutes les propriétés d'instance 
     *
     * Exécute get_object_vars() dans le scope de l'objet, ce qui retourne
     * public + protected + private sans setAccessible().
     * Compatible PHP 8.1+ sans dépréciation.
     *
     * @return array<string, mixed>
     */
    private static function extractProperties(object $obj): array
    {
        /** @var \Closure $extractor */
        $extractor = \Closure::bind(
            static fn(object $o): array => get_object_vars($o),
            null,
            $obj,
        );

        return $extractor($obj);
    }

    private static function generateId(): string
    {
        return sprintf('%s-%s', date('Ymd-His'), bin2hex(random_bytes(8)));
    }

    /**
     * @throws QueueException
     */
    private static function assertAllowedClass(string $class): void
    {
        if (self::$allowedClasses === null) {
            return;
        }

        if (!in_array($class, self::$allowedClasses, strict: true)) {
            throw new QueueException(
                sprintf('Job class "%s" is not in the allowed classes list.', $class)
            );
        }
    }
}