<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\setup;

use Amirreza\BedFight\BedFightHelper;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BedFightSetUpBreakEvent implements Listener
{

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        $BedFightSetUpSession = BedFightHelper::get()->BedFightSetUpSession();
        if (!$BedFightSetUpSession->isConnect($player->getName())) {
            return;
        }
        BedFightHelper::get()->BedFightHandler()
            ->BedFightSetUpBreakHandler()->handle($player, $item, $block);
    }
}