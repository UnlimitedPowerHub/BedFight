<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Event;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class SetUpEvent implements Listener {

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        if ($block->getTypeId() === VanillaBlocks::BEACON()->getTypeId()) {
            return;
        }
    }
}