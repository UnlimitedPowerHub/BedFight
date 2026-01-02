<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\player;

use Amirreza\BedFight\BedFightHelper;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class BedFightInventoryEvent implements Listener
{

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        if ($player->isCreative()) {
            return;
        }

        if ($this->shouldCancel($player)) {
            $event->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player->isCreative()) {
            return;
        }

        if ($this->shouldCancel($player)) {
            $event->cancel();
        }
    }

    private function shouldCancel(Player $player): int
    {
        return !BedFightHelper::get()->BedFightGameSession()->isConnect($player->getName())
            & !BedFightHelper::get()->BedFightSetUpSession()->isConnect($player->getName());
    }

}