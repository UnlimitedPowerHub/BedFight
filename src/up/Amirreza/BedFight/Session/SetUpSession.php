<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Session;

class SetUpSession {


    private array $pending_setups = [];
    private array $setups = [];
    private array $arenaNames = [];


    public function exists(string $name): bool {
        return array_key_exists(strtolower($name), $this->pending_setups);
    }

    public function set_pending_setup(string $name): void {
        $this->pending_setups[strtolower($name)] = true;
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
        $this->setups[strtolower($name)][$bedColor]['bed'] = [
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'worldName' => $worldName
        ];
    }

    public function setSpawn(
        string $name,
        ?string $teamColor,
        string|int $x,
        string|int $y,
        string|int $z,
        ?string $worldName
    )
        : void
    {
        $this->setups[strtolower($name)]['spawn'][$teamColor] = [
            'x' => $x,
            'y' => $y,
            'z' => $z,
            'worldName' => $worldName
        ];
    }

    public function SetUpIsOk(string $name): bool {
        return (
            isset($this->setups[strtolower($name)]['worldName']) ||
            isset($this->setups[strtolower($name)]['bed']['blue']) ||
            isset($this->setups[strtolower($name)]['bed']['red']) ||
            isset($this->setups[strtolower($name)]['spawn']['blue']) ||
            isset($this->setups[strtolower($name)]['spawn']['red'])
        );
    }

    public function getSetUpInfo(string $name): ?string
    {
        $setUpData = $this->getSetupData($name);
        $InfoData = [
            'arena'=> $this->getArenaName($name),
            'world' => $setUpData['worldName'],
            'team' => [
                'red' => "...",
                'blue' => "..."
            ],
            'bed' => [
                'red' => "...",
                'blue' => "..."
            ]
        ];


        return "arena: $InfoData[0]\n" . "world: ";
    }

    public function getSetUpData(string $name): array {
        $this->setups[strtolower($name)]['empty'] = 'yes';
        return $this->setups[strtolower($name)];
    }

    public function cancelSetUp(string $name)
    : void { $this->remArenaName($name);
        unset($this->setups[strtolower($name)]); }

    public function getArenaName(string $name)
    : ?string { return $this->arenaNames[strtolower($name)]; }

    public function setArenaName(string $name, string $arenaName)
    : void { $this->arenaNames[strtolower($name)] = $arenaName; }

    public function remArenaName(string $name)
    : void { unset($this->arenaNames[strtolower($name)]); }
}
