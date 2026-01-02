<?php

declare(strict_types=1);

namespace Amirreza\BedFight\session;

use Amirreza\BedFight\BedFight;
use Amirreza\BedFight\BedFightHelper;
use Exception;

class BedFightGameSession {

    /**
     * @throws Exception
     */
    public function connect(string $name): void {
        $helper = BedFightHelper::get();
        $server = BedFight::getInstance()->getServer();
        $arenaStorage = BedFightHelper::get()->BedFightArenaStorage();
        if ($this->countConnections() > 2) {
            $player1 = $server->getPlayerExact($this->getConnection(0));
            $player2 = $server->getPlayerExact($this->getConnection(1));
            list($arenaName,$arenaData) = $arenaStorage->getEmptyArena();
            if ($arenaData !== null) {
                $helper->BedFightGameManager()->prepareGame($player1,$player2,$arenaName, $arenaData);
            }
        }
        $helper->BedFightSessionManager()->add($name);
    }

    public function isConnect(string $name): bool {
        return BedFightHelper::get()->BedFightSessionManager()->exists($name);
    }

    public function getConnections(): array
    {
        return BedFightHelper::get()->BedFightSessionManager()->getAll();
    }

    public function countConnections(): int {
        return count(BedFightHelper::get()->BedFightSessionManager()->getAll());

    }

    public function getConnection(int $who): string {
        return $this->getConnections()[$who];
    }

    public function disconnect(string $name): void {
        BedFightHelper::get()->BedFightSessionManager()->remove($name);
    }
}