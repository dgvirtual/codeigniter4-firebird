# Firebird Database Driver for CodeIgniter 4

A PDO-based Firebird/InterBase database driver for CodeIgniter 4, initially based on
[leirags/CI4-PDO-Firebird](https://github.com/leirags/CI4-PDO-Firebird).

## Requirements

- PHP 8.1 or higher
- `ext-pdo` with the `pdo_firebird` extension enabled
- CodeIgniter 4.7 or higher


## Installation

```bash
composer require dgvirtual/codeigniter4-firebird
```

Then add a database group to `app/Config/Database.php` (see [Configuration](#configuration) below).

## Configuration

```php
// app/Config/Database.php

public array $firebird = [
    'DSN'      => 'firebird:dbname=192.168.1.10:/path/to/database.fdb;charset=UTF8;dialect=3',
    'hostname' => '',
    'port'     => '',           // default Firebird port: 3050
    'username' => 'DBUSERHERE',
    'password' => 'DbPasswordHere',
    'database' => '',
    'DBDriver' => 'Dgvirtual\CI4Firebird',
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

Or:

```php
    // app/Config/Database.php
    public $firebird = [
        'DSN'      => '',
        'hostname' => 'example.com',
        'port'     => '3050',
        'username' => 'DBUSERHERE',
        'password' => 'DbPasswordHere',
        'database' => 'databasename',
        'DBDriver' => 'Dgvirtual\CI4Firebird',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'UTF8',
        'swapPre'  => '',
        'failover' => [],
];
```

> **Note:** `DBDriver` must be set to the namespace `Dgvirtual\CI4Firebird`.

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

## Running Tests

The test suite requires a live Firebird server. Everything else is self-contained —
no CI4 application project is needed.

### 1. Install dependencies

```bash
git clone https://github.com/dgvirtual/codeigniter4-firebird.git
cd codeigniter4-firebird
composer install
```

### 2. Start a Firebird server

The quickest way is Docker:

```bash
docker run -d --name firebird-test \
  -e ISC_PASSWORD=masterkey \
  -e FIREBIRD_DATABASE=test.fdb \
  -p 3050:3050 \
  jacobalberty/firebird:3.0
```

> **Note:** Use the Firebird **3.x** image. The `php8.3-interbase` / `pdo_firebird` package
> ships a Firebird 3 client library (`LI-V6.3.x`). Connecting it to a Firebird 4 server
> causes subtle mis-behaviour (most notably, `PDO::rollBack()` silently commits instead).

The test suite creates and drops its own tables (`TEST_CATS`, `TEST_ITEMS`), so any
empty database works.

### 3. Configure credentials (if necessary)

The default credentials in `phpunit.xml.dist` match the Docker command above
(`SYSDBA` / `masterkey`, database `/firebird/data/test.fdb` on `localhost:3050`).

If your server uses different credentials or a different path, copy
`phpunit.xml.dist` to `phpunit.xml` (which is git-ignored) and adjust the `<env>`
entries:

```xml
<env name="FIREBIRD_DSN"      value="firebird:dbname=myhost:/data/custom.fdb;charset=UTF8;dialect=3"/>
<env name="FIREBIRD_USER"     value="MYUSER"/>
<env name="FIREBIRD_PASSWORD" value="secret"/>
```

Setting `FIREBIRD_DSN` takes precedence over the individual `FIREBIRD_HOST`,
`FIREBIRD_PORT`, and `FIREBIRD_DATABASE` variables.

### 4. Run the tests

```bash
vendor/bin/phpunit
```

If the `firebird_test` connection cannot be established (server unavailable, wrong
credentials, missing `pdo_firebird` extension), every test is automatically
**skipped** rather than failing — so the suite is safe to run in CI environments
that do not have Firebird available.

## Known limitations (Needs checking/update)

| Area | Limitation |
|---|---|
| **Identifier quoting** | `escapeChar` is an empty string — column and table names are **not** quoted automatically. Identifiers that conflict with reserved words must be quoted manually in raw SQL. |
| **Persistent connections** | The `pConnect` config option is passed through but Firebird's PDO driver does not reliably support persistent connections. Treat `pConnect = FALSE` as the safe default. |
| **`setDatabase()`** | Always returns `false`. Switching to a different database after the connection is established is not possible. |
| **`PDO::ATTR_AUTOCOMMIT`** | Cannot be set — the PDO Firebird driver throws an error. Auto-commit behaviour follows PDO Firebird's default. |
| **Forge — `CREATE DATABASE`** | `createDatabaseStr` and `createDatabaseIfStr` are empty strings. Creating or dropping databases through CI4's `Forge` class is **not supported**. |
| **Forge — column types** | The `UNSIGNED` list and table-option handling are copied from the MySQL driver and are **not valid Firebird SQL**. Using `Forge` to create or alter tables may produce incorrect DDL. |
| **Forge — column types (DDL)** | `UNSIGNED` modifiers and MySQL-style table options are not valid Firebird SQL. Relying on `Forge` for schema management may produce incorrect DDL. |
| **Utils — `listDatabases`** | Uses MySQL's `SHOW DATABASES` statement, which does not exist in Firebird. Calling `$db->listDatabases()` will throw an error. |
| **Utils — `_backup()`** | Always throws `DatabaseException: Unsupported feature`. Database backup through CI4's Utils is not available. |
| **`INSERT IGNORE` / `UPDATE IGNORE`** | `supportedIgnoreStatements` is empty — the Query Builder's `ignore()` modifier has no effect and will not produce valid SQL. |
| **Memory usage** | All result rows are fetched into a PHP array at once (`PDOStatement::fetchAll`). Avoid using this driver for queries that return very large result sets. |
| **Transactions — rollback** | The `pdo_firebird` PHP extension calls `isc_commit_retaining()` after every DML statement, which silently commits each row while keeping the transaction handle open. As a result, `PDO::rollBack()` (and CI4's `transRollback()`) **cannot undo already-committed rows**. `commit()` / `transComplete()` work correctly. This is a known limitation of the `pdo_firebird` extension. |
