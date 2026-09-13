<?php

declare(strict_types=1);

namespace BedFight\Game;

use BedFight\Arena\Arena;
use BedFight\Arena\ArenaManager;
use BedFight\Bot\BotManager;
use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use BedFight\Leaderboard\LeaderboardManager;
use BedFight\NPC\NPCManager;
use BedFight\Storage\StorageManager;
use BedFight\Utils\Logger;
use BedFight\Utils\VapmScheduler;
use BedFight\Utils\ParticleManager;
use BedFight\Utils\SoundManager;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;

class GameManager {

    private BedFight $plugin;
    private ArenaManager $arenaManager;
    private ConfigManager $config;
    private StorageManager $storage;
    private BotManager $botManager;
    private LeaderboardManager $leaderboardManager;
    private NPCManager $npcManager;
    private VapmScheduler $scheduler;
    private Logger $logger;
    private ParticleManager $particles;
    private SoundManager $sounds;
    private array $games = [];
    private array $playerGame = [];
    private array $lobbyTasks = [];
    private array $gameTasks = [];

    public function __construct(
        BedFight $plugin,
        ArenaManager $arenaManager,
        ConfigManager $config
    ) {
        $this->plugin = $plugin;
        $this->arenaManager = $arenaManager;
        $this->config = $config;
        $this->storage = $plugin->getStorage();
        $this->botManager = $plugin->getBotManager();
        $this->leaderboardManager = $plugin->getLeaderboardManager();
        $this->npcManager = $plugin->getNPCManager();
        $this->scheduler = $plugin->getVapmScheduler();
        $this->logger = $plugin->getLoggerWrapper();
        $this->particles = new ParticleManager($plugin);
        $this->sounds = new SoundManager($plugin);
    }

    public function joinQueue(Player $player): bool {
        $name = $player->getName();
        if (isset($this->playerGame[$name])) return false;

        $arenas = $this->arenaManager->getWaitingArenas();
        if (empty($arenas)) {
            $this->sendMessage($player, 'no_arenas_available');
            return false;
        }

        $arena = $this->findBestArena($arenas);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_full');
            return false;
        }

        return $this->joinArena($player, $arena);
    }

    public function joinArena(Player $player, Arena $arena): bool {
        $name = $player->getName();
        if (isset($this->playerGame[$name])) return false;

        if ($arena->isFull($this->config->getMinPlayers())) return false;

        $world = $this->arenaManager->getWorldForArena($arena);
        if ($world === null) return false;

        $team = $this->selectTeam($arena);
        $spawn = $arena->getSpawnForTeam($team);
        $position = new Position($spawn->x, $spawn->y, $spawn->z, $world);

        $player->teleport($position);
        $this->giveGameItems($player, $team);
        $this->clearInventory($player);

        $arena->addPlayer($name);
        $arena->setStatus(Arena::STATUS_WAITING);
        $this->playerGame[$name] = $arena->getId();

        $this->saveArena($arena);

        if ($this->shouldStartGame($arena)) {
            $this->startGame($arena);
        } else {
            $this->startLobbyCountdown($arena);
        }

        $this->sendMessage($player, 'joined_game', ['arena' => $arena->getName(), 'team' => $team]);
        return true;
    }

    public function leaveGame(Player $player): bool {
        $name = $player->getName();
        $arenaId = $this->playerGame[$name] ?? null;
        if ($arenaId === null) return false;

        $arena = $this->arenaManager->getArena($arenaId);
        if ($arena === null) return false;

        $game = $this->games[$arenaId] ?? null;
        if ($game !== null) {
            $game->removePlayer($name);
            $this->handlePlayerLeave($player, $arena, $game);
        } else {
            $arena->removePlayer($name);
            if ($arena->getPlayerCount() === 0) {
                $this->cancelLobbyCountdown($arena);
                $arena->setStatus(Arena::STATUS_EMPTY);
            }
        }

        unset($this->playerGame[$name]);
        $this->sendToLobby($player);
        $this->saveArena($arena);
        return true;
    }

    private function findBestArena(array $arenas): ?Arena {
        usort($arenas, fn(Arena $a, Arena $b) => $a->getPlayerCount() <=> $b->getPlayerCount());
        return $arenas[0] ?? null;
    }

    private function selectTeam(Arena $arena): string {
        $game = $this->games[$arena->getId()] ?? null;
        $blueCount = $game ? count($game->getTeams()['blue']) : 0;
        $redCount = $game ? count($game->getTeams()['red']) : 0;
        return $blueCount <= $redCount ? 'blue' : 'red';
    }

    private function shouldStartGame(Arena $arena): bool {
        return $arena->getPlayerCount() >= $this->config->getMinPlayers();
    }

    private function startGame(Arena $arena): void {
        $game = new Game($this->generateGameId(), $arena->getId());
        $this->games[$arena->getId()] = $game;
        $arena->setGame($game);
        $arena->setStatus(Arena::STATUS_IN_GAME);

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $team = $this->getPlayerTeam($game, $playerName);
                $game->addPlayer($team, $playerName);
                $this->sendMessage($player, 'game_started', ['arena' => $arena->getName()]);
                $this->sounds->play($player, 'game_start');
            }
        }

        $this->startGameTimer($arena, $game);
        $this->saveGame($game);
        $this->saveArena($arena);
    }

    private function startGameTimer(Arena $arena, Game $game): void {
        $duration = $this->config->getGameTimer();
        $taskId = $this->plugin->getScheduler()->scheduleDelayedTask(new class($this, $arena, $game, $duration) extends \pocketmine\scheduler\Task {
            private GameManager $manager;
            private Arena $arena;
            private Game $game;
            private int $time;
            public function __construct(GameManager $manager, Arena $arena, Game $game, int $duration) {
                $this->manager = $manager;
                $this->arena = $arena;
                $this->game = $game;
                $this->time = $duration;
            }
            public function onRun(): void {
                if ($this->time <= 0) {
                    $this->manager->endGame($this->arena, $this->game, 'time');
                    return;
                }

                if ($this->time % 60 === 0 || $this->time <= 10) {
                    $this->manager->broadcastToArena($this->arena, 'time_remaining', ['time' => $this->time]);
                }

                if ($this->manager->checkSuddenDeath($this->arena, $this->game)) {
                    return;
                }

                $this->time--;
                $this->manager->getPlugin()->getScheduler()->scheduleDelayedTask($this, 20);
            }
        }, 20);

        $game->setTaskId($taskId);
        $this->gameTasks[$arena->getId()] = $taskId;
    }

    private function startLobbyCountdown(Arena $arena): void {
        $duration = $this->config->getLobbyTimer();
        $taskId = $this->plugin->getScheduler()->scheduleDelayedTask(new class($this, $arena, $duration) extends \pocketmine\scheduler\Task {
            private GameManager $manager;
            private Arena $arena;
            private int $time;
            public function __construct(GameManager $manager, Arena $arena, int $duration) {
                $this->manager = $manager;
                $this->arena = $arena;
                $this->time = $duration;
            }
            public function onRun(): void {
                if ($this->time <= 0) {
                    if ($this->manager->shouldStartGame($this->arena)) {
                        $this->manager->startGame($this->arena);
                    } else {
                        $this->manager->cancelLobbyCountdown($this->arena);
                        $this->arena->setStatus(Arena::STATUS_EMPTY);
                        foreach ($this->arena->getPlayers() as $playerName) {
                            $player = Server::getInstance()->getPlayerExact($playerName);
                            if ($player !== null) {
                                $this->manager->sendToLobby($player);
                                $this->manager->sendMessage($player, 'not_enough_players');
                            }
                        }
                    }
                    return;
                }

                if ($this->time <= 10 || $this->time % 10 === 0) {
                    $this->manager->broadcastToArena($this->arena, 'game_starting', ['time' => $this->time]);
                }

                $this->time--;
                $this->manager->getPlugin()->getScheduler()->scheduleDelayedTask($this, 20);
            }
        }, 20);

        $this->lobbyTasks[$arena->getId()] = $taskId;
    }

    private function cancelLobbyCountdown(Arena $arena): void {
        if (isset($this->lobbyTasks[$arena->getId()])) {
            $this->plugin->getScheduler()->cancelTask($this->lobbyTasks[$arena->getId()]);
            unset($this->lobbyTasks[$arena->getId()]);
        }
    }

    public function handleBedBreak(Player $player, Arena $arena, string $team): void {
        $game = $this->games[$arena->getId()] ?? null;
        if ($game === null) return;

        $game->destroyBed($team);
        $this->particles->spawnBedDestroy($arena, $team);
        $this->sounds->playToArena($arena, 'bed_destroy');
        $this->broadcastToArena($arena, 'bed_destroyed', ['player' => $player->getName(), 'team' => $team]);

        $this->checkWinCondition($arena, $game);
        $this->saveGame($game);
    }

    public function handlePlayerDeath(Player $victim, ?Player $killer, Arena $arena): void {
        $game = $this->games[$arena->getId()] ?? null;
        if ($game === null) return;

        $victimName = $victim->getName();
        $killerName = $killer?->getName() ?? 'unknown';

        if ($this->config->isRespawnEnabled()) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new class($this, $victim, $arena) extends \pocketmine\scheduler\Task {
                private GameManager $manager;
                private Player $victim;
                private Arena $arena;
                public function __construct(GameManager $manager, Player $victim, Arena $arena) {
                    $this->manager = $manager;
                    $this->victim = $victim;
                    $this->arena = $arena;
                }
                public function onRun(): void {
                    $this->manager->respawnPlayer($this->victim, $this->arena);
                }
            }, $this->config->getRespawnDelay() * 20);

            $this->sendMessage($victim, 'you_died', ['time' => $this->config->getRespawnDelay()]);
        } else {
            $this->eliminatePlayer($victim, $arena);
        }

        if ($killer !== null) {
            $this->leaderboardManager->incrementStat($killerName, 'kills');
            $this->broadcastToArena($arena, 'player_killed', ['killer' => $killerName, 'victim' => $victimName]);
            $this->particles->spawnKill($arena, $killer->getPosition());
            $this->sounds->play($killer, 'kill');
        }

        $this->checkWinCondition($arena, $game);
    }

    private function respawnPlayer(Player $player, Arena $arena): void {
        $game = $this->games[$arena->getId()] ?? null;
        if ($game === null) return;

        $team = $this->getPlayerTeam($game, $player->getName());
        if ($team === null) return;

        $bedAlive = $game->isBedAlive($team);
        if (!$bedAlive) {
            $this->eliminatePlayer($player, $arena);
            return;
        }

        $world = $this->arenaManager->getWorldForArena($arena);
        if ($world === null) return;

        $spawn = $arena->getSpawnForTeam($team);
        $position = new Position($spawn->x, $spawn->y, $spawn->z, $world);
        $player->teleport($position);
        $this->giveGameItems($player, $team);
        $this->clearInventory($player);
        $this->sendMessage($player, 'respawned');
        $this->particles->spawnRespawn($arena, $player->getPosition());
        $this->sounds->play($player, 'respawn');
    }

    private function eliminatePlayer(Player $player, Arena $arena): void {
        $this->leaveGame($player);
        $this->sendMessage($player, 'eliminated');
    }

    private function checkWinCondition(Arena $arena, Game $game): void {
        $aliveTeams = $game->getAliveTeams();
        if (count($aliveTeams) <= 1) {
            $this->endGame($arena, $game, count($aliveTeams) === 1 ? 'bed' : 'all_beds_destroyed');
        }
    }

    public function endGame(Arena $arena, Game $game, string $reason): void {
        $winner = $game->getWinner();
        $arena->setStatus(Arena::STATUS_ENDING);

        $this->cancelGameTimer($arena);

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $this->sendToLobby($player);
                if ($winner !== null) {
                    $team = $this->getPlayerTeam($game, $playerName);
                    if ($team === $winner) {
                        $this->sendMessage($player, 'game_won', ['arena' => $arena->getName()]);
                        $this->leaderboardManager->incrementStat($playerName, 'wins');
                        $this->leaderboardManager->incrementStat($playerName, 'win_streak');
                        $this->particles->spawnWin($arena, $player->getPosition());
                        $this->sounds->play($player, 'win');
                    } else {
                        $this->sendMessage($player, 'game_lost', ['arena' => $arena->getName(), 'winner' => $winner]);
                        $this->leaderboardManager->resetStat($playerName, 'win_streak');
                        $this->sounds->play($player, 'lose');
                    }
                } else {
                    $this->sendMessage($player, 'game_draw', ['arena' => $arena->getName()]);
                }
            }
        }

        $game->setStatus('finished');
        $game->setEndTime(time());
        $this->saveGame($game);

        $this->botManager->removeBotsFromArena($arena->getId());
        $this->npcManager->despawnFromArena($arena->getId());

        $arena->setStatus(Arena::STATUS_EMPTY);
        $arena->setGame(null);
        $this->saveArena($arena);

        $this->logger->info("Game ended in arena {$arena->getName()}: $reason");
    }

    private function cancelGameTimer(Arena $arena): void {
        if (isset($this->gameTasks[$arena->getId()])) {
            $this->plugin->getScheduler()->cancelTask($this->gameTasks[$arena->getId()]);
            unset($this->gameTasks[$arena->getId()]);
        }
    }

    public function getPlayerTeam(Game $game, string $playerName): ?string {
        foreach ($game->getTeams() as $team => $players) {
            if (in_array($playerName, $players)) return $team;
        }
        return null;
    }

    private function giveGameItems(Player $player, string $team): void {
        $items = $this->config->getItems("game.$team");
        $player->getInventory()->clearAll();
        foreach ($items as $index => $itemData) {
            $item = $this->createItem($itemData);
            if ($item !== null) {
                $slot = $itemData['slot'] ?? $index;
                $player->getInventory()->setItem($slot, $item);
            }
        }
    }

    private function createItem(array $data): ?\pocketmine\item\Item {
        $type = $data['type'] ?? 'AIR';
        $name = $data['name'] ?? '';
        $amount = $data['amount'] ?? 1;

        $item = \pocketmine\item\VanillaItems::getInstance()->get($type);
        if ($item->isNull()) return null;

        $item->setCustomName($name);
        $item->setCount($amount);
        return $item;
    }

    private function clearInventory(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getOffhandInventory()->clearAll();
    }

    private function sendToLobby(Player $player): void {
        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($world !== null) {
            $player->teleport($world->getSafeSpawn());
        }
        $this->giveLobbyItems($player);
    }

    public function giveLobbyItems(Player $player): void {
        $items = $this->config->getItems('lobby');
        $player->getInventory()->clearAll();
        foreach ($items as $itemData) {
            $item = $this->createItem($itemData);
            if ($item !== null) {
                $slot = $itemData['slot'] ?? 0;
                $player->getInventory()->setItem($slot, $item);
            }
        }
    }

    public function checkSuddenDeath(Arena $arena, Game $game): bool {
        if (!$this->config->isSuddenDeathEnabled()) return false;

        $elapsed = time() - $game->getStartTime();
        if ($elapsed >= $this->config->getGameTimer() - $this->config->getSuddenDeathTime()) {
            $this->broadcastToArena($arena, 'sudden_death', ['time' => $this->config->getSuddenDeathTime()]);
            return false;
        }
        return false;
    }

    public function broadcastToArena(Arena $arena, string $messageKey, array $placeholders = []): void {
        $message = $this->config->getMessage($messageKey);
        foreach ($placeholders as $key => $value) {
            $message = str_replace("%$key%", $value, $message);
        }
        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->sendMessage($message);
            }
        }
    }

    public function sendMessage(Player $player, string $messageKey, array $placeholders = []): void {
        $message = $this->config->getMessage($messageKey);
        foreach ($placeholders as $key => $value) {
            $message = str_replace("%$key%", $value, $message);
        }
        $player->sendMessage($message);
    }

    public function getPlayerArena(Player $player): ?Arena {
        $arenaId = $this->playerGame[$player->getName()] ?? null;
        return $arenaId ? $this->arenaManager->getArena($arenaId) : null;
    }

    public function isInGame(Player $player): bool {
        return isset($this->playerGame[$player->getName()]);
    }

    private function saveGame(Game $game): void {
        $this->storage->set('games', $game->getId(), $game->toArray());
    }

    private function saveArena(Arena $arena): void {
        $this->storage->setAsync('arenas', $arena->getId(), $arena->toArray());
    }

    public function handlePlayerLeave(Player $player, Arena $arena, Game $game): void {
        $game->removePlayer($player->getName());
        $this->checkWinCondition($arena, $game);
    }

    public function cleanupFinishedGames(): void {
        $now = time();
        foreach ($this->games as $id => $game) {
            if ($game->getStatus() === 'finished' && $now - ($game->getEndTime() ?? $now) > 300) {
                unset($this->games[$id]);
            }
        }
    }

    public function stopAllGames(): void {
        foreach ($this->games as $game) {
            $arena = $this->arenaManager->getArena($game->getArenaId());
            if ($arena !== null) {
                $this->endGame($arena, $game, 'server_shutdown');
            }
        }
        foreach ($this->lobbyTasks as $taskId) {
            $this->plugin->getScheduler()->cancelTask($taskId);
        }
        foreach ($this->gameTasks as $taskId) {
            $this->plugin->getScheduler()->cancelTask($taskId);
        }
    }

    private function generateGameId(): string {
        return 'game_' . bin2hex(random_bytes(8));
    }

    public function getPlugin(): BedFight { return $this->plugin; }
}