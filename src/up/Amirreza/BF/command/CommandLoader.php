<?php

declare(strict_types=1);

namespace up\Amirreza\BF\command;

use up\Amirreza\BF\BFPluginBase;

class CommandLoader {

    public static function init(): void
    {
        BFPluginBase::getMe()->getServer()->getCommandMap()->register('bedfight', new BFDCommand);
        BFPluginBase::getMe()->getServer()->getCommandMap()->register('bedfightmanage', new BFMCommand);
    }
}