<?php

declare(strict_types=1);

namespace Amirreza\BF\manager;

class BFManager {

    private ?array $managerName;

    public function __construct(string $managerName)
    {
        $this->managerName[$managerName] = [];
    }

    public function add(string $key, mixed $value = true): void {
        $this->managerName[$this->managerName][$key] = $value;
    }

    public function exists(string $key): bool {
        return isset($this->managerName[$this->managerName][$key]);
    }

    public function get(string $key): mixed {
        return $this->managerName[$this->managerName][$key];
    }

    public function remove(string $key): void {
        unset($this->managerName[$this->managerName][$key]);
    }
}