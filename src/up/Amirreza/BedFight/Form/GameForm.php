<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use up\Amirreza\BedFight\BedFight;

class GameForm {


    public function sendBedFightForm(Player $player): void
    {
        $bedfight = BedFight::getInstance();
        $arenaSession = $bedfight->getArenaSession();
        $form = new SimpleForm(function (Player $player, ?int $data) use ($arenaSession) {
            if ($data === null) {
                return;
            }
            $playerName = $player->getName();

            switch ($data) {
                case 0:
                    if($arenaSession->isPending($playerName)) {
                        $player->sendMessage("You Already In Pending!");
                    } else {
                        $arenaSession->joinPending($playerName);
                        $player->sendMessage("Wait For Players(1/2)...");
                    }
                    break;
                case 1:
                    if ($arenaSession->isPending($playerName)) {
                        $arenaSession->leavePending($playerName);
                    } else {
                        $player->sendMessage("You Are Not In Pending!");
                    }
                    break;
                case 2:
                    $player->sendMessage("Clicked on Manage Button!");
                    break;
            }
        });

        $form->setTitle("BedFight");
        $form->addButton("Join Game");
        $form->addButton("Leave Game");
        if ($player->getServer()->isOp($player->getName())) {
            $form->addButton("Manage");
        }
        $player->sendForm($form);
    }

    private function sendManageForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) use ($player) {
            if ($data === null) {
                return;
            }

            $setUpSession = BedFight::getInstance()->getSetUpSession();

            switch ($data) {
                case 0:
                    if ($setUpSession->exists($player->getName())) {
                        $player->sendMessage("You Already In Pending!");
                    } else {
                        $setUpSession->set_pending_setup($player->getName());
                        $player->sendMessage("Now Use Map Selector Item In Your Inventory");
                    }
                    break;
                case 1:
                    $this->sendBedFightForm($player);
                    break;
            }
        });
        $form->setTitle("BedFight Manage");
        $form->addButton("SetUpArena");
        $form->addButton("Back");
        $player->sendForm($form);
    }

}