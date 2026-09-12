<?php

declare(strict_types=1);

namespace BedFight\Bot;

use BedFight\Arena\Arena;
use BedFight\Arena\ArenaManager;
use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use BedFight\Game\GameManager;
use BedFight\Utils\Logger;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;

class BotManager {

    private BedFight $plugin;
    private GameManager $gameManager;
    private ConfigManager $config;
    private Logger $logger;
    private array $bots = [];
    private array $botArenas = [];
    private array $botTasks = [];

    public function __construct(BedFight $plugin, GameManager $gameManager, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->gameManager = $gameManager;
        $this->config = $config;
        $this->logger = $plugin->getLoggerWrapper();
    }

    public function addBot(Arena $arena, string $difficulty = 'player'): ?Bot {
        if (!$this->config->isBotsEnabled()) return null;

        $arenaBots = $this->botArenas[$arena->getId()] ?? [];
        if (count($arenaBots) >= $this->config->getMaxBotsPerArena()) return null;

        $names = $this->config->getBotNames();
        $name = $this->getUniqueBotName($names, $arena);
        if ($name === null) return null;

        $bot = new Bot($name, $difficulty, $arena->getId(), $this->config->getBotDifficulty($difficulty));
        $this->bots[$name] = $bot;
        $this->botArenas[$arena->getId()][] = $name;
        $arena->addBot($name);

        $this->spawnBot($bot);
        $this->startBotAI($bot);

        return $bot;
    }

    public function removeBot(string $name): bool {
        $bot = $this->bots[$name] ?? null;
        if ($bot === null) return false;

        $this->despawnBot($bot);
        $this->stopBotAI($bot);

        $arenaId = $bot->getArenaId();
        if (isset($this->botArenas[$arenaId])) {
            $this->botArenas[$arenaId] = array_filter($this->botArenas[$arenaId], fn($n) => $n !== $name);
        }

        $arena = $this->plugin->getArenaManager()->getArena($arenaId);
        if ($arena !== null) {
            $arena->removeBot($name);
        }

        unset($this->bots[$name]);
        return true;
    }

    public function removeAllBots(): void {
        foreach (array_keys($this->bots) as $name) {
            $this->removeBot($name);
        }
    }

    public function removeBotsFromArena(string $arenaId): void {
        $bots = $this->botArenas[$arenaId] ?? [];
        foreach ($bots as $name) {
            $this->removeBot($name);
        }
    }

    public function fillArena(Arena $arena, int $targetPlayers): void {
        $current = $arena->getPlayerCount();
        $needed = $targetPlayers - $current;
        $difficulties = ['noob', 'player', 'pro'];
        
        for ($i = 0; $i < $needed; $i++) {
            $difficulty = $difficulties[array_rand($difficulties)];
            $this->addBot($arena, $difficulty);
        }
    }

    public function getBot(string $name): ?Bot {
        return $this->bots[$name] ?? null;
    }

    public function getBotsInArena(string $arenaId): array {
        $names = $this->botArenas[$arenaId] ?? [];
        return array_map(fn($n) => $this->bots[$n], $names);
    }

    private function getUniqueBotName(array $names, Arena $arena): ?string {
        shuffle($names);
        foreach ($names as $name) {
            $player = Server::getInstance()->getPlayerExact($name);
            $exists = false;
            foreach ($this->bots as $bot) {
                if ($bot->getName() === $name) {
                    $exists = true;
                    break;
                }
            }
            if ($player === null && !$exists) {
                return $name;
            }
        }
        return 'Bot_' . bin2hex(random_bytes(4));
    }

    private function spawnBot(Bot $bot): void {
        $arena = $this->plugin->getArenaManager()->getArena($bot->getArenaId());
        if ($arena === null) return;

        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $team = $this->gameManager->getPlugin()->getGameManager()->selectTeam($arena);
        $spawn = $arena->getSpawnForTeam($team);
        $position = new Position($spawn->x, $spawn->y, $spawn->z, $world);

        $bot->setPosition($position);
        $bot->setTeam($team);
        $bot->setEntityId(EntityIds::PLAYER);

        $pk = new AddEntityPacket();
        $pk->entityRuntimeId = $bot->getEntityId();
        $pk->entityUniqueId = $bot->getUniqueId();
        $pk->type = EntityIds::PLAYER;
        $pk->position = $position->asVector3();
        $pk->motion = new Vector3(0, 0, 0);
        $pk->yaw = 0;
        $pk->pitch = 0;
        $pk->headYaw = 0;
        $pk->attributes = [];
        $pk->entityMetadata = [
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $bot->getName()],
            Entity::DATA_ALWAYS_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
        ];
        $pk->entityLinks = [];

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    private function despawnBot(Bot $bot): void {
        $arena = $this->plugin->getArenaManager()->getArena($bot->getArenaId());
        if ($arena === null) return;

        $pk = new RemoveEntityPacket();
        $pk->entityUniqueId = $bot->getUniqueId();

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    private function startBotAI(Bot $bot): void {
        $taskId = $this->plugin->getScheduler()->scheduleRepeatingTask(new class($this, $bot) extends \pocketmine\scheduler\Task {
            private BotManager $manager;
            private Bot $bot;
            public function __construct(BotManager $manager, Bot $bot) {
                $this->manager = $manager;
                $this->bot = $bot;
            }
            public function onRun(): void {
                $this->manager->tickBot($this->bot);
            }
        }, 1);

        $this->botTasks[$bot->getName()] = $taskId;
    }

    private function stopBotAI(Bot $bot): void {
        if (isset($this->botTasks[$bot->getName()])) {
            $this->plugin->getScheduler()->cancelTask($this->botTasks[$bot->getName()]);
            unset($this->botTasks[$bot->getName()]);
        }
    }

    public function tickBot(Bot $bot): void {
        $arena = $this->plugin->getArenaManager()->getArena($bot->getArenaId());
        if ($arena === null) return;

        $game = $this->gameManager->getPlugin()->getGameManager()->getPlayerArena(
            \pocketmine\Server::getInstance()->getPlayerExact($bot->getName()) ?? new class {}
        );
        
        if ($game === null || $game->getId() !== $arena->getId()) return;

        $difficulty = $bot->getDifficulty();
        
        switch ($difficulty['pathfinding']) {
            case 'basic':
                $this->basicAI($bot, $arena);
                break;
            case 'advanced':
                $this->advancedAI($bot, $arena);
                break;
            case 'expert':
                $this->expertAI($bot, $arena);
                break;
        }
    }

    private function basicAI(Bot $bot, Arena $arena): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $position = $bot->getPosition();
        $target = $this->findNearestTarget($bot, $arena);

        if ($target !== null) {
            $this->moveTowards($bot, $target->getPosition());
            $this->attackIfClose($bot, $target);
        } else {
            $this->randomWalk($bot);
        }
    }

    private function advancedAI(Bot $bot, Arena $arena): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $target = $this->findBestTarget($bot, $arena);
        
        if ($target !== null) {
            $this->pathfindTo($bot, $target->getPosition());
            
            if ($this->canAttack($bot, $target)) {
                $this->attack($bot, $target);
            }
        } else {
            $this->defendBed($bot, $arena);
        }
    }

    private function expertAI(Bot $bot, Arena $arena): void {
        $this->advancedAI($bot, $arena);
        
        if (random_int(1, 100) <= 30) {
            $this->bridgeIfNeeded($bot, $arena);
        }
        
        if (random_int(1, 100) <= 20) {
            $this->buildDefense($bot, $arena);
        }
    }

    private function findNearestTarget(Bot $bot, Arena $arena): ?Player {
        $nearest = null;
        $minDist = PHP_FLOAT_MAX;
        $botPos = $bot->getPosition();

        foreach ($arena->getPlayers() as $playerName) {
            if ($playerName === $bot->getName()) continue;
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player === null) continue;
            
            $game = $this->gameManager->getPlugin()->getGameManager()->getPlayerArena($player);
            if ($game === null) continue;
            
            $playerTeam = $this->gameManager->getPlugin()->getGameManager()->getPlayerTeam(
                $this->gameManager->getPlugin()->getGameManager()->games[$arena->getId()] ?? new class{public function getTeams(){return ['blue'=>[],'red'=>[]];}},
                $playerName
            );
            $botTeam = $bot->getTeam();
            
            if ($playerTeam === $botTeam) continue;

            $dist = $botPos->distance($player->getPosition());
            if ($dist < $minDist) {
                $minDist = $dist;
                $nearest = $player;
            }
        }
        return $nearest;
    }

    private function findBestTarget(Bot $bot, Arena $arena): ?Player {
        $best = null;
        $bestScore = -1;
        $botPos = $bot->getPosition();

        foreach ($arena->getPlayers() as $playerName) {
            if ($playerName === $bot->getName()) continue;
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player === null) continue;
            
            $game = $this->gameManager->getPlugin()->getGameManager()->getPlayerArena($player);
            if ($game === null) continue;
            
            $playerTeam = $this->gameManager->getPlugin()->getGameManager()->getPlayerTeam(
                $this->gameManager->getPlugin()->getGameManager()->games[$arena->getId()] ?? new class{public function getTeams(){return ['blue'=>[],'red'=>[]];}},
                $playerName
            );
            $botTeam = $bot->getTeam();
            
            if ($playerTeam === $botTeam) continue;

            $dist = $botPos->distance($player->getPosition());
            $health = $player->getHealth();
            $score = (100 - $health) * 2 + (50 - min($dist, 50));
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $player;
            }
        }
        return $best;
    }

    private function moveTowards(Bot $bot, Vector3 $target): void {
        $current = $bot->getPosition();
        $diff = $target->subtract($current);
        $dist = $diff->length();
        
        if ($dist < 1) return;

        $speed = $bot->getDifficulty()['build_speed'] ?? 0.5;
        $move = $diff->normalize()->multiply($speed);
        $newPos = $current->add($move);
        
        $this->updateBotPosition($bot, $newPos);
    }

    private function pathfindTo(Bot $bot, Vector3 $target): void {
        $this->moveTowards($bot, $target);
    }

    private function randomWalk(Bot $bot): void {
        $current = $bot->getPosition();
        $newPos = $current->add(new Vector3(
            random_int(-100, 100) / 100,
            0,
            random_int(-100, 100) / 100
        ));
        $this->updateBotPosition($bot, $newPos);
    }

    private function defendBed(Bot $bot, Arena $arena): void {
        $bedPos = $arena->getBedForTeam($bot->getTeam());
        $botPos = $bot->getPosition();
        $dist = $botPos->distance($bedPos);
        
        if ($dist > 5) {
            $this->moveTowards($bot, $bedPos);
        }
    }

    private function bridgeIfNeeded(Bot $bot, Arena $arena): void {
    }

    private function buildDefense(Bot $bot, Arena $arena): void {
    }

    private function canAttack(Bot $bot, Player $target): bool {
        $dist = $bot->getPosition()->distance($target->getPosition());
        return $dist < 3.5;
    }

    private function attackIfClose(Bot $bot, Player $target): void {
        if ($this->canAttack($bot, $target)) {
            $this->attack($bot, $target);
        }
    }

    private function attack(Bot $bot, Player $target): void {
        $pk = new AnimatePacket();
        $pk->entityRuntimeId = $bot->getEntityId();
        $pk->action = AnimatePacket::ACTION_SWING_ARM;

        $arena = $this->plugin->getArenaManager()->getArena($bot->getArenaId());
        if ($arena === null) return;

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }

        $damage = $bot->getDifficulty()['combat_skill'] ?? 0.5;
        $target->attack(new \pocketmine\entity\damage\source\EntityDamageSource($bot), $damage * 6 + 2);
    }

    private function updateBotPosition(Bot $bot, Vector3 $position): void {
        $bot->setPosition(new Position($position->x, $position->y, $position->z, $bot->getPosition()->getWorld()));
        
        $pk = new MoveEntityPacket();
        $pk->entityRuntimeId = $bot->getEntityId();
        $pk->position = $position;
        $pk->yaw = 0;
        $pk->pitch = 0;
        $pk->headYaw = 0;
        $pk->mode = MoveEntityPacket::MODE_NORMAL;
        $pk->onGround = true;
        $pk->tick = 0;

        $arena = $this->plugin->getArenaManager()->getArena($bot->getArenaId());
        if ($arena === null) return;

        foreach ($arena->getPlayers() as $playerName) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }
}

class Bot {

    private string $name;
    private string $difficulty;
    private string $arenaId;
    private array $difficultyConfig;
    private int $entityId;
    private string $uniqueId;
    private Position $position;
    private string $team = 'blue';

    public function __construct(string $name, string $difficulty, string $arenaId, array $difficultyConfig) {
        $this->name = $name;
        $this->difficulty = $difficulty;
        $this->arenaId = $arenaId;
        $this->difficultyConfig = $difficultyConfig;
        $this->entityId = random_int(1000000, 9999999);
        $this->uniqueId = bin2hex(random_bytes(16));
    }

    public function getName(): string { return $this->name; }
    public function getDifficulty(): string { return $this->difficulty; }
    public function getArenaId(): string { return $this->arenaId; }
    public function getDifficultyConfig(): array { return $this->difficultyConfig; }
    public function getEntityId(): int { return $this->entityId; }
    public function getUniqueId(): string { return $this->uniqueId; }
    public function getPosition(): Position { return $this->position; }
    public function getTeam(): string { return $this->team; }

    public function setPosition(Position $position): void { $this->position = $position; }
    public function setTeam(string $team): void { $this->team = $team; }
}