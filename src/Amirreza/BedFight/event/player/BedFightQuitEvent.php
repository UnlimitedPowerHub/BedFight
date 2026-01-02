<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\player;

use Amirreza\BedFight\utils\ExecutionUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class BedFightQuitEvent implements Listener
{

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        ExecutionUtils::clear($player->getName());
    }
}