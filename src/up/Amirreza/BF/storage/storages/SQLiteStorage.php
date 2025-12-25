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

namespace Amirreza\BF\storage\storages;


use Exception;
use SQLite3;
use up\Amirreza\BF\BFHelper;

class SQLiteStorage
{

    protected ?string $storage_name;
    protected ?string $storage_encryption_key;

    public function __construct(string $storage_name, string $storage_encryption_key = '')
    {
        $this->storage_name = $storage_name;
        $this->storage_encryption_key = $storage_encryption_key;
    }

    private static function init(): Sqlite3
    {
        $me = BFHelper::get()->SQLiteStorage();

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
     * @return array|bool JSON-serializable array on success, otherwise false
     * @throws Exception
     */
    public function get(string $id): array|bool
    {
        $db = self::init();
        $stmt = $db->prepare("SELECT data FROM cache WHERE id=?;");
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT)) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        $cur = $stmt->execute();
        if (!$cur) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        while ($row = $cur->fetchArray()) {
            return json_decode($row['data'], true);
        }
        return [];
    }

    /**
     * Retrieves all entries from database.
     * @return array Assoc array of id => [data]
     * @throws Exception
     */
    public function getAll(): array
    {
        $me = BFHelper::get()->SQLiteStorage();
        $db = $me->init();
        $ret = [];
        $stmt = $db->prepare("SELECT id, data FROM cache;");
        if (!$stmt) {
            throw new Exception($db->lastErrorMsg());
            return $ret;
        }
        $cur = $stmt->execute();
        if (!$cur) {
            throw new Exception($db->lastErrorMsg());
            return $ret;
        }
        while ($row = $cur->fetchArray()) {
            $ret[$row['id']] = json_decode($row['data'], true);
        }
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
        $me = BFHelper::get()->SQLiteStorage();
        $db = $me->init();
        $jdata = json_encode($data);
        if ($jdata === false) {
            throw new Exception(json_last_error_msg());
            return false;
        }
        $stmt = $db->prepare("INSERT INTO cache (id, data) VALUES(?, ?) ON CONFLICT(id) DO UPDATE SET data=excluded.data, mod=excluded.mod;");
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT) || !$stmt->bindValue(2, $jdata, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Deletes a single entry.
     * @param string $id Key of entry to delete.
     * @return bool Success indicator.
     * @throws Exception
     */
    public function delete(string $id): bool
    {
        $me = BFHelper::get()->SQLiteStorage();
        $db = $me->init();
        $stmt = $db->prepare("DELETE FROM cache WHERE id=?;");
        if (!$stmt || !$stmt->bindValue(1, $id, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }

    /**
     * Get the total count of rows in the database.
     * @return int Total number of rows.
     */
    public function count(): int
    {
        $me = BFHelper::get()->SQLiteStorage();
        $db = $me->init();
        return $db->querySingle("SELECT COUNT(*) FROM cache;");
    }

    /**
     * Deletes all entries older than the given timespan.
     * @param string $age See https://www.sqlite.org/lang_datefunc.html for valid values.
     * @return bool Success indicator.
     * @throws Exception
     */
    public function prune(string $age): bool
    {
        $me = BFHelper::get()->SQLiteStorage();
        $db = $me->init();
        $stmt = $db->prepare("DELETE FROM cache WHERE mod <= DATETIME('now', 'localtime', ?);");
        if (!$stmt || !$stmt->bindValue(1, $age, SQLITE3_TEXT) || !$stmt->execute()) {
            throw new Exception($db->lastErrorMsg());
            return false;
        }
        return true;
    }
}