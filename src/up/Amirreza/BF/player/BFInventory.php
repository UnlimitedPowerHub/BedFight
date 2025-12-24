<?php

declare(strict_types=1);

namespace up\Amirreza\BF\player;

use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

class BFInventory {

    private PlayerInventory $pInv;

    public function __construct(Player $player) {
        $this->pInv = $player->getInventory();
    }

    public function newItems(array $items): void {
        $this->pInv->clearAll();
        foreach ($items as $item) {
            $aItem = $item[0];
            $slot = $item[1] ?? null;
            $this->addItem($aItem, $slot);
        }
    }

    public function addItem(Item $item, int|null $slot): void {
        if ($slot === null) {
            $this->pInv->addItem($item);
        } else {
            $this->pInv->setItem($slot, $item);
        }
    }
}