<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Manager;


use pocketmine\entity\Location;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;
use up\Amirreza\BedFight\BedFight;

class GameManager {

    public function startGame(array $players, string $map): void {
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        $gameStorage = $bedfight->getGameStorage();
    }

    public function endGame(Player $player): void {
        $bedfight = BedFight::getInstance();
        $Lobby_Location = $bedfight->LOBBY->getSpawnLocation();
        $gameSession = $bedfight->getGameSession();
        $gameStorage = $bedfight->getGameStorage();
        $playerName = $player->getName();
        $player_session = $gameSession->getSessionPlayer($playerName);
        $blueTeam = $gameSession->getSessionBlueTeam($player_session);
        $redTeam = $gameSession->getSessionRedTeam($player_session);
        if ($blueTeam === $playerName) {
            $gameStorage->setGameWinner($player_session,'Red');
            $winner = $bedfight->getServer()->getPlayerExact($redTeam);
        } else {
            $gameStorage->setGameWinner($player_session,'Blue');
            $winner = $bedfight->getServer()->getPlayerExact($blueTeam);
        }

        $player->setGamemode(GameMode::SPECTATOR);
        $player->getInventory()->clearAll();
        $winner->getInventory()->clearAll();
        $player->sendMessage("Teleporting to lobby in 10s...");
        $player->sendMessage("Teleporting to lobby in 10s...");
        $player->sendTitle("Its Over!","You Are The Loser!");
        $winner->sendTitle("Its Over!","You Are The Winner!");

        $bedfight->getScheduler()->scheduleRepeatingTask( function (
        ) use ($Lobby_Location, $winner, $player, $player_session, $gameSession) {
            $lobby = (new Location($Lobby_Location->x, $Lobby_Location->y, $Lobby_Location->z, $Lobby_Location->world, $Lobby_Location->yaw, $Lobby_Location->pitch))->asVector3();
            $player->teleport($lobby);
            $winner->teleport($lobby);
            $gameSession->endSession($player_session);
        },20*10);
    }
}