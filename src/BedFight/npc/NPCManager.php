<?php

declare(strict_types=1);

namespace BedFight\NPC;

use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use BedFight\Storage\StorageManager;
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
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class NPCManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private StorageManager $storage;
    private Logger $logger;
    private array $npcs = [];
    private array $npcArenas = [];

    public function __construct(BedFight $plugin, ConfigManager $config) {
        $this->plugin = $plugin;
        $this->config = $config;
        $this->storage = $plugin->getStorage();
        $this->logger = $plugin->getLoggerWrapper();
    }

    public function loadNPCs(): void {
        $data = $this->storage->getAll('npcs');
        foreach ($data as $id => $npcData) {
            try {
                $npc = NPC::fromArray($npcData);
                $this->npcs[$id] = $npc;
                if ($npc->getArenaId() !== null) {
                    $this->npcArenas[$npc->getArenaId()][] = $id;
                }
            } catch (\Throwable $e) {
                $this->logger->error("Failed to load NPC $id: " . $e->getMessage());
            }
        }
        $this->logger->info("Loaded " . count($this->npcs) . " NPCs");
    }

    public function createNPC(Player $creator, string $name, ?string $arenaId = null, string $skin = ''): NPC {
        $position = $creator->getPosition();
        $id = 'npc_' . bin2hex(random_bytes(8));
        
        $npc = new NPC($id, $name, $position, $arenaId, $skin);
        $this->npcs[$id] = $npc;
        
        if ($arenaId !== null) {
            $this->npcArenas[$arenaId][] = $id;
        }
        
        $this->storage->set('npcs', $id, $npc->toArray());
        $this->spawnNPC($npc);
        
        return $npc;
    }

    public function removeNPC(string $id): bool {
        $npc = $this->npcs[$id] ?? null;
        if ($npc === null) return false;

        $this->despawnNPC($npc);
        
        if ($npc->getArenaId() !== null) {
            $this->npcArenas[$npc->getArenaId()] = array_filter(
                $this->npcArenas[$npc->getArenaId()] ?? [],
                fn($n) => $n !== $id
            );
        }
        
        unset($this->npcs[$id]);
        $this->storage->delete('npcs', $id);
        return true;
    }

    public function getNPC(string $id): ?NPC {
        return $this->npcs[$id] ?? null;
    }

    public function getAllNPCs(): array {
        return $this->npcs;
    }

    public function getNPCsInArena(string $arenaId): array {
        $ids = $this->npcArenas[$arenaId] ?? [];
        return array_map(fn($id) => $this->npcs[$id], $ids);
    }

    public function spawnNPC(NPC $npc): void {
        $world = $npc->getPosition()->getWorld();
        if ($world === null) return;

        $pk = new AddEntityPacket();
        $pk->entityRuntimeId = $npc->getEntityId();
        $pk->entityUniqueId = $npc->getUniqueId();
        $pk->type = EntityIds::PLAYER;
        $pk->position = $npc->getPosition()->asVector3();
        $pk->motion = new Vector3(0, 0, 0);
        $pk->yaw = $npc->getYaw();
        $pk->pitch = $npc->getPitch();
        $pk->headYaw = $npc->getHeadYaw();
        $pk->attributes = [];
        $pk->entityMetadata = [
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $npc->getName()],
            Entity::DATA_ALWAYS_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
            Entity::DATA_NAMETAG_COLOR => [Entity::DATA_TYPE_STRING, $npc->getNameColor()],
        ];
        $pk->entityLinks = [];

        if ($npc->getSkin() !== '') {
            $pk->entityMetadata[Entity::DATA_SKIN_ID] = [Entity::DATA_TYPE_STRING, $npc->getSkin()];
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->getWorld() === $world) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public function despawnNPC(NPC $npc): void {
        $pk = new RemoveEntityPacket();
        $pk->entityUniqueId = $npc->getUniqueId();

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->getWorld() === $npc->getPosition()->getWorld()) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public function despawnAll(): void {
        foreach ($this->npcs as $npc) {
            $this->despawnNPC($npc);
        }
    }

    public function despawnFromArena(string $arenaId): void {
        $npcs = $this->getNPCsInArena($arenaId);
        foreach ($npcs as $npc) {
            $this->despawnNPC($npc);
        }
    }

    public function teleportNPC(string $id, Position $position): bool {
        $npc = $this->npcs[$id] ?? null;
        if ($npc === null) return false;

        $oldWorld = $npc->getPosition()->getWorld();
        $npc->setPosition($position);
        
        $pk = new MoveEntityPacket();
        $pk->entityRuntimeId = $npc->getEntityId();
        $pk->position = $position->asVector3();
        $pk->yaw = $npc->getYaw();
        $pk->pitch = $npc->getPitch();
        $pk->headYaw = $npc->getHeadYaw();
        $pk->mode = MoveEntityPacket::MODE_TELEPORT;
        $pk->onGround = true;
        $pk->tick = 0;

        if ($oldWorld !== null) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($player->getWorld() === $oldWorld || $player->getWorld() === $position->getWorld()) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }

        $this->storage->set('npcs', $id, $npc->toArray());
        return true;
    }

    public function setNPCRotation(string $id, float $yaw, float $pitch, float $headYaw): bool {
        $npc = $this->npcs[$id] ?? null;
        if ($npc === null) return false;

        $npc->setRotation($yaw, $pitch, $headYaw);
        
        $pk = new MoveEntityPacket();
        $pk->entityRuntimeId = $npc->getEntityId();
        $pk->position = $npc->getPosition()->asVector3();
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->headYaw = $headYaw;
        $pk->mode = MoveEntityPacket::MODE_ROTATION;
        $pk->onGround = true;
        $pk->tick = 0;

        $world = $npc->getPosition()->getWorld();
        if ($world !== null) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($player->getWorld() === $world) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }

        return true;
    }

    public function setNPCSkin(string $id, string $skinId): bool {
        $npc = $this->npcs[$id] ?? null;
        if ($npc === null) return false;

        $npc->setSkin($skinId);
        $this->storage->set('npcs', $id, $npc->toArray());
        
        $pk = new SetEntityDataPacket();
        $pk->entityRuntimeId = $npc->getEntityId();
        $pk->entityMetadata = [
            Entity::DATA_SKIN_ID => [Entity::DATA_TYPE_STRING, $skinId]
        ];

        $world = $npc->getPosition()->getWorld();
        if ($world !== null) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($player->getWorld() === $world) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
        return true;
    }

    public function animateNPC(string $id, int $action): bool {
        $npc = $this->npcs[$id] ?? null;
        if ($npc === null) return false;

        $pk = new AnimatePacket();
        $pk->entityRuntimeId = $npc->getEntityId();
        $pk->action = $action;

        $world = $npc->getPosition()->getWorld();
        if ($world !== null) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($player->getWorld() === $world) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
        return true;
    }
}

class NPC {

    private string $id;
    private string $name;
    private Position $position;
    private ?string $arenaId;
    private string $skin;
    private float $yaw = 0;
    private float $pitch = 0;
    private float $headYaw = 0;
    private string $nameColor = 'white';
    private int $entityId;
    private string $uniqueId;

    public function __construct(string $id, string $name, Position $position, ?string $arenaId = null, string $skin = '') {
        $this->id = $id;
        $this->name = $name;
        $this->position = $position;
        $this->arenaId = $arenaId;
        $this->skin = $skin;
        $this->entityId = random_int(1000000, 9999999);
        $this->uniqueId = bin2hex(random_bytes(16));
    }

    public static function fromArray(array $data): self {
        $npc = new self(
            $data['id'],
            $data['name'],
            new Position($data['x'], $data['y'], $data['z'], $data['world']),
            $data['arena_id'] ?? null,
            $data['skin'] ?? ''
        );
        $npc->yaw = $data['yaw'] ?? 0;
        $npc->pitch = $data['pitch'] ?? 0;
        $npc->headYaw = $data['head_yaw'] ?? 0;
        $npc->nameColor = $data['name_color'] ?? 'white';
        $npc->entityId = $data['entity_id'] ?? random_int(1000000, 9999999);
        $npc->uniqueId = $data['unique_id'] ?? bin2hex(random_bytes(16));
        return $npc;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->position->x,
            'y' => $this->position->y,
            'z' => $this->position->z,
            'world' => $this->position->getWorld()->getFolderName(),
            'arena_id' => $this->arenaId,
            'skin' => $this->skin,
            'yaw' => $this->yaw,
            'pitch' => $this->pitch,
            'head_yaw' => $this->headYaw,
            'name_color' => $this->nameColor,
            'entity_id' => $this->entityId,
            'unique_id' => $this->uniqueId
        ];
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPosition(): Position { return $this->position; }
    public function getArenaId(): ?string { return $this->arenaId; }
    public function getSkin(): string { return $this->skin; }
    public function getYaw(): float { return $this->yaw; }
    public function getPitch(): float { return $this->pitch; }
    public function getHeadYaw(): float { return $this->headYaw; }
    public function getNameColor(): string { return $this->nameColor; }
    public function getEntityId(): int { return $this->entityId; }
    public function getUniqueId(): string { return $this->uniqueId; }

    public function setPosition(Position $position): void { $this->position = $position; }
    public function setRotation(float $yaw, float $pitch, float $headYaw): void {
        $this->yaw = $yaw;
        $this->pitch = $pitch;
        $this->headYaw = $headYaw;
    }
    public function setSkin(string $skin): void { $this->skin = $skin; }
    public function setNameColor(string $color): void { $this->nameColor = $color; }
}