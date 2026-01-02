<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event;

use Amirreza\BedFight\BedFight;
use pocketmine\event\Listener;

class BedFightEventLoader implements Listener {

    public static function init(): void
    {
        $BedFight = BedFight::getInstance();
        $events = [
            [new player\BedFightJoinEvent(), $BedFight],
            [new setup\BedFightSetUpBreakEvent(), $BedFight],
            [new player\BedFightInventoryEvent(), $BedFight],
            [new player\BedFightInteractEvent(), $BedFight],
            [new player\BedFightQuitEvent(), $BedFight]
        ];
        foreach ($events as $event) {
            $BedFight->getServer()->getPluginManager()->registerEvents($event[0], $event[1]);
        }
    }
}