<?php

declare(strict_types=1);

namespace Amirreza\BF\manager;

use Amirreza\BF\trait\BFSTrait;

class BFAManager
{

    use BFSTrait;

    private BFManager $BFManager;

    public function __construct()
    {
        $this->BFManager = new BFManager('arenas');
    }

    public function setRedBedX(string|int $value): void
    {
        $this->BFManager->add('bedredx', $value);
    }

    public function getRedBedX(): string|int
    {
        return $this->BFManager->get('bedredx');
    }

    public function setRedBedY(string|int $value): void
    {
        $this->BFManager->add('bedredy', $value);
    }

    public function getRedBedY(): string|int
    {
        return $this->BFManager->get('bedredy');
    }

    public function setRedBedZ(string|int $value): void
    {
        $this->BFManager->add('bedredz', $value);
    }

    public function getRedBedZ(): string|int
    {
        return $this->BFManager->get('bedredz');
    }

    public function setBlueBedX(string|int $value): void
    {
        $this->BFManager->add('bedbluex', $value);
    }

    public function getBlueBedX(): string|int
    {
        return $this->BFManager->get('bedbluex');
    }

    public function setBlueBedY(string|int $value): void
    {
        $this->BFManager->add('bedbluey', $value);
    }

    public function getBlueBedY(): string|int
    {
        return $this->BFManager->get('bedbluey');
    }

    public function setBlueBedZ(string|int $value): void
    {
        $this->BFManager->add('bedbluez', $value);
    }

    public function getBlueBedZ(): string|int
    {
        return $this->BFManager->get('bedbluez');
    }

    public function setRedTeamX(string|int $value): void
    {
        $this->BFManager->add('teamredx', $value);
    }

    public function getRedTeamX(): string|int
    {
        return $this->BFManager->get('teamredx');
    }

    public function setRedTeamY(string|int $value): void
    {
        $this->BFManager->add('teamredy', $value);
    }

    public function getRedTeamY(): string|int
    {
        return $this->BFManager->get('teamredy');
    }

    public function setRedTeamZ(): void
    {
        $this->BFManager->add('teanredz');
    }

    public function getRedTeamZ(): string|int
    {
        return $this->BFManager->get('teamredy');
    }

    public function setBlueTeamX(string|int $value): void
    {
        $this->BFManager->add('teambluex', $value);
    }

    public function getBlueTeamX(): string|int
    {
        return $this->BFManager->get('teambluex');
    }

    public function setBlueTeamY(): void
    {
        $this->BFManager->add('bedbluey');
    }

    public function getBlueTeamY(): string|int
    {
        return $this->BFManager->get('bedbluey');
    }

    public function setBlueTeamZ(): void
    {
        $this->BFManager->add('bedbluez');
    }

    public function getBlueTeamZ(): string|int
    {
        return $this->BFManager->get('bedbluey');
    }

    public function setWorldName(?string $value): void
    {
        $this->BFManager->add('worldname', $value);
    }

    public function getWorldName(): string
    {
        return $this->BFManager->get('worldName');
    }

    public function setArenaName(?string $value): void
    {
        $this->BFManager->add('arenaName', $value);
    }

    public function getArenaName(): string
    {
        return $this->BFManager->get('arenaName');
    }

    public function getReadyArenaData(): ?array
    {
        return [
            'status' => self::EMPTY,
            'world' => $this->getWorldName(),
            'bed' => [
                'red' => [
                    'x' => $this->getRedBedX(),
                    'y' => $this->getRedBedY(),
                    'z' => $this->getRedBedZ(),
                ],
                'blue' => [
                    'x' => $this->getBlueBedZ(),
                    'y' => $this->getBlueBedY(),
                    'z' => $this->getBlueBedZ(),
                ]
            ],
            'team' => [
                'red' => [
                    'x' => $this->getRedTeamX(),
                    'y' => $this->getRedTeamY(),
                    'z' => $this->getRedTeamZ(),
                ],
                'blue' => [
                    'x' => $this->getBlueTeamX(),
                    'y' => $this->getBlueTeamY(),
                    'z' => $this->getBlueTeamZ(),
                ]
            ]
        ];
    }
}