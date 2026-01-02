<?php

declare(strict_types=1);

namespace Amirreza\BedFight\handler\setup;

use Amirreza\BedFight\BedFightHelper;
use pocketmine\player\Player;

class BedFightSetUpStepHandler
{

    public function handle(Player $player): void
    {
        BedFightHelper::get()->BedFightDefaultItem()->setSetUpStepItems($player);
        BedFightHelper::get()->BedFightSetUpStepManager()->nextStep($player->getName());
    }
}