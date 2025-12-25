<?php

declare(strict_types=1);

namespace Amirreza\BF\item\factory;

use pocketmine\item\Item;
use pocketmine\lang\Translatable;

class SUIFactory {

    private Item $item;
    private string $name;

    public function __construct(Item $item) {
        $this->item = $item;
        $this->name = $item->getName();
    }

    public function create(Translatable|string $name = null, int $count = 1): Item {
        $this->item->setCustomName($name ?? $this->name);
        $this->item->setCount($count);
        return $this->item;
    }

    public function asItem(): Item {
        return $this->item;
    }
}