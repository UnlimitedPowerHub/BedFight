<?php

declare(strict_types=1);

namespace Amirreza\BF\session;

use Amirreza\BF\BFHelper;

class GSession {

    const session_name = "game";

    public function connect(string $name): void {
        BFHelper::get()->BFSession()->add(self::session_name, $name);
    }

    public function isConnect(string $name): bool {
        return BFHelper::get()->BFSession()->exists(self::session_name, $name);
    }

    public function disconnect(string $name): void {
        BFHelper::get()->BFSession()->remove(self::session_name, $name);
    }
}