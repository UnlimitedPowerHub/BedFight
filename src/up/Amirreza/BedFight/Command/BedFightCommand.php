<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BedFightCommand extends Command {

    public function __construct()
    {
        parent::__construct("bedfight", "BedFight Command","Usage: /bedfight" ,['bf']);
        $this->setPermission("bedfight.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $sender->sendMessage("Plugin is still developing!");
        // TODO: Implement execute() method.
    }
}