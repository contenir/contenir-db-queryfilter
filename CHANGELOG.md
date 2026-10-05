# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

2.0 moves the database layer from laminas-db to php-db/phpdb, with a table
interface that contenir-db-model 2 repositories implement as they are. It also
moves to PHP 8.3+ and the php-db QA toolchain shared by all Contenir 2.x
packages, and adds native types and bug fixes that change behaviour at the
edges. See [UPGRADE-2.0.md](UPGRADE-2.0.md) for every break.

### Changed

- **laminas-db is replaced by php-db/phpdb** (`Laminas\Db\…` → `PhpDb\…`).
  laminas/laminas-paginator-adapter-laminasdb is replaced by
  laminas/laminas-paginator and the new `Paginator\SelectAdapter`.
- **`QueryFilterTableInterface` is now `createSelect()` and `fetch(Select)`**,
  matching contenir-db-model 2's `Repository`, so a repository subclass
  implements it without code and pages are hydrated entities. `select()`,
  `getAdapter()`, `getTable()`, `prepareSelect()` and `getResultSet()` are
  gone.
- New `RepositoryTable` adapts a plain contenir-db-model 2 `Repository` (for
  example from `$em->getRepository()`) to the interface. It needs
  contenir-db-model, which stays optional.
- The query filter takes the adapter for counting and `getPosition()` through
  the new `setAdapter()`/`getAdapter()` (also on `QueryFilterInterface`).
- `getPagingResultSet()` returns `Paginator\SelectAdapter`, which fetches pages
  through the table, instead of `DbSelect`.
- The table name is read from the `FROM` of the table's `createSelect()`, and
  `getPosition()` starts from that select.
- Requires PHP 8.3, 8.4 or 8.5. PHP 8.1 and 8.2 stay on 1.x (`1.x` branch).
- `laminas/laminas-servicemanager` is a direct requirement (it was already
  installed through laminas-form).
- `AbstractFilterSelect::getValueOptions()` declares `array`.
- `getPosition()` types `$primaryKey` as `string` (an iterable never worked).
- `FilterSet::addFilter()` accepts `AbstractFilter|string`, and rejects class
  names that are not filters with `InvalidArgumentException`.
- `AbstractQueryFilter` and `AbstractForm` keep their dependencies in nullable
  properties; getters called before the setter throw `RuntimeException`
  instead of an `Error`. `getTableName()` does too.
- `setQueryParams()` sets data on the form instead of binding an object: every
  call validates again, and input filters (such as `ToNull`) apply even when
  validation fails.
- `QueryFilterPlugin::__invoke()`: `$className` is optional, options are
  passed as an array (or `null` when empty), the container must be a
  Laminas `ServiceLocatorInterface`, and the built service must implement
  `QueryFilterInterface`; failures throw `RuntimeException`.
- Every concrete class is `final`: `QueryFilter`, `Form`, `FilterSet`,
  `QueryFilterPlugin`, `ConfigProvider`, `Module` and `QueryFilterPluginFactory`.
  Extend `AbstractQueryFilter` and `AbstractForm` instead; `AbstractForm` is
  now declared `abstract`.
- `@api` annotations and `#[Override]` attributes throughout.
- Documentation split into `docs/` (filters, forms, query filters, framework
  integration).

### Fixed

- After the paginator counted results, fetching a page failed on PDO
  drivers ("column index out of range"): the count query rendered the paging
  `Select` itself, and laminas-db kept the sub-select parameter prefix on it.
  The count query now renders a copy (php-db does not show the problem, but
  the copy is kept so the filtered select is never modified).
- `getPosition()` compared the row key and the entity key with `===`, so an
  integer entity key never matched the string a driver returns, and the
  entity was treated as outside the result. Scalar keys now compare as
  strings.
- `ConfigProvider::__invoke()` nested `controller_plugins` under
  `dependencies`, so applications using the ConfigProvider never registered
  the `queryFilter` plugin. It now returns top-level keys, as `Module` does.
- `$this->queryFilter()` without arguments was an `ArgumentCountError`;
  building with `InvokableFactory` passed `[]` as the constructor argument;
  `Traversable` options were a `TypeError`.
- A second `setQueryParams()` call stored the new values without running the
  input filters.
- Immutable filters read the input with a `null` array offset, deprecated in
  PHP 8.5. Their value is now always the default.

### Added

- `LICENSE.md` (BSD-3-Clause, as declared in composer.json).
- Continuous integration on PHP 8.3, 8.4 and 8.5 against lowest, locked and
  latest dependencies, with coverage reported to Codecov.
- Separate unit (test doubles, no database) and integration (in-memory SQLite,
  a contenir-db-model 2 repository, real service manager) test suites, with
  100% line and branch coverage.

### Removed

- `phpcs.xml`, `phpstan.neon`, laminas-coding-standard and PHPStan, replaced by
  Mago via `php-db/phpdb-qa-tools`. PHPUnit 9 is replaced by PHPUnit 11.

## [1.2.2] - 2026-06-26

### Fixed

- `QueryFilterPlugin::__invoke()` returns `self|QueryFilterInterface`, covering
  both the plugin (no class name) and a built query filter.

## [1.2.1] - 2025-11-25

### Added

- `LICENSE.md` (BSD-3-Clause, as declared in composer.json).
- SQL string comparison tests for `AbstractQueryFilter`.

## [1.2.0] - 2025-11-25

### Added

- `LICENSE.md` (BSD-3-Clause, as declared in composer.json).
- **`QueryFilterInterface`** - New interface defining the contract for query filter implementations. `AbstractQueryFilter` now implements this interface.
- **`QueryFilterTableInterface`** - New interface for table/repository classes, decoupling from `contenir/contenir-db-model`.
- **`onBeforeFilter()` hook** - Override in subclasses to add global query modifications before filters are applied (e.g., multi-tenancy, security filters).
- **`onAfterFilter()` hook** - Override in subclasses to add query modifications after filters are applied (e.g., soft delete filters).
- **`FilterSet::hasFilter()`** - Check if a filter with a given parameter name exists.
- **`FilterSet::getFilter()`** - Get a filter by its parameter name.
- **`FilterSet::removeFilter()`** - Remove a filter by parameter name.
- **`FilterSet::clear()`** - Clear all filters from the set.
- **`FilterSet::applyFilters()`** - New method name for applying filters (clearer than `filter()`).
- **State validation** - Methods now throw `RuntimeException` with clear messages if form or queryFilterTable is not set.

### Changed

- **`setRequest()` replaced with `setQueryParams()`** - Accepts an array of query parameters instead of `Laminas\Http\Request`. Works seamlessly with both PSR-7 (`$request->getQueryParams()`) and Laminas MVC (`$this->params()->fromQuery()`).
- **`setRepository()` renamed to `setQueryFilterTable()`** - Reflects the new interface-based design.
- **`getRepository()` renamed to `getQueryFilterTable()`** - Reflects the new interface-based design.
- **All setters now return `QueryFilterInterface`** - Consistent fluent interface.
- **`setTableName()` now returns `QueryFilterInterface`** - Previously returned `void`.

### Deprecated

- **`FilterSet::filter()`** - Use `FilterSet::applyFilters()` instead. The old method remains as an alias for backwards compatibility.

### Removed

- **Hard dependency on `contenir/contenir-db-model`** - Now optional via `QueryFilterTableInterface`.
- **Hard dependency on `laminas/laminas-http`** - No longer required due to `setQueryParams()` accepting arrays.
- **Hard dependency on `laminas/laminas-mvc`** - Now optional, only needed for controller plugin support.

## [1.1.0] - 2025-11-25

### Added

- `LICENSE.md` (BSD-3-Clause, as declared in composer.json).
- Mezzio (PSR-15) framework support
- Comprehensive documentation with examples for both MVC and Mezzio
- Development tooling (PHPStan, PHPCS)

### Changed

- Updated documentation with Mezzio and MVC implementation guides

## [1.0.0] - 2025-11-01

### Added

- `LICENSE.md` (BSD-3-Clause, as declared in composer.json).
- Initial release
- `AbstractQueryFilter` and `QueryFilter` classes
- `AbstractForm` and `Form` classes
- `FilterSet` for managing collections of filters
- Filter types: `AbstractFilterText`, `AbstractFilterSelect`, `AbstractFilterRadio`, `AbstractFilterHidden`, `AbstractFilterImmutable`
- `FilterTrait` with common filter properties and methods
- Laminas MVC controller plugin
- Pagination support via `DbSelect` adapter
- Position/navigation tracking for prev/next items

[2.0.0]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.2.2...HEAD
[1.2.2]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.2.0...v1.2.2
[1.2.1]: https://github.com/contenir/contenir-db-queryfilter/releases/tag/v1.2.1
[1.2.0]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/contenir/contenir-db-queryfilter/releases/tag/v1.0.0