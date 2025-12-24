<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Storage;


use pocketmine\block\tile\Bed;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use up\Amirreza\BedFight\BedFight;

class ArenaStorage {

    private Config $arenas;

    public function __construct()
    {
        $this->arenas = new Config(Bedfight::getInstance()->getDataFolder() . "arenas.json", Config::JSON);
    }

    public function existEmptyArena(): bool {
        $emptyArenas = [];
        foreach($this->arenas->getAll() as $arena) {
            if ($this->isArenaEmpty($arena)) {
                $emptyArenas[] = $arena;
            }
        }
        return count($emptyArenas) > 0;
    }

    public function isArenaEmpty(string $arenaName): bool {
        return $this->getArena($arenaName)['empty'] === 'yes';
    }

    public function setEmptyArena(string $arenaName, ?string $empty='yes') : void {
        $this->arenas->setNested($arenaName.'.empty', $empty);
    }

    public function isExists(string $arenaName): bool {
        return array_key_exists($arenaName, $this->arenas->getAll());
    }

    public function createArena(
        string $playerName
    ): void {
        $setUpSession = BedFight::getInstance()->getSetUpSession();
        $this->arenas->setNested(
            $setUpSession->getArenaName($playerName),
            $setUpSession->getSetUpData($playerName)
        );
        $this->arenas->save();
    }

    public function getArena(string $arenaName): ?array {
        return $this->arenas->getNested($arenaName);
    }


    // use it in another updates
    public function removeArena(string $arenaName): void {
        $this->arenas->removeNested($arenaName);
        $this->arenas->save();
    }

    public function getArenaData(string $arenaName): ?array {
        return $this->arenas->getNested($arenaName);
    }

    public function getBlueTeamPos(string $arenaName): Position {
        $arenaData = $this->getArenaData($arenaName);
        $x = $arenaData['team']['blue']['x'];
        $y = $arenaData['team']['blue']['y'];
        $z = $arenaData['team']['blue']['z'];
        $worldName = $arenaData['team']['blue']['worldName'];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        return new Position((int)$x, (int)$y, (int)$z, $world);
    }

    public function getRedTeamPos(string $arenaName): Position {
        $arenaData = $this->getArenaData($arenaName);
        $x = $arenaData['team']['red']['x'];
        $y = $arenaData['team']['red']['y'];
        $z = $arenaData['team']['red']['z'];
        $worldName = $arenaData['team']['red']['worldName'];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        return new Position((int)$x, (int)$y, (int)$z, $world);
    }

    public function getBlueBed(string $arenaName): Bed {
        $arenaData = $this->getArenaData($arenaName);
        $x = $arenaData['bed']['blue']['x'];
        $y = $arenaData['bed']['blue']['y'];
        $z = $arenaData['bed']['blue']['z'];
        $worldName = $arenaData['bed']['blue']['worldName'];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        $position = new Position((int)$x, (int)$y, (int)$z, $world);
        return new Bed($world,$position);
    }

    public function getRedBed(string $arenaName): Bed {
        $arenaData = $this->getArenaData($arenaName);
        $x = $arenaData['bed']['red']['x'];
        $y = $arenaData['bed']['red']['y'];
        $z = $arenaData['bed']['red']['z'];
        $worldName = $arenaData['bed']['red']['worldName'];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        $position = new Position((int)$x, (int)$y, (int)$z, $world);
        return new Bed($world,$position);
    }
}