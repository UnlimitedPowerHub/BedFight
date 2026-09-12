<?php

declare(strict_types=1);

namespace BedFight\Storage;

use BedFight\Config\ConfigManager;
use BedFight\Core\BedFight;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function json_decode;
use function json_encode;

class JSONDriver implements StorageDriver {

    private BedFight $plugin;
    private ConfigManager $config;
    private string $dataDir;
    private array $cache = [];

    public function __construct(BedFight $plugin, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->dataDir = $plugin->getDataFolder() . $config->getJSONDirectory() . "/";
    }

    public function initialize(): void {
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    public function close(): void {
        $this->flushCache();
    }

    private function getFilePath(string $table): string {
        return $this->dataDir . $table . ".json";
    }

    private function loadTable(string $table): array {
        if (isset($this->cache[$table])) {
            return $this->cache[$table];
        }

        $file = $this->getFilePath($table);
        if (!file_exists($file)) {
            $this->cache[$table] = [];
            return [];
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);
        $this->cache[$table] = $data ?? [];
        return $this->cache[$table];
    }

    private function saveTable(string $table): bool {
        if (!isset($this->cache[$table])) return true;

        $file = $this->getFilePath($table);
        $json = json_encode($this->cache[$table], $this->config->get('storage.json.pretty_print', false) ? JSON_PRETTY_PRINT : 0);
        if ($json === false) return false;

        return file_put_contents($file, $json) !== false;
    }

    public function get(string $table, string $key): ?array {
        $data = $this->loadTable($table);
        return $data[$key] ?? null;
    }

    public function set(string $table, string $key, array $data): bool {
        $tableData = $this->loadTable($table);
        $tableData[$key] = $data;
        $this->cache[$table] = $tableData;
        return $this->saveTable($table);
    }

    public function delete(string $table, string $key): bool {
        $tableData = $this->loadTable($table);
        if (!isset($tableData[$key])) return false;
        unset($tableData[$key]);
        $this->cache[$table] = $tableData;
        return $this->saveTable($table);
    }

    public function getAll(string $table): array {
        return $this->loadTable($table);
    }

    public function exists(string $table, string $key): bool {
        $data = $this->loadTable($table);
        return isset($data[$key]);
    }

    public function batchSet(string $table, array $data): bool {
        $tableData = $this->loadTable($table);
        foreach ($data as $key => $value) {
            $tableData[$key] = $value;
        }
        $this->cache[$table] = $tableData;
        return $this->saveTable($table);
    }

    public function batchGet(string $table, array $keys): array {
        $tableData = $this->loadTable($table);
        $result = [];
        foreach ($keys as $key) {
            if (isset($tableData[$key])) {
                $result[$key] = $tableData[$key];
            }
        }
        return $result;
    }

    private function flushCache(): void {
        foreach (array_keys($this->cache) as $table) {
            $this->saveTable($table);
        }
        $this->cache = [];
    }
}