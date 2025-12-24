<?php

declare(strict_types=1);

namespace up\Amirreza\BF\form\simple;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use up\Amirreza\BF\BFHelper;
use up\Amirreza\BF\trait\CFUTrait;

class BFSForm {

    use CFUTrait;

    public function sendBFForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }
        });

        $form->setTitle(self::RBFFP);
        $form->setContent(
            self::NLWLU.
            "...".
            self::NLWLD
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
                    BFHelper::get()->BFCForm()->sendCreateArenaForm($player);
                    break;
                case 1:
                    $player->sendMessage(self::RBFFM . "very soon..(●'◡'●)");
                    break;
                case 2:
                    // Do Nothing Yet ¯\_(ツ)_/¯
                    break;
            }
        });

        $form->setTitle(self::RBFFP . " ⇒ Manage");
        $form->addButton("◜ Create Arena ◞");
        $form->addButton("◜ Manage Arenas ◞");
        $form->addButton(" Close ");
        $player->sendForm($form);
    }
}