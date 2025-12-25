<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;

class BedFightJoinEvent implements Listener {

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $player->sendMessage(BedFightConstant::RBFFM."Welcome ༼ つ ◕_◕ ༽つ");
        $player->getInventory()->clearAll();
        BedFightHelper::get()->BedFightDefaultItem()->setLobbyItems($player);
        $event->setJoinMessage('');
    }
}