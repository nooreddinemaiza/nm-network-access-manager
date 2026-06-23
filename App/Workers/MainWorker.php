<?php


declare(strict_types=1);

namespace App\Workers;

use App\Controllers\JobController;
use Core\Database\Database;
use Core\Exception\ConnectionException;
use Core\Exception\ValidationException;
use Core\File;
use Core\Helper\Data;
use Core\Logger;
use Core\Queue\Drivers\DatabaseDriver;
use Core\Queue\QueueManager;
use Core\Queue\UniqueJobGuard;
use Core\Queue\Worker;
use Core\Queue\WorkerOptions;
use Core\Routing\Http\Request;
use Core\Routing\Http\Response;
use Core\Routing\RouteException;
use Core\System\Config;
use Throwable;

final class MainWorker
{
    private const TOKEN_FILE = 'cron_secret.php';
    private const VALID_QUEUES = ['default', 'dns-processing'];
    private const ALLOWED_IPS = [
        '127.0.0.1',
        '::1',
        // '100.105.181.84',
        '192.168.0.20'
    ];
    private string $logFile;
    private int $chunkSize;
    private string $date;
    public function __construct(
        private readonly Database $db = new Database()
    ) {
        $this->setConfig();
    }
    public function create(Request $request)
    {
        try {
            if (!$this->check($request)) {
                throw new ValidationException('Votre adresse IP est non autorisée!(' . $request->ip() . ')');
            }
            $data = Data::create($request->all())->only([
                'job',
                'token',
                'date',
            ]);
            if (!File::exists('file', self::TOKEN_FILE)) {
                throw new ValidationException(message: 'Le token de securite est invalide ou Fichier introuvable!');
            }
            $token = File::require('file', self::TOKEN_FILE);
            $token = $token['token'];

            $errors = $data->validate([
                'job'       => 'required|in:' . implode(',', self::VALID_QUEUES),
                'token'      => 'required|same:' . $token,
                'date'      => 'required|same:' . $this->date,
            ]);
            if ($errors) {
                throw new ValidationException(errors: $errors);
            }
            $jobController = $this->resolvejobcontroller();

            $status = $jobController->dispatchDnsJob();
            if ($status !== 202 && $status !== 409) {
                throw new ValidationException(sprintf(
                    'Erreur :%d',
                    $status
                ));
            }
            $this->worker($data['job'])->run();
            return Response::json([
                'success' => true,
                'message' => 'Worker a démarré!'
            ]);
        } catch (ValidationException $e) {
            $this->log(
                "Validation échouée: " . $e->getMessage() . ($e->getErrors() ? json_encode($e->getErrors()) : ''),
                'warning'
            );
            return RouteException::handleForbidden($request);
        } catch (ConnectionException $e) {

            $this->log(
                "Erreur de connexion lors du traitement cron DNS",
                'message'
            );
            return Response::json([
                'success' => false,
                'message' => 'Pas de traitement a faire!'
            ]);
        } catch (RouteException $e) {
            return Response::json([
                'success' => false,
                'message'   => $e->getMessage() . "code:" . $e->getCode()
            ], 500);
        } catch (\Throwable $e) {

            $this->log(
                $e->getMessage(),
                'critical'
            );

            return Response::json([
                'success' => false,
                'message'   => 'Erreur interne du serveur'
            ], 500);
        }
    }
    public function worker($queue): Worker
    {
        $workerOption = new WorkerOptions(queue: $queue);
        $manager = new QueueManager();
        $manager->registerDriver('database', function () {
            $driver = new DatabaseDriver($this->db->getpdo());
            return $driver;
        });

        return $manager->createWorker($workerOption);
    }

    public function testWorker($queue)
    {
        $workerOption = new WorkerOptions(queue: $queue);
        $manager = new QueueManager();
        $manager->registerDriver('database', function () {
            $driver = new DatabaseDriver($this->db->getpdo());
            return $driver;
        });

        return $manager->createWorker($workerOption)->run();
    }
    public function testDispatch()
    {
        $this->resolvejobcontroller()->dispatchDnsLogs();
    }

    public function resolvejobcontroller(): JobController
    {
        $guard = $this->resolveguard();
        $manager = $this->resolvemanager();
        $jobController = new JobController(
            $manager,
            $guard,
            $this->logFile,
            $this->chunkSize
        );
        return $jobController;
    }
    private function check(Request $request): bool
    {
        return in_array($request->ip(), self::ALLOWED_IPS);
    }
    private function resolvemanager(): QueueManager
    {
        $manager = new QueueManager();
        $manager->registerDriver('database', function () {
            $driver = new DatabaseDriver(
                $this->db->getpdo()
            );
            return $driver;
        });
        return $manager;
    }
    private function resolveguard(): UniqueJobGuard
    {
        return new UniqueJobGuard(
            pdo: $this->db->getpdo()
        );
    }
    private function setConfig()
    {
        $jobConfig = Config::get('job');
        $dns = $jobConfig['dns'];
        $this->logFile = $dns['log_file_path'];
        $this->chunkSize = (int)$dns['chunk_size'];
        $this->date        = date('Y-m-d');
    }
    /**
     * Logging simple
     * 
     * @param string $message Message à logger
     * @param string $level Niveau de log (info, warning, error)
     */
    private function log(string $message, string $level = 'info'): void
    {
        Logger::$level("Worker: {$message}");
    }
}
