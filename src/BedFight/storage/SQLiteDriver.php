<?php

declare(strict_types=1);

namespace BedFight\Storage;

use BedFight\Config\ConfigManager;
use BedFight\Core\BedFight;
use SQLite3;
use function file_exists;

class SQLiteDriver implements StorageDriver {

    private BedFight $plugin;
    private ConfigManager $config;
    private SQLite3 $db;
    private string $dbPath;
    private array $preparedStatements = [];

    public function __construct(BedFight $plugin, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->dbPath = $plugin->getDataFolder() . $config->getSQLiteFile();
    }

    public function initialize(): void {
        $dir = dirname($this->dbPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        $this->db = new SQLite3($this->dbPath, $flags);

        if ($this->config->isSQLiteWALMode()) {
            $this->db->exec('PRAGMA journal_mode=WAL;');
        }
        $this->db->exec('PRAGMA busy_timeout=' . $this->config->getSQLiteBusyTimeout() . ';');
        $this->db->exec('PRAGMA synchronous=NORMAL;');
        $this->db->exec('PRAGMA cache_size=-32768;');

        $this->createTables();
    }

    private function createTables(): void {
        $tables = [
            'arenas' => "CREATE TABLE IF NOT EXISTS arenas (
                id TEXT PRIMARY KEY,
                data TEXT NOT NULL,
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            'players' => "CREATE TABLE IF NOT EXISTS players (
                uuid TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                data TEXT NOT NULL,
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            'leaderboards' => "CREATE TABLE IF NOT EXISTS leaderboards (
                category TEXT NOT NULL,
                uuid TEXT NOT NULL,
                name TEXT NOT NULL,
                value INTEGER NOT NULL,
                updated_at INTEGER DEFAULT (strftime('%s', 'now')),
                PRIMARY KEY (category, uuid)
            )",
            'bots' => "CREATE TABLE IF NOT EXISTS bots (
                id TEXT PRIMARY KEY,
                arena_id TEXT NOT NULL,
                data TEXT NOT NULL,
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            'npcs' => "CREATE TABLE IF NOT EXISTS npcs (
                id TEXT PRIMARY KEY,
                arena_id TEXT,
                data TEXT NOT NULL,
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )",
            'games' => "CREATE TABLE IF NOT EXISTS games (
                id TEXT PRIMARY KEY,
                arena_id TEXT NOT NULL,
                data TEXT NOT NULL,
                started_at INTEGER NOT NULL,
                ended_at INTEGER,
                updated_at INTEGER DEFAULT (strftime('%s', 'now'))
            )"
        ];

        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }

        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_leaderboards_category ON leaderboards(category)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_leaderboards_value ON leaderboards(value DESC)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_games_arena ON games(arena_id)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_bots_arena ON bots(arena_id)');
    }

    public function close(): void {
        foreach ($this->preparedStatements as $stmt) {
            $stmt->close();
        }
        $this->preparedStatements = [];
        $this->db->close();
    }

    private function getStmt(string $sql): \SQLite3Stmt {
        if (!isset($this->preparedStatements[$sql])) {
            $this->preparedStatements[$sql] = $this->db->prepare($sql);
        }
        return $this->preparedStatements[$sql];
    }

    public function get(string $table, string $key): ?array {
        $stmt = $this->getStmt("SELECT data FROM $table WHERE id = ?");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $stmt->reset();

        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            return json_decode($row['data'], true) ?? null;
        }
        return null;
    }

    public function set(string $table, string $key, array $data): bool {
        $json = json_encode($data);
        if ($json === false) return false;

        $stmt = $this->getStmt("INSERT INTO $table (id, data, updated_at) VALUES (?, ?, strftime('%s', 'now')) ON CONFLICT(id) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $stmt->bindValue(2, $json, SQLITE3_TEXT);
        $success = $stmt->execute() !== false;
        $stmt->reset();
        return $success;
    }

    public function delete(string $table, string $key): bool {
        $stmt = $this->getStmt("DELETE FROM $table WHERE id = ?");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $success = $stmt->execute() !== false;
        $stmt->reset();
        return $success;
    }

    public function getAll(string $table): array {
        $primaryKey = $this->getPrimaryKey($table);
        $result = $this->db->query("SELECT * FROM $table");
        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $key = $row[$primaryKey] ?? '';
            $jsonData = $row['data'] ?? json_encode($row);
            $data[$key] = json_decode($jsonData, true) ?? [];
        }
        return $data;
    }

    private function getPrimaryKey(string $table): string {
        return match ($table) {
            'leaderboards' => 'category',
            'arenas', 'players', 'bots', 'npcs', 'games' => 'id',
            default => 'id',
        };
    }

    public function exists(string $table, string $key): bool {
        $stmt = $this->getStmt("SELECT 1 FROM $table WHERE id = ? LIMIT 1");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $stmt->reset();
        return $result->fetchArray() !== false;
    }

    public function batchSet(string $table, array $data): bool {
        $this->db->exec('BEGIN TRANSACTION');
        try {
            $stmt = $this->getStmt("INSERT INTO $table (id, data, updated_at) VALUES (?, ?, strftime('%s', 'now')) ON CONFLICT(id) DO UPDATE SET data = excluded.data, updated_at = excluded.updated_at");
            foreach ($data as $key => $value) {
                $json = json_encode($value);
                if ($json === false) continue;
                $stmt->bindValue(1, $key, SQLITE3_TEXT);
                $stmt->bindValue(2, $json, SQLITE3_TEXT);
                $stmt->execute();
                $stmt->reset();
            }
            $this->db->exec('COMMIT');
            return true;
        } catch (\Throwable) {
            $this->db->exec('ROLLBACK');
            return false;
        }
    }

    public function batchGet(string $table, array $keys): array {
        if (empty($keys)) return [];

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT id, data FROM $table WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);

        foreach ($keys as $i => $key) {
            $stmt->bindValue($i + 1, $key, SQLITE3_TEXT);
        }

        $result = $stmt->execute();
        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['id']] = json_decode($row['data'], true) ?? [];
        }
        return $data;
    }
}