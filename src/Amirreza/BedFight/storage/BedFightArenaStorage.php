<?php

declare(strict_types=1);

namespace Amirreza\BedFight\storage;

use Amirreza\BedFight\constant\BedFightArenaStatusConstant;
use Exception;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\storage\storages\SQLiteStorage;

class BedFightArenaStorage
{

    private string $storage_name = "arenas";

    /**
     * @throws Exception
     */
    public function makeArena(
    ): void
    {
        $sqlite = new SQLiteStorage($this->storage_name);
        $arenaManager = BedFightHelper::get()->BedFightArenaManager();
        $sqlite->set($arenaManager->getArenaName() , $arenaManager->getReadyArenaData());
    }

    /**
     * @throws Exception
     */
    public function getEmptyArena(): ?array {
        $sqlite = new SQLiteStorage($this->storage_name);
        $arenas = $sqlite->getAll();
        foreach ($arenas as $arena) {
            if ($arena['status'] === BedFightArenaStatusConstant::EMPTY) {
                return $arena;
            }
        }
        return null;
    }

}