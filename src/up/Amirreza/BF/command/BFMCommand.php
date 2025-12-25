<?php

declare(strict_types=1);

namespace Amirreza\BF\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use Amirreza\BF\BFHelper;
use Amirreza\BF\trait\CFUTrait;

class BFMCommand extends Command {

    use CFUTrait;

    public function __construct(
        string $name = "bedfightmanage",
        Translatable|string $description = self::RBFFM."Manage ".self::BFP,
        Translatable|string|null $usageMessage = null,
        array $aliases = ['bfm']
    ) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        self::setPermission("up.bf.op");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(self::RBFFW."Use It In-Game");
            return true;
        }
        BFHelper::get()->BFSForm()->sendBFMForm($sender);
        return true;
        // TODO: Implement execute() method.
    }

}
