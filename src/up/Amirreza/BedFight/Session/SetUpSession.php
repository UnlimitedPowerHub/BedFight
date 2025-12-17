<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Session;

class SetUpSession {

    private array $pending_setups = [];
    private array $setups = [];

    public function exists(string $name): bool {
        return array_key_exists(strtolower($name), $this->pending_setups);
    }

    public function set_pending_setup(string $name, bool $value = true): void
    {
        $this->pending_setups[strtolower($name)] = $value;
    }

    public function get_pending_setup(string $name) {
        if ($this->exists($name)) {
            return $this->pending_setups[strtolower($name)];
        }
        return [];
    }


}