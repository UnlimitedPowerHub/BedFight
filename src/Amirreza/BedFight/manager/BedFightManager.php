<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

class BedFightManager {

    private string $managerName;
    private array $data = [];

    public function __construct(string $managerName)
    {
        $this->managerName = $managerName;
    }

    public function add(string $key, mixed $value = true): void {
        $this->data[$this->managerName][$key] = $value;
    }

    public function exists(string $key): bool {
        return isset($this->data[$this->managerName][$key]);
    }

    public function get(string $key): mixed {
        return $this->data[$this->managerName][$key] ?? null;
    }

    public function getAll(): array {
        return $this->data[$this->managerName];
    }

    public function remove(string $key): void {
        unset($this->data[$this->managerName][$key]);
    }

    public function reset(): void {
        $this->data[$this->managerName] = [];
    }

    public function genId(): string {
        return uniqid();
    }
}