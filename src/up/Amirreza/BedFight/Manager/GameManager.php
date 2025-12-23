<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Manager;


use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use up\Amirreza\BedFight\BedFight;

class GameManager {


    public function startGame(array $players, string $map): void
    {
        $bedfight = BedFight::getInstance();
        $gameSession = $bedfight->getGameSession();
        $gameStorage = $bedfight->getGameStorage();
        $teams = [
            'blue' => $players[1],
            'red' => $players[0],
            'status' => [
                'red' => 'bnb',
                'blue' => 'bnb'
            ]
        ];
        $session_name = $gameSession->startSession($players, $map, $teams);
        $game = [
            'session' => $session_name,
            'name' => $map,
            'teams' => $teams,
        ];
        $gameStorage->makeGame($game);
        $this->teleportingToGame($teams,$map);
    }

    private function teleportingToGame(
        array $teams,
        string $arenaName,
    ): void
    {
        $arenaStorage = BedFight::getInstance()->getArenaStorage();
        $blue = $teams['blue'];
        $red = $teams['red'];
        $blueArenaPos = $arenaStorage->getBlueTeamPos($arenaName);
        $redArenaPos = $arenaStorage->getRedTeamPos($arenaName);
        $player0 = Server::getInstance()->getPlayerExact($red);
        $player1 = Server::getInstance()->getPlayerExact($blue);
        $player0->teleport($redArenaPos);
        $this->giveBedFightItems($player0,'o');
        $player1->teleport($blueArenaPos);
        $this->giveBedFightItems($player1,'blue');
    }

    public function giveBedFightItems(Player $player, ?string $teamColor)
    : void
    {
        $pInv = $player->getInventory();
        $pInv->clearAll();

        if
        (
            $teamColor === 'blue'
        ) {
            $color = DyeColor::BLUE;
        } else {
            $color = DyeColor::RED;
        }

        $items =
            [
             VanillaItems::STONE_SWORD(),
                VanillaBlocks::WOOL()->setColor(
                    $color
                )->asItem()
                ->setCount(64)
                ,
                VanillaItems::STONE_AXE(),
                VanillaItems::STONE_PICKAXE(),
                VanillaItems::SHEARS(),
            ]
            ;

        foreach ($items as $item) {
            $pInv->addItem($item);
        }

        if
        (
            $teamColor === 'blue'
        ) {
            $color = Color::fromRGB(255);
        } else {
            $color = Color::fromRGB(125);
        }

        $pArInv = $player->getArmorInventory();

        $pArInv->setHelmet(
            VanillaItems::LEATHER_CAP()->setCustomColor($color)
        );
        $pArInv->setChestplate(
            VanillaItems::LEATHER_TUNIC()->setCustomColor($color)
        );
        $pArInv->setLeggings(
            VanillaItems::LEATHER_PANTS()->setCustomColor($color)
        );
        $pArInv->setBoots(
            VanillaItems::LEATHER_BOOTS()->setCustomColor($color)
        );
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
        },10*20);
    }
}
