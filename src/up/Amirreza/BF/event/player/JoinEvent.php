<?php

declare(strict_types=1);

namespace Amirreza\BF\event\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use Amirreza\BF\BFHelper;
use Amirreza\BF\trait\CFUTrait;

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