<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event;

use pocketmine\event\Listener;
use Amirreza\BedFight\BedFight;

class BedFightEventLoader implements Listener {

    public static function init(): void
    {
        $BedFight = BedFight::getInstance();
        $events = [
            [new player\BedFightJoinEvent(),$BedFight],
            [new setup\BedFightSetUpBreakEvent(),$BedFight],
        ];
        foreach ($events as $event) {
            BedFight::getInstance()->getServer()->getPluginManager()->registerEvents($event[0], $event[1]);
        }
    }
}