# Firebird Database Driver for CodeIgniter 4

A PDO-based Firebird/InterBase database driver for CodeIgniter 4, adapted from
[leirags/CI4-PDO-Firebird](https://github.com/leirags/CI4-PDO-Firebird) and
maintained locally in `app/Database/Firebird/`.

---

## Supported features

### Connection
- Connects via PHP's `PDO` with the `firebird:` DSN prefix.
- Supports both explicit config (hostname + port + database path) and a pre-built DSN string.
- Error mode is set to `PDO::ERRMODE_EXCEPTION` — all driver errors throw exceptions.
- `reconnect()` is implemented (closes and re-initialises the connection).

### Querying
- Full `SELECT`, `INSERT`, `UPDATE`, `DELETE` support through CodeIgniter's Query Builder.
- Raw `simpleQuery()` / `query()` execution works normally.
- Prepared statements are supported via `PreparedQuery`.
- `affectedRows()` returns the correct row count via `PDOStatement::rowCount()`.
- A **delete hack** is applied automatically: bare `DELETE FROM table` statements are
  rewritten to `DELETE FROM table WHERE 1=1` so that `affectedRows()` returns a
  meaningful value instead of 0.

### Pagination
- Uses Firebird's native `SELECT FIRST n SKIP m …` syntax instead of the standard
  `LIMIT`/`OFFSET` clause, which Firebird does not support.

### Result set
- `getFieldCount()`, `getFieldNames()`, and `getFieldData()` are implemented.
- Result rows are buffered internally (full fetch into a PHP array) to work around
  PDO's lack of a native cursor-seek.
- Return type can be configured to `array` or `object` as with any CI4 driver.

### Schema introspection
- `listTables()` — queries `RDB$RELATIONS`, filtering out system tables (`RDB$*`,
  `SEC$*`, `MON$*`). Table prefix filtering is supported.
- `listColumns()` / `_fieldData()` — returns column names, Firebird native types,
  max length, and default values from `RDB$RELATION_FIELDS` / `RDB$FIELDS`.
- `_indexData()` — returns index metadata from `RDB$INDICES`.
- `_foreignKeyData()` — returns foreign key information.
- `getVersion()` — returns the server version string via `PDO::ATTR_SERVER_INFO`.

### String / identifier handling
- String escaping uses Firebird-compatible single-quote doubling (`'` → `''`).
- `LIKE` wildcard escaping is implemented in `escapeLikeStringDirect()`.

---

## Known limitations

| Area | Limitation |
|---|---|
| **Identifier quoting** | `escapeChar` is an empty string — column and table names are **not** quoted automatically. Identifiers that conflict with reserved words must be quoted manually in raw SQL. |
| **Persistent connections** | The `pConnect` config option is passed through but Firebird's PDO driver does not reliably support persistent connections. Treat `pConnect = FALSE` as the safe default. |
| **`setDatabase()`** | Always returns `false`. Switching to a different database after the connection is established is not possible. |
| **`PDO::ATTR_AUTOCOMMIT`** | Cannot be set — the PDO Firebird driver throws an error. Auto-commit behaviour follows PDO Firebird's default. |
| **Forge — `CREATE DATABASE`** | `createDatabaseStr` and `createDatabaseIfStr` are empty strings. Creating or dropping databases through CI4's `Forge` class is **not supported**. |
| **Forge — column types** | The `UNSIGNED` list and table-option handling are copied from the MySQL driver and are **not valid Firebird SQL**. Using `Forge` to create or alter tables may produce incorrect DDL. |
| **Forge — namespace** | `Forge.php` declares namespace `CodeIgniter\Database\Firebird` instead of `App\Database\Firebird`. If Forge is invoked it will not be found by the autoloader unless this is corrected. |
| **Utils — `listDatabases`** | Uses MySQL's `SHOW DATABASES` statement, which does not exist in Firebird. Calling `$db->listDatabases()` will throw an error. |
| **Utils — `_backup()`** | Always throws `DatabaseException: Unsupported feature`. Database backup through CI4's Utils is not available. |
| **`INSERT IGNORE` / `UPDATE IGNORE`** | `supportedIgnoreStatements` is empty — the Query Builder's `ignore()` modifier has no effect and will not produce valid SQL. |
| **Memory usage** | All result rows are fetched into a PHP array at once (`PDOStatement::fetchAll`). Avoid using this driver for queries that return very large result sets. |
| **Transactions** | Transaction methods are inherited from `BaseConnection` and rely on PDO's `beginTransaction()` / `commit()` / `rollBack()`. Basic transactions work, but savepoints and nested transactions have not been tested. |

---

## Configuration

```php
// app/Config/Database.php

public array $firebird = [
    'DSN'      => 'firebird:dbname=192.168.1.10:/path/to/database.fdb;charset=UTF8;dialect=3',
    'hostname' => '',
    'port'     => '',           // default Firebird port: 3050
    'username' => 'SYSDBA',
    'password' => 'masterkey',
    'database' => '',
    'DBDriver' => 'App\Database\Firebird\Connection',
    'DBPrefix' => '',
    'pConnect' => false,
    'DBDebug'  => true,
    'charset'  => 'UTF8',
    'DBCollat' => '',
    'swapPre'  => '',
    'encrypt'  => false,
    'compress' => false,
    'strictOn' => false,
    'failover' => [],
];
```

> **Note:** `DBDriver` must be set to the fully-qualified class name
> `App\Database\Firebird\Connection`, not the short string `'Firebird'`, because
> the driver lives in the application namespace rather than the framework namespace.

---

## Usage

```php
// Connect explicitly (e.g. in a controller)
$db = \Config\Database::connect('firebird');

// In a model
class MyModel extends \CodeIgniter\Model
{
    protected $DBGroup = 'firebird';
    protected $table   = 'MY_TABLE';
    protected $primaryKey = 'ID';
    protected $returnType = 'object';
}
```
