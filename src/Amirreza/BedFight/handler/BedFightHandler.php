<?php

declare(strict_types=1);

namespace Amirreza\BedFight\handler;

use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\handler\game\BedFightGameHandler;
use Amirreza\BedFight\handler\setup\BedFightSetUpBlockBreakHandler;
use Amirreza\BedFight\handler\setup\BedFightSetUpStepHandler;

class BedFightHandler
{

    private BedFightSetUpBlockBreakHandler $BedFightSetUpBreakHandler;
    private BedFightSetUpStepHandler $BedFightSetUpStepHandler;
    private BedFightGameHandler $BedFightGameHandler;

    public static function init(): void
    {
        $me = BedFightHelper::get()->BedFightHandler();
        $me->BedFightSetUpBreakHandler = new BedFightSetUpBlockBreakHandler();
        $me->BedFightSetUpStepHandler = new BedFightSetUpStepHandler();
        $me->BedFightGameHandler = new BedFightGameHandler();
    }

    public function BedFightSetUpBreakHandler(): BedFightSetUpBlockBreakHandler
    {
        return $this->BedFightSetUpBreakHandler;
    }

    public function BedFightSetUpStepHandler(): BedFightSetUpStepHandler
    {
        return $this->BedFightSetUpStepHandler;
    }

    public function BedFightGameHandler(): BedFightGameHandler
    {
        return $this->BedFightGameHandler;
    }
}