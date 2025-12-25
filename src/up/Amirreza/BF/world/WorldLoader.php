<?php

declare(strict_types=1);

namespace Amirreza\BF\world;

use czechpmdevs\multiworld\MultiWorld;
use czechpmdevs\multiworld\util\WorldUtils;
use Amirreza\BF\BFPluginBase;

class WorldLoader {

    public static ?array $worlds = [];

    public static function init(): void {
        $worldManager = BFPluginBase::getMe()->getServer()->getWorldManager();
        foreach (WorldUtils::getAllWorlds() as $worldName) {
            if (!$worldManager->isWorldLoaded($worldName)) {
                $worldManager->loadWorld($worldName);
            }
            self::$worlds[] = $worldName;
        }
    }

    public static function getWorlds(): array {
        return self::$worlds;
    }

}