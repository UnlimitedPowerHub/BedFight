<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Event;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use up\Amirreza\BedFight\BedFight;

class SetUpEvent implements Listener {


    public function onInteract(PlayerInteractEvent $event)
    : void {
        $player = $event->getPlayer();
        $setUpSession = BedFight::getInstance()->getSetUpSession();
        if ($setUpSession->exists($player->getName())) {
            $this->handleInteract(
                $player,
                $event->getItem(),
                $event->getBlock()
            );
        }
    }

    public function handleInteract(Player $player, Item $item, Block $block)
    : void {
        $bedFight = BedFight::getInstance();
        $blockPosition = $block->getPosition();
        $itemTypeId = $item->getTypeId();
        $name = $player->getName();
        $setUpForm = $bedFight->getSetUpForm();
        $setUpSession = $bedFight->getSetUpSession();
        if ($itemTypeId === VanillaBlocks::BEACON()->getTypeId() ||
            $item->getCustomName() === "MapSelector"
        ) {
            $setUpForm->sendMapSelectorForm($player);
        }

        if ($itemTypeId === VanillaItems::EMERALD()->getTypeId() ||
            $item->getCustomName() === "Info"
        ) {
            if ($setUpSession->exists($name)) {
                $info = $setUpSession->getSetUpData($name);
                $player->sendMessage(" ".join($info));
            } else {
                if ($player->getServer()->isOp($player->getName())) {
                    $player->sendMessage("You Are Not In Pending SetUp");
                }
            }
        }
        elseif (
            $itemTypeId
            === VanillaItems::ENDER_PEARL() ||
            $item->getCustomName() === "SetSpawn(Blue)"
        ) {
            $setUpForm->sendConfirmSpawnForm(
                $player,
                'blue',
                $blockPosition
            );
        }
        elseif (
            $itemTypeId
            === VanillaItems::ENDER_PEARL() ||
            $item->getCustomName() === "SetSpawn(Red)"
        )
        {
            $setUpForm->sendConfirmSpawnForm(
                $player,
                'red',
                $blockPosition
            );
        }
        elseif ($itemTypeId === VanillaItems::Emerald()->getTypeId()||
            $item->getCustomName() === "Done") {
            if (!$setUpSession->SetUpIsOk($name)) {
                $player->sendMessage("SetUp Data Is Not Ok For Done It");
            } else {
                $setUpForm->sendConfirmForm($player);
            }
        }
    }

    public function onBreak(BlockBreakEvent $event)
    : void {
        $player = $event->getPlayer();
        $block = $event->getItem();
        $blockTypeId = $block->getTypeId();
        $blockCustomName = $block->getCustomName();
        $bedfight = BedFight::getInstance();
        $setUpSession = $bedfight->getSetUpSession();
        $setUpForm = $bedfight->getSetUpForm();
        $pName = $player->getName();

        if ($setUpSession->exists($pName)) {
            if (!$setUpSession->isOkWorldName(
                $player->getName(),
                $player->getWorld()->getFolderName()
            )) {
                $player->sendMessage("You Cannot SetUp Arena In This Map!");
                $event->cancel();
                return;
            }
            if ($blockTypeId === VanillaBlocks::BED()->getTypeId() ||
                $blockCustomName === "SetBed(Blue)"
            ) {
                $setUpForm->sendConfirmBedForm(
                    $player,
                    'blue',
                    $event->getBlock()->getPosition()
                );
            } elseif (
                $blockTypeId === VanillaBlocks::BED()->getTypeId() ||
                $blockCustomName === "SetBed(Red)"
            ) {
                $setUpForm->sendConfirmBedForm(
                    $player,
                    'red',
                    $event->getBlock()->getPosition()
                );
            }
            $event->cancel();
        }
    }

}