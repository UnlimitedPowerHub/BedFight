<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

class GameForm {


    public function sendBedFightForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $player->sendMessage("Clicked on Join Button!");
                    break;
                case 1:
                    $player->sendMessage("Clicked on Leave Button!");
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
}