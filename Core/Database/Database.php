<?php

namespace Core\Database;

use PDO;
use Exception;
use Core\Logger;
use PDOException;
use Core\System\Config;
use InvalidArgumentException;
use Core\Routing\RouteException;
use Core\Security\Encrypter;

class Database
{
    private $pdo;
    private string $driver;

    public function __construct(bool $connect = true)
    {
        if ($connect) {
            try {
                $configs = Config::all('database');
                $connections = $configs['connections'];
                $default = $configs['default'];
                $config = $connections[$default];
                $config['password'] = (new Encrypter())->decrypt($config['password']);
                $this->pdo = $this->connect($config);
                $this->driver = strtolower($config['driver']);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die($this->handleError("Connection failed: " . $e->getMessage()));
            }
        }
    }

    /**
     * Connexion PDO générique multi-driver
     */
    function connect(array $config): PDO
    {
        if (empty($config['driver'])) {
            throw new InvalidArgumentException('Driver PDO non défini.');
        }

        $driver = strtolower($config['driver']);
        $dsn = '';

        switch ($driver) {
            case 'mysql':
            case 'pgsql':
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=%s',
                    $driver,
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? null,
                    $config['database'] ?? null,
                    $config['charset'] ?? 'utf8'
                );
                break;

            case 'sqlite':
                if (empty($config['database'])) {
                    throw new InvalidArgumentException('Chemin SQLite manquant.');
                }
                $dsn = "sqlite:" . $config['database'];
                break;

            case 'sqlsrv':
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%s;Database=%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? 1433,
                    $config['database'] ?? null
                );
                break;

            default:
                throw new InvalidArgumentException("Driver PDO non supporté : {$driver}");
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $options
        );
    }

    /**
     * Test de connexion à la base de données
     */
    static function test(array $config = []): array
    {
        $defaultConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ];

        $testConfig = array_merge($defaultConfig, $config);

        $result = [
            'success' => false,
            'message' => '',
            'details' => [
                'driver' => $testConfig['driver'],
                'host' => $testConfig['host'],
                'database' => $testConfig['database'],
                'error' => null
            ]
        ];

        try {
            $startTime = microtime(true);
            $pdo = (new self(false))->connect($testConfig);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $stmt = $pdo->query('SELECT 1');
            $stmt->fetch();

            $result['success'] = true;
            $result['message'] = 'Connexion réussie à la base de données.';
            $result['details']['connection_time'] = "{$duration}ms";
            $result['details']['server_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (PDOException $e) {
            $result['success'] = false;
            $result['message'] = 'Échec de la connexion à la base de données.';
            Logger::debug($e->getMessage());
            Logger::debug($e->getCode());
            $result['details']['error_code'] = $e->getCode();
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = 'Erreur inattendue.';
            $result['details']['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Gestion d'erreur améliorée
     * @param string $message Message d'erreur
     * @param bool $throwException Si true, lance une exception au lieu de die()
     * @throws Exception
     */
    private function handleError(string $message, bool $throwException = false)
    {
        Logger::critical($message);

        // Rollback automatique si on est dans une transaction
        if ($this->pdo && $this->pdo->inTransaction()) {
            try {
                $this->pdo->rollBack();
                Logger::info("Transaction automatiquement annulée suite à une erreur.");
            } catch (PDOException $e) {
                Logger::error("Impossible d'annuler la transaction : " . $e->getMessage());
            }
        }

        if ($throwException) {
            throw new Exception($message);
        }

        return RouteException::handleInternalServerError();
    }

    /**
     * Crée un QueryBuilder pour des requêtes complexes
     * 
     * @param string $table Nom de la table
     * @return QueryBuilder
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * SELECT avec support complet (méthode legacy compatible)
     */
    public function select(
        string $table,
        string $columns = '*',
        string $conditions = '',
        array $params = [],
        string $groupBy = '',
        string $having = '',
        string $orderBy = '',
        string $limit = '',
        string $joins = '',
        bool $distinct = false,
        bool $debug = false
    ) {
        // Traitement des conditions IN
        if (!empty($conditions) && !empty($params)) {
            list($conditions, $params) = $this->processInConditions($conditions, $params);
        }

        $query = "SELECT " . ($distinct ? "DISTINCT " : "") . "$columns FROM $table";
        if (!empty($joins)) $query .= " $joins";
        if (!empty($conditions)) $query .= " WHERE $conditions";
        if (!empty($groupBy)) $query .= " GROUP BY $groupBy";
        if (!empty($having)) $query .= " HAVING $having";
        if (!empty($orderBy)) $query .= " ORDER BY $orderBy";
        if (!empty($limit)) $query .= " LIMIT $limit";

        $params = array_filter($params, fn($value) => $value !== null && $value !== '');

        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleError("Select : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Traite les conditions IN en convertissant les tableaux en placeholders
     * 
     * @param string $conditions Les conditions SQL
     * @param array $params Les paramètres
     * @return array [$conditions, $params] modifiés
     */
    private function processInConditions(string $conditions, array $params): array
    {
        $newParams = [];
        $newConditions = $conditions;

        foreach ($params as $key => $value) {
            // Si la valeur est un tableau, c'est pour une condition IN
            if (is_array($value)) {
                $placeholders = [];

                foreach ($value as $index => $item) {
                    $placeholder = $key . '_' . $index;
                    $placeholders[] = ':' . $placeholder;
                    $newParams[$placeholder] = $item;
                }

                // Remplace :key par :key_0, :key_1, :key_2, ... (sans parenthèses)
                $inPlaceholders = implode(', ', $placeholders);
                $newConditions = preg_replace(
                    '/:' . preg_quote($key, '/') . '\b/',
                    $inPlaceholders,
                    $newConditions,
                    1
                );
            } else {
                // Paramètre normal
                $newParams[$key] = $value;
            }
        }

        return [$newConditions, $newParams];
    }

    /**
     * SELECT avec une seule ligne retournée
     */
    public function selectOne(
        string $table,
        string $columns = '*',
        string $conditions = '',
        array $params = [],
        string $joins = '',
        string $groupBy = '',
        bool $debug = false
    ) {
        $results = $this->select($table, $columns, $conditions, $params, $groupBy, '', '', '1', $joins, false, $debug);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Compte le nombre de lignes
     */
    public function count(string $table, string $conditions = '', array $params = [], bool $debug = false): int
    {
        $result = $this->selectOne($table, 'COUNT(*) as total', $conditions, $params, '', '', $debug);
        return (int)($result['total'] ?? 0);
    }

    /**
     * INSERT
     */
    public function insert($table, $data, $debug = false)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        if ($debug) {
            $this->debugQuery($query, $data);
        }
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($data);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError("Insertion : " . $e->getMessage());
        }
    }

    /**
     * INSERT multiple rows
     */
    public function insertBatch(string $table, array $rows, bool $debug = false): bool
    {
        if (empty($rows)) return false;

        $columns = array_keys($rows[0]);
        $columnsStr = implode(", ", $columns);
        $placeholders = "(" . implode(", ", array_fill(0, count($columns), "?")) . ")";
        $allPlaceholders = implode(", ", array_fill(0, count($rows), $placeholders));

        $query = "INSERT INTO $table ($columnsStr) VALUES $allPlaceholders";

        $params = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $params[] = $row[$col] ?? null;
            }
        }

        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->handleError("Insertion batch : " . $e->getMessage());
            return false;
        }
    }

    /**
     * UPDATE
     */
    public function update($table, $data, $conditions, $params = [], $debug = false)
    {
        if (empty($data)) return false;

        $setClause = implode(", ", array_map(fn($key) => "$key = :$key", array_keys($data)));
        $query = "UPDATE $table SET $setClause WHERE $conditions";

        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_merge(
                array_combine(array_map(fn($key) => ":$key", array_keys($data)), array_values($data)),
                $params
            ));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError("Update : " . $e->getMessage());
        }
    }

    /**
     * DELETE
     */
    public function delete($table, $conditions, $params = [], $debug = false)
    {
        if (empty($table) || empty(trim($conditions))) return false;

        $query = "DELETE FROM $table WHERE $conditions";
        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_values($params));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la suppression : " . $e->getMessage());
        }
    }

    /**
     * DELETE IN
     */
    public function deleteIn(string $table, string $column, array $values, bool $debug = false)
    {
        if (empty($table) || empty($column) || empty($values)) return false;

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $query = "DELETE FROM $table WHERE $column IN ($placeholders)";

        if ($debug) {
            $this->debugQuery($query, $values);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($values);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la suppression : " . $e->getMessage());
            return false;
        }
    }

    /**
     * TRUNCATE table
     */
    public function truncate(string $table, bool $debug = false): bool
    {
        $query = "TRUNCATE TABLE $table";
        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors du TRUNCATE : " . $e->getMessage());
            return false;
        }
    }

    // ==================== GESTION DES VUES ====================

    /**
     * Vérifie si une vue existe
     */
    public function viewExists(string $viewName, bool $debug = false): bool
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    $result = $this->select(
                        "information_schema.views",
                        "*",
                        "table_schema = DATABASE() AND table_name = ?",
                        [$viewName],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );
                    break;

                case 'pgsql':
                    $result = $this->select(
                        "information_schema.views",
                        "*",
                        "table_schema = 'public' AND table_name = ?",
                        [$viewName],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );
                    break;

                case 'sqlite':
                    $result = $this->select(
                        "sqlite_master",
                        "*",
                        "type = 'view' AND name = ?",
                        [$viewName],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );
                    break;

                case 'sqlsrv':
                    $result = $this->select(
                        "information_schema.views",
                        "*",
                        "table_name = ?",
                        [$viewName],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );
                    break;

                default:
                    return false;
            }

            return !empty($result);
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la vérification de la vue : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée une vue
     */
    public function createView(string $viewName, string $selectQuery, bool $orReplace = false, bool $debug = false): bool
    {
        $createOrReplace = $orReplace ? "CREATE OR REPLACE" : "CREATE";
        $query = "$createOrReplace VIEW $viewName AS $selectQuery";

        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la création de la vue : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une vue
     */
    public function dropView(string $viewName, bool $ifExists = true, bool $debug = false): bool
    {
        $ifExistsClause = $ifExists ? "IF EXISTS" : "";
        $query = "DROP VIEW $ifExistsClause $viewName";

        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la suppression de la vue : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Liste toutes les vues
     */
    public function listViews(bool $debug = false): array
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    return $this->select(
                        "information_schema.views",
                        "table_name as view_name",
                        "table_schema = DATABASE()",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'pgsql':
                    return $this->select(
                        "information_schema.views",
                        "table_name as view_name",
                        "table_schema = 'public'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'sqlite':
                    return $this->select(
                        "sqlite_master",
                        "name as view_name",
                        "type = 'view'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'sqlsrv':
                    return $this->select(
                        "information_schema.views",
                        "table_name as view_name",
                        "",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                default:
                    return [];
            }
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la récupération des vues : " . $e->getMessage());
            return [];
        }
    }

    // ==================== GESTION DES TABLES ====================

    /**
     * Vérifie si une table existe
     */
    public function tableExists($tableName, $debug = false): bool
    {
        try {
            $result = $this->select(
                "information_schema.tables",
                "*",
                "table_schema = DATABASE() AND table_name = ?",
                [$tableName],
                '',
                '',
                '',
                '',
                '',
                false,
                $debug
            );
            return !empty($result);
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la vérification de la table : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Liste toutes les tables
     */
    public function listTables(bool $debug = false): array
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    return $this->select(
                        "information_schema.tables",
                        "table_name",
                        "table_schema = DATABASE() AND table_type = 'BASE TABLE'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'pgsql':
                    return $this->select(
                        "information_schema.tables",
                        "table_name",
                        "table_schema = 'public' AND table_type = 'BASE TABLE'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'sqlite':
                    return $this->select(
                        "sqlite_master",
                        "name as table_name",
                        "type = 'table' AND name NOT LIKE 'sqlite_%'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'sqlsrv':
                    return $this->select(
                        "information_schema.tables",
                        "table_name",
                        "table_type = 'BASE TABLE'",
                        [],
                        '',
                        '',
                        '',
                        '',
                        '',
                        false,
                        $debug
                    );

                default:
                    return [];
            }
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la récupération des tables : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les colonnes d'une table
     */
    public function getColumns(string $tableName, bool $debug = false): array
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    return $this->select(
                        "information_schema.columns",
                        "column_name, data_type, is_nullable, column_default, column_key",
                        "table_schema = DATABASE() AND table_name = ?",
                        [$tableName],
                        '',
                        '',
                        'ordinal_position',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'pgsql':
                    return $this->select(
                        "information_schema.columns",
                        "column_name, data_type, is_nullable, column_default",
                        "table_schema = 'public' AND table_name = ?",
                        [$tableName],
                        '',
                        '',
                        'ordinal_position',
                        '',
                        '',
                        false,
                        $debug
                    );

                case 'sqlite':
                    $stmt = $this->pdo->prepare("PRAGMA table_info($tableName)");
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);

                case 'sqlsrv':
                    return $this->select(
                        "information_schema.columns",
                        "column_name, data_type, is_nullable, column_default",
                        "table_name = ?",
                        [$tableName],
                        '',
                        '',
                        'ordinal_position',
                        '',
                        '',
                        false,
                        $debug
                    );

                default:
                    return [];
            }
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la récupération des colonnes : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée une table
     */
    public function createTable($query, $debug = false)
    {
        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            $this->execQuery($query);
            return true;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la création de la table : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une table
     */
    public function dropTable(string $tableName, bool $ifExists = true, bool $debug = false): bool
    {
        $ifExistsClause = $ifExists ? "IF EXISTS" : "";
        $query = "DROP TABLE $ifExistsClause $tableName";

        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la suppression de la table : " . $e->getMessage());
            return false;
        }
    }

    // ==================== GESTION DES INDEX ====================

    /**
     * Crée un index
     */
    public function createIndex(string $table, string $indexName, array $columns, bool $unique = false, bool $debug = false): bool
    {
        $uniqueClause = $unique ? "UNIQUE" : "";
        $columnsStr = implode(", ", $columns);
        $query = "CREATE $uniqueClause INDEX $indexName ON $table ($columnsStr)";

        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la création de l'index : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un index existe sur une table
     */
    public function indexExists(string $table, string $indexName, bool $debug = false): bool
    {
        try {
            if ($this->driver === 'mysql') {
                $query = "SHOW INDEX FROM $table WHERE Key_name = :indexName";

                if ($debug) {
                    $this->debugQuery($query, ['indexName' => $indexName]);
                }

                $stmt = $this->pdo->prepare($query);
                $stmt->execute(['indexName' => $indexName]);

                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            } elseif ($this->driver === 'pgsql') {
                $query = "SELECT indexname 
                      FROM pg_indexes 
                      WHERE tablename = :table 
                      AND indexname = :indexName";

                if ($debug) {
                    $this->debugQuery($query, ['table' => $table, 'indexName' => $indexName]);
                }

                $stmt = $this->pdo->prepare($query);
                $stmt->execute([
                    'table' => $table,
                    'indexName' => $indexName
                ]);

                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            } elseif ($this->driver === 'sqlite') {
                $query = "SELECT name 
                      FROM sqlite_master 
                      WHERE type = 'index' 
                      AND tbl_name = :table 
                      AND name = :indexName";

                if ($debug) {
                    $this->debugQuery($query, ['table' => $table, 'indexName' => $indexName]);
                }

                $stmt = $this->pdo->prepare($query);
                $stmt->execute([
                    'table' => $table,
                    'indexName' => $indexName
                ]);

                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            }

            return false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la vérification de l'index : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un index
     */
    public function dropIndex(string $indexName, string $tableName = '', bool $debug = false): bool
    {
        // MySQL nécessite le nom de la table
        $query = $this->driver === 'mysql'
            ? "DROP INDEX $indexName ON $tableName"
            : "DROP INDEX $indexName";

        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query) !== false;
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de la suppression de l'index : " . $e->getMessage());
            return false;
        }
    }

    // ==================== UTILITAIRES ====================

    /**
     * Exécute une requête directe
     */
    public function execQuery($query, $debug = false)
    {
        if ($debug) {
            $this->debugQuery($query);
        }

        try {
            return $this->pdo->exec($query);
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de l'exécution de la requête : " . $e->getMessage());
        }
    }

    /**
     * Récupère une seule ligne
     */
    public function fetch($query, $params = [], $debug = false)
    {
        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de l'exécution de la requête FETCH: " . $e->getMessage());
        }
    }

    /**
     * Exécute une requête RAW et retourne tous les résultats
     */
    public function query(string $query, array $params = [], bool $debug = false)
    {
        if ($debug) {
            $this->debugQuery($query, $params);
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de l'exécution de la requête : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retourne le dernier ID inséré
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Retourne l'objet PDO
     */
    public function getpdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Retourne le driver actuel
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Ferme la connexion
     */
    public function closeConnection(): void
    {
        $this->pdo = null;
    }

    // ==================== TRANSACTIONS ====================

    /**
     * Démarre une transaction
     */
    public function beginTransaction(): bool
    {
        try {
            if ($this->pdo->inTransaction()) {
                Logger::warning("Une transaction est déjà en cours.");
                return false;
            }
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            Logger::error("Erreur lors du démarrage de la transaction : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide une transaction
     */
    public function commitTransaction(): bool
    {
        try {
            if (!$this->pdo->inTransaction()) {
                Logger::warning("Aucune transaction active à valider.");
                return false;
            }
            return $this->pdo->commit();
        } catch (PDOException $e) {
            Logger::error("Erreur lors de la validation de la transaction : " . $e->getMessage());
            // Tentative de rollback
            if ($this->pdo->inTransaction()) {
                $this->rollbackTransaction();
            }
            return false;
        }
    }

    public function rollbackTransaction(): bool
    {
        try {
            return $this->pdo->rollBack();
        } catch (PDOException $e) {
            $this->handleError("Erreur lors de l'annulation de la transaction : " . $e->getMessage());
            return false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
    /**
     * Exécute une requête RAW de manière sécurisée avec support des transactions
     * 
     * @param string $sql Requête SQL brute
     * @param array $params Paramètres à lier (supporte :named et ? placeholders)
     * @param int $fetchMode Mode de fetch (PDO::FETCH_ASSOC, PDO::FETCH_OBJ, etc.)
     * @param bool $debug Mode debug
     * @return array|int|false Résultats ou nombre de lignes affectées
     * @throws PDOException
     */
    public function raw(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC, bool $debug = false)
    {
        // Validation basique pour éviter les requêtes dangereuses
        $sqlUpper = strtoupper(trim($sql));

        // Détection des requêtes multiples (potentiellement dangereuses)
        if (preg_match('/;(?![^\']*\'[^\']*\')(?![^"]*"[^"]*")/', $sql)) {
            throw new InvalidArgumentException("Requêtes multiples non autorisées dans raw()");
        }

        // Détection des commandes dangereuses pour les requêtes SELECT
        if (str_starts_with($sqlUpper, 'SELECT')) {
            $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
            foreach ($dangerousKeywords as $keyword) {
                if (strpos($sqlUpper, $keyword) !== false && strpos($sqlUpper, "--") === false) {
                    Logger::warning("Requête SELECT contenant '$keyword' détectée: " . substr($sql, 0, 200));
                }
            }
        }

        if ($debug) {
            $this->debugQuery($sql, $params);
        }

        try {
            // Vérifier si on est dans une transaction
            $inTransaction = $this->pdo->inTransaction();

            // Préparer la requête
            $stmt = $this->pdo->prepare($sql);

            if (!$stmt) {
                throw new PDOException("Erreur de préparation de la requête");
            }

            // Lier les paramètres de manière sécurisée
            foreach ($params as $key => $value) {
                // Déterminer le type PDO
                $type = PDO::PARAM_STR;
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                } elseif (is_null($value)) {
                    $type = PDO::PARAM_NULL;
                } elseif (is_float($value)) {
                    $type = PDO::PARAM_STR; // Les floats sont traités comme strings
                }

                // Gérer les placeholders nommés et anonymes
                if (is_string($key)) {
                    $stmt->bindValue(':' . ltrim($key, ':'), $value, $type);
                } else {
                    $stmt->bindValue($key + 1, $value, $type);
                }
            }

            // Exécuter la requête
            $stmt->execute();

            // Déterminer le type de résultat à retourner
            if (str_starts_with($sqlUpper, 'SELECT') || str_starts_with($sqlUpper, 'SHOW') || str_starts_with($sqlUpper, 'PRAGMA')) {
                return $stmt->fetchAll($fetchMode);
            } elseif (str_starts_with($sqlUpper, 'INSERT')) {
                return (int) $this->pdo->lastInsertId();
            } elseif (str_starts_with($sqlUpper, 'UPDATE') || str_starts_with($sqlUpper, 'DELETE')) {
                return $stmt->rowCount();
            } else {
                // Pour CREATE, ALTER, DROP, etc.
                return $stmt->rowCount();
            }
        } catch (PDOException $e) {
            // Log détaillé de l'erreur
            $errorMsg = "Erreur lors de l'exécution de la requête RAW : " . $e->getMessage();
            Logger::error($errorMsg);
            Logger::debug("SQL: " . $sql);
            Logger::debug("Params: " . json_encode($params));

            $this->handleError($errorMsg);
            return false;
        }
    }

    /**
     * Version sécurisée pour exécuter des requêtes RAW avec gestion automatique des transactions
     * 
     * @param string $sql Requête SQL brute
     * @param array $params Paramètres à lier
     * @param callable|null $callback Callback optionnel pour traitement intermédiaire
     * @param bool $debug Mode debug
     * @return mixed Résultat de la requête ou du callback
     * @throws Exception
     */
    public function rawSecure(string $sql, array $params = [], ?callable $callback = null, bool $debug = false)
    {
        $autoCommit = !$this->pdo->inTransaction();

        try {
            if ($autoCommit) {
                $this->beginTransaction();
            }

            $result = $this->raw($sql, $params, PDO::FETCH_ASSOC, $debug);

            if ($callback !== null) {
                $result = $callback($result);
            }

            if ($autoCommit) {
                $this->commitTransaction();
            }

            return $result;
        } catch (Exception $e) {
            if ($autoCommit && $this->pdo->inTransaction()) {
                $this->rollbackTransaction();
            }

            Logger::error("RawSecure échoué: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécute une requête RAW avec protection renforcée pour les opérations sensibles
     * 
     * @param string $sql Requête SQL brute
     * @param array $params Paramètres à lier
     * @param array $allowedOperations Opérations autorisées (SELECT, INSERT, UPDATE, DELETE)
     * @param bool $debug Mode debug
     * @return array|int|false
     * @throws InvalidArgumentException
     */
    public function rawRestricted(string $sql, array $params = [], array $allowedOperations = ['SELECT'], bool $debug = false)
    {
        $sqlUpper = strtoupper(trim($sql));
        $operation = explode(' ', $sqlUpper)[0];

        if (!in_array($operation, $allowedOperations)) {
            throw new InvalidArgumentException(
                "Opération '$operation' non autorisée. Opérations permises: " . implode(', ', $allowedOperations)
            );
        }

        // Protection supplémentaire pour DELETE et UPDATE sans WHERE
        if (in_array($operation, ['DELETE', 'UPDATE'])) {
            if (stripos($sql, 'WHERE') === false && stripos($sql, 'where') === false) {
                if ($debug) {
                    Logger::warning("Tentative de $operation sans clause WHERE: " . substr($sql, 0, 200));
                }
                throw new InvalidArgumentException("Opération $operation sans clause WHERE non autorisée");
            }
        }

        return $this->raw($sql, $params, PDO::FETCH_ASSOC, $debug);
    }

    /**
     * Exécute une requête préparée avec liaison automatique des paramètres
     * (Alias pour raw avec vérification supplémentaire)
     * 
     * @param string $sql Requête SQL avec placeholders (:nom ou ?)
     * @param array $params Paramètres à lier
     * @param bool $debug Mode debug
     * @return array|int|false
     */
    public function executePrepared(string $sql, array $params = [], bool $debug = false)
    {
        // Vérification que tous les placeholders nommés ont des valeurs
        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches)) {
            foreach ($matches[1] as $placeholder) {
                if (!isset($params[$placeholder]) && !isset($params[':' . $placeholder])) {
                    Logger::warning("Placeholder manquant: $placeholder dans la requête: " . substr($sql, 0, 200));
                }
            }
        }

        // Vérification du nombre de placeholders anonymes
        $anonymousCount = substr_count($sql, '?');
        $paramCount = count($params);

        if ($anonymousCount > 0 && $paramCount < $anonymousCount) {
            Logger::warning("Nombre de paramètres insuffisant: attendu $anonymousCount, reçu $paramCount");
        }

        return $this->raw($sql, $params, PDO::FETCH_ASSOC, $debug);
    }
    // ==================== DEBUG ====================

    private function debugQuery(string $query, array $params = [], ?bool $json = true)
    {
        if ($json) {
            exit($this->debugQueryJson($query, $params));
        }
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (is_string($value)) {
                    $value = "'" . addslashes($value) . "'";
                }
                $query = preg_replace('/\?/', $value, $query, 1);
            }
        }

        echo '<div class="container mt-4">';
        echo '<div class="card border-primary mb-3 shadow">';
        echo '<div class="card-header bg-primary text-white d-flex align-items-center">';
        echo '<i class="fas fa-tools me-2"></i>';
        echo '<strong>Debug SQL Query</strong>';
        echo '</div>';
        echo '<div class="card-body">';

        echo '<div class="mb-4 position-relative">';
        echo '<h5 class="text-primary mb-3"><i class="fas fa-search me-2"></i>Requête SQL</h5>';
        echo '<pre id="debugQueryText" class="bg-light p-3 border rounded text-dark" style="font-family: monospace; white-space: pre-wrap;">';
        echo htmlspecialchars($query);
        echo '</pre>';

        echo '<button class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2" onclick="copyQueryText(this)" data-bs-toggle="tooltip" data-bs-placement="top" title="Copier">';
        echo '<i class="fas fa-copy"></i>';
        echo '</button>';
        echo '</div>';

        if (!empty($params)) {
            echo '<div>';
            echo '<h5 class="text-primary mb-3"><i class="fas fa-map-pin me-2"></i>Paramètres</h5>';
            echo '<ul class="list-group">';
            foreach ($params as $key => $value) {
                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                echo '<span class="badge bg-primary rounded-pill me-2">' . htmlspecialchars($key) . '</span>';
                echo '<span class="text-muted">➜</span>';
                echo '<span class="text-success">' . htmlspecialchars($value) . '</span>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<p class="text-muted"><i class="fas fa-info-circle me-2"></i>Aucun paramètre fourni.</p>';
        }

        echo '</div></div></div>';

        echo <<<HTML
    <script>
        function copyQueryText(button) {
            const queryText = document.getElementById('debugQueryText').innerText;
            navigator.clipboard.writeText(queryText).then(() => {
                button.setAttribute('data-bs-original-title', 'Copié !');
                let tooltip = bootstrap.Tooltip.getInstance(button);
                if (!tooltip) tooltip = new bootstrap.Tooltip(button);
                tooltip.show();
                setTimeout(() => {
                    button.setAttribute('data-bs-original-title', 'Copier');
                    tooltip.hide();
                }, 1500);
            }).catch(err => console.error('Erreur lors de la copie:', err));
        }
        document.addEventListener("DOMContentLoaded", function() {
            let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
        });
    </script>
    HTML;
    }

    private function debugQueryJson(string $query, array $params = []): void
    {
        // Remplacement des placeholders par les valeurs
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (is_string($value)) {
                    $value = "'" . addslashes($value) . "'";
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } elseif (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                $query = preg_replace('/\?/', $value, $query, 1);
            }
        }

        // Préparer le tableau JSON sans json_encode pour la requête
        $output = [
            'query' => $query,
            'parameters' => $params,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Construction manuelle du JSON pour la requête avec vrais sauts de ligne
        $json = "{\n";
        $json .= "  \"query\": \"" . str_replace('"', '\"', $output['query']) . "\",\n";
        $json .= "  \"parameters\": " . json_encode($output['parameters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ",\n";
        $json .= "  \"timestamp\": \"" . $output['timestamp'] . "\"\n";
        $json .= "}";

        header('Content-Type: application/json; charset=utf-8');
        echo $json;
    }
}
