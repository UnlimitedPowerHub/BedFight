<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use up\Amirreza\BedFight\BedFight;

class SetUpForm {

    protected array $maps_ = [];

    protected BedFight $bedFight;

    public function __construct()
    {

        $this->maps_ =  [];

        foreach (BedFight::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
            $this->maps_[$world->getFolderName()] = $world->getFolderName();
        }

        $this->bedFight = BedFight::getInstance();
    }

    public function sendMapSelectorForm(Player $player) : void {

        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }

            $map = $this->maps_[$data];
            $world = $this->bedFight->getServer()->getWorldManager()->getWorldByName($map);
            $player->sendMessage("Map {$map} selected");
            $player->teleport($world->getSafeSpawn());
            $this->getReadyForSetUpMap($player);
            $player->sendMessage("First Set Team Beds And After Set Team Spawns, When Set All Arena Will Create");
        });

        $form->setTitle("Map Selector - SetUp");
        foreach ($this->maps_ as $world) {
            $form->addButton($world);
        }
        $form->addButton("cancel");
        $player->sendForm($form);
    }

    private function getReadyForSetUpMap(Player $player) : void {
        $player_inventory = $player->getInventory();
        $player_inventory->clearAll();
        $player_inventory->setItem(0, VanillaItems::EMERALD()->setCustomName("Info"));
        $player_inventory->setItem(1, VanillaBlocks::BED()->setColor(DyeColor::BLUE)->asItem()->setCustomName("SetBed ( Blue )")->setLore(["With Place Bed"]));
        $player_inventory->setItem(8, VanillaItems::REDSTONE_DUST()->setCustomName("Cancel"));
    }
}
