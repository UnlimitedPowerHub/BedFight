<?php

declare(strict_types=1);

namespace BedFight\Storage;

use BedFight\Config\ConfigManager;
use BedFight\Core\BedFight;
use mysqli;
use mysqli_sql_exception;

class MySQLDriver implements StorageDriver {

    private BedFight $plugin;
    private ConfigManager $config;
    private ?mysqli $connection = null;
    private array $preparedStatements = [];

    public function __construct(BedFight $plugin, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->config = $config;
    }

    public function initialize(): void {
        $mysql = $this->config->getMySQLConfig();
        
        $this->connection = new mysqli(
            $mysql['host'],
            $mysql['username'],
            $mysql['password'],
            $mysql['database'],
            $mysql['port']
        );

        if ($this->connection->connect_error) {
            throw new \RuntimeException("MySQL connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
        $this->createTables();
    }

    private function createTables(): void {
        $tables = [
            'arenas' => "CREATE TABLE IF NOT EXISTS `arenas` (
                `id` VARCHAR(255) PRIMARY KEY,
                `data` JSON NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'players' => "CREATE TABLE IF NOT EXISTS `players` (
                `uuid` VARCHAR(36) PRIMARY KEY,
                `name` VARCHAR(16) NOT NULL,
                `data` JSON NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'leaderboards' => "CREATE TABLE IF NOT EXISTS `leaderboards` (
                `category` VARCHAR(50) NOT NULL,
                `uuid` VARCHAR(36) NOT NULL,
                `name` VARCHAR(16) NOT NULL,
                `value` BIGINT NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`category`, `uuid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'bots' => "CREATE TABLE IF NOT EXISTS `bots` (
                `id` VARCHAR(255) PRIMARY KEY,
                `arena_id` VARCHAR(255) NOT NULL,
                `data` JSON NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'npcs' => "CREATE TABLE IF NOT EXISTS `npcs` (
                `id` VARCHAR(255) PRIMARY KEY,
                `arena_id` VARCHAR(255),
                `data` JSON NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'games' => "CREATE TABLE IF NOT EXISTS `games` (
                `id` VARCHAR(255) PRIMARY KEY,
                `arena_id` VARCHAR(255) NOT NULL,
                `data` JSON NOT NULL,
                `started_at` BIGINT NOT NULL,
                `ended_at` BIGINT,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        foreach ($tables as $sql) {
            $this->connection->query($sql);
        }

        $indexes = [
            'CREATE INDEX IF NOT EXISTS `idx_leaderboards_category` ON `leaderboards`(`category`)',
            'CREATE INDEX IF NOT EXISTS `idx_leaderboards_value` ON `leaderboards`(`value` DESC)',
            'CREATE INDEX IF NOT EXISTS `idx_games_arena` ON `games`(`arena_id`)',
            'CREATE INDEX IF NOT EXISTS `idx_bots_arena` ON `bots`(`arena_id`)'
        ];

        foreach ($indexes as $sql) {
            $this->connection->query($sql);
        }
    }

    public function close(): void {
        foreach ($this->preparedStatements as $stmt) {
            $stmt->close();
        }
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }

    private function getStmt(string $sql): \mysqli_stmt {
        if (!isset($this->preparedStatements[$sql])) {
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new \RuntimeException("Prepare failed: " . $this->connection->error);
            }
            $this->preparedStatements[$sql] = $stmt;
        }
        return $this->preparedStatements[$sql];
    }

    public function get(string $table, string $key): ?array {
        $stmt = $this->getStmt("SELECT `data` FROM `$table` WHERE `id` = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row !== null) {
            return json_decode($row['data'], true) ?? null;
        }
        return null;
    }

    public function set(string $table, string $key, array $data): bool {
        $json = json_encode($data);
        if ($json === false) return false;

        $stmt = $this->getStmt("INSERT INTO `$table` (`id`, `data`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)");
        $stmt->bind_param('ss', $key, $json);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete(string $table, string $key): bool {
        $stmt = $this->getStmt("DELETE FROM `$table` WHERE `id` = ?");
        $stmt->bind_param('s', $key);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function getAll(string $table): array {
        $result = $this->connection->query("SELECT `id`, `data` FROM `$table`");
        $data = [];
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['id']] = json_decode($row['data'], true) ?? [];
            }
            $result->free();
        }
        return $data;
    }

    public function exists(string $table, string $key): bool {
        $stmt = $this->getStmt("SELECT 1 FROM `$table` WHERE `id` = ? LIMIT 1");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc() !== null;
        $stmt->close();
        return $exists;
    }

    public function batchSet(string $table, array $data): bool {
        if (empty($data)) return true;

        $this->connection->begin_transaction();
        try {
            $stmt = $this->getStmt("INSERT INTO `$table` (`id`, `data`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)");
            foreach ($data as $key => $value) {
                $json = json_encode($value);
                if ($json === false) continue;
                $stmt->bind_param('ss', $key, $json);
                $stmt->execute();
            }
            $this->connection->commit();
            return true;
        } catch (\Throwable) {
            $this->connection->rollback();
            return false;
        }
    }

    public function batchGet(string $table, array $keys): array {
        if (empty($keys)) return [];

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT `id`, `data` FROM `$table` WHERE `id` IN ($placeholders)";
        $stmt = $this->connection->prepare($sql);
        
        $types = str_repeat('s', count($keys));
        $stmt->bind_param($types, ...$keys);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['id']] = json_decode($row['data'], true) ?? [];
        }
        $result->free();
        $stmt->close();
        return $data;
    }
}