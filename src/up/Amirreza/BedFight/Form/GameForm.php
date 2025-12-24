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
        $arenaStorage = $bedfight->getArenaStorage();
        $form = new SimpleForm(function (Player $player, ?int $data) use ($arenaSession, $arenaStorage) {
            if ($data === null) {
                return;
            }
            $playerName = $player->getName();

            switch ($data) {
                case 0:
                    if (!$arenaStorage->existEmptyArena()){
                        $player->sendMessage("Have No Empty Arena Wait");
                        return;
                    }
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
                    $this->sendManageForm($player);
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
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }

            $bedFight = BedFight::getInstance();
            $setUpSession = $bedFight->getSetUpSession();

            switch ($data) {
                case 0:
                    if ($setUpSession->exists($player->getName())) {
                        $player->sendMessage("You Already In Pending!");
                    } else {
                        $bedFight->getSetUpForm()->sendSetArenaNameForm($player);
                    }
                    break;
                case 1:
                    if (!$setUpSession->exists($player->getName())) {
                        $player->sendMessage("You Are Not In Pending!");
                    } else {
                        $setUpSession->remove_pending_setup($player->getName());
                        $player->getInventory()->clearAll();
                        $player->sendMessage("You Canceled SetUp Arena");
                    }
                    break;
                case 2:
                    $this->sendBedFightForm($player);
                    break;
            }
        });
        $form->setTitle("BedFight Manage");
        $form->addButton("Start SetUpArena");
        $form->addButton("Cancel SetUpArena");
        $form->addButton("Back");
        $player->sendForm($form);
    }

}