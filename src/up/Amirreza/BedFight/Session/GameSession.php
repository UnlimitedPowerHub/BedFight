<?php

declare(strict_types=1);

namespace up\Amirreza\BedFight\Session;

class GameSession {

    public ?array $sessions = [];

    public function isSession(string $session_name): bool {
        return array_key_exists(strtolower($session_name), $this->sessions);
    }

    /*
     * teams = [
     *      'red' => $players[0]
     *      'blue' => $players[1]
     *      'status' => [
     *              'red' => 'bnb', # bed not broked
     *              'blue' => 'bb' # bed broked
     *      ]
     * ]
     */
    public function startSession(array $players, string $map, array $teams): string {
        $session_name = strtolower($players[0].$players[1]);
        $this->sessions[strtolower($players[0])] = $session_name;
        $this->sessions[strtolower($players[1])] = $session_name;
        $this->sessions[$session_name] = ['players' => $players,'map' => $map, 'teams' => $teams];
        return $session_name;
    }

    public function getSessionPlayer(string $name): string {
        return $this->sessions[strtolower($name)];
    }

    public function getSessionMap(string $session_name): string {
        return $this->sessions[strtolower($session_name)]['map'];
    }

    public function getSessionRedTeam(string $session_name): string {
        return $this->sessions[strtolower($session_name)]['teams']['red'];
    }

    public function getSessionBlueTeam(string $session_name): string {
        return $this->sessions[strtolower($session_name)]['teams']['blue'];
    }

    public function getSessionRedBedStats(string $session_name): string {
        return $this->sessions[strtolower($session_name)]['teams']['blue']['stats'];
    }

    public function getSessionBlueBedStats(string $session_name): string {
        return $this->sessions[strtolower($session_name)]['teams']['blue']['stats'];
    }

    public function setSessionRedBedStats(string $session_name, string $stats='bb'): void {
        $this->sessions[strtolower($session_name)]['teams']['blue']['stats'] = $stats;
    }

    public function setSessionBlueBedStats(string $session_name, string $stats='bb'): void {
        $this->sessions[strtolower($session_name)]['teams']['blue']['stats'] = $stats;
    }

    public function getSessionPlayers(string $session_name): array {
        return $this->sessions[strtolower($session_name)]['players'];
    }

    public function endSession(string $session_name): void {
        unset($this->sessions[strtolower($session_name)]);
    }
}