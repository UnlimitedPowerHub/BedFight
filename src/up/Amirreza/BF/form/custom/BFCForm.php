<?php

namespace Amirreza\BF\form\custom;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\player\Player;
use Amirreza\BF\trait\CFUTrait;
use Amirreza\BF\world\WorldLoader;

class BFCForm {

    use CFUTrait;

    public function sendCreateArenaForm(Player $player): void
    {
        $options = WorldLoader::getWorlds();

        $form = new CustomForm(function (Player $player, ?array $index) use ($options) {
            if ($index === null) {
                return;
            }
            $arenaName = $index[0];
            $worldName = $options[$index[1]];
            print_r($arenaName);
            print_r($worldName);
        });

        $form->setTitle(self::RBFFP . " ⇒ Create Arena");
        $form->addInput("Name", "...");
        $form->addDropdown("Map",$options);
        $player->sendForm($form);
    }
}
