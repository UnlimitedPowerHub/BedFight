<?php

declare(strict_types=1);

namespace up\Amirreza\BF\storage;

use Exception;
use pocketmine\lang\Translatable;
use up\Amirreza\BF\BFHelper;
use up\Amirreza\BF\storage\storages\SQLiteStorage;
use up\Amirreza\BF\trait\BFSTrait;

class BFAStorage
{

    use BFSTrait;

    private string $storage_name = "arenas";

    /**
     * @throws Exception
     */
    public function makeArena(
        Translatable|string $arenaName
    ): void
    {
        $sqlite = new SQLiteStorage($this->storage_name);

        $sqlite->set($arenaName, BFHelper::get()->BFAManager()->getReadyArenaData($arenaName));
    }
}