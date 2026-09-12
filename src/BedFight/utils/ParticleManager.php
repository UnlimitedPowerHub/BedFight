<?php

declare(strict_types=1);

namespace BedFight\Utils;

use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\particle\Particle;
use pocketmine\player\Player;
use pocketmine\world\World;

class ParticleManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private bool $optimized;

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
        $this->config = $plugin->getConfigManager();
        $this->optimized = $this->config->isOptimizeParticles();
    }

    public function spawn(World $world, Vector3 $position, Particle $particle, array $recipients = []): void {
        if ($this->optimized && !empty($recipients)) {
            $pk = LevelEventPacket::create($particle->getId(), $position, $particle->getData());
            foreach ($recipients as $player) {
                if ($player->getWorld() === $world) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        } else {
            $world->addParticle($position, $particle);
        }
    }

    public function spawnToAll(World $world, Vector3 $position, Particle $particle): void {
        $world->addParticle($position, $particle);
    }

    public function spawnBedDestroy(Arena $arena, string $team): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $bedPos = $arena->getBedForTeam($team);
        $position = new Vector3($bedPos->x, $bedPos->y + 1, $bedPos->z);
        $config = $this->config->getParticles('bed_destroy');
        $particle = $this->createParticle($config['type'] ?? 'EXPLOSION');
        
        for ($i = 0; $i < ($config['count'] ?? 20); $i++) {
            $offset = new Vector3(
                (random_int(-100, 100) / 100) * 0.5,
                (random_int(0, 100) / 100) * 0.5,
                (random_int(-100, 100) / 100) * 0.5
            );
            $this->spawn($world, $position->add($offset), $particle);
        }
    }

    public function spawnKill(Arena $arena, Vector3 $position): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $config = $this->config->getParticles('player_kill');
        $particle = $this->createParticle($config['type'] ?? 'HEART');
        
        for ($i = 0; $i < ($config['count'] ?? 10); $i++) {
            $offset = new Vector3(
                (random_int(-50, 50) / 100) * 0.3,
                (random_int(0, 100) / 100) * 0.5,
                (random_int(-50, 50) / 100) * 0.3
            );
            $this->spawn($world, $position->add($offset), $particle);
        }
    }

    public function spawnWin(Arena $arena, Vector3 $position): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $config = $this->config->getParticles('win');
        $particle = $this->createParticle($config['type'] ?? 'FIREWORK');
        
        for ($i = 0; $i < ($config['count'] ?? 5); $i++) {
            $offset = new Vector3(
                (random_int(-100, 100) / 100) * 1.0,
                (random_int(0, 100) / 100) * 2.0,
                (random_int(-100, 100) / 100) * 1.0
            );
            $this->spawn($world, $position->add($offset), $particle);
        }
    }

    public function spawnRespawn(Arena $arena, Vector3 $position): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $config = $this->config->getParticles('respawn');
        $particle = $this->createParticle($config['type'] ?? 'VILLAGER_HAPPY');
        
        for ($i = 0; $i < ($config['count'] ?? 15); $i++) {
            $offset = new Vector3(
                (random_int(-50, 50) / 100) * 0.5,
                (random_int(0, 100) / 100) * 0.5,
                (random_int(-50, 50) / 100) * 0.5
            );
            $this->spawn($world, $position->add($offset), $particle);
        }
    }

    private function createParticle(string $type): Particle {
        return match (strtoupper($type)) {
            'EXPLOSION' => \pocketmine\particle\HugeExplodeSeedParticle::create(),
            'HEART' => \pocketmine\particle\HeartParticle::create(),
            'FIREWORK' => \pocketmine\particle\FireworkParticle::create(),
            'VILLAGER_HAPPY' => \pocketmine\particle\VillagerHappyParticle::create(),
            'SMOKE' => \pocketmine\particle\LargeSmokeParticle::create(),
            'FLAME' => \pocketmine\particle\FlameParticle::create(),
            'NOTE' => \pocketmine\particle\NoteParticle::create(),
            'PORTAL' => \pocketmine\particle\PortalParticle::create(),
            'ENCHANT' => \pocketmine\particle\EnchantParticle::create(),
            'CRIT' => \pocketmine\particle\CritParticle::create(),
            'MAGIC_CRIT' => \pocketmine\particle\MagicCritParticle::create(),
            default => \pocketmine\particle\HeartParticle::create()
        };
    }
}