<?php

declare(strict_types=1);

namespace Amirreza\BF\command;

use Amirreza\BF\BFPluginBase;

class CommandLoader {

    public static function init(): void
    {
        BFPluginBase::getMe()->getServer()->getCommandMap()->register('bedfight', new BFDCommand);
        BFPluginBase::getMe()->getServer()->getCommandMap()->register('bedfightmanage', new BFMCommand);
    }
}