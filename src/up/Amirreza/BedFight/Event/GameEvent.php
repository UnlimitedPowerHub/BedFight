<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Event;


use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\GameMode;
use up\Amirreza\BedFight\BedFight;

class GameEvent implements Listener {

    public function onBedBroke(BlockBreakEvent $event): void
    {

        $player = $event->getPlayer();
        $playerName = $player->getName();
        $block_type_id = $event->getBlock()->getTypeId();
        $bed_type_id = VanillaBlocks::BED()->getTypeId();

        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();

        if(!$gameSession->getSessionPlayer($playerName)){
            $event->cancel();
            return;
        }

        $player_session = $gameSession->getSessionPlayer($player->getName());

        $blueTeam = $gameSession->getSessionBlueTeam($player_session);
        $redTeam = $gameSession->getSessionRedTeam($player_session);

        if ($block_type_id === $bed_type_id) {
            if ($blueTeam === $playerName) {
                $gameSession->setSessionRedBedStats($player_session);
                $redPlayer = $bedfight->getServer()->getPlayerExact($redTeam);
                $redPlayer->sendTitle("Bed Broked!");
                $redPlayer->sendMessage("Your Bed Broked!");
            } else {
                $gameSession->setSessionBlueBedStats($player_session);
                $bluePlayer = $bedfight->getServer()->getPlayerExact($blueTeam);
                $bluePlayer->sendTitle("Bed Broked!");
                $bluePlayer->sendMessage("Your Blue Broked!");
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        if(!$gameSession->getSessionPlayer($playerName)){
            return;
        }
        $player_session = $gameSession->getSessionPlayer($player->getName());
        $blueTeam = $gameSession->getSessionBlueTeam($player_session);
        $redTeam = $gameSession->getSessionRedTeam($player_session);
        if ($blueTeam === $playerName) {
            $bedStats = $gameSession->getSessionBlueBedStats($player_session);
            if ($bedStats === 'bnb'){
                return;
            }
            $bedfight->getGameManager()->endGame($player);
        } else {
            $bedStats = $gameSession->getSessionRedBedStats($player_session);
            if ($bedStats === 'bnb'){
                return;
            }
            $bedfight->getGameManager()->endGame($player);
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        if(!$gameSession->getSessionPlayer($player->getName())){
            return;
        }
        $bedfight->getGameManager()->endGame($player);
    }
}