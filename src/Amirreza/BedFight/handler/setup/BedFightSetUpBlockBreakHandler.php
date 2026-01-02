<?php

declare(strict_types=1);

namespace Amirreza\BedFight\handler\setup;

use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;
use Amirreza\BedFight\utils\ExecutionUtils;
use Exception;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;

class BedFightSetUpBlockBreakHandler
{

    /**
     * @throws Exception
     */
    public function handle(Player $player, Item $item, Block $block): void
    {
        $block_position = $block->getPosition();
        $helper = BedFightHelper::get();
        $arenaManager = $helper->BedFightArenaManager();

        ExecutionUtils::do()->forPlayer($player)->action(
            'setup_block_break_handler',
            function () use ($player, $arenaManager, $block_position, $helper, $item) {
                $itemName = $item->getCustomName();
                $x = (int)$block_position->x;
                $y = (int)$block_position->y;
                $z = (int)$block_position->z;

                $proceedToNextStep = true;

                switch ($itemName) {
                    case "SetBlueSpawn":
                        $arenaManager->setBlueTeamX($x);
                        $arenaManager->setBlueTeamY($y);
                        $arenaManager->setBlueTeamZ($z);
                        $player->sendMessage("BlueSpawn Set Successfully!");
                        break;

                    case "SetBlueBed":
                        $arenaManager->setBlueBedX($x);
                        $arenaManager->setBlueBedY($y);
                        $arenaManager->setBlueBedZ($z);
                        $player->sendMessage("BlueBed Set Successfully!");
                        break;

                    case "SetRedSpawn":
                        $arenaManager->setRedTeamX($x);
                        $arenaManager->setRedTeamY($y);
                        $arenaManager->setRedTeamZ($z);
                        $player->sendMessage("RedSpawn Set Successfully!");
                        break;

                    case "SetRedBed":
                        $arenaManager->setRedBedX($x);
                        $arenaManager->setRedBedY($y);
                        $arenaManager->setRedBedZ($z);
                        $player->sendMessage("RedBed Set Successfully!");
                        break;

                    case "Confirm":
                        $helper->BedFightArenaStorage()->makeArena();
                        $player->sendMessage(BedFightConstant::RBFFM . "Arena Created Successfully!");
                        $helper->BedFightSetUpSession()->disconnect($player->getName());
                        $player->getInventory()->clearAll();
                        $proceedToNextStep = false;
                        break;

                    case "Cancel":
                        $arenaManager->clearData();
                        $defaultWorld = $player->getServer()->getWorldManager()->getDefaultWorld();
                        if ($defaultWorld !== null) {
                            $player->teleport($defaultWorld->getSafeSpawn());
                        }
                        $helper->BedFightSetUpSession()->disconnect($player->getName());
                        $player->getInventory()->clearAll();
                        $player->sendMessage(BedFightConstant::RBFFM . "Arena Creation Canceled Successfully!");
                        $proceedToNextStep = false;
                        break;

                    default:
                        $proceedToNextStep = false;
                        break;
                }

                if ($proceedToNextStep) {
                    $helper->BedFightHandler()->BedFightSetUpStepHandler()->handle($player);
                }
            },
            1.0
        );
    }
}