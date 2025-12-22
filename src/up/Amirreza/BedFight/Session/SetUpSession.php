<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Session;

class SetUpSession {

    private array $pending_setups = [];
    private array $setups = [];

    public function exists(string $name): bool {
        return array_key_exists(strtolower($name), $this->pending_setups);
    }

    public function set_pending_setup(string $name, bool $value = true): void {
        $this->pending_setups[strtolower($name)] = $value;
    }

    public function get_pending_setup(string $name) {
        return $this->pending_setups[strtolower($name)];
    }

    public function remove_pending_setup(string $name): void {
        unset($this->pending_setups[strtolower($name)]);
    }

    public function setWorldName(
        string $name,
        ?string $worldName
    ) : void {
        $this->setups[strtolower($name)]['worldName'] = $worldName;
    }

    public function getWorldName(
        string $name,
    ) : ?string {
        return $this->setups[strtolower($name)]['worldName'];
    }

    public function isOkWorldName(
        string $name,
        ?string $worldName
    ) : bool {
        return $worldName === $this->getWorldName($name);
    }

    public function setBed(
        string $name,
        ?string $bedColor,
        string|int $x,
        string|int $y,
        string|int $z,
        ?string $worldName
    ) : void {
        $this->setups[strtolower($name)][$bedColor] = [
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'worldName' => $worldName
        ];
    }
}