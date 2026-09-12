# BedFight - High-Performance BedWars Plugin

A complete, production-ready BedWars plugin for PocketMine-MP with advanced features including AI bots, leaderboards, NPCs, and multi-storage support.

## Features

### Core Gameplay
- **BedWars Game Logic**: Complete game loop with bed destruction, team elimination, and win conditions
- **Arena System**: Multi-arena support with configurable spawns, beds, and world management
- **Matchmaking**: Automatic queue system with arena assignment
- **Lobby & Game Timers**: Configurable pre-game and game duration timers
- **Sudden Death**: Optional sudden death mode when time runs out
- **Respawn System**: Bed-based respawning with configurable delays

### Performance Optimizations
- **Async Storage**: Non-blocking database operations using PocketMine's AsyncTask
- **Multi-Storage Backends**: SQLite (default), JSON, and MySQL support
- **Caching Layer**: In-memory caching with configurable TTL
- **Batch Operations**: Bulk database writes for better performance
- **Connection Pooling**: MySQL connection pool for high concurrency
- **WAL Mode**: SQLite Write-Ahead Logging for better concurrent access
- **Optimized Particles/Sounds**: Direct packet sending for reduced overhead

### AI Bot System
- **Three Difficulties**: Noob, Player, Pro with distinct behaviors
- **Pathfinding**: Basic, Advanced, and Expert pathfinding modes
- **Combat AI**: Target selection, attack timing, and damage scaling
- **Building/Bridging**: Pro bots can bridge and build defenses
- **Bed Defense**: Bots prioritize defending their bed
- **Configurable Spawn**: Auto-fill arenas with bots

### Leaderboards
- **Multiple Categories**: Wins, Kills, Beds Destroyed, Games Played, Win Streak, KDR
- **Real-time Updates**: Async leaderboard updates with periodic persistence
- **Player Stats**: Individual player statistics and rankings
- **In-Game Display**: Form-based leaderboard viewing

### NPC System
- **Custom NPCs**: Create NPCs with custom names, skins, and positions
- **Arena Integration**: NPCs can be tied to specific arenas
- **Animations**: Support for NPC animations (swing, interact, etc.)
- **Equipment**: Custom equipment and armor
- **Persistence**: NPCs saved to storage and loaded on startup

### Administration
- **Arena Management**: Create, delete, list, and configure arenas
- **Setup Wizard**: Step-by-step arena configuration with items
- **Bot Management**: Add, remove, list, and fill arenas with bots
- **NPC Management**: Create, remove, list, and teleport to NPCs
- **Configuration**: Comprehensive YAML configuration with hot-reload

## Installation

1. Place `BedFight.phar` in your `plugins/` folder
2. Restart the server
3. Configure `plugins/BedFight/config.yml` to your liking
4. Use `/bfa create <name> [world]` to create your first arena
5. Use the setup items to configure spawns and beds
6. Players can join with `/bf` or `/bedfight`

## Commands

### Player Commands
| Command | Aliases | Description |
|---------|---------|-------------|
| `/bedfight` | `/bf` | Main menu - join queue, view stats, leaderboards |
| `/bedfight join` | - | Join matchmaking queue |
| `/bedfight leave` | - | Leave current game/queue |
| `/bedfight stats` | - | View your statistics |
| `/bedfight leaderboard [category]` | `/bf lb` | View leaderboard |

### Admin Commands (`bedfight.admin`)
| Command | Aliases | Description |
|---------|---------|-------------|
| `/bedfightadmin create <name> [world]` | `/bfa` | Create new arena |
| `/bedfightadmin delete <name>` | - | Delete arena |
| `/bedfightadmin list` | - | List all arenas |
| `/bedfightadmin setspawn <type> <arena>` | - | Set spawn/bed (bluespawn, bluebed, redspawn, redbed) |
| `/bedfightadmin tp <arena>` | - | Teleport to arena |
| `/bedfightadmin reload` | - | Reload config and arenas |

### Bot Commands (`bedfight.bot`)
| Command | Aliases | Description |
|---------|---------|-------------|
| `/bedfightbot add <arena> [difficulty]` | `/bfb` | Add bot (noob, player, pro) |
| `/bedfightbot remove <name>` | - | Remove bot |
| `/bedfightbot list` | - | List bots |
| `/bedfightbot fill <arena> [count]` | - | Fill arena with bots |

### NPC Commands (`bedfight.npc`)
| Command | Aliases | Description |
|---------|---------|-------------|
| `/bedfightnpc create <name> [arena]` | `/bfn` | Create NPC at your location |
| `/bedfightnpc remove <id>` | - | Remove NPC |
| `/bedfightnpc list` | - | List all NPCs |
| `/bedfightnpc tp <id>` | - | Teleport to NPC |

## Configuration

The plugin uses `config.yml` for all configuration. Key sections:

### Storage
```yaml
storage:
  type: "sqlite"  # sqlite, json, mysql
  sqlite:
    file: "bedfight.db"
    wal_mode: true
    busy_timeout: 5000
  mysql:
    host: "localhost"
    port: 3306
    database: "bedfight"
    username: "root"
    password: ""
    pool_size: 10
```

### Game Settings
```yaml
game:
  min_players: 2
  max_players_per_team: 4
  game_timer: 900        # 15 minutes
  lobby_timer: 30        # 30 seconds
  respawn_enabled: true
  respawn_delay: 5
  sudden_death: true
  sudden_death_time: 60
```

### Bot Difficulties
```yaml
bot:
  enabled: true
  max_bots_per_arena: 8
  difficulties:
    noob:
      aim_assist: 0.1
      reaction_time: 800
      build_speed: 0.3
      combat_skill: 0.1
      pathfinding: "basic"
    player:
      aim_assist: 0.4
      reaction_time: 400
      build_speed: 0.6
      combat_skill: 0.4
      pathfinding: "advanced"
    pro:
      aim_assist: 0.8
      reaction_time: 100
      build_speed: 1.0
      combat_skill: 0.9
      pathfinding: "expert"
```

## Architecture

```
src/BedFight/
├── Core/           # Main plugin class, initialization
├── Config/         # Configuration management
├── Storage/        # Storage abstraction (SQLite, JSON, MySQL)
├── Form/           # Custom FormAPI implementation
├── Arena/          # Arena and Game models + Manager
├── Game/           # Game logic, timers, matchmaking
├── Bot/            # AI Bot system with 3 difficulties
├── Leaderboard/    # Statistics and leaderboards
├── NPC/            # NPC system
├── Command/        # Command registration and handling
├── Event/          # Event listeners
├── Utils/          # Async scheduler, particles, sounds, logger
├── Item/           # Item factories (empty, for future use)
├── Particle/       # Particle effects (empty, for future use)
└── Sound/          # Sound effects (empty, for future use)
```

## Performance Tips for Low-End VPS

1. **Use SQLite with WAL mode** (default) - best for single-server setups
2. **Reduce async threads** to 2-3 in config if CPU is limited
3. **Disable particle/sound optimizations** if not needed
4. **Reduce max concurrent arenas** to match your RAM
5. **Use JSON storage** for very small servers (< 10 players)
6. **Increase cache TTL** to reduce database reads
7. **Disable bots** if not needed - they consume CPU for AI

## Requirements

- PocketMine-MP 5.0.0+
- PHP 8.1+
- SQLite3 extension (for SQLite storage)
- MySQL extension (for MySQL storage)

## License

This project is licensed under the AGPL-3.0 License.

## Credits

- **Author**: UnlimitedPowerHub
- **Original FormAPI**: jojoe77777 (reference implementation)
- **SQLiteStorage**: draconigen@dogpixels.net (reference implementation)

## Support

- GitHub: https://github.com/UnlimitedPowerHub/BedFight
- Issues: Report bugs and feature requests on GitHub