<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Form;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\Position;
use up\Amirreza\BedFight\BedFight;

class SetUpForm {

    public function sendMapSelectorForm(Player $player) : void {
        $maps =  [];
        $bedFight = BedFight::getInstance();

        foreach ($bedFight->getServer()->getWorldManager()->getWorlds() as $world) {
            $maps[] = $world->getFolderName();
        }
        $form = new SimpleForm(function (Player $player, ?int $data)
        use ($maps, $bedFight) {
            if ($data === null) {
                return;
            }

            $map = $maps[$data];

            $world = $bedFight->getServer()->getWorldManager()->getWorldByName($map);
            $bedFight->getSetUpSession()->setWorldName(
                $player->getName(),
                $map
            );
            $player->sendMessage("Map $map selected");
            $player->teleport($world->getSafeSpawn());
            $this->getReadyForSetUpMap($player);
            $player->sendMessage("First Set Team Beds And After Set Team Spawns, When Set All Arena Will Create");
        });

        $form->setTitle("Map Selector - SetUp");
        foreach ($maps as $map) {
            $form->addButton($map);
        }
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
                       $player->getInventory()
                           ->setItem(
                               1,VanillaBlocks::BED()->setColor(
                               DyeColor::RED
                           )
                               ->asItem()
                               ->setCustomName("SetBed(Red)"));
                   } else {
                       $player->getInventory()
                           ->setItem(
                           1,VanillaItems::ENDER_PEARL()
                               ->setCustomName("SetSpawn(Blue)"));
                       $player->sendMessage(
                           "Beds Successfully Seted Now Set Team Spawns"
                       );
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

    public function sendConfirmSpawnForm(
        Player $player,
        ?string $teamColor,
        Position $position
    ) : void
    {

        $x = $position->x;
        $y = $position->y;
        $z = $position->z;
        $worldName = $position->getWorld()->getFolderName();
        $bedFight = BedFight::getInstance();

        $form = new SimpleForm(function (Player $player, ?int $data) use (
            $teamColor,
            $x,$y,$z,
            $worldName,
            $bedFight
        ){
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $bedFight->getSetUpSession()->setSpawn(
                        $player->getName(),
                        $teamColor,
                        $x,$y,$z,
                        $worldName
                    );
                    if ($teamColor === "blue") {
                        $player->
                        getInventory()
                            ->setItem(
                                1,
                                VanillaItems::ENDER_PEARL()->setCustomName("SetSpawn(Red)")
                            );
                    } else {
                        $player->
                            getInventory()
                            ->removeItem(
                                VanillaItems::ENDER_PEARL()
                            );
                        $player->sendMessage("Successfully Set Spawn $teamColor");
                        $player->getInventory()->setItem(7,VanillaItems::EMERALD()->setCustomName("Done"));
                        $player->sendMessage("Now You Can Done It With Emerald Item");
                        return;
                    }
                    $player->sendMessage("Successfully Set Spawn $teamColor");
                    break;
                    case 1:
                        $player->sendMessage("Canceled Set Spawn $teamColor");
                        // Do Nothing
                    break;
            }
        });

        $form->setTitle("Confirm for Spawn $teamColor");
        $form->setContent(
            "\n\nPosition $x $y $z $worldName\n\n"
        );
        $form->addButton("Confirm Spawn");
        $form->addButton("cancel");
        $player->sendForm($form);
    }

    public function sendConfirmForm(Player $player)
    :void {
        $bedfight = BedFight::getInstance();
        $setUpSession = $bedfight->getSetUpSession();
        $arenaStorage = $bedfight->getArenaStorage();
        $pName = $player->getName();
        $arenaName = $setUpSession->getArenaName($pName);
        $form = new SimpleForm(function (Player $player, ?int $data = null)
        use ($setUpSession, $arenaStorage, $arenaName, $pName) {
            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    $arenaStorage->createArena(
                        $arenaName
                    );
                    $player->sendMessage(
                        "Successfully Seted Arena With Name $arenaName"
                    );
                    break;
                case 1:
                    $setUpSession->cancelSetUp(
                        $pName
                    );
                    $player->sendMessage(
                        "Successfully Canceled SetUp"
                    );
                    break;
            }
        });
        $form->setTitle("SetUp Confirm");
        $form->setContent("\n");
        $form->addButton("Confirm");
        $form->addButton("Cancel");
        $player->sendForm($form);
    }

    public function sendSetArenaNameForm(
        Player $player,
    ) : void {
        $setUpSession = BedFight::getInstance()->getSetUpSession();
        $form = new CustomForm(function (Player $player, ?array $data = null)
        use ($setUpSession) {
            if ($data === null) {
                return;
            }
            $arenaName = $data[0];
            $setUpSession->setArenaName(
                $player->getName(),
                $arenaName
            );
            $player->sendMessage(
                "Successfully Set Arena Name $arenaName"
            );
            $setUpSession->set_pending_setup($player->getName());
            $pInv = $player->getInventory();
            $pInv->clearAll();
            $pInv->setItem(0,
                VanillaBlocks::BEACON()->asItem()->setCustomName(
                    "MapSelector"
                )
            );
            $player->sendMessage("Now Use Map Selector Item In Your Inventory");
        });
        $form->setTitle("Set Arena Name");
        $form->addInput("Enter the Arena Name","example: arena1");
        $player->sendForm($form);
    }

}
