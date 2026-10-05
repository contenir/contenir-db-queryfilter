# Query filters

A query filter ties a [filter form](forms.md) to a table: it reads the request's
query parameters through the form, then applies the filters to the table's
`Select` to produce a paginator adapter or prev/next navigation.

`QueryFilterInterface` is the contract, `AbstractQueryFilter` implements it,
and `QueryFilter` is the ready-to-use concrete class. Extend `QueryFilter` (or
`AbstractQueryFilter`) to add hooks or application methods.

## Lifecycle

```php
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Paginator\Paginator;

$queryFilter = new QueryFilter(new ProductFilterForm());
$queryFilter->setQueryFilterTable($productRepository);
$queryFilter->setQueryParams($request->getQueryParams());

$paginator = new Paginator($queryFilter->getPagingResultSet());
```

| Method | Description |
| --- | --- |
| `__construct(?AbstractForm $form = null)` | Optionally sets the form |
| `setForm(AbstractForm $form)` / `getForm()` | The filter form |
| `setQueryFilterTable(QueryFilterTableInterface $table)` / `getQueryFilterTable()` | The table; also sets the table name from `getTable()` |
| `setTableName(string $name)` / `getTableName()` | The table name used to qualify columns in `getPosition()` |
| `setQueryParams(array $params): void` | Reads and validates the request parameters |
| `getPagingResultSet(): DbSelect` | Paginator adapter for the filtered select |
| `getPosition(object $entity, string $identifier = 'slug', string $primaryKey = 'resource_id', string $title = 'title'): array` | Previous and next rows around an entity |
| `isValidated(): bool` | Whether `setQueryParams()` has run |
| `isSubmitted(): bool` | Whether the last `setQueryParams()` received any parameters |

The setters return the query filter. A getter called before its value is set
throws `RuntimeException` with a message naming the missing setter, and so do
the methods that need it.

## Setting query parameters

`setQueryParams()` needs the form (with its filter set) and the table. For
each filter that has a parameter name it takes the request value, or the
filter's default when the parameter is missing or null. Other request
parameters (for example `page`) are ignored, and immutable filters are
skipped.

It then sets that data on the form, validates it, and stores the result in
the filter set's input:

- Values with an input specification (text, select and radio filters) are
  stored **after** the form's input filters run, so `ToNull` has turned empty
  strings into `null`. This happens whether or not validation passed: an
  invalid value, such as a select value outside its options, still reaches the
  filter. Check `$queryFilter->getForm()->isValid()` if you need to reject it.
- Values without one (hidden filters) are stored as given.

Every call re-validates, so calling it again with new parameters replaces the
previous values.

Framework examples:

```php
$queryFilter->setQueryParams($request->getQueryParams());   // PSR-7 / Mezzio
$queryFilter->setQueryParams($this->params()->fromQuery()); // Laminas MVC
```

## Pagination

`getPagingResultSet()` builds the select in this order:

1. `$table->select()`
2. the `onBeforeFilter()` hook
3. every filter, through `FilterSet::applyFilters()`
4. the `onAfterFilter()` hook
5. `$table->prepareSelect()`, for default ordering, joins and so on

It returns a `Laminas\Paginator\Adapter\LaminasDb\DbSelect` over that select,
the table's adapter and result set prototype, with a count query of the form
`SELECT COUNT(*) AS C FROM (<select>) AS total_count`.

## Hooks

Override the protected hooks for conditions every query needs:

```php
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Db\Sql\Select;

final class TenantAwareQueryFilter extends QueryFilter
{
    public function __construct(private int $tenantId)
    {
        parent::__construct();
    }

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

Both hooks also run for `getPosition()`.

## Previous and next navigation

`getPosition()` finds an entity in the filtered, ordered result and returns
its neighbours:

```php
$position = $queryFilter->getPosition($product, identifier: 'slug', primaryKey: 'id', title: 'name');

// [
//     'prev' => ['id' => '41', 'slug' => 'red-book', 'name' => 'Red Book'],
//     'next' => ['id' => '43', 'slug' => 'red-record', 'name' => 'Red Record'],
// ]
```

- The entity must expose the primary key as a readable property
  (`$entity->{$primaryKey}`). Keys compare by their string form, so an
  integer property matches the string a driver returns.
- `prev` is missing for the first row and `next` for the last. An entity
  outside the filtered set gets only `next`, the first row.
- The columns are qualified with `getTableName()`.
- The queries number rows with MySQL user variables (`SET @num := 0`) and
  `IF()`, so **this method needs MySQL or MariaDB**.

## Tables

The table can be any class that implements `QueryFilterTableInterface`:

| Method | Description |
| --- | --- |
| `getAdapter(): Adapter` | Database adapter |
| `select(): Select` | A new select on the table |
| `getTable(): string` | Table name |
| `prepareSelect(Select $select): void` | Final changes: ordering, joins |
| `getResultSet(): ResultSetInterface` | Result set prototype for the paginator |

```php
use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;

final class ProductRepository implements QueryFilterTableInterface
{
    public function __construct(private Adapter $adapter, private string $table = 'products')
    {
    }

    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function select(): Select
    {
        return new Select($this->table);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function prepareSelect(Select $select): void
    {
        $select->order('created_at DESC');
    }

    public function getResultSet(): ResultSetInterface
    {
        return new ResultSet();
    }
}
```

## Class hierarchy

```
QueryFilterInterface
    └── AbstractQueryFilter
        └── QueryFilter

QueryFilterTableInterface
    └── your repository or table gateway

Laminas\Form\Form
    └── AbstractForm
        └── Form

FilterSet

AbstractFilter (uses FilterTrait)
    ├── AbstractFilterText
    ├── AbstractFilterSelect
    │   └── AbstractFilterRadio
    └── AbstractFilterHidden
        └── AbstractFilterImmutable
```
