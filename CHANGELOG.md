# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-03-18

### Fixed
- `_transBegin()` in `Connection` now commits any pending implicit transaction before
  calling `PDO::beginTransaction()`. Firebird/PDO never runs in autocommit mode, so
  without this fix calling `transStart()` / `transBegin()` threw
  `"There is already an active transaction"`.

### Added
- Standalone PHPUnit test suite: tests can now be run directly from the package
  directory without embedding it in a CI4 application (`composer install` + a live
  Firebird server is all that is needed).
- `tests/_support/Config/` stubs (`Paths`, `Database`, `Constants`, `Autoload`,
  `Modules`, `Services`, `Events`, `Boot/testing`) required by CI4's test bootstrap.
- `phpunit.xml.dist` updated to use package-local paths; Firebird credentials
  configurable via `<env>` entries or a local `phpunit.xml` override.

## [1.0.1] - 2026-03-18

### Added
- Updates to README file.

## [1.0.0] - 2026-03-18

### Added
- Initial release.
- PDO-based Firebird/InterBase database driver for CodeIgniter 4.
- `Connection`, `Builder`, `Result`, `PreparedQuery`, `Forge`, and `Utils` classes.
- `SELECT FIRST n SKIP m` pagination support (Firebird native syntax).
- `EXECUTE BLOCK` batch insert support.
- Schema introspection: `listTables()`, `listColumns()`, `_indexData()`, `_foreignKeyData()`.
- PHPUnit test suite.
