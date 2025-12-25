<?php

namespace Amirreza\BedFight\form\custom;

use Amirreza\BedFight\BedFightHelper;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\player\Player;
use Amirreza\BedFight\constant\BedFightConstant;
use Amirreza\BedFight\world\WorldLoader;

class BedFightManageCustomForm {

    public function sendCreateArenaForm(Player $player): void
    {
        $options = WorldLoader::getWorlds();

        $form = new CustomForm(function (Player $player, ?array $index) use ($options) {
            if ($index === null) {
                return;
            }
            $arenaName = $index[0];
            $worldName = $options[$index[1]];
            BedFightHelper::get()->BedFightSetUpSession()->connect($player->getName());
            BedFightHelper::get()->BedFightArenaManager()->setArenaName($arenaName);
            BedFightHelper::get()->BedFightArenaManager()->setWorldName($worldName);
        });

        $form->setTitle(BedFightConstant::RBFFP . " ⇒ Create Arena");
        $form->addInput("Name", "...");
        $form->addDropdown("Map",$options);
        $player->sendForm($form);
    }
}
