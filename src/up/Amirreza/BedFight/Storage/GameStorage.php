<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Storage;


use pocketmine\utils\Config;
use up\Amirreza\BedFight\BedFight;

class GameStorage {


    private Config $games;

    public function __construct() {
        $this->games = new Config( BedFight::getInstance()->getDataFolder() . "games.json", Config::JSON );
    }


    public function getGames(): array {
        return $this->games->getAll();
    }

    public function makeGame(array $game): void {
        $gameSession = $game['session'];
        $gameTeams = $game['teams'];
        $gameWinner = "";

        $game_data = [
            'session'=>$gameSession,
            'teams'=>$gameTeams,
            'winner'=>$gameWinner
        ];

        $this->games->set($game['name'], $game_data);
        $this->games->save();
    }

    public function setGameWinner(string $gameName,string $winner): void {
        $this->games->setNested($gameName.'winner', $winner);
        $this->games->save();
    }
}
