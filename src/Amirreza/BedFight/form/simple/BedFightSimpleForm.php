<?php

declare(strict_types=1);

namespace Amirreza\BedFight\form\simple;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;

class BedFightSimpleForm {

    public function sendBFForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }
        });

        $form->setTitle(BedFightConstant::RBFFP);
        $form->setContent(
            BedFightConstant::NLWLU.
            "...".
            BedFightConstant::NLWLD
        );
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