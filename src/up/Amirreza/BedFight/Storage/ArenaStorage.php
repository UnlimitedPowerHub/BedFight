<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Storage;


use pocketmine\utils\Config;
use up\Amirreza\BedFight\BedFight;

class ArenaStorage {

    private Config $arenas;

    public function __construct()
    {
        $this->arenas = new Config(Bedfight::getInstance()->getDataFolder() . "arenas.json", Config::JSON);
    }

    public function isArenaEmpty(string $arenaName): bool {
        return $this->arenas->getNested($arenaName.'empty');
    }

    public function isExists(string $arenaName): bool {
        return array_key_exists($arenaName, $this->arenas->getAll());
    }

    public function createArena(string $arenaName, string $arenaData): void {
        $this->arenas->setNested($arenaName, $arenaData);
        $this->arenas->save();
    }

    public function getArena(string $arenaName): ?array {
        return $this->arenas->getNested($arenaName);
    }

    public function removeArena(string $arenaName): void {
        $this->arenas->removeNested($arenaName);
        $this->arenas->save();
    }
}