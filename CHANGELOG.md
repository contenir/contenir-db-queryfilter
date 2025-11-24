# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2025-11-25

### Added

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

- Mezzio (PSR-15) framework support
- Comprehensive documentation with examples for both MVC and Mezzio
- Development tooling (PHPStan, PHPCS)

### Changed

- Updated documentation with Mezzio and MVC implementation guides

## [1.0.0] - 2025-11-01

### Added

- Initial release
- `AbstractQueryFilter` and `QueryFilter` classes
- `AbstractForm` and `Form` classes
- `FilterSet` for managing collections of filters
- Filter types: `AbstractFilterText`, `AbstractFilterSelect`, `AbstractFilterRadio`, `AbstractFilterHidden`, `AbstractFilterImmutable`
- `FilterTrait` with common filter properties and methods
- Laminas MVC controller plugin
- Pagination support via `DbSelect` adapter
- Position/navigation tracking for prev/next items

[Unreleased]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/contenir/contenir-db-queryfilter/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/contenir/contenir-db-queryfilter/releases/tag/v1.0.0