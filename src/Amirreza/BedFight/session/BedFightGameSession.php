<?php

declare(strict_types=1);

namespace Amirreza\BedFight\session;

use Amirreza\BedFight\BedFightHelper;
use Exception;

class BedFightGameSession {

    /**
     * @throws Exception
     */
    public function connect(string $name): void {
        $arenaStorage = BedFightHelper::get()->BedFightArenaStorage();
        if ($this->countConnections() > 2) {
            $player1 = $this->getConnection(0);
            $player2 = $this->getConnection(1);
//            $emptyArena = $arenaStorage->getEmptyArenaWorld();
            if ($emptyArena !== null) {

            }
        }
        BedFightHelper::get()->BedFightSessionManager()->add($name);
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