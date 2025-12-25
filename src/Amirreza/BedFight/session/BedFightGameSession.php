<?php

declare(strict_types=1);

namespace Amirreza\BedFight\session;

use Amirreza\BedFight\BedFightHelper;

class BedFightGameSession {

    public function connect(string $name): void {
        BedFightHelper::get()->BedFightSessionManager()->add($name);
    }

    public function isConnect(string $name): bool {
        return BedFightHelper::get()->BedFightSessionManager()->exists($name);
    }

    public function disconnect(string $name): void {
        BedFightHelper::get()->BedFightSessionManager()->remove($name);
    }
}