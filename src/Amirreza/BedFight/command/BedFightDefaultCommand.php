<?php

declare(strict_types=1);

namespace Amirreza\BedFight\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;

class BedFightDefaultCommand extends Command {


    public function __construct(
        string $name = "bedfight",
        Translatable|string $description = BedFightConstant::BedFightPrefix." Command",
        Translatable|string|null $usageMessage = null,
        array $aliases = ['bf']
    ) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        self::setPermission("up.bf.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(BedFightConstant::RBFFW."Use It In-Game");
            return true;
        }
        BedFightHelper::get()->BedFightSimpleForm()->sendBFForm($sender);
        return true;
        // TODO: Implement execute() method.
    }

}
