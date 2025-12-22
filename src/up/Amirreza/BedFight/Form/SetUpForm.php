<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\Position;
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
            $player->sendMessage("Map $map selected");
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
        $player_inventory->setItem(1, VanillaBlocks::BED()->setColor(DyeColor::BLUE)->asItem()->setCustomName("SetBed(Blue)")->setLore(["With Place Bed"]));
        $player_inventory->setItem(8, VanillaItems::REDSTONE_DUST()->setCustomName("Cancel"));
    }

    public function sendConfirmBedForm(Player $player, ?string $bedColor, Position $position): void {

        $x = $position->x;
        $y = $position->y;
        $z = $position->z;
        $worldName = $position->getWorld()->getFolderName();
        $bedFight = BedFight::getInstance();

        $form = new SimpleForm(function (Player $player, ?int $data) use (
            $x,$y,$z,$worldName,$bedFight,$bedColor,$position
        ){
           if ($data === null) {
               return;
           }
           switch ($data) {
               case 0:
                   $bedFight->getSetUpSession()->setBed(
                       $player->getName(),
                       $bedColor,
                       $x,
                       $y,
                       $z,$worldName
                   );
                   if ($bedColor === "blue") {
                       $player->getInventory()->getItem(1)->setCustomName("SetBed(Red)");
                   } else {
                       // ...
                   }
                   $player->sendMessage("Successfully Set Bed $bedColor");
                   break;
               case 1:
                   $bedFight->getServer()->getWorldManager()->getWorldByName($worldName)->setBlock(
                       $position->asVector3(),
                       VanillaBlocks::AIR()
                   );
                   $player->sendMessage("Canceled Set Bed $bedColor");
                   break;
           }
        });

        $form->setTitle("Confirm for Bed $bedColor");
        $form->setContent(
            "\n\nPosition $x $y $z $worldName\n\n"
        );
        $form->addButton("Confirm Bed");
        $form->addButton("cancel");
        $player->sendForm($form);
    }
}
