<?php

declare(strict_types=1);

namespace Amirreza\BedFight\item;

use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;
use Amirreza\BedFight\constant\BedFightSetUpConstant;
use Amirreza\BedFight\constant\BedFightTeamConstant;
use Amirreza\BedFight\item\factory\BedFightItemFactory;
use Amirreza\BedFight\player\BedFightInventory;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class BedFightDefaultItem
{

    public function setLobbyItems(Player $player): void
    {
        $items = [
            [BedFightConstant::BedFightPrefix, VanillaBlocks::BED()->setColor(DyeColor::RED)->asItem(), 0],
            ['Duel', VanillaItems::TOTEM(), 1],
            ['About', VanillaItems::NAME_TAG(), 8]
        ];

        $inv = new BedFightInventory($player);
        $this->doAdding($inv, $items);
    }

    public function setSetUpStepItems(Player $player): void
    {
        $step = BedFightHelper::get()->BedFightSetUpStepManager()->getStep(
            $player->getName()
        );

        $items = match ($step) {
            BedFightSetUpConstant::STEP_1 => [
                [
                    'SetBlueSpawn',
                    VanillaItems::ENDER_PEARL(),
                    0
                ]
            ],
            BedFightSetUpConstant::STEP_2 => [
                [
                    'SetBlueBed',
                    VanillaBlocks::BED()->setColor(DyeColor::BLUE)->asItem(),
                    0
                ]
            ],
            BedFightSetUpConstant::STEP_3 => [
                [
                    'SetRedSpawn',
                    VanillaItems::ENDER_PEARL(),
                    0
                ]
            ],
            BedFightSetUpConstant::STEP_4 => [
                [
                    'SetRedBed',
                    VanillaBlocks::BED()->setColor(DyeColor::RED)->asItem(),
                    0
                ]
            ],
            BedFightSetUpConstant::STEP_5 => [
                [
                    'Confirm',
                    VanillaItems::EMERALD(),
                    0
                ],
                [
                    'Cancel',
                    VanillaItems::REDSTONE_DUST(),
                    1
                ]
            ],
            default => [],
        };

        if (!empty($items)) {
            $inv = new BedFightInventory($player);
            $this->doAdding($inv, $items);
        }
    }

    public function setMatchItems(
        Player $player,
        string $team
    ): void
    {
        $color = ($team === BedFightTeamConstant::BLUE_TEAM) ? DyeColor::BLUE : DyeColor::RED;

        $items = [
            [null, VanillaItems::WOODEN_SWORD(), 0],
            [null, VanillaBlocks::WOOL()->setColor($color)->asItem(), 1, 64],
            [null, VanillaItems::SHEARS(), 2],
            [null, VanillaItems::WOODEN_PICKAXE(), 3],
            [null, VanillaItems::WOODEN_AXE(), 4],
        ];

        $inv = new BedFightInventory($player);
        $this->doAdding($inv, $items);
    }

    private function doAdding(BedFightInventory $inventory, array $items): void
    {
        foreach ($items as $itemData) {
            $itemFactory = new BedFightItemFactory($itemData[1]);
            $item = $itemFactory->create($itemData[0] ?? null);
            if (isset($itemData[3])) {
                $item->setCount($itemData[3]);
            }
            $slot = $itemData[2] ?? null;
            $inventory->addItem($item, $slot);
        }
    }
}
