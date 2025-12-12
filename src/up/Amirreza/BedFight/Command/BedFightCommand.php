<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use up\Amirreza\BedFight\BedFight;

class BedFightCommand extends Command {

    public function __construct()
    {
        parent::__construct("bedfight", "BedFight Command","Usage: /bedfight" ,['bf']);
        $this->setPermission("bedfight.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof Player) {
            BedFight::getInstance()->getGameForm()->sendBedFightForm($sender);
        } else{
            $sender->sendMessage("Please Use This Command IN-GAME!");
        }

        // TODO: Implement execute() method.
    }
}