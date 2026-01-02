<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

use Amirreza\BedFight\BedFight;
use Amirreza\BedFight\BedFightHelper;
use pocketmine\player\Player;
use pocketmine\world\World;

class BedFightGameManager {

    public function prepareGame(
        Player $player1,
        Player $player2,
        array $arenaData,
    ) : void {
        $bedfight = BedFight::getInstance();
        $helper = BedFightHelper::get();
    }
}