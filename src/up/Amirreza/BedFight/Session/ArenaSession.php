<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Session;


use up\Amirreza\BedFight\BedFight;

class ArenaSession {

    public array $pending = [];

    public function isPending(string $name): bool {
        return isset($this->pending[$name]);
    }

    public function joinPending(string $name) : void {
        $this->pending[$name] = true;
        if (count($this->pending) == 2) {
            $players = [$this->pending[0], $this->pending[1]];
            BedFight::getInstance()->getGameManager()->startGame($players,"d");
        }
    }

    public function leavePending(string $name) : void {
        unset($this->pending[$name]);
    }

}