<?php

declare(strict_types=1);

namespace Amirreza\BF\session;

use pocketmine\lang\Translatable;

class BFSession {

    private ?array $sessions = [];

    public function add(string $name,Translatable|string $playerName): void{
        $this->sessions[$name] = $playerName;
    }

    public function exists(string $name,Translatable|string $playerName): bool {
        return isset($this->sessions[$name][$playerName]);
    }

    public function remove(string $name,Translatable|string $playerName): void {
        unset($this->sessions[$name][$playerName]);
    }
}