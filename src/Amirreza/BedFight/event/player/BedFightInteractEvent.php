<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\player;

use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;
use Amirreza\BedFight\utils\ExecutionUtils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class BedFightInteractEvent implements Listener
{

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $itemName = $item->getCustomName();
        ExecutionUtils::do()
            ->forPlayer($player)
            ->action("lobby_interact", function () use ($player, $itemName) {
                $bedfightHelper = BedFightHelper::get();
                switch ($itemName) {
                    case "BedFight":
                        $bedfightHelper->BedFightSimpleForm()->sendBFForm($player);
                        break;

                    case 'Duel':
                        $player->sendMessage(BedFightConstant::RBFFM . "Duel soon...");
                        break;

                    case 'About':
                        $player->sendMessage(BedFightConstant::L . BedFightConstant::NL);
                        $player->sendMessage(" ◜BedFight ⇒ v0.1.0-beta ◝");
                        $player->sendMessage("   Made By UnlimitedPowerHub");
                        $player->sendMessage(" ◟website: https://github.com/UnlimitedPowerHub/◞" . BedFightConstant::NL);
                        $player->sendMessage(BedFightConstant::L);
                        break;
                }
            });
    }
}