<?php

declare(strict_types=1);

namespace Amirreza\BF\storage\storages;

use pocketmine\utils\Config;
use up\Amirreza\BF\BFPluginBase;

class JsonStorage {

    private Config $storage;

    public function __construct(string $name) {
        $this->storage = new Config(BFPluginBase::getMe()->getDataFolder().$name.".json", Config::JSON);
    }

    public function existsKey(string $key): bool {
        return $this->storage->exists($key);
    }

    public function addKey(string $key, string $value): void {
        $this->storage->set($key, $value);
    }

    public function getKey(string $key) {
        return $this->storage->get($key);
    }

    public function removeKey(string $key): void {
        $this->storage->remove($key);
    }

    public function close(): void {
        $this->storage->save();
    }
}