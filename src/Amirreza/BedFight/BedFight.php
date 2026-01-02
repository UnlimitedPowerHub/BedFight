<?php

namespace Amirreza\BedFight;

use Amirreza\BedFight\constant\BedFightConstant;
use pocketmine\plugin\PluginBase;

class BedFight extends PluginBase {

    private static BedFight $instance;

    public static function getInstance(): BedFight {
        return self::$instance;
    }

    protected function onLoad(): void
    {
        self::$instance = $this;
        $this->getLogger()->info(
            BedFightConstant::RBFFM . "Loaded!"
        );
    }

    protected function onEnable(): void
    {
        parent::onEnable();
        BedFightHelper::init();
        $this->getLogger()->info(
            BedFightConstant::RBFFM."Enabled!"
        );
        $o = BedFightHelper::get()->BedFightArenaStorage()->getEmptyArenaWorld();
        print $o;
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info(
            BedFightConstant::RBFFM."Disabled!"
        );
    }
}