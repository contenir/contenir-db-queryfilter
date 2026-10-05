# contenir/contenir-db-queryfilter

[![Continuous Integration](https://github.com/contenir/contenir-db-queryfilter/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-db-queryfilter/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-db-queryfilter/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-db-queryfilter)

Filterable, paginated laminas-db queries driven by a search form, for
[Contenir CMS](https://github.com/contenir) and any Laminas MVC or Mezzio
application.

You describe each query parameter once, as a filter class. The filter
supplies its form element and input validation, and changes the SQL
`Select` according to the submitted value. A query filter reads the
request through the form, applies every filter to a table's select, and
hands back a Laminas Paginator adapter, or prev/next navigation around a
record.

- Text, select, radio, hidden and immutable filter types
- Form elements and input filters generated from the filters
- `DbSelect` paginator adapter with a count query
- Before/after hooks for tenant isolation, soft deletes and similar
- Previous/next navigation within the filtered result (MySQL)
- Works with any table class implementing `QueryFilterTableInterface`
- `queryFilter()` controller plugin for Laminas MVC

## Requirements

- PHP 8.3, 8.4 or 8.5
- laminas/laminas-db ^2.0, laminas/laminas-form ^3.20,
  laminas/laminas-paginator-adapter-laminasdb ^1.4,
  laminas/laminas-servicemanager ^3.22
- laminas/laminas-mvc, only for the controller plugin

The 1.x releases, which support PHP 8.1, remain available from the `1.x`
branch; see [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Install

```bash
composer require contenir/contenir-db-queryfilter
```

laminas-component-installer registers the `ConfigProvider` (Mezzio) or the
`Contenir\Db\QueryFilter` module (Laminas MVC). See
[framework integration](docs/framework-integration.md#configuration) to do it
by hand.

## Usage

### 1. Filters

```php
use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Laminas\Db\Sql\Select;

final class SearchFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'search';
    protected ?string $filterLabel = 'Search';

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if (is_string($value) && $value !== '') {
            $query->where->like('name', "%{$value}%");
        }
    }
}

final class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';
    protected ?string $filterLabel = 'Category';

    public function getValueOptions(): array
    {
        return ['' => 'All categories', 'books' => 'Books', 'music' => 'Music'];
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value !== null) {
            $query->where(['category' => $value]);
        }
    }
}
```

### 2. A form built from the filters

```php
use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;

$form = new Form('product-filter');
$form->setFilterSet(new FilterSet([new SearchFilter(), new CategoryFilter()]));
$form->build();
```

### 3. Filter and paginate

```php
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Paginator\Paginator;

$queryFilter = new QueryFilter($form);
$queryFilter->setQueryFilterTable($productRepository); // implements QueryFilterTableInterface
$queryFilter->setQueryParams($request->getQueryParams());

$paginator = new Paginator($queryFilter->getPagingResultSet());
$paginator->setCurrentPageNumber((int) ($request->getQueryParams()['page'] ?? 1));
```

In a Laminas MVC controller, `$this->queryFilter(QueryFilter::class)` builds
the query filter from the service manager instead.

## Public API

| Class | Role | Reference |
| --- | --- | --- |
| `Filter\AbstractFilter`, `AbstractFilterText`, `AbstractFilterSelect`, `AbstractFilterRadio`, `AbstractFilterHidden`, `AbstractFilterImmutable`, `FilterTrait` | Filter types and their shared properties and helpers | [Filters](docs/filters.md) |
| `FilterSet` | Ordered filters plus input values | [Filters](docs/filters.md#filterset) |
| `AbstractForm`, `Form` | Laminas form built from a filter set | [Forms](docs/forms.md) |
| `QueryFilterInterface`, `AbstractQueryFilter`, `QueryFilter` | Request handling, pagination, hooks, prev/next navigation | [Query filters](docs/query-filter.md) |
| `QueryFilterTableInterface` | What a table or repository provides | [Query filters](docs/query-filter.md#tables) |
| `ConfigProvider`, `Module`, `Controller\Plugin\QueryFilterPlugin`, `Controller\Plugin\QueryFilterPluginFactory` | Configuration and the MVC controller plugin | [Framework integration](docs/framework-integration.md) |

## Configuration

The package has no configuration keys. Its `ConfigProvider` and `Module`
register the `queryFilter` controller plugin (aliases `queryFilter` and
`QueryFilter`) under `controller_plugins`. Query filters built by the plugin
must be registered with your service manager; see
[the plugin](docs/framework-integration.md#the-queryfilter-controller-plugin).

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: test doubles, no database
composer test-integration  # integration suite: in-memory SQLite and a real service manager
composer test-coverage     # both suites, clover.xml for Codecov
```

## License

BSD-3-Clause.
