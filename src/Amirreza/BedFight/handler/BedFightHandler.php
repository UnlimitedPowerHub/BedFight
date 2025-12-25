<?php

declare(strict_types=1);

namespace Amirreza\BedFight\handler;

use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\handler\setup\BedFightSetUpBlockBreakHandler;

class BedFightHandler {

    private BedFightSetUpBlockBreakHandler $BedFightSetUpBreakHandler;

    public static function init(): void {
        $me = BedFightHelper::get()->BedFightHandler();
        $me->BedFightSetUpBreakHandler = new BedFightSetUpBlockBreakHandler();
    }

    public function BedFightSetUpBreakHandler (): BedFightSetUpBlockBreakHandler {
        return $this->BedFightSetUpBreakHandler;
    }
}