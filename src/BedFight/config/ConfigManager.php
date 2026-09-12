<?php

declare(strict_types=1);

namespace BedFight\Config;

use BedFight\Core\BedFight;
use pocketmine\utils\Config;

class ConfigManager {

    private BedFight $plugin;
    private Config $config;
    private array $data = [];

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
    }

    public function load(): void {
        $this->config = new Config($this->plugin->getDataFolder() . "config.yml", Config::YAML);
        $this->data = $this->config->getAll();
        $this->plugin->saveResource("config.yml");
        $this->config->reload();
        $this->data = array_merge($this->data, $this->config->getAll());
    }

    public function get(string $path, mixed $default = null): mixed {
        $keys = explode('.', $path);
        $value = $this->data;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    }

    public function set(string $path, mixed $value): void {
        $keys = explode('.', $path);
        $ref = &$this->data;
        foreach ($keys as $key) {
            if (!is_array($ref) || !array_key_exists($key, $ref)) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
        $this->config->setAll($this->data);
        $this->config->save();
    }

    public function getStorageType(): string { return $this->get('storage.type', 'sqlite'); }
    public function getPerformanceThreads(): int { return $this->get('performance.async_threads', 4); }
    public function getArenaCleanupInterval(): int { return $this->get('arena.cleanup_interval', 30); }
    public function getMaxConcurrentArenas(): int { return $this->get('arena.max_concurrent_arenas', 50); }
    public function getMinPlayers(): int { return $this->get('game.min_players', 2); }
    public function getMaxPlayersPerTeam(): int { return $this->get('game.max_players_per_team', 4); }
    public function getGameTimer(): int { return $this->get('game.game_timer', 900); }
    public function getLobbyTimer(): int { return $this->get('game.lobby_timer', 30); }
    public function isRespawnEnabled(): bool { return $this->get('game.respawn_enabled', true); }
    public function getRespawnDelay(): int { return $this->get('game.respawn_delay', 5); }
    public function isSuddenDeathEnabled(): bool { return $this->get('game.sudden_death', true); }
    public function getSuddenDeathTime(): int { return $this->get('game.sudden_death_time', 60); }
    public function isBotsEnabled(): bool { return $this->get('bot.enabled', true); }
    public function getMaxBotsPerArena(): int { return $this->get('bot.max_bots_per_arena', 8); }
    public function getBotNames(): array { return $this->get('bot.names', []); }
    public function getBotDifficulty(string $difficulty): array { return $this->get("bot.difficulties.$difficulty", []); }
    public function isLeaderboardEnabled(): bool { return $this->get('leaderboard.enabled', true); }
    public function getLeaderboardUpdateInterval(): int { return $this->get('leaderboard.update_interval', 5); }
    public function getLeaderboardTopEntries(): int { return $this->get('leaderboard.top_entries', 100); }
    public function getLeaderboardCategories(): array { return $this->get('leaderboard.categories', []); }
    public function isNPCsEnabled(): bool { return $this->get('npc.enabled', true); }
    public function getMessage(string $key): string { return $this->get("messages.$key", ""); }
    public function getItems(string $category): array { return $this->get("items.$category", []); }
    public function getParticles(string $key): array { return $this->get("particles.$key", []); }
    public function getSounds(string $key): string { return $this->get("sounds.$key", ""); }
    public function getChunkLoadRadius(): int { return $this->get('performance.chunk_load_radius', 4); }
    public function getEntityTrackingRange(): int { return $this->get('performance.entity_tracking_range', 64); }
    public function isOptimizeParticles(): bool { return $this->get('performance.optimize_particles', true); }
    public function isOptimizeSounds(): bool { return $this->get('performance.optimize_sounds', true); }
    public function getDBBatchSize(): int { return $this->get('performance.db_batch_size', 100); }
    public function getCacheTTL(): int { return $this->get('performance.cache_ttl', 300); }
    public function getSQLiteFile(): string { return $this->get('storage.sqlite.file', 'bedfight.db'); }
    public function isSQLiteWALMode(): bool { return $this->get('storage.sqlite.wal_mode', true); }
    public function getSQLiteBusyTimeout(): int { return $this->get('storage.sqlite.busy_timeout', 5000); }
    public function getJSONDirectory(): string { return $this->get('storage.json.directory', 'data'); }
    public function getMySQLConfig(): array { return $this->get('storage.mysql', []); }
}