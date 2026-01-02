<?php

declare(strict_types=1);

namespace Amirreza\BedFight\utils;

use pocketmine\player\Player;

class ExecutionUtils {

    /** @var array<string, array<string, float>> */
    private static array $cooldowns = [];

    private ?Player $player = null;

    public static function do(): self {
        return new self();
    }


    public function forPlayer(Player $player): self {
        $this->player = $player;
        return $this;
    }

    public function action(string $actionId, callable $callback, float $time = 0.5): void {
        if ($this->player === null) {
            return;
        }

        $playerName = $this->player->getName();
        $currentTime = microtime(true);

        if (isset(self::$cooldowns[$playerName][$actionId])) {
            $lastTime = self::$cooldowns[$playerName][$actionId];
            if (($currentTime - $lastTime) < $time) {
                return;
            }
        }

        self::$cooldowns[$playerName][$actionId] = $currentTime;
        $callback();
    }

    public static function clear(string $playerName): void {
        unset(self::$cooldowns[$playerName]);
    }
}