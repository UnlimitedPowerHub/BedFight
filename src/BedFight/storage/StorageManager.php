<?php

declare(strict_types=1);

namespace BedFight\Storage;

use BedFight\Config\ConfigManager;
use BedFight\Core\BedFight;
use BedFight\Utils\VapmScheduler;
use vennv\vapm\Promise;

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
    private VapmScheduler $scheduler;
    private StorageDriver $driver;
    private array $cache = [];
    private int $cacheTTL;

    public function __construct(BedFight $plugin, ConfigManager $config, VapmScheduler $scheduler) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->scheduler = $scheduler;
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
        $promise = $this->scheduler->runAsync(function () use ($table, $key) {
            return $this->driver->get($table, $key);
        });
        
        $promise->then(function ($result) use ($table, $key, $callback) {
            $cacheKey = "$table:$key";
            if ($result !== null) {
                $this->cache[$cacheKey] = $result;
            }
            $callback(null, $result);
        })->catch(function ($error) use ($callback) {
            $callback($error, null);
        });
    }

    public function set(string $table, string $key, array $data): bool {
        $this->cache["$table:$key"] = $data;
        return $this->driver->set($table, $key, $data);
    }

    public function setAsync(string $table, string $key, array $data, callable $callback): void {
        $promise = $this->scheduler->runAsync(function () use ($table, $key, $data) {
            return $this->driver->set($table, $key, $data);
        });
        
        $promise->then(function ($result) use ($table, $key, $data, $callback) {
            if ($result) {
                $this->cache["$table:$key"] = $data;
            }
            $callback(null, $result);
        })->catch(function ($error) use ($callback) {
            $callback($error, false);
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
        $promise = $this->scheduler->runAsync(function () use ($table) {
            return $this->driver->getAll($table);
        });
        
        $promise->then(function ($result) use ($callback) {
            $callback(null, $result);
        })->catch(function ($error) use ($callback) {
            $callback($error, []);
        });
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
        $promise = $this->scheduler->runAsync(function () use ($table, $data) {
            return $this->driver->batchSet($table, $data);
        });
        
        $promise->then(function ($result) use ($table, $data, $callback) {
            if ($result) {
                foreach ($data as $key => $value) {
                    $this->cache["$table:$key"] = $value;
                }
            }
            $callback(null, $result);
        })->catch(function ($error) use ($callback) {
            $callback($error, false);
        });
    }

    public function flushCache(): void {
        $this->cache = [];
    }

    public function getDriver(): StorageDriver {
        return $this->driver;
    }
}