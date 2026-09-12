<?php

declare(strict_types=1);

namespace BedFight\Arena;

use BedFight\Core\BedFight;
use BedFight\Storage\StorageManager;
use BedFight\Config\ConfigManager;
use BedFight\Utils\Logger;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;

class Arena {

    public const STATUS_WAITING = 'waiting';
    public const STATUS_STARTING = 'starting';
    public const STATUS_IN_GAME = 'in_game';
    public const STATUS_ENDING = 'ending';
    public const STATUS_EMPTY = 'empty';

    private string $id;
    private string $name;
    private string $worldName;
    private Vector3 $blueSpawn;
    private Vector3 $blueBed;
    private Vector3 $redSpawn;
    private Vector3 $redBed;
    private string $status = self::STATUS_EMPTY;
    private int $createdAt;
    private int $updatedAt;
    private array $players = [];
    private array $bots = [];
    private ?Game $game = null;

    public function __construct(string $id, string $name, string $worldName) {
        $this->id = $id;
        $this->name = $name;
        $this->worldName = $worldName;
        $this->createdAt = time();
        $this->updatedAt = time();
    }

    public static function fromArray(array $data): self {
        $arena = new self($data['id'], $data['name'], $data['world']);
        $arena->blueSpawn = new Vector3($data['blue_spawn']['x'], $data['blue_spawn']['y'], $data['blue_spawn']['z']);
        $arena->blueBed = new Vector3($data['blue_bed']['x'], $data['blue_bed']['y'], $data['blue_bed']['z']);
        $arena->redSpawn = new Vector3($data['red_spawn']['x'], $data['red_spawn']['y'], $data['red_spawn']['z']);
        $arena->redBed = new Vector3($data['red_bed']['x'], $data['red_bed']['y'], $data['red_bed']['z']);
        $arena->status = $data['status'] ?? self::STATUS_EMPTY;
        $arena->createdAt = $data['created_at'] ?? time();
        $arena->updatedAt = $data['updated_at'] ?? time();
        return $arena;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'world' => $this->worldName,
            'blue_spawn' => ['x' => $this->blueSpawn->x, 'y' => $this->blueSpawn->y, 'z' => $this->blueSpawn->z],
            'blue_bed' => ['x' => $this->blueBed->x, 'y' => $this->blueBed->y, 'z' => $this->blueBed->z],
            'red_spawn' => ['x' => $this->redSpawn->x, 'y' => $this->redSpawn->y, 'z' => $this->redSpawn->z],
            'red_bed' => ['x' => $this->redBed->x, 'y' => $this->redBed->y, 'z' => $this->redBed->z],
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getWorldName(): string { return $this->worldName; }
    public function getBlueSpawn(): Vector3 { return $this->blueSpawn; }
    public function getBlueBed(): Vector3 { return $this->blueBed; }
    public function getRedSpawn(): Vector3 { return $this->redSpawn; }
    public function getRedBed(): Vector3 { return $this->redBed; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getUpdatedAt(): int { return $this->updatedAt; }
    public function getPlayers(): array { return $this->players; }
    public function getBots(): array { return $this->bots; }
    public function getGame(): ?Game { return $this->game; }

    public function setName(string $name): void { $this->name = $name; $this->touch(); }
    public function setWorldName(string $world): void { $this->worldName = $world; $this->touch(); }
    public function setBlueSpawn(Vector3 $pos): void { $this->blueSpawn = $pos; $this->touch(); }
    public function setBlueBed(Vector3 $pos): void { $this->blueBed = $pos; $this->touch(); }
    public function setRedSpawn(Vector3 $pos): void { $this->redSpawn = $pos; $this->touch(); }
    public function setRedBed(Vector3 $pos): void { $this->redBed = $pos; $this->touch(); }
    public function setStatus(string $status): void { $this->status = $status; $this->touch(); }
    public function setGame(?Game $game): void { $this->game = $game; }

    public function addPlayer(string $playerName): void {
        if (!in_array($playerName, $this->players)) {
            $this->players[] = $playerName;
            $this->touch();
        }
    }

    public function removePlayer(string $playerName): void {
        $this->players = array_filter($this->players, fn($p) => $p !== $playerName);
        $this->touch();
    }

    public function addBot(string $botName): void {
        if (!in_array($botName, $this->bots)) {
            $this->bots[] = $botName;
            $this->touch();
        }
    }

    public function removeBot(string $botName): void {
        $this->bots = array_filter($this->bots, fn($b) => $b !== $botName);
        $this->touch();
    }

    public function getPlayerCount(): int {
        return count($this->players) + count($this->bots);
    }

    public function isFull(int $maxPlayers): bool {
        return $this->getPlayerCount() >= $maxPlayers;
    }

    public function isReady(): bool {
        return $this->blueSpawn !== null && $this->blueBed !== null 
            && $this->redSpawn !== null && $this->redBed !== null;
    }

    public function getSpawnForTeam(string $team): Vector3 {
        return $team === 'blue' ? $this->blueSpawn : $this->redSpawn;
    }

    public function getBedForTeam(string $team): Vector3 {
        return $team === 'blue' ? $this->blueBed : $this->redBed;
    }

    public function getOppositeTeam(string $team): string {
        return $team === 'blue' ? 'red' : 'blue';
    }

    private function touch(): void {
        $this->updatedAt = time();
    }
}

class Game {

    private string $id;
    private string $arenaId;
    private array $teams = ['blue' => [], 'red' => []];
    private array $beds = ['blue' => true, 'red' => true];
    private int $startTime;
    private ?int $endTime = null;
    private string $status = 'waiting';
    private int $taskId = 0;

    public function __construct(string $id, string $arenaId) {
        $this->id = $id;
        $this->arenaId = $arenaId;
        $this->startTime = time();
    }

    public static function fromArray(array $data): self {
        $game = new self($data['id'], $data['arena_id']);
        $game->teams = $data['teams'] ?? ['blue' => [], 'red' => []];
        $game->beds = $data['beds'] ?? ['blue' => true, 'red' => true];
        $game->startTime = $data['started_at'] ?? time();
        $game->endTime = $data['ended_at'] ?? null;
        $game->status = $data['status'] ?? 'waiting';
        return $game;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'arena_id' => $this->arenaId,
            'teams' => $this->teams,
            'beds' => $this->beds,
            'started_at' => $this->startTime,
            'ended_at' => $this->endTime,
            'status' => $this->status
        ];
    }

    public function getId(): string { return $this->id; }
    public function getArenaId(): string { return $this->arenaId; }
    public function getTeams(): array { return $this->teams; }
    public function getBeds(): array { return $this->beds; }
    public function getStartTime(): int { return $this->startTime; }
    public function getEndTime(): ?int { return $this->endTime; }
    public function getStatus(): string { return $this->status; }
    public function getTaskId(): int { return $this->taskId; }

    public function addPlayer(string $team, string $playerName): void {
        if (!in_array($playerName, $this->teams[$team])) {
            $this->teams[$team][] = $playerName;
        }
    }

    public function removePlayer(string $playerName): void {
        $this->teams['blue'] = array_filter($this->teams['blue'], fn($p) => $p !== $playerName);
        $this->teams['red'] = array_filter($this->teams['red'], fn($p) => $p !== $playerName);
    }

    public function destroyBed(string $team): void {
        $this->beds[$team] = false;
    }

    public function isBedAlive(string $team): bool {
        return $this->beds[$team] ?? false;
    }

    public function getAliveTeams(): array {
        $alive = [];
        foreach ($this->beds as $team => $alive_) {
            if ($alive_) $alive[] = $team;
        }
        return $alive;
    }

    public function getWinner(): ?string {
        $alive = $this->getAliveTeams();
        if (count($alive) === 1) return $alive[0];
        return null;
    }

    public function setStatus(string $status): void { $this->status = $status; }
    public function setEndTime(int $time): void { $this->endTime = $time; }
    public function setTaskId(int $id): void { $this->taskId = $id; }
}