<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Manager;


use pocketmine\player\Player;
use up\Amirreza\BedFight\BedFight;

class GameManager {

    public function startGame(array $players, string $map): void {
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        $gameStorage = $bedfight->getGameStorage();
    }
    
    public function endGame(Player $player): void {
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        $gameStorage = $bedfight->getGameStorage();
    }
}