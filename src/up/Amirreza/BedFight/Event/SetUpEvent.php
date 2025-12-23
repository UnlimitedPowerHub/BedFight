<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Event;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use up\Amirreza\BedFight\BedFight;

class SetUpEvent implements Listener {

    protected BedFight $bedFight;


    public function __construct()
    {
        $this->bedFight = BedFight::getInstance();
    }


    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $blockPosition = $event->getBlock()->getPosition();
        $itemTypeId = $item->getTypeId();
        $name = $player->getName();
        $setUpForm = $this->bedFight->getSetUpForm();
        $setUpSesion = $this->bedFight->getSetUpSession();

        if ($itemTypeId === VanillaBlocks::BEACON()->getTypeId()) {
            $setUpForm->sendMapSelectorForm($player);
        }

        if ($itemTypeId === VanillaItems::EMERALD()->getTypeId() ||
            $item->getCustomName() === "Info"
        ) {
            if ($setUpSesion->exists($name)) {
                $info = $setUpSesion->get_pending_setup($name);
            } else {
                return;
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
            return;
        }
    }

    public function onPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getItem();
        $blockTypeId = $block->getTypeId();
        $blockCustomName = $block->getCustomName();
        $bedfight = BedFight::getInstance();
        $setUpSession = $bedfight->getSetUpSession();
        $setUpForm = $bedfight->getSetUpForm();

        if (!$setUpSession->
        isOkWorldName(
            $player->
                getName(),
            $player->getWorld()->getFolderName()
        )
        ) {
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
                $event->getBlockAgainst()->getPosition()
            );
        } elseif (
            $blockTypeId === VanillaBlocks::BED()->getTypeId() ||
            $blockCustomName === "SetBed(Red)"
        ) {
            $setUpForm->sendConfirmBedForm(
                $player,
                'red',
                $event->getBlockAgainst()->getPosition()
            );
        } else {
            return;
        }
    }
}