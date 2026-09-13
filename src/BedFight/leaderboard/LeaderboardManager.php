<?php

declare(strict_types=1);

namespace BedFight\Leaderboard;

use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use BedFight\Storage\StorageManager;
use BedFight\Utils\Logger;
use pocketmine\player\Player;

class LeaderboardManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private StorageManager $storage;
    private Logger $logger;
    private array $cache = [];
    private int $updateInterval;
    private int $topEntries;
    private array $categories = [];

    public function __construct(BedFight $plugin, StorageManager $storage, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->storage = $storage;
        $this->config = $config;
        $this->logger = $plugin->getLoggerWrapper();
        $this->updateInterval = $config->getLeaderboardUpdateInterval() * 60 * 1000;
        $this->topEntries = $config->getLeaderboardTopEntries();
        $this->categories = $config->getLeaderboardCategories();
    }

    public function startUpdateTask(): void {
        $this->loadAllLeaderboards();
        
        $this->plugin->getScheduler()->scheduleRepeatingTask(new class($this) extends \pocketmine\scheduler\Task {
            private LeaderboardManager $manager;
            public function __construct(LeaderboardManager $manager) { $this->manager = $manager; }
            public function onRun(): void {
                $this->manager->saveAllLeaderboards();
            }
        }, $this->updateInterval / 50);
    }

    public function incrementStat(string $playerName, string $category, int $amount = 1): void {
        if (!$this->config->isLeaderboardEnabled()) return;
        if (!in_array($category, $this->categories)) return;

        $uuid = $this->getPlayerUuid($playerName);
        if ($uuid === null) return;

        $cacheKey = "$category:$uuid";
        $current = $this->cache[$cacheKey] ?? 0;
        $this->cache[$cacheKey] = $current + $amount;
    }

    public function setStat(string $playerName, string $category, int $value): void {
        if (!$this->config->isLeaderboardEnabled()) return;
        if (!in_array($category, $this->categories)) return;

        $uuid = $this->getPlayerUuid($playerName);
        if ($uuid === null) return;

        $cacheKey = "$category:$uuid";
        $this->cache[$cacheKey] = $value;
    }

    public function resetStat(string $playerName, string $category): void {
        $this->setStat($playerName, $category, 0);
    }

    public function getStat(string $playerName, string $category): int {
        $uuid = $this->getPlayerUuid($playerName);
        if ($uuid === null) return 0;

        $cacheKey = "$category:$uuid";
        return $this->cache[$cacheKey] ?? 0;
    }

    public function getTop(string $category, int $limit = 10): array {
        if (!in_array($category, $this->categories)) return [];

        $entries = [];
        foreach ($this->cache as $key => $value) {
            if (str_starts_with($key, "$category:")) {
                $uuid = substr($key, strlen($category) + 1);
                $name = $this->getPlayerName($uuid) ?? 'Unknown';
                $entries[] = ['uuid' => $uuid, 'name' => $name, 'value' => $value];
            }
        }

        usort($entries, fn($a, $b) => $b['value'] <=> $a['value']);
        return array_slice($entries, 0, $limit);
    }

    public function getPlayerRank(string $playerName, string $category): int {
        $top = $this->getTop($category, $this->topEntries);
        foreach ($top as $index => $entry) {
            if ($entry['name'] === $playerName) {
                return $index + 1;
            }
        }
        return 0;
    }

    public function showLeaderboard(Player $player, string $category): void {
        if (!in_array($category, $this->categories)) {
            $player->sendMessage($this->config->getMessage('leaderboard_empty'));
            return;
        }

        $top = $this->getTop($category, 10);
        $header = str_replace('%category%', $category, $this->config->getMessage('leaderboard_header'));
        $player->sendMessage($header);

        if (empty($top)) {
            $player->sendMessage($this->config->getMessage('leaderboard_empty'));
            return;
        }

        foreach ($top as $index => $entry) {
            $msg = str_replace(
                ['%rank%', '%player%', '%value%'],
                [$index + 1, $entry['name'], $entry['value']],
                $this->config->getMessage('leaderboard_entry')
            );
            $player->sendMessage($msg);
        }
    }

    public function getCategories(): array {
        return $this->categories;
    }

    private function loadAllLeaderboards(): void {
        $data = $this->storage->getAll('leaderboards');
        foreach ($data as $key => $entry) {
            if (isset($entry['category'], $entry['uuid'], $entry['value'])) {
                $cacheKey = "{$entry['category']}:{$entry['uuid']}";
                $this->cache[$cacheKey] = $entry['value'];
            }
        }
        $this->logger->info("Loaded leaderboards: " . count($this->cache) . " entries");
    }

    public function saveAllLeaderboards(): void {
        $data = [];
        foreach ($this->cache as $key => $value) {
            [$category, $uuid] = explode(':', $key, 2);
            $name = $this->getPlayerName($uuid) ?? 'Unknown';
            $data[$key] = [
                'category' => $category,
                'uuid' => $uuid,
                'name' => $name,
                'value' => $value
            ];
        }
        $this->storage->batchSet('leaderboards', $data);
    }

    public function save(): void {
        $this->saveAllLeaderboards();
    }

    private function getPlayerUuid(string $name): ?string {
        $players = $this->storage->getAll('players');
        foreach ($players as $uuid => $data) {
            if (($data['name'] ?? '') === $name) return $uuid;
        }
        return null;
    }

    private function getPlayerName(string $uuid): ?string {
        $player = $this->storage->get('players', $uuid);
        return $player['name'] ?? null;
    }
}