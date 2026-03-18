# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-03-18

### Added
- updates to README file.

## [1.0.0] - 2026-03-18

### Added
- Initial release.
- PDO-based Firebird/InterBase database driver for CodeIgniter 4.
- `Connection`, `Builder`, `Result`, `PreparedQuery`, `Forge`, and `Utils` classes.
- `SELECT FIRST n SKIP m` pagination support (Firebird native syntax).
- `EXECUTE BLOCK` batch insert support.
- Schema introspection: `listTables()`, `listColumns()`, `_indexData()`, `_foreignKeyData()`.
- PHPUnit test suite.
