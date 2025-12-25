<?php

declare(strict_types=1);

namespace Amirreza\BF\event;

use pocketmine\event\Listener;
use Amirreza\BF\BFPluginBase;

class EventLoader implements Listener {

    public static function init(): void
    {
        BFPluginBase::getMe()->getServer()->getPluginManager()->registerEvents(new player\JoinEvent(),BFPluginBase::getMe());
    }
}