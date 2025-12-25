<?php

declare(strict_types=1);

namespace Amirreza\BedFight\storage;

use Exception;
use pocketmine\lang\Translatable;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\storage\storages\SQLiteStorage;
use Amirreza\BedFight\constant\BedFightArenaStatusConstant;

class BedFightArenaStorage
{

    private string $storage_name = "arenas";

    /**
     * @throws Exception
     */
    public function makeArena(
        Translatable|string $arenaName
    ): void
    {
        $sqlite = new SQLiteStorage($this->storage_name);
        $sqlite->set($arenaName, BedFightHelper::get()->BedFightArenaManager()->getReadyArenaData());
    }
}