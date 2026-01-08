<?php

declare(strict_types=1);

namespace Amirreza\BedFight\manager;

use Amirreza\BedFight\BedFight;
use Amirreza\BedFight\BedFightHelper;
use Amirreza\BedFight\constant\BedFightConstant;
use Amirreza\BedFight\world\WorldLoader;
use Exception;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;

class BedFightGameManager {

    private BedFightManager $bedFightManager;

    public function __construct()
    {
        $this->bedFightManager = new BedFightManager('game');
    }

    public function startGame(
        Player $player1,
        Player $player2,
        string $arenaName,
        array $arenaData,
    ) : void {
        $bedfight = BedFight::getInstance();
        $helper = BedFightHelper::get();
        $worldName = $arenaData['world'];
        $world = $bedfight->getServer()->getWorldManager()->getWorldByName($worldName);
        $teams = $arenaData['team'];
        $blueteam = $teams['blue'];
        $redteam = $teams['red'];
        $blueTeamPos = new Position($blueteam['x'], $blueteam['y'], $blueteam['z'], $world);
        $redTeamPos = new Position( $redteam['x'], $redteam['y'], $redteam['z'], $world);
        $players = [$player1, $player2];
        $i=true;
        foreach ($players as $player) {
            $player->sendMessage("Arena: " . $arenaName);
            if ($i) {
                $player->teleport($blueTeamPos);
                $player->sendMessage(BedFightConstant::RBFFM . "Red Team: " . $player2->getName());
                $i = false;
            } else {
                $player->teleport($redTeamPos);
                $player->sendMessage(BedFightConstant::RBFFM."Blue Team: " . $player1->getName());
            }
            $helper->BedFightGameSession()->connect($player->getName());
            $player->sendMessage(BedFightConstant::RBFFM."Have A Good Game (∩^o^)⊃");
        }
        $this->setPlayerArenaName($arenaName,$player1->getName());
        $this->setPlayerArenaName($arenaName,$player2->getName());
    }

    public function setPlayerArenaName(
        string $arenaName,
        string $player
    ): void
    {
        $this->bedFightManager->add($player,$arenaName);
    }

    public function getPlayerArenaName(
        string $player
    ): string {
        return $this->bedFightManager->get($player);
    }

    public function removePlayerArenaName(
        string $player,
    ) : void
    {
        $this->bedFightManager->remove($player);
    }

    /**
     * @throws Exception
     */
    public function endGame(
        Player $player1,
        Player $player2,
    ) : void
    {
        $helper = BedFightHelper::get();
        $helper->BedFightArenaStorage()->setArenaIsEmpty(
            $this->getPlayerArenaName($player1->getName())
                ?? $this->getPlayerArenaName($player2->getName())
        );
        $lobbyWorld = WorldLoader::getDefaultWorld();
        $players = [$player1, $player2];
        foreach ($players as $player) {
            $player->teleport($lobbyWorld->getSpawnLocation());
            $player->sendMessage("Good Game \(@^0^@)/");
        }
    }
}
