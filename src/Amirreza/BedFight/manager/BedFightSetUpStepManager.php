<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

use Amirreza\BedFight\constant\BedFightSetUpConstant;
use pocketmine\player\Player;

class BedFightSetUpStepManager {

    private BedFightManager $bedFightManager;

    public function __construct()
    {
        $this->bedFightManager = new BedFightManager('setupstep');
    }

    public function setStep(string $playerName, BedFightSetUpConstant $step): void
    {
        $this->bedFightManager->add(strtolower($playerName), $step);
    }

    public function getStep(string $playerName): BedFightSetUpConstant
    {
        return $this->bedFightManager->get(strtolower($playerName));
    }

    public function isStep(string $playerName,BedFightSetUpConstant $step): bool {
        return ($this->getStep($playerName) === $step);
    }

    public function remove(string $playerName): void
    {
        $this->bedFightManager->remove(strtolower($playerName));
    }
}