<?php

declare(strict_types=1);

namespace Amirreza\BedFight\event\game;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;

# do nothing yet o(*^▽^*)┛
class BedFighGameEvent implements Listener {

    public function onBreak(BlockBreakEvent $event): void {}

    public function onDeath(PlayerDeathEvent $event): void {}

    public function onQuit(PlayerQuitEvent $event): void {}

    public function onPlace(BlockPlaceEvent $event): void {}

    public function onMove(PlayerMoveEvent $event): void {}
}