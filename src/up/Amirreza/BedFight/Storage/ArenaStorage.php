<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Storage;


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
        string $arenaName,
        ?int $bluePositions,
        ?int $redPositions,
    ): void {
        $arenaData = [
            'empty' => 'yes',
            'blue' => [
                'pos'=>[
                    'x' => $bluePositions['x'],
                    'y' => $bluePositions['y'],
                    'z' => $bluePositions['z'],
                    'worldName' => $bluePositions['worldName'],
                ]
            ],
            'red' => [
                'pos'=>[
                    'x' => $redPositions['x'],
                    'y' => $redPositions['y'],
                    'z' => $redPositions['z'],
                    'worldName' => $redPositions['worldName'],
                ]
            ]
        ];
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

    public function getBlueTeamPos(string $arenaName): Position {
        $x = $this->arenas->getNested($arenaName.'.blue.pos.x');
        $y = $this->arenas->getNested($arenaName.'.blue.pos.y');
        $z = $this->arenas->getNested($arenaName.'.blue.pos.z');
        $worldName = $this->arenas->getNested($arenaName.'.blue.pos.world');
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        return new Position((int)$x, (int)$y, (int)$z, $world);
    }

    public function getRedTeamPos(string $arenaName): Position {
        $x = $this->arenas->getNested($arenaName.'.red.pos.x');
        $y = $this->arenas->getNested($arenaName.'.red.pos.y');
        $z = $this->arenas->getNested($arenaName.'.red.pos.z');
        $worldName = $this->arenas->getNested($arenaName.'.red.pos.world');
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        return new Position((int)$x, (int)$y, (int)$z, $world);
    }
}