<?php

declare(strict_types=1);

namespace Amirreza\BedFight\item;

use Amirreza\BedFight\constant\BedFightSetUpConstant;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use Amirreza\BedFight\player\BedFightInventory;

class BedFightDefaultItem {

    public function setLobbyItems(Player $player): void {
        $items = [
            ['BedFight',VanillaBlocks::BED()->setColor(DyeColor::RED)->asItem(),0],
            ['Duel',VanillaItems::TOTEM(),1],
            ['About',VanillaItems::NAME_TAG(),8]
        ];
        $inv = new BedFightInventory($player);
        $this->doAdding($inv, $items);
    }

    private function doAdding(BedFightInventory $inventory, array $items): void
    {
        foreach ($items as $vaItem) {
            $item = new factory\BedFightItemFactory($vaItem[1]);
            $item->create($vaItem[0]);
            $inventory->addItem($item->asItem(), $vaItem[2] ?? null);
        }
    }
}