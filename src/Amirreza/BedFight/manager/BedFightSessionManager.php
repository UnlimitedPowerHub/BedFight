<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

class BedFightSessionManager {

    private BedFightManager $bedFightManager;

    public function __construct()
    {
        $this->bedFightManager = new BedFightManager('session');
    }

    public function exists(string $name): bool
    {
        return $this->bedFightManager->exists($name);
    }

    public function add(string $name): void
    {
        $this->bedFightManager->add($name);
    }

    public function get(string $name): ?string
    {
        return $this->bedFightManager->get($name);
    }

    public function remove(string $name): void
    {
        $this->bedFightManager->remove($name);
    }
}