<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

use Amirreza\BedFight\constant\BedFightSetUpConstant;

class BedFightSetUpStepManager {

    private BedFightManager $bedFightManager;

    public function __construct()
    {
        $this->bedFightManager = new BedFightManager('setupstep');
    }

    public function nextStep(string $playerName): ?string
    {
        $nextStep = null;

        if ($this->isStep($playerName, BedFightSetUpConstant::STEP_1)) {
            $nextStep = BedFightSetUpConstant::STEP_2;
        } elseif ($this->isStep($playerName, BedFightSetUpConstant::STEP_2)) {
            $nextStep = BedFightSetUpConstant::STEP_3;
        } elseif ($this->isStep($playerName, BedFightSetUpConstant::STEP_3)) {
            $nextStep = BedFightSetUpConstant::STEP_4;
        } elseif ($this->isStep($playerName, BedFightSetUpConstant::STEP_4)) {
            $nextStep = BedFightSetUpConstant::STEP_5;
        }

        if ($nextStep !== null) {
            $this->bedFightManager->add(strtolower($playerName), $nextStep);
        }

        return $nextStep;
    }

    public function getStep(string $playerName): string
    {
        return (string) ($this->bedFightManager->get(strtolower($playerName)) ?? BedFightSetUpConstant::STEP_1);
    }

    public function isStep(string $playerName, string $step): bool {
        return ($this->getStep($playerName) === $step);
    }

    public function remove(string $playerName): void
    {
        $this->bedFightManager->remove(strtolower($playerName));
    }
}