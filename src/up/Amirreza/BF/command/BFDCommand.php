<?php

declare(strict_types=1);

namespace Amirreza\BF\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use Amirreza\BF\BFHelper;
use Amirreza\BF\trait\CFUTrait;

class BFDCommand extends Command {

    use CFUTrait;

    public function __construct(
        string $name = "bedfight",
        Translatable|string $description = self::BFP." Command",
        Translatable|string|null $usageMessage = null,
        array $aliases = ['bf']
    ) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        self::setPermission("up.bf.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(self::RBFFW."Use It In-Game");
            return true;
        }
        BFHelper::get()->BFSForm()->sendBFForm($sender);
        return true;
        // TODO: Implement execute() method.
    }

}
