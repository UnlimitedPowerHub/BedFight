<?php

declare(strict_types=1);

namespace BedFight\Utils;

use BedFight\Core\BedFight;
use BedFight\Config\ConfigManager;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\player\Player;
use pocketmine\world\World;

class SoundManager {

    private BedFight $plugin;
    private ConfigManager $config;
    private bool $optimized;

    private array $soundMap = [
        'join_queue' => 'random.pop',
        'leave_queue' => 'random.click',
        'game_start' => 'mob.enderdragon.growl',
        'game_end' => 'random.levelup',
        'bed_destroy' => 'random.explode',
        'kill' => 'random.orb',
        'death' => 'mob.zombie.death',
        'respawn' => 'random.pop',
        'click' => 'random.click',
        'win' => 'random.levelup',
        'lose' => 'mob.zombie.death',
    ];

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
        $this->config = $plugin->getConfigManager();
        $this->optimized = $this->config->isOptimizeSounds();
    }

    public function play(Player $player, string $soundKey): void {
        $sound = $this->config->getSounds($soundKey) ?? $this->soundMap[$soundKey] ?? 'random.click';
        $this->playSound($player, $sound);
    }

    public function playAt(World $world, Vector3 $position, string $soundKey, array $recipients = []): void {
        $sound = $this->config->getSounds($soundKey) ?? $this->soundMap[$soundKey] ?? 'random.click';
        
        if ($this->optimized && !empty($recipients)) {
            $pk = LevelSoundEventPacket::create($this->getSoundId($sound), $position, 1.0, 1.0);
            foreach ($recipients as $player) {
                if ($player->getWorld() === $world) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        } else {
            $world->addSound($position, $this->getSoundId($sound));
        }
    }

    public function playToArena(Arena $arena, string $soundKey): void {
        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) return;

        $recipients = [];
        foreach ($arena->getPlayers() as $playerName) {
            $player = \pocketmine\Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null) {
                $recipients[] = $player;
            }
        }

        $bedPos = $arena->getBedForTeam('blue');
        $position = new Vector3($bedPos->x, $bedPos->y, $bedPos->z);
        $this->playAt($world, $position, $soundKey, $recipients);
    }

    private function playSound(Player $player, string $sound): void {
        if ($this->optimized) {
            $pk = LevelSoundEventPacket::create($this->getSoundId($sound), $player->getPosition(), 1.0, 1.0);
            $player->getNetworkSession()->sendDataPacket($pk);
        } else {
            $player->getWorld()->addSound($player->getPosition(), $this->getSoundId($sound));
        }
    }

    private function getSoundId(string $sound): int {
        return match ($sound) {
            'random.pop' => LevelSoundEventPacket::SOUND_POP,
            'random.click' => LevelSoundEventPacket::SOUND_CLICK,
            'random.explode' => LevelSoundEventPacket::SOUND_EXPLODE,
            'random.levelup' => LevelSoundEventPacket::SOUND_LEVEL_UP,
            'random.orb' => LevelSoundEventPacket::SOUND_ORB,
            'mob.enderdragon.growl' => LevelSoundEventPacket::SOUND_ENDERDRAGON_GROWL,
            'mob.zombie.death' => LevelSoundEventPacket::SOUND_ZOMBIE_DEATH,
            default => LevelSoundEventPacket::SOUND_CLICK
        };
    }
}