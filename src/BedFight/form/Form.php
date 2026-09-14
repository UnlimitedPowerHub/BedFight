<?php

declare(strict_types=1);

namespace BedFight\Form;

use pocketmine\player\Player;

interface Form {
    public function getId(): int;
    public function getType(): string;
    public function encode(): array;
    public function handleResponse(Player $player, mixed $data): void;
}