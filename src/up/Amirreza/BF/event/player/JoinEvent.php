<?php

declare(strict_types=1);

namespace up\Amirreza\BF\event\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use up\Amirreza\BF\BFHelper;
use up\Amirreza\BF\trait\CFUTrait;

class JoinEvent implements Listener {

    use CFUTrait;

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $player->sendMessage(self::RBFFM."Welcome ༼ つ ◕_◕ ༽つ");
        $player->getInventory()->clearAll();
        BFHelper::get()->IFDefault()->setLobbyItems($player);
        $event->setJoinMessage('');
    }
}