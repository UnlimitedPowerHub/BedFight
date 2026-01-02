<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\setup;

use Amirreza\BedFight\BedFightHelper;
use Exception;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BedFightSetUpBreakEvent implements Listener
{

    /**
     * @throws Exception
     */
    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        $BedFightSetUpSession = BedFightHelper::get()->BedFightSetUpSession();

        if (!$BedFightSetUpSession->isConnect($player->getName())) {
            return;
        }

        if (in_array(
            $item->getName(),
            ['SetBlueSpawn','SetBlueBed','SetRedSpawn','SetRedBed','Confirm','Cancel']
        )) {
            return;
        }
        $event->cancel();
        BedFightHelper::get()->BedFightHandler()->BedFightSetUpBreakHandler()->handle(
            $player, $item, $block
        );
    }
}