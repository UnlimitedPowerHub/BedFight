<?php

declare(strict_types=1);

namespace BedFight\Storage;

use BedFight\Config\ConfigManager;
use BedFight\Core\BedFight;
use BedFight\Utils\AsyncTaskScheduler;
use pocketmine\Server;
use function file_exists;
use function mkdir;

interface StorageDriver {
    public function initialize(): void;
    public function close(): void;
    public function get(string $table, string $key): ?array;
    public function set(string $table, string $key, array $data): bool;
    public function delete(string $table, string $key): bool;
    public function getAll(string $table): array;
    public function exists(string $table, string $key): bool;
    public function batchSet(string $table, array $data): bool;
    public function batchGet(string $table, array $keys): array;
}

class StorageManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private AsyncTaskScheduler $scheduler;
    private StorageDriver $driver;
    private array $cache = [];
    private int $cacheTTL;

    public function __construct(BedFight $plugin, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->scheduler = $plugin->getTaskScheduler();
        $this->cacheTTL = $config->getCacheTTL();
        $this->driver = $this->createDriver();
    }

    private function createDriver(): StorageDriver {
        $type = $this->config->getStorageType();
        return match ($type) {
            'sqlite' => new SQLiteDriver($this->plugin, $this->config),
            'json' => new JSONDriver($this->plugin, $this->config),
            'mysql' => new MySQLDriver($this->plugin, $this->config),
            default => new SQLiteDriver($this->plugin, $this->config),
        };
    }

    public function initialize(): void {
        $this->driver->initialize();
    }

    public function close(): void {
        $this->flushCache();
        $this->driver->close();
    }

    public function get(string $table, string $key): ?array {
        $cacheKey = "$table:$key";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $data = $this->driver->get($table, $key);
        if ($data !== null) {
            $this->cache[$cacheKey] = $data;
        }
        return $data;
    }

    public function getAsync(string $table, string $key, callable $callback): void {
        $this->scheduler->submit(function () use ($table, $key) {
            return $this->driver->get($table, $key);
        }, function (?\Throwable $error, mixed $result) use ($callback) {
            if ($error !== null) {
                $callback($error, null);
            } else {
                $cacheKey = "$table:$key";
                if ($result !== null) {
                    $this->cache[$cacheKey] = $result;
                }
                $callback(null, $result);
            }
        });
    }

    public function set(string $table, string $key, array $data): bool {
        $this->cache["$table:$key"] = $data;
        return $this->driver->set($table, $key, $data);
    }

    public function setAsync(string $table, string $key, array $data, callable $callback): void {
        $this->scheduler->submit(function () use ($table, $key, $data) {
            return $this->driver->set($table, $key, $data);
        }, function (?\Throwable $error, mixed $result) use ($table, $key, $data, $callback) {
            if ($error === null && $result) {
                $this->cache["$table:$key"] = $data;
            }
            $callback($error, $result ?? false);
        });
    }

    public function delete(string $table, string $key): bool {
        unset($this->cache["$table:$key"]);
        return $this->driver->delete($table, $key);
    }

    public function getAll(string $table): array {
        return $this->driver->getAll($table);
    }

    public function getAllAsync(string $table, callable $callback): void {
        $this->scheduler->submit(function () use ($table) {
            return $this->driver->getAll($table);
        }, $callback);
    }

    public function exists(string $table, string $key): bool {
        $cacheKey = "$table:$key";
        if (isset($this->cache[$cacheKey])) {
            return true;
        }
        return $this->driver->exists($table, $key);
    }

    public function batchSet(string $table, array $data): bool {
        foreach ($data as $key => $value) {
            $this->cache["$table:$key"] = $value;
        }
        return $this->driver->batchSet($table, $data);
    }

    public function batchSetAsync(string $table, array $data, callable $callback): void {
        $this->scheduler->submit(function () use ($table, $data) {
            return $this->driver->batchSet($table, $data);
        }, function (?\Throwable $error, mixed $result) use ($table, $data, $callback) {
            if ($error === null && $result) {
                foreach ($data as $key => $value) {
                    $this->cache["$table:$key"] = $value;
                }
            }
            $callback($error, $result ?? false);
        });
    }

    public function flushCache(): void {
        $this->cache = [];
    }

    public function getDriver(): StorageDriver {
        return $this->driver;
    }
}