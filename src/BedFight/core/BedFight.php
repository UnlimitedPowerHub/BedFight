<?php

declare(strict_types=1);

namespace BedFight\Core;

use BedFight\Arena\ArenaManager;
use BedFight\Bot\BotManager;
use BedFight\Command\CommandRegistry;
use BedFight\Config\ConfigManager;
use BedFight\Event\EventListener;
use BedFight\Game\GameManager;
use BedFight\Leaderboard\LeaderboardManager;
use BedFight\NPC\NPCManager;
use BedFight\Storage\StorageManager;
use BedFight\Utils\Logger;
use BedFight\Utils\VapmScheduler;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use vennv\vapm\VapmPMMP;
use function mkdir;

class BedFight extends PluginBase {

    private static ?BedFight $instance = null;
    private ConfigManager $config;
    private StorageManager $storage;
    private ArenaManager $arenaManager;
    private GameManager $gameManager;
    private BotManager $botManager;
    private LeaderboardManager $leaderboardManager;
    private NPCManager $npcManager;
    private VapmScheduler $vapmScheduler;
    private Logger $logger;
    private \BedFight\Form\FormManager $formManager;
    private \BedFight\Event\EventListener $eventListener;

    public static function getInstance(): BedFight {
        return self::$instance;
    }

    public function onLoad(): void {
        self::$instance = $this;
        $this->logger = new Logger($this->getLogger());
        $this->logger->info("Loading BedFight...");
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = new ConfigManager($this);
        $this->config->load();

        // Initialize LibVapmPMMP async runtime
        VapmPMMP::init($this);

        $dataFolder = $this->getDataFolder();
        $dataFolderPath = $dataFolder instanceof \SplFileInfo ? $dataFolder->getPathname() : (string) $dataFolder;
        if (!file_exists($dataFolderPath)) {
            mkdir($dataFolderPath, 0755, true);
        }

        $this->vapmScheduler = new VapmScheduler($this);
        $this->storage = new StorageManager($this, $this->config, $this->vapmScheduler);
        $this->storage->initialize();

        $this->arenaManager = new ArenaManager($this, $this->storage, $this->config);
        $this->gameManager = new GameManager($this, $this->arenaManager, $this->config);
        $this->botManager = new BotManager($this, $this->gameManager, $this->config);
        $this->leaderboardManager = new LeaderboardManager($this, $this->storage, $this->config);
        $this->npcManager = new NPCManager($this, $this->config);
        $this->formManager = new \BedFight\Form\FormManager($this);
        $this->eventListener = new \BedFight\Event\EventListener($this);

        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);

        $this->npcManager->loadNPCs();

        new CommandRegistry($this);

        $this->arenaManager->loadAllArenas();
        $this->leaderboardManager->startUpdateTask();
        $this->startMaintenanceTasks();

        $this->logger->info("BedFight v{$this->getDescription()->getVersion()} enabled!");
        $this->logger->info("Storage: {$this->config->getStorageType()}");
        $this->logger->info("Async runtime: LibVapmPMMP (Fibers)");
    }

    public function onDisable(): void {
        $this->gameManager->stopAllGames();
        $this->arenaManager->saveAllArenas();
        $this->leaderboardManager->save();
        $this->npcManager->despawnAll();
        $this->botManager->removeAllBots();
        $this->storage->close();
        $this->logger->info("BedFight disabled!");
    }

    private function startMaintenanceTasks(): void {
        $interval = $this->config->getArenaCleanupInterval() * 20 * 60;
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends \pocketmine\scheduler\Task {
            private BedFight $plugin;
            public function __construct(BedFight $plugin) { $this->plugin = $plugin; }
            public function onRun(): void {
                $this->plugin->getArenaManager()->cleanupEmptyArenas();
                $this->plugin->getGameManager()->cleanupFinishedGames();
            }
        }, $interval);
    }

    public function getConfigManager(): ConfigManager { return $this->config; }
    public function getStorage(): StorageManager { return $this->storage; }
    public function getArenaManager(): ArenaManager { return $this->arenaManager; }
    public function getGameManager(): GameManager { return $this->gameManager; }
    public function getBotManager(): BotManager { return $this->botManager; }
    public function getLeaderboardManager(): LeaderboardManager { return $this->leaderboardManager; }
    public function getNPCManager(): NPCManager { return $this->npcManager; }
    public function getVapmScheduler(): VapmScheduler { return $this->vapmScheduler; }
    public function getLoggerWrapper(): Logger { return $this->logger; }
    public function getFormManager(): \BedFight\Form\FormManager { return $this->formManager; }
    public function getEventListener(): \BedFight\Event\EventListener { return $this->eventListener; }
}