<?php

namespace App\Migrations;

/**
 * Script de migration des collations MySQL
 * Uniformise toutes les tables vers utf8mb4_unicode_ci
 * 
 * Usage: php migrate_collations.php
 */


use Exception;
use Core\Database\Database;

class CollationMigrator
{
    private $db;
    private $targetCharset = 'utf8mb4';
    private $targetCollation = 'utf8mb4_unicode_ci';
    private $dryRun = false; // Mettre à true pour tester sans exécuter

    public function __construct(bool $dryRun = false)
    {
        $this->db = new Database();
        $this->dryRun = $dryRun;
    }

    /**
     * Lance la migration complète
     */
    public function migrate(): void
    {
        echo "========================================\n";
        echo "Migration des collations MySQL\n";
        echo "========================================\n";
        echo "Mode: " . ($this->dryRun ? "DRY RUN (simulation)" : "PRODUCTION") . "\n";
        echo "Collation cible: {$this->targetCollation}\n\n";

        // 1. Modifier la collation par défaut de la base de données
        $this->migrateDatabaseDefault();

        // 2. Récupérer toutes les tables
        $tables = $this->getTables();
        echo "Nombre de tables trouvées: " . count($tables) . "\n\n";

        // 3. Migrer chaque table
        foreach ($tables as $table) {
            $this->migrateTable($table);
        }

        echo "\n========================================\n";
        echo "Migration terminée !\n";
        echo "========================================\n";
    }

    /**
     * Modifie la collation par défaut de la base de données
     */
    private function migrateDatabaseDefault(): void
    {
        $dbName = $this->getCurrentDatabase();
        $query = "ALTER DATABASE `{$dbName}` CHARACTER SET {$this->targetCharset} COLLATE {$this->targetCollation}";

        echo "► Migration de la base de données par défaut...\n";
        echo "  Query: {$query}\n";

        if (!$this->dryRun) {
            try {
                $this->db->execQuery($query);
                echo "  ✓ Succès\n\n";
            } catch (Exception $e) {
                echo "  ✗ Erreur: " . $e->getMessage() . "\n\n";
            }
        } else {
            echo "  [SIMULATION]\n\n";
        }
    }

    /**
     * Migre une table et toutes ses colonnes
     */
    private function migrateTable(string $table): void
    {
        echo "► Table: {$table}\n";

        // Récupérer les colonnes avec collation
        $columns = $this->getColumnsWithCollation($table);

        if (empty($columns)) {
            echo "  Aucune colonne à migrer\n\n";
            return;
        }

        // Convertir la table entière
        $query = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET {$this->targetCharset} COLLATE {$this->targetCollation}";
        echo "  Query: {$query}\n";

        if (!$this->dryRun) {
            try {
                $this->db->execQuery($query);
                echo "  ✓ Table migrée avec succès\n";
                echo "  Colonnes affectées: " . count($columns) . "\n\n";
            } catch (Exception $e) {
                echo "  ✗ Erreur: " . $e->getMessage() . "\n";
                echo "  Tentative de migration colonne par colonne...\n\n";
                $this->migrateColumnsIndividually($table, $columns);
            }
        } else {
            echo "  [SIMULATION] " . count($columns) . " colonnes seraient migrées\n\n";
        }
    }

    /**
     * Migre les colonnes individuellement si la migration de table échoue
     */
    private function migrateColumnsIndividually(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            $columnName = $column['COLUMN_NAME'];
            $dataType = $column['COLUMN_TYPE'];
            $isNullable = $column['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $this->getDefaultClause($column);

            $query = "ALTER TABLE `{$table}` MODIFY `{$columnName}` {$dataType} CHARACTER SET {$this->targetCharset} COLLATE {$this->targetCollation} {$isNullable} {$default}";

            echo "    ► Colonne: {$columnName}\n";
            echo "      Query: {$query}\n";

            if (!$this->dryRun) {
                try {
                    $this->db->execQuery($query);
                    echo "      ✓ Succès\n";
                } catch (Exception $e) {
                    echo "      ✗ Erreur: " . $e->getMessage() . "\n";
                }
            } else {
                echo "      [SIMULATION]\n";
            }
        }
        echo "\n";
    }

    /**
     * Récupère le nom de la base de données actuelle
     */
    private function getCurrentDatabase(): string
    {
        $result = $this->db->query("SELECT DATABASE() as db");
        return $result[0]['db'] ?? '';
    }

    /**
     * Récupère toutes les tables de la base
     */
    private function getTables(): array
    {
        $result = $this->db->query("SHOW TABLES");
        $tables = [];
        foreach ($result as $row) {
            $tables[] = array_values($row)[0];
        }
        return $tables;
    }

    /**
     * Récupère les colonnes avec collation pour une table
     */
    private function getColumnsWithCollation(string $table): array
    {
        $query = "
            SELECT 
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLLATION_NAME,
                EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLLATION_NAME IS NOT NULL
            ORDER BY ORDINAL_POSITION
        ";

        return $this->db->query($query, [$table]);
    }

    /**
     * Génère la clause DEFAULT pour une colonne
     */
    private function getDefaultClause(array $column): string
    {
        $default = $column['COLUMN_DEFAULT'];
        $extra = $column['EXTRA'] ?? '';

        if (stripos($extra, 'auto_increment') !== false) {
            return 'AUTO_INCREMENT';
        }

        if ($default === null) {
            return $column['IS_NULLABLE'] === 'YES' ? 'DEFAULT NULL' : '';
        }

        if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
            return 'DEFAULT CURRENT_TIMESTAMP';
        }

        return "DEFAULT '{$default}'";
    }

    /**
     * Affiche un rapport des collations actuelles
     */
    public function report(): void
    {
        echo "========================================\n";
        echo "Rapport des collations\n";
        echo "========================================\n\n";

        $query = "
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                COLLATION_NAME,
                COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND COLLATION_NAME IS NOT NULL
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ";

        $columns = $this->db->query($query);

        $collationStats = [];
        foreach ($columns as $column) {
            $collation = $column['COLLATION_NAME'];
            if (!isset($collationStats[$collation])) {
                $collationStats[$collation] = 0;
            }
            $collationStats[$collation]++;
        }

        echo "Statistiques par collation:\n";
        foreach ($collationStats as $collation => $count) {
            $marker = $collation === $this->targetCollation ? '✓' : '✗';
            echo "  {$marker} {$collation}: {$count} colonnes\n";
        }

        echo "\nColonnes à migrer:\n";
        foreach ($columns as $column) {
            if ($column['COLLATION_NAME'] !== $this->targetCollation) {
                echo "  • {$column['TABLE_NAME']}.{$column['COLUMN_NAME']} ({$column['COLLATION_NAME']})\n";
            }
        }

        echo "\n";
    }

    /**
     * Crée une sauvegarde de la structure
     */
    public function backup(string $filename = 'backup_structure.sql'): void
    {
        echo "Création d'une sauvegarde de la structure...\n";

        $dbName = $this->getCurrentDatabase();
        $command = "mysqldump -u root -p --no-data {$dbName} > {$filename}";

        echo "Commande: {$command}\n";
        echo "⚠ Exécutez cette commande manuellement dans votre terminal\n\n";
    }
}

// ========================================
// Exécution du script
// ========================================

try {
    $migrator = new CollationMigrator(dryRun: false); // Mettre à true pour simuler

    // 1. Afficher le rapport actuel
    echo "\n";
    $migrator->report();

    // 2. Suggestion de sauvegarde
    echo "\n⚠  IMPORTANT: Avant de continuer, faites une sauvegarde !\n";
    $migrator->backup();

    // 3. Demander confirmation
    echo "Voulez-vous continuer avec la migration ? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) !== 'yes') {
        echo "Migration annulée.\n";
        exit(0);
    }

    // 4. Lancer la migration
    echo "\n";
    $migrator->migrate();

    // 5. Afficher le rapport final
    echo "\n";
    $migrator->report();
} catch (Exception $e) {
    echo "ERREUR FATALE: " . $e->getMessage() . "\n";
    exit(1);
}
