<?php

declare(strict_types=1);

namespace BedFight\Arena;

use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use BedFight\Storage\StorageManager;
use BedFight\Utils\Logger;
use pocketmine\Server;
use pocketmine\world\World;

class ArenaManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private StorageManager $storage;
    private Logger $logger;
    private array $arenas = [];
    private array $arenasByName = [];
    private int $maxArenas;
    private int $cleanupInterval;

    public function __construct(BedFight $plugin, StorageManager $storage, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->storage = $storage;
        $this->config = $config;
        $this->logger = $plugin->getLoggerWrapper();
        $this->maxArenas = $config->getMaxConcurrentArenas();
        $this->cleanupInterval = $config->getArenaCleanupInterval();
    }

    public function loadAllArenas(): void {
        $this->logger->info("Loading arenas...");
        $data = $this->storage->getAll('arenas');
        $loaded = 0;

        foreach ($data as $id => $arenaData) {
            try {
                $arena = Arena::fromArray($arenaData);
                $this->registerArena($arena);
                $loaded++;
            } catch (\Throwable $e) {
                $this->logger->error("Failed to load arena $id: " . $e->getMessage());
            }
        }

        $this->logger->info("Loaded $loaded arenas");
    }

    public function createArena(string $name, string $worldName): ?Arena {
        if (isset($this->arenasByName[$name])) {
            return null;
        }

        if (count($this->arenas) >= $this->maxArenas) {
            return null;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) {
            return null;
        }

        $id = $this->generateId();
        $arena = new Arena($id, $name, $worldName);
        $this->registerArena($arena);
        $this->saveArena($arena);
        return $arena;
    }

    public function deleteArena(string $id): bool {
        $arena = $this->arenas[$id] ?? null;
        if ($arena === null) return false;

        if ($arena->getStatus() !== Arena::STATUS_EMPTY) {
            return false;
        }

        unset($this->arenas[$id]);
        unset($this->arenasByName[$arena->getName()]);
        $this->storage->delete('arenas', $id);
        return true;
    }

    public function getArena(string $id): ?Arena {
        return $this->arenas[$id] ?? null;
    }

    public function getArenaByName(string $name): ?Arena {
        return $this->arenasByName[$name] ?? null;
    }

    public function getAllArenas(): array {
        return $this->arenas;
    }

    public function getArenasByStatus(string $status): array {
        return array_filter($this->arenas, fn(Arena $a) => $a->getStatus() === $status);
    }

    public function getEmptyArenas(): array {
        return array_filter($this->arenas, fn(Arena $a) => 
            $a->getStatus() === Arena::STATUS_EMPTY && $a->isReady()
        );
    }

    public function getWaitingArenas(): array {
        return array_filter($this->arenas, fn(Arena $a) => 
            $a->getStatus() === Arena::STATUS_WAITING && !$a->isFull($this->config->getMinPlayers())
        );
    }

    public function registerArena(Arena $arena): void {
        $this->arenas[$arena->getId()] = $arena;
        $this->arenasByName[$arena->getName()] = $arena;
    }

    public function unregisterArena(Arena $arena): void {
        unset($this->arenas[$arena->getId()]);
        unset($this->arenasByName[$arena->getName()]);
    }

    public function saveArena(Arena $arena): void {
        $this->storage->set('arenas', $arena->getId(), $arena->toArray());
    }

    public function saveArenaAsync(Arena $arena, callable $callback = null): void {
        $this->storage->setAsync('arenas', $arena->getId(), $arena->toArray(), $callback ?? function() {});
    }

    public function saveAllArenas(): void {
        $data = [];
        foreach ($this->arenas as $arena) {
            $data[$arena->getId()] = $arena->toArray();
        }
        $this->storage->batchSet('arenas', $data);
    }

    public function cleanupEmptyArenas(): void {
        $now = time();
        foreach ($this->arenas as $arena) {
            if ($arena->getStatus() === Arena::STATUS_EMPTY) {
                if ($now - $arena->getUpdatedAt() > $this->cleanupInterval * 60) {
                    $this->deleteArena($arena->getId());
                    $this->logger->info("Cleaned up empty arena: " . $arena->getName());
                }
            }
        }
    }

    public function getWorldForArena(Arena $arena): ?World {
        return Server::getInstance()->getWorldManager()->getWorldByName($arena->getWorldName());
    }

    private function generateId(): string {
        return 'arena_' . bin2hex(random_bytes(8));
    }
}