# Migration Guide

This guide helps you migrate between major versions of `contenir/contenir-db-queryfilter`.

## Migrating from 1.1.x to 1.2.x

Version 1.2.0 introduces several breaking changes to improve flexibility and decouple from specific implementations. Most changes are straightforward renames with backwards compatibility where possible.

### Breaking Changes

#### 1. `setRequest()` replaced with `setQueryParams()`

**Before (1.1.x):**
```php
// Laminas MVC
$queryFilter->setRequest($this->getRequest());

// Mezzio (required conversion)
$laminasRequest = new \Laminas\Http\Request();
$laminasRequest->setQuery(new \Laminas\Stdlib\Parameters($request->getQueryParams()));
$queryFilter->setRequest($laminasRequest);
```

**After (1.2.x):**
```php
// Laminas MVC
$queryFilter->setQueryParams($this->params()->fromQuery());

// Mezzio
$queryFilter->setQueryParams($request->getQueryParams());
```

#### 2. `setRepository()` renamed to `setQueryFilterTable()`

**Before (1.1.x):**
```php
$queryFilter->setRepository($this->productRepository);
$repository = $queryFilter->getRepository();
```

**After (1.2.x):**
```php
$queryFilter->setQueryFilterTable($this->productRepository);
$table = $queryFilter->getQueryFilterTable();
```

#### 3. Repository must implement `QueryFilterTableInterface`

If you were using `contenir/contenir-db-model`'s `AbstractRepository`, you need to ensure it implements the new interface.

**Option A: Update your repository to implement the interface:**
```php
<?php

namespace App\Repository;

use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;

class ProductRepository implements QueryFilterTableInterface
{
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function select(): Select
    {
        return $this->sql->select($this->table);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function prepareSelect(Select $select): void
    {
        // Add default ordering, joins, etc.
    }

    public function getResultSet(): ResultSetInterface
    {
        return $this->resultSetPrototype;
    }
}
```

**Option B: If using `contenir/contenir-db-model`, update to version that implements the interface.**

#### 4. `FilterSet::filter()` deprecated in favor of `applyFilters()`

The old method still works but is deprecated.

**Before (1.1.x):**
```php
$filterSet->filter($select);
```

**After (1.2.x):**
```php
$filterSet->applyFilters($select);
```

### New Features Available

After migrating, you can take advantage of these new features:

#### Global Query Hooks

Add multi-tenancy, soft deletes, or security filters without modifying individual filters:

```php
class TenantAwareQueryFilter extends QueryFilter
{
    public function __construct(private int $tenantId) {}

    protected function onBeforeFilter(Select $select): void
    {
        $select->where(['tenant_id' => $this->tenantId]);
    }

    protected function onAfterFilter(Select $select): void
    {
        $select->where(['deleted_at' => null]);
    }
}
```

#### Dynamic Filter Management

```php
$filterSet = new FilterSet([...]);

// Check if a filter exists
if ($filterSet->hasFilter('category')) {
    $filter = $filterSet->getFilter('category');
}

// Remove a filter
$filterSet->removeFilter('status');

// Clear all filters
$filterSet->clear();
```

#### Type-hint against interface

```php
public function __construct(
    private QueryFilterInterface $queryFilter
) {}
```

### Dependency Changes

Update your `composer.json` if you were relying on transitive dependencies:

**Before:**
```json
{
    "require": {
        "contenir/contenir-db-queryfilter": "^1.1"
    }
}
```

**After:**
```json
{
    "require": {
        "contenir/contenir-db-queryfilter": "^1.2"
    }
}
```

If you need the MVC controller plugin, you may also need:
```json
{
    "require": {
        "laminas/laminas-mvc": "^3.0"
    }
}
```

### Quick Migration Checklist

- [ ] Replace `setRequest($request)` with `setQueryParams($params)`
  - MVC: Use `$this->params()->fromQuery()`
  - Mezzio: Use `$request->getQueryParams()`
- [ ] Replace `setRepository()` with `setQueryFilterTable()`
- [ ] Replace `getRepository()` with `getQueryFilterTable()`
- [ ] Ensure your repository implements `QueryFilterTableInterface`
- [ ] Replace `FilterSet::filter()` with `FilterSet::applyFilters()` (optional, old method still works)
- [ ] Run your tests to verify everything works

### Getting Help

If you encounter issues during migration:

1. Check the [README.md](README.md) for updated usage examples
2. Review the [CHANGELOG.md](CHANGELOG.md) for a complete list of changes
3. Open an issue at https://github.com/contenir/contenir-db-queryfilter/issues