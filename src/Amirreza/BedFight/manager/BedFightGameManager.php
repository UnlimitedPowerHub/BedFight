<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

use Amirreza\BedFight\BedFight;
use Amirreza\BedFight\BedFightHelper;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;

class BedFightGameManager {

    public function prepareGame(
        Player $player1,
        Player $player2,
        string $arenaName,
        array $arenaData,
    ) : void {
        $bedfight = BedFight::getInstance();
        $helper = BedFightHelper::get();
        $worldName = $arenaData['world'];
        $world = $bedfight->getServer()->getWorldManager()->getWorldByName($worldName);
        $teams = $arenaData['team'];
        $blueteam = $teams['blue'];
        $bluex = $blueteam['x'];
        $bluey = $blueteam['y'];
        $bluez = $blueteam['z'];
        $redteam = $teams['red'];
        $redx = $redteam['x'];
        $redy = $redteam['y'];
        $redz = $redteam['z'];
        $blueTeamPos = new Position($bluex, $bluey, $bluez, $world);
        $redTeamPos = new Position($redx, $redy, $redz, $world);
        $player1->teleport($blueTeamPos);
        $player2->teleport($redTeamPos);
    }
}