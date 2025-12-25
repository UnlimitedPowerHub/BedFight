<?php

declare(strict_types=1);

namespace Amirreza\BedFight\command;

use Amirreza\BedFight\BedFight;

class CommandLoader {

    public static function init(): void
    {
        $commands = [
            ['bedfight', new BedFightDefaultCommand],
            ['bedfightmanage' , new BedFightManageCommand],
        ];
        foreach ($commands as $command) {
            BedFight::getInstance()->getServer()->getCommandMap()->register($command[0], $command[1]);
        }
    }
}