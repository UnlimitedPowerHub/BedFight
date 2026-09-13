<?php

declare(strict_types=1);

namespace BedFight\Command;

use BedFight\Core\BedFight;
use BedFight\Arena\ArenaManager;
use BedFight\Config\ConfigManager;
use BedFight\Utils\Logger;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class CommandRegistry {

    private BedFight $plugin;
    private ConfigManager $config;
    private Logger $logger;

    public function __construct(BedFight $plugin) {
        $this->plugin = $plugin;
        $this->config = $plugin->getConfigManager();
        $this->logger = $plugin->getLoggerWrapper();
        
        $this->registerCommands();
    }

    private function registerCommands(): void {
        $commands = [
            'bedfight' => new BedFightCommand($this->plugin),
            'bf' => new BedFightCommand($this->plugin),
            'bedfightadmin' => new BedFightAdminCommand($this->plugin),
            'bfa' => new BedFightAdminCommand($this->plugin),
            'bedfightbot' => new BedFightBotCommand($this->plugin),
            'bfb' => new BedFightBotCommand($this->plugin),
            'bedfightnpc' => new BedFightNPCCommand($this->plugin),
            'bfn' => new BedFightNPCCommand($this->plugin),
        ];

        foreach ($commands as $name => $command) {
            $this->plugin->getServer()->getCommandMap()->register($name, $command);
        }
    }
}

abstract class BaseCommand extends Command {

    protected BedFight $plugin;
    protected ConfigManager $config;
    protected Logger $logger;

    public function __construct(BedFight $plugin, string $name, string $description, string $usage, array $aliases, string $permission) {
        $this->plugin = $plugin;
        $this->config = $plugin->getConfigManager();
        $this->logger = $plugin->getLoggerWrapper();
        
        parent::__construct($name, $description, $usage, $aliases);
        $this->setPermission($permission);
    }

    protected function sendMessage(CommandSender $sender, string $key, array $placeholders = []): void {
        $message = $this->config->getMessage($key);
        foreach ($placeholders as $k => $v) {
            $message = str_replace("%$k%", $v, $message);
        }
        $sender->sendMessage($message);
    }

    protected function checkPlayer(CommandSender $sender): ?Player {
        if (!$sender instanceof Player) {
            $this->sendMessage($sender, 'player_only');
            return null;
        }
        return $sender;
    }

    protected function checkPermission(CommandSender $sender, string $permission): bool {
        if (!$sender->hasPermission($permission)) {
            $this->sendMessage($sender, 'no_permission');
            return false;
        }
        return true;
    }
}

class BedFightCommand extends BaseCommand {

    public function __construct(BedFight $plugin) {
        parent::__construct($plugin, 'bedfight', 'Main BedFight command', '/bedfight <join|leave|stats|leaderboard>', ['bf'], 'bedfight.use');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        $player = $this->checkPlayer($sender);
        if ($player === null) return true;

        $subCommand = array_shift($args);
        
        switch ($subCommand ?? 'menu') {
            case 'join':
                $this->plugin->getGameManager()->joinQueue($player);
                break;
            case 'leave':
                $this->plugin->getGameManager()->leaveGame($player);
                break;
            case 'stats':
                $this->showStats($player);
                break;
            case 'leaderboard':
            case 'lb':
                $category = $args[0] ?? 'wins';
                $this->plugin->getLeaderboardManager()->showLeaderboard($player, $category);
                break;
            case 'menu':
            default:
                $this->plugin->getEventListener()->showMainForm($player);
                break;
        }
        return true;
    }

    private function showStats(Player $player): void {
        $name = $player->getName();
        $categories = $this->plugin->getLeaderboardManager()->getCategories();
        
        $player->sendMessage($this->config->getMessage('prefix') . ' §6Your Stats:');
        foreach ($categories as $category) {
            $value = $this->plugin->getLeaderboardManager()->getStat($name, $category);
            $rank = $this->plugin->getLeaderboardManager()->getPlayerRank($name, $category);
            $player->sendMessage("  §e" . ucfirst($category) . ": §a$value §7(Rank: §b#$rank§7)");
        }
    }
}

class BedFightAdminCommand extends BaseCommand {

    private array $setupSessions = [];

    public function __construct(BedFight $plugin) {
        parent::__construct($plugin, 'bedfightadmin', 'BedFight admin commands', '/bedfightadmin <create|delete|list|setspawn|reload>', ['bfa'], 'bedfight.admin');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->checkPermission($sender, 'bedfight.admin')) return true;

        $player = $this->checkPlayer($sender);
        if ($player === null) return true;

        $subCommand = array_shift($args);
        
        switch ($subCommand ?? 'help') {
            case 'create':
                $this->createArena($player, $args);
                break;
            case 'delete':
                $this->deleteArena($player, $args);
                break;
            case 'list':
                $this->listArenas($player);
                break;
            case 'setspawn':
                $this->setSpawn($player, $args);
                break;
            case 'reload':
                $this->reload($sender);
                break;
            case 'tp':
                $this->teleport($player, $args);
                break;
            case 'help':
            default:
                $this->showHelp($sender);
                break;
        }
        return true;
    }

    private function createArena(Player $player, array $args): void {
        $name = $args[0] ?? null;
        $worldName = $args[1] ?? $player->getWorld()->getFolderName();
        
        if ($name === null) {
            $this->sendMessage($player, 'arena_usage_create');
            return;
        }

        $arena = $this->plugin->getArenaManager()->createArena($name, $worldName);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_already_exists', ['arena' => $name]);
            return;
        }

        $this->startSetupSession($player, $arena);
        $this->sendMessage($player, 'arena_created', ['arena' => $name]);
    }

    private function deleteArena(Player $player, array $args): void {
        $name = $args[0] ?? null;
        if ($name === null) {
            $this->sendMessage($player, 'arena_usage_delete');
            return;
        }

        $arena = $this->plugin->getArenaManager()->getArenaByName($name);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_not_found', ['arena' => $name]);
            return;
        }

        if ($this->plugin->getArenaManager()->deleteArena($arena->getId())) {
            $this->sendMessage($player, 'arena_deleted', ['arena' => $name]);
        } else {
            $player->sendMessage("§cCannot delete arena with active game!");
        }
    }

    private function listArenas(Player $player): void {
        $arenas = $this->plugin->getArenaManager()->getAllArenas();
        if (empty($arenas)) {
            $player->sendMessage("§cNo arenas found.");
            return;
        }

        $player->sendMessage($this->config->getMessage('prefix') . ' §6Arenas:');
        foreach ($arenas as $arena) {
            $status = $arena->getStatus();
            $players = $arena->getPlayerCount();
            $ready = $arena->isReady() ? '§a✓' : '§c✗';
            $player->sendMessage("  §e{$arena->getName()} §7({$arena->getWorldName()}) §8[$ready] §7Status: §f$status §7Players: §f$players");
        }
    }

    private function setSpawn(Player $player, array $args): void {
        $type = $args[0] ?? null;
        $arenaName = $args[1] ?? null;
        
        if ($type === null || $arenaName === null) {
            $this->sendMessage($player, 'arena_usage_setspawn');
            return;
        }

        $arena = $this->plugin->getArenaManager()->getArenaByName($arenaName);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_not_found', ['arena' => $arenaName]);
            return;
        }

        $pos = $player->getPosition()->asVector3();
        
        switch (strtolower($type)) {
            case 'bluespawn':
                $arena->setBlueSpawn($pos);
                $player->sendMessage("§aBlue spawn set for {$arenaName}!");
                break;
            case 'bluebed':
                $arena->setBlueBed($pos);
                $player->sendMessage("§aBlue bed set for {$arenaName}!");
                break;
            case 'redspawn':
                $arena->setRedSpawn($pos);
                $player->sendMessage("§aRed spawn set for {$arenaName}!");
                break;
            case 'redbed':
                $arena->setRedBed($pos);
                $player->sendMessage("§aRed bed set for {$arenaName}!");
                break;
            default:
                $player->sendMessage("§cInvalid type! Use: bluespawn, bluebed, redspawn, redbed");
                return;
        }
        
        $this->plugin->getArenaManager()->saveArena($arena);
    }

    private function reload(CommandSender $sender): void {
        $this->plugin->getConfigManager()->load();
        $this->plugin->getArenaManager()->loadAllArenas();
        $this->sendMessage($sender, 'config_reloaded');
    }

    private function teleport(Player $player, array $args): void {
        $arenaName = $args[0] ?? null;
        if ($arenaName === null) {
            $this->sendMessage($player, 'arena_usage_tp');
            return;
        }

        $arena = $this->plugin->getArenaManager()->getArenaByName($arenaName);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_not_found', ['arena' => $arenaName]);
            return;
        }

        $world = $this->plugin->getArenaManager()->getWorldForArena($arena);
        if ($world === null) {
            $player->sendMessage("§cWorld not loaded!");
            return;
        }

        $player->teleport($world->getSafeSpawn());
        $player->sendMessage("§aTeleported to arena: $arenaName");
    }

    private function startSetupSession(Player $player, Arena $arena): void {
        $this->setupSessions[$player->getName()] = $arena->getId();
        
        $items = $this->config->getItems('setup');
        $player->getInventory()->clearAll();
        
        $step1 = $this->createItem($items['step1']);
        $player->getInventory()->setItem(0, $step1);
        
        $player->sendMessage($this->config->getMessage('setup_mode'));
    }

    private function createItem(array $data): Item {
        $type = $data['type'] ?? 'AIR';
        $name = $data['name'] ?? '';
        
        $item = VanillaItems::getInstance()->get($type);
        if ($item->isNull()) return VanillaItems::AIR();
        
        $item->setCustomName($name);
        return $item;
    }

    private function showHelp(CommandSender $sender): void {
        $sender->sendMessage($this->config->getMessage('prefix') . ' §6Admin Commands:');
        $sender->sendMessage("  §e/bfa create <name> [world] §7- Create new arena");
        $sender->sendMessage("  §e/bfa delete <name> §7- Delete arena");
        $sender->sendMessage("  §e/bfa list §7- List all arenas");
        $sender->sendMessage("  §e/bfa setspawn <type> <arena> §7- Set spawn/bed (bluespawn, bluebed, redspawn, redbed)");
        $sender->sendMessage("  §e/bfa tp <arena> §7- Teleport to arena");
        $sender->sendMessage("  §e/bfa reload §7- Reload config and arenas");
    }
}

class BedFightBotCommand extends BaseCommand {

    public function __construct(BedFight $plugin) {
        parent::__construct($plugin, 'bedfightbot', 'Bot management', '/bedfightbot <add|remove|list|fill>', ['bfb'], 'bedfight.bot');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->checkPermission($sender, 'bedfight.bot')) return true;

        $player = $this->checkPlayer($sender);
        if ($player === null) return true;

        $subCommand = array_shift($args);
        
        switch ($subCommand ?? 'help') {
            case 'add':
                $this->addBot($player, $args);
                break;
            case 'remove':
                $this->removeBot($player, $args);
                break;
            case 'list':
                $this->listBots($player);
                break;
            case 'fill':
                $this->fillArena($player, $args);
                break;
            case 'help':
            default:
                $this->showHelp($sender);
                break;
        }
        return true;
    }

    private function addBot(Player $player, array $args): void {
        $arenaName = $args[0] ?? null;
        $difficulty = $args[1] ?? 'player';
        
        if ($arenaName === null) {
            $this->sendMessage($player, 'bot_usage_add');
            return;
        }

        $arena = $this->plugin->getArenaManager()->getArenaByName($arenaName);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_not_found', ['arena' => $arenaName]);
            return;
        }

        $bot = $this->plugin->getBotManager()->addBot($arena, $difficulty);
        if ($bot !== null) {
            $this->sendMessage($player, 'bot_added', ['name' => $bot->getName(), 'difficulty' => $difficulty, 'arena' => $arenaName]);
        } else {
            $player->sendMessage("§cFailed to add bot (arena full or bots disabled)");
        }
    }

    private function removeBot(Player $player, array $args): void {
        $name = $args[0] ?? null;
        if ($name === null) {
            $this->sendMessage($player, 'bot_usage_remove');
            return;
        }

        if ($this->plugin->getBotManager()->removeBot($name)) {
            $this->sendMessage($player, 'bot_removed', ['name' => $name]);
        } else {
            $player->sendMessage("§cBot not found!");
        }
    }

    private function listBots(Player $player): void {
        $bots = $this->plugin->getBotManager()->getBotsInArena('');
        $player->sendMessage("§cNo arena specified. Use /bfb list <arena>");
    }

    private function fillArena(Player $player, array $args): void {
        $arenaName = $args[0] ?? null;
        $count = (int)($args[1] ?? 8);
        
        if ($arenaName === null) {
            $this->sendMessage($player, 'bot_usage_fill');
            return;
        }

        $arena = $this->plugin->getArenaManager()->getArenaByName($arenaName);
        if ($arena === null) {
            $this->sendMessage($player, 'arena_not_found', ['arena' => $arenaName]);
            return;
        }

        $this->plugin->getBotManager()->fillArena($arena, $count);
        $player->sendMessage("§aFilled arena $arenaName with $count bots");
    }

    private function showHelp(CommandSender $sender): void {
        $sender->sendMessage($this->config->getMessage('prefix') . ' §6Bot Commands:');
        $sender->sendMessage("  §e/bfb add <arena> [difficulty] §7- Add bot (noob, player, pro)");
        $sender->sendMessage("  §e/bfb remove <name> §7- Remove bot");
        $sender->sendMessage("  §e/bfb fill <arena> [count] §7- Fill arena with bots");
    }
}

class BedFightNPCCommand extends BaseCommand {

    public function __construct(BedFight $plugin) {
        parent::__construct($plugin, 'bedfightnpc', 'NPC management', '/bedfightnpc <create|remove|list|tp>', ['bfn'], 'bedfight.npc');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->checkPermission($sender, 'bedfight.npc')) return true;

        $player = $this->checkPlayer($sender);
        if ($player === null) return true;

        $subCommand = array_shift($args);
        
        switch ($subCommand ?? 'help') {
            case 'create':
                $this->createNPC($player, $args);
                break;
            case 'remove':
                $this->removeNPC($player, $args);
                break;
            case 'list':
                $this->listNPCs($player);
                break;
            case 'tp':
                $this->teleportNPC($player, $args);
                break;
            case 'help':
            default:
                $this->showHelp($sender);
                break;
        }
        return true;
    }

    private function createNPC(Player $player, array $args): void {
        $name = $args[0] ?? 'NPC';
        $arenaName = $args[1] ?? null;
        $arenaId = null;
        
        if ($arenaName !== null) {
            $arena = $this->plugin->getArenaManager()->getArenaByName($arenaName);
            if ($arena !== null) {
                $arenaId = $arena->getId();
            }
        }

        $npc = $this->plugin->getNPCManager()->createNPC($player, $name, $arenaId);
        $this->sendMessage($player, 'npc_created', ['name' => $npc->getName()]);
    }

    private function removeNPC(Player $player, array $args): void {
        $id = $args[0] ?? null;
        if ($id === null) {
            $this->sendMessage($player, 'npc_usage_remove');
            return;
        }

        if ($this->plugin->getNPCManager()->removeNPC($id)) {
            $this->sendMessage($player, 'npc_removed', ['name' => $id]);
        } else {
            $player->sendMessage("§cNPC not found!");
        }
    }

    private function listNPCs(Player $player): void {
        $npcs = $this->plugin->getNPCManager()->getAllNPCs();
        if (empty($npcs)) {
            $player->sendMessage("§cNo NPCs found.");
            return;
        }

        $player->sendMessage($this->config->getMessage('prefix') . ' §6NPCs:');
        foreach ($npcs as $npc) {
            $arena = $npc->getArenaId() ? $this->plugin->getArenaManager()->getArena($npc->getArenaId()) : null;
            $arenaName = $arena ? $arena->getName() : 'Global';
            $player->sendMessage("  §e{$npc->getName()} §7(ID: {$npc->getId()}) §7Arena: §f$arenaName");
        }
    }

    private function teleportNPC(Player $player, array $args): void {
        $id = $args[0] ?? null;
        if ($id === null) {
            $this->sendMessage($player, 'npc_usage_tp');
            return;
        }

        $npc = $this->plugin->getNPCManager()->getNPC($id);
        if ($npc === null) {
            $player->sendMessage("§cNPC not found!");
            return;
        }

        $player->teleport($npc->getPosition());
        $player->sendMessage("§aTeleported to NPC: {$npc->getName()}");
    }

    private function showHelp(CommandSender $sender): void {
        $sender->sendMessage($this->config->getMessage('prefix') . ' §6NPC Commands:');
        $sender->sendMessage("  §e/bfn create <name> [arena] §7- Create NPC at your location");
        $sender->sendMessage("  §e/bfn remove <id> §7- Remove NPC");
        $sender->sendMessage("  §e/bfn list §7- List all NPCs");
        $sender->sendMessage("  §e/bfn tp <id> §7- Teleport to NPC");
    }
}