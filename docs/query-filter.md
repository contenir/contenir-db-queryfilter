# Query filters

A query filter ties a [filter form](forms.md) to a table: it reads the request's
query parameters through the form, then applies the filters to the table's
`Select` to produce a paginator adapter or prev/next navigation.

`QueryFilterInterface` is the contract, `AbstractQueryFilter` implements it,
and `QueryFilter` is the ready-to-use concrete class. `QueryFilter` is final:
extend `AbstractQueryFilter` to add hooks or application methods.

## Lifecycle

```php
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Paginator\Paginator;

$queryFilter = new QueryFilter(new ProductFilterForm());
$queryFilter->setQueryFilterTable($productRepository); // implements QueryFilterTableInterface
$queryFilter->setAdapter($adapter);                    // PhpDb\Adapter\AdapterInterface
$queryFilter->setQueryParams($request->getQueryParams());

$paginator = new Paginator($queryFilter->getPagingResultSet());
```

| Method | Description |
| --- | --- |
| `__construct(?AbstractForm $form = null)` | Optionally sets the form |
| `setForm(AbstractForm $form)` / `getForm()` | The filter form |
| `setQueryFilterTable(QueryFilterTableInterface $table)` / `getQueryFilterTable()` | The table; also sets the table name from the `FROM` of its `createSelect()` (the table, or its alias) |
| `setAdapter(AdapterInterface $adapter)` / `getAdapter()` | The php-db adapter that runs the count and position queries |
| `setTableName(string $name)` / `getTableName()` | The table name used to qualify columns in `getPosition()` |
| `setQueryParams(array $params): void` | Reads and validates the request parameters |
| `getPagingResultSet(): Paginator\SelectAdapter` | Paginator adapter for the filtered select |
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

1. `$table->createSelect()`, with the table's columns, joins and default order
2. the `onBeforeFilter()` hook
3. every filter, through `FilterSet::applyFilters()`
4. the `onAfterFilter()` hook

It returns a `Contenir\Db\QueryFilter\Paginator\SelectAdapter`, a Laminas
Paginator adapter:

- `getItems($offset, $itemCountPerPage)` adds `LIMIT`/`OFFSET` to a copy of the
  select and runs it through `$table->fetch()`, so a page holds whatever the
  table returns: entities for a contenir-db-model repository, rows for a
  table gateway.
- `count()` runs `SELECT COUNT(*) AS C FROM (<select>) AS total_count` on the
  query filter's adapter, once per adapter instance.

## Hooks

Override the protected hooks for conditions every query needs:

```php
use Contenir\Db\QueryFilter\AbstractQueryFilter;
use PhpDb\Sql\Select;

final class TenantAwareQueryFilter extends AbstractQueryFilter
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
- The query starts from `$table->createSelect()`, replaces its columns with
  the key, identifier and title qualified with `getTableName()`, and keeps
  its joins and order. The hooks and filters apply as for pagination.
- The queries run on the query filter's adapter. They number rows with MySQL
  user variables (`SET @num := 0`) and `IF()`, so **this method needs MySQL or
  MariaDB**.

## Tables

The table is any class that implements `QueryFilterTableInterface`:

| Method | Description |
| --- | --- |
| `createSelect(): Select` | A new `PhpDb\Sql\Select` over the table, with the columns, joins and default order `fetch()` needs |
| `fetch(Select $select): array` | Runs a select built from `createSelect()` and returns its items in order |

### contenir-db-model 2 repositories

The interface matches contenir-db-model 2's `Repository`, which already has
`createSelect()` and `fetch()`. A repository subclass implements it with no
code, and pages are hydrated entities:

```php
use App\Entity\Product;
use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Repository;
use Contenir\Db\QueryFilter\QueryFilterTableInterface;

/** @extends Repository<Product> */
final class ProductRepository extends Repository implements QueryFilterTableInterface
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, Product::class);
    }
}

$queryFilter->setQueryFilterTable(new ProductRepository($em));
$queryFilter->setAdapter($adapter); // the adapter the EntityManager was built with
```

A plain repository, such as one from `$em->getRepository()`, does not declare
the interface. Wrap it in `RepositoryTable`, which delegates `createSelect()`
and `fetch()` to it:

```php
use Contenir\Db\QueryFilter\RepositoryTable;

$queryFilter->setQueryFilterTable(new RepositoryTable($em->getRepository(Product::class)));
```

`RepositoryTable` needs contenir/contenir-db-model ^2.0 installed; this
package does not require it.

`createSelect()` lists every mapped column, which `fetch()` needs to hydrate
entities; filters add conditions and joins but must keep those columns. To
give the list a default order, override `createSelect()` in the repository or
use the `onAfterFilter()` hook.

contenir-db-model is not a dependency of this package; add it to your project to use these.

### Other tables

Any table gateway works:

```php
use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;

final class ProductTable implements QueryFilterTableInterface
{
    public function __construct(private AdapterInterface $adapter)
    {
    }

    public function createSelect(): Select
    {
        return (new Select('products'))->order('created_at DESC');
    }

    public function fetch(Select $select): array
    {
        return iterator_to_array(
            (new Sql($this->adapter))->prepareStatementForSqlObject($select)->execute() ?? [],
            false,
        );
    }
}
```

## Class hierarchy

```
QueryFilterInterface
    └── AbstractQueryFilter
        └── QueryFilter

QueryFilterTableInterface
    ├── RepositoryTable (wraps a contenir-db-model Repository)
    └── your contenir-db-model repository or table gateway

Laminas\Paginator\Adapter\AdapterInterface
    └── Paginator\SelectAdapter

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
