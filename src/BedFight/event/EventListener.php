<?php

declare(strict_types=1);

namespace BedFight\Event;

use BedFight\Arena\Arena;
use BedFight\Arena\ArenaManager;
use BedFight\Core\BedFight;
use BedFight\Form\FormManager;
use BedFight\Form\SimpleForm;
use BedFight\Form\CustomForm;
use BedFight\Game\GameManager;
use BedFight\Utils\Logger;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\player\Player;
use pocketmine\Server;

class EventListener implements Listener {

    private BedFight $plugin;
    private GameManager $gameManager;
    private ArenaManager $arenaManager;
    private FormManager $formManager;
    private Logger $logger;

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
        $this->gameManager = $plugin->getGameManager();
        $this->arenaManager = $plugin->getArenaManager();
        $this->formManager = new FormManager($plugin);
        $this->logger = $plugin->getLoggerWrapper();
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $event->setJoinMessage('');
        $this->gameManager->giveLobbyItems($player);
        $player->sendMessage($this->plugin->getConfigManager()->getMessage('welcome'));
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $event->setQuitMessage('');
        $this->gameManager->leaveGame($player);
        $this->plugin->getBotManager()->removeBot($player->getName());
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $name = $item->getCustomName();

        if ($name === $this->plugin->getConfigManager()->getMessage('prefix') . 'BedFight') {
            $this->showMainForm($player);
        } elseif ($name === 'Duel') {
            $player->sendMessage($this->plugin->getConfigManager()->getMessage('duel_soon'));
        } elseif ($name === 'About') {
            $this->showAbout($player);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        
        $arena = $this->gameManager->getPlayerArena($player);
        if ($arena === null) {
            // In lobby - only allow ops to break blocks
            if (!$player->isOp()) {
                $event->cancel();
            }
            return;
        }

        $game = $this->plugin->getGameManager()->getPlayerArena($player);
        if ($game !== null) {
            $this->handleGameBlockBreak($player, $block, $arena, $game);
        } else {
            $this->handleSetupBlockBreak($player, $block, $item, $arena);
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $arena = $this->gameManager->getPlayerArena($player);
        if ($arena === null) {
            // In lobby - only allow ops to place blocks
            if (!$player->isOp()) {
                $event->cancel();
            }
        }
    }

    public function onDamage(EntityDamageByEntityEvent $event): void {
        if (!$event->getDamager() instanceof Player) return;
        if (!$event->getEntity() instanceof Player) return;

        $damager = $event->getDamager();
        $victim = $event->getEntity();

        $arena = $this->gameManager->getPlayerArena($damager);
        if ($arena === null) {
            $event->cancel();
            return;
        }

        $game = $this->plugin->getGameManager()->getPlayerArena($damager);
        if ($game === null) {
            $event->cancel();
            return;
        }

        $damagerTeam = $this->plugin->getGameManager()->getPlayerTeam(
            $this->plugin->getGameManager()->games[$arena->getId()] ?? new class{public function getTeams(){return ['blue'=>[],'red'=>[]];}},
            $damager->getName()
        );
        $victimTeam = $this->plugin->getGameManager()->getPlayerTeam(
            $this->plugin->getGameManager()->games[$arena->getId()] ?? new class{public function getTeams(){return ['blue'=>[],'red'=>[]];}},
            $victim->getName()
        );

        if ($damagerTeam === $victimTeam) {
            $event->cancel();
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;

        $arena = $this->gameManager->getPlayerArena($entity);
        if ($arena === null) return;

        $cause = $event->getCause();
        if ($cause === EntityDamageEvent::CAUSE_VOID || $cause === EntityDamageEvent::CAUSE_FALL) {
            $event->setBaseDamage($entity->getHealth());
        }
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        $event->setDeathMessage('');
        $event->setDrops([]);

        $arena = $this->gameManager->getPlayerArena($victim);
        if ($arena === null) return;

        $killer = $victim->getLastDamageCause()?->getDamager();
        if ($killer instanceof Player) {
            $this->gameManager->handlePlayerDeath($victim, $killer, $arena);
        } else {
            $this->gameManager->handlePlayerDeath($victim, null, $arena);
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void {
        $player = $event->getPlayer();
        $arena = $this->gameManager->getPlayerArena($player);
        if ($arena === null) {
            $event->cancel();
        }
    }

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $arena = $this->gameManager->getPlayerArena($player);
        if ($arena === null) return;

        $game = $this->plugin->getGameManager()->getPlayerArena($player);
        if ($game === null) return;

        $from = $event->getFrom();
        $to = $event->getTo();

        if ($from->getFloorY() > 0 && $to->getFloorY() < 0) {
            $event->cancel();
            $this->gameManager->handlePlayerDeath($player, null, $arena);
        }
    }

    public function onRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        $arena = $this->gameManager->getPlayerArena($player);
        if ($arena === null) return;

        $event->setRespawnPosition($arena->getSpawnForTeam(
            $this->plugin->getGameManager()->getPlayerTeam(
                $this->plugin->getGameManager()->games[$arena->getId()] ?? new class{public function getTeams(){return ['blue'=>[],'red'=>[]];}},
                $player->getName()
            )
        ));
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if (!$packet instanceof ModalFormResponsePacket) return;

        $player = $event->getOrigin()->getPlayer();
        if ($player === null) return;

        $formId = $packet->formId;
        $data = json_decode($packet->formData ?? 'null', true);
        
        $this->formManager->handleResponse($player, $formId, $data);
    }

    public function showMainForm(Player $player): void {
        $form = $this->formManager->createSimpleForm();
        $form->setTitle($this->plugin->getConfigManager()->getMessage('prefix'));
        $form->setContent("§7Join a BedFight game\n§7Players in queue: §a" . $this->getQueueCount());

        $inQueue = $this->isInQueue($player);
        $form->addButton($inQueue ? "§cLeave Queue" : "§aJoin Queue");
        $form->addButton("§eLeaderboards");
        $form->addButton("§bStats");
        $form->setCallback(function (Player $player, $data) use ($inQueue) {
            if ($data === null) return;
            
            switch ($data) {
                case 0:
                    if ($inQueue) {
                        $this->gameManager->leaveGame($player);
                    } else {
                        $this->gameManager->joinQueue($player);
                    }
                    break;
                case 1:
                    $this->showLeaderboardForm($player);
                    break;
                case 2:
                    $this->showStatsForm($player);
                    break;
            }
        });

        $form->send($player);
    }

    private function showLeaderboardForm(Player $player): void {
        $categories = $this->plugin->getLeaderboardManager()->getCategories();
        
        $form = $this->formManager->createSimpleForm();
        $form->setTitle($this->plugin->getConfigManager()->getMessage('prefix') . ' Leaderboards');
        
        foreach ($categories as $category) {
            $form->addButton("§e" . ucfirst($category));
        }
        
        $form->setCallback(function (Player $player, $data) use ($categories) {
            if ($data === null) return;
            if (isset($categories[$data])) {
                $this->plugin->getLeaderboardManager()->showLeaderboard($player, $categories[$data]);
            }
        });

        $form->send($player);
    }

    private function showStatsForm(Player $player): void {
        $name = $player->getName();
        $form = $this->formManager->createSimpleForm();
        $form->setTitle($this->plugin->getConfigManager()->getMessage('prefix') . ' Your Stats');
        
        $categories = $this->plugin->getLeaderboardManager()->getCategories();
        foreach ($categories as $category) {
            $value = $this->plugin->getLeaderboardManager()->getStat($name, $category);
            $rank = $this->plugin->getLeaderboardManager()->getPlayerRank($name, $category);
            $form->addButton("§e" . ucfirst($category) . ": §a$value §7(Rank: §b#$rank§7)");
        }
        
        $form->send($player);
    }

    private function showAbout(Player $player): void {
        $player->sendMessage($this->plugin->getConfigManager()->getMessage('about'));
    }

    private function handleGameBlockBreak(Player $player, Block $block, Arena $arena, Game $game): void {
        $blockPos = $block->getPosition();
        
        foreach (['blue', 'red'] as $team) {
            $bedPos = $arena->getBedForTeam($team);
            if ($bedPos->equals($blockPos)) {
                $playerTeam = $this->plugin->getGameManager()->getPlayerTeam($game, $player->getName());
                if ($playerTeam !== $team) {
                    $this->gameManager->handleBedBreak($player, $arena, $team);
                } else {
                    $event->cancel();
                    $player->sendMessage("§cYou cannot break your own bed!");
                }
                return;
            }
        }

        $protected = false;
        foreach (['blue', 'red'] as $team) {
            $bedPos = $arena->getBedForTeam($team);
            if ($blockPos->distance($bedPos) <= 3) {
                $protected = true;
                break;
            }
        }

        if ($protected && $game->isBedAlive('blue') && $game->isBedAlive('red')) {
            $event->cancel();
        }
    }

    private function handleSetupBlockBreak(Player $player, Block $block, Item $item, Arena $arena): void {
        $itemName = $item->getCustomName();
        $validItems = ['SetBlueSpawn', 'SetBlueBed', 'SetRedSpawn', 'SetRedBed', 'Confirm', 'Cancel'];
        
        if (!in_array($itemName, $validItems)) {
            return;
        }

        $pos = $block->getPosition();
        
        switch ($itemName) {
            case 'SetBlueSpawn':
                $arena->setBlueSpawn($pos->asVector3());
                $player->sendMessage("§aBlue spawn set!");
                break;
            case 'SetBlueBed':
                $arena->setBlueBed($pos->asVector3());
                $player->sendMessage("§aBlue bed set!");
                break;
            case 'SetRedSpawn':
                $arena->setRedSpawn($pos->asVector3());
                $player->sendMessage("§aRed spawn set!");
                break;
            case 'SetRedBed':
                $arena->setRedBed($pos->asVector3());
                $player->sendMessage("§aRed bed set!");
                break;
            case 'Confirm':
                if (!$arena->isReady()) {
                    $player->sendMessage("§cArena not fully configured!");
                    return;
                }
                $arena->setStatus(Arena::STATUS_EMPTY);
                $this->arenaManager->saveArena($arena);
                $this->gameManager->sendToLobby($player);
                $player->sendMessage($this->plugin->getConfigManager()->getMessage('setup_complete'));
                break;
            case 'Cancel':
                $this->arenaManager->deleteArena($arena->getId());
                $this->gameManager->sendToLobby($player);
                $player->sendMessage($this->plugin->getConfigManager()->getMessage('setup_cancelled'));
                break;
        }
    }

    private function getQueueCount(): int {
        return 0;
    }

    private function isInQueue(Player $player): bool {
        return $this->gameManager->isInGame($player);
    }
}