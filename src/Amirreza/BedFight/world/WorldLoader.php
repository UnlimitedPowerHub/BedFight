<?php

declare(strict_types=1);

namespace Amirreza\BedFight\world;

use Amirreza\BedFight\BedFight;
use Amirreza\BedFight\utils\WorldUtils;

class WorldLoader {

    public static ?array $worlds = [];

    public static function init(): void {
        $worldManager = BedFight::getInstance()->getServer()->getWorldManager();
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