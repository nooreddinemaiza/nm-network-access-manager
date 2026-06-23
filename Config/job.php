<?php

declare(strict_types=1);

use Core\File;
use Core\System\Environment;

/**
 * Configuration du sous-système Queue.
 *
 * À charger dans votre bootstrap / container IoC.
 *
 * Variables d'environnement requises :
 *   INTERNAL_TOKEN      → token secret partagé avec le crontab (min 32 chars)
 *   DNS_LOG_DIRECTORY   → répertoire des fichiers de logs DNS
 *   DNS_CHUNK_SIZE      → lignes par chunk (défaut : 1000)
 *   QUEUE_DRIVER        → database | sync (défaut : database)
 */
$env = new Environment();

return [

    // -------------------------------------------------------------------------
    // Driver de queue
    // -------------------------------------------------------------------------

    'default_driver' => $env->get('QUEUE_DRIVER', 'database'),

    'drivers' => [

        'database' => [
            'table'        => 'jobs',
            'failed_table' => 'failed_jobs',
            'locks_table' => 'job_locks',
        ],

    ],

    // -------------------------------------------------------------------------
    // Worker
    // -------------------------------------------------------------------------

    'worker' => [
        'default_queue'    => 'default',
        'sleep'            => 3,
        'memory'           => 256,
        'max-jobs'         => 0,     // 0 = illimité, le Worker s'arrête via stop-when-empty
        'max-time'         => 0,     // idem
        'backoff-on-error' => 5,
        'verbose'          => true,
        'stop-when-empty'  => true,  // ← ajouter cette ligne
    ],

    // -------------------------------------------------------------------------
    // Sécurité HTTP (Option B)
    // -------------------------------------------------------------------------
    /**
     * Token secret partagé entre le crontab et le QueueController.
     *
     * Générer un token fort :
     *   php -r "echo bin2hex(random_bytes(32));"
     *
     * Ne jamais mettre cette valeur en dur dans le code.
     * La stocker dans .env ou dans les variables d'environnement système.
     */
    'internal_token' => File::is_readable('file', 'cron_secret.php')
        ? File::require('file', 'cron_secret.php')['token'] : throw new \RuntimeException(
            'INTERNAL_TOKEN environment variable is not set.'
        ),

    // -------------------------------------------------------------------------
    // DNS Log Ingestion
    // -------------------------------------------------------------------------

    'dns' => [
        'log_file_path' => File::getPath('log', 'pfsense_dns_today.log'),
        'chunk_size'    => 1000,
    ],

    // -------------------------------------------------------------------------
    // Tables SQL
    // -------------------------------------------------------------------------

    // 'tables' => [
    //     'jobs'        => 'jobs',
    //     'failed_jobs' => 'failed_jobs',
    //     'job_locks'   => 'job_locks',
    // ],

];
