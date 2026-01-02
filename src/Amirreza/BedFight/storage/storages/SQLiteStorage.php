<?php declare(strict_types=1);
/**
 * Persistent Key-Value Storage 1.0
 * (c) 2025 draconigen@dogpixels.net
 * AGPL 3.0, see https://www.gnu.org/licenses/agpl-3.0.de.html
 * Provided "as is", without warranty of any kind.
 */


/**
 * Static Class for persistent Key-Value storage.
 * Creates a database file (default: storage.sqlite) next to the script.
 */

namespace Amirreza\BedFight\storage\storages;


use Amirreza\BedFight\BedFight;
use Exception;
use SQLite3;
use Amirreza\BedFight\BedFightHelper;

class SQLiteStorage
{

    protected ?string $storage_name;
    protected ?string $storage_encryption_key;

    public function __construct(string $storage_name, string $storage_encryption_key = '')
    {
        $this->storage_name = BedFight::getInstance()->getDataFolder() . $storage_name . ".sqlite";
        $this->storage_encryption_key = $storage_encryption_key;

        $db = $this->getDbConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS cache (
            id    TEXT NOT NULL UNIQUE,
            mod   DATETIME DEFAULT (DATETIME('now', 'localtime')),
            data  TEXT,
            PRIMARY KEY(id)
        );");
        $db->close();
    }

    private function getDbConnection(): SQLite3
    {
        return new SQLite3($this->storage_name, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $this->storage_encryption_key);
    }

    private static function init(): Sqlite3
    {
        $me = BedFightHelper::get()->SQLiteStorage();

        $flagInitDatabase = !file_exists($me->storage_name);
        $db = new SQLite3($me->storage_name, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $me->storage_encryption_key);
        if ($flagInitDatabase) {
            $db->exec("CREATE TABLE cache (
                id    TEXT NOT NULL UNIQUE,
                mod   DATATIME DEFAULT (DATETIME('now', 'localtime')),
                data  TEXT,
                PRIMARY KEY(id)
            );");
        }
        return $db;
    }

    /**
     * Retrieve a single entry from database.
     * @param string $id Key of the entry to retrieve.
     * @return array JSON-serializable array on success, otherwise false
     * @throws Exception
     */
    public function get(string $id): array
    {
        $db = $this->getDbConnection();
        $stmt = $db->prepare("SELECT data FROM cache WHERE id=?;");
        if (!$stmt) return [];

        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $result = $stmt->execute();

        $data = [];
        if ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = json_decode($row['data'], true) ?? [];
        }

        $db->close();
        return $data;
    }

    /**
     * Retrieves all entries from database.
     * @return array Assoc array of id => [data]
     * @throws Exception
     */
    public function getAll(): array
    {
        $db = $this->getDbConnection();
        $result = $db->query("SELECT id, data FROM cache;");

        $ret = [];
        while ($result && $row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[$row['id']] = json_decode($row['data'], true);
        }

        $db->close();
        return $ret;
    }

    /**
     * Inserts or updates data
     * @param string $id Key of the entry to insert/update.
     * @param array $data JSON-serializable data array to write to database.
     * @return bool Success indicator.
     * @throws Exception
     */
    public function set(string $id, array $data): bool
    {
        $db = $this->getDbConnection();
        $json_data = json_encode($data);
        if ($json_data === false) {
            $db->close();
            return false;
        }

        $stmt = $db->prepare("INSERT INTO cache (id, data) VALUES(?, ?) ON CONFLICT(id) DO UPDATE SET data=excluded.data, mod=excluded.mod;");
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $stmt->bindValue(2, $json_data, SQLITE3_TEXT);
        $success = $stmt->execute() !== false;

        $db->close();
        return $success;
    }

    /**
     * Deletes a single entry.
     * @param string $id Key of entry to delete.
     * @return bool Success indicator.
     * @throws Exception
     */
    public function delete(string $id): bool
    {
        $db = $this->getDbConnection();
        $stmt = $db->prepare("DELETE FROM cache WHERE id=?;");
        $stmt->bindValue(1, $id, SQLITE3_TEXT);
        $success = $stmt->execute() !== false;

        $db->close();
        return $success;
    }

    /**
     * Get the total count of rows in the database.
     * @return int Total number of rows.
     */
    public function count(): int
    {
        $db = $this->getDbConnection();
        $count = $db->querySingle("SELECT COUNT(*) FROM cache;");
        $db->close();
        return (int)$count;
    }

    /**
     * Deletes all entries older than the given timespan.
     * @param string $age See https://www.sqlite.org/lang_datefunc.html for valid values.
     * @return bool Success indicator.
     * @throws Exception
     */
    public function prune(string $age): bool
    {
        $db = $this->getDbConnection();
        $stmt = $db->prepare("DELETE FROM cache WHERE mod <= DATETIME('now', 'localtime', ?);");
        $stmt->bindValue(1, $age, SQLITE3_TEXT);
        $success = $stmt->execute() !== false;

        $db->close();
        return $success;
    }
}