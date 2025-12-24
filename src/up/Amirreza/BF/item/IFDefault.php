<?php

declare(strict_types=1);

namespace up\Amirreza\BF\item;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use up\Amirreza\BF\player\BFInventory;

class IFDefault {

    public function setLobbyItems(Player $player): void {
        $items = [
            ['BedFight',VanillaBlocks::BED()->setColor(DyeColor::RED)->asItem(),0],
            ['Duel',VanillaItems::TOTEM(),1],
            ['About',VanillaItems::NAME_TAG(),8]
        ];
        $inv = new BFInventory($player);
        $this->doAdding($inv, $items);
    }

    private function doAdding(BFInventory $inventory, array $items): void
    {
        foreach ($items as $vaItem) {
            $item = new factory\SUIFactory($vaItem[1]);
            $item->create($vaItem[0]);
            $inventory->addItem($item->asItem(), $vaItem[2] ?? null);
        }
    }

}