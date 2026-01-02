<?php

declare(strict_types=1);

namespace Amirreza\BedFight\form\simple;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;

class BedFightSimpleForm {

    public function sendBFForm(Player $player): void {
        $gameSession = BedFightHelper::get()->BedFightGameSession();
        $playerName = $player->getName();
        $form = new SimpleForm(function (Player $player, ?int $data) use ($gameSession, $playerName) {
            if ($data === null) {
                return;
            }


            if ($data === 0) {
                if (!$gameSession->isConnect($playerName)) {
                    $gameSession->connect($playerName);
                } else {
                    $gameSession->disconnect($playerName);
                }
            }
        });

        $form->setTitle(BedFightConstant::RBFFP);
        $form->setContent(
            BedFightConstant::NLWLU.
            "...".
            BedFightConstant::NLWLD
        );
        if (!$gameSession->isConnect($playerName)) {
            $form->addButton("Join");
        } else {
            $form->addButton("Left");
        }
        $player->sendForm($form);
    }

    public function sendBFMForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }
            switch ($data) {
                case 0:
                    BedFightHelper::get()->BedFightManageCustomForm()->sendCreateArenaForm($player);
                    break;
                case 1:
                    $player->sendMessage(BedFightConstant::RBFFM . "very soon..(●'◡'●)");
                    break;
                case 2:
                    // Do Nothing Yet ¯\_(ツ)_/¯
                    break;
            }
        });

        $form->setTitle(BedFightConstant::RBFFP . " ⇒ Manage");
        $form->addButton("◜ Create Arena ◞");
        $form->addButton("◜ Manage Arenas ◞");
        $form->addButton(" Close ");
        $player->sendForm($form);
    }
}