# Upgrading from 1.x to 2.0

2.0 moves from laminas-db to [php-db/phpdb](https://github.com/php-db/phpdb),
the successor used by contenir-db-model 2, and redesigns the table interface
around it (section 1). The other breaks come from adding native types and from
bug fixes; filters, forms and `setQueryParams()` are otherwise unchanged.

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| laminas/laminas-db | ^2.0 | removed |
| php-db/phpdb | — | 0.6.x-dev, plus a platform package (php-db/phpdb-mysql, ...) |
| laminas/laminas-paginator-adapter-laminasdb | ^1.4 | removed |
| laminas/laminas-paginator | indirect | ^2.18, required directly |
| laminas/laminas-form | ^3.20 | ^3.20 |
| laminas/laminas-servicemanager | indirect | ^3.22, required directly |
| laminas/laminas-stdlib | indirect | 3.21+ (older releases conflict: deprecations on PHP 8.4+) |

```bash
composer require contenir/contenir-db-queryfilter:^2.0
```

Projects that must stay on PHP 8.1 or 8.2 can keep using `^1.2`, which is
maintained on the `1.x` branch.

## 1. laminas-db is replaced by php-db/phpdb

### Namespaces

Every `Laminas\Db\…` class in filters, hooks and tables becomes `PhpDb\…`:

```php
// 1.x
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;

// 2.0
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Select;
```

`AbstractFilter::setAdapter()` takes a `PhpDb\Adapter\AdapterInterface`, and
`getSql()`, `getWhere()` and `hasJoin()` work on `PhpDb\Sql` objects. Most
`Select`/`Where` code is unchanged; one difference filters meet is that
`Where::in()` wants a sub-select wrapped: `new PhpDb\Sql\Argument\Select($select)`.

### `QueryFilterTableInterface`

The 1.x interface described a laminas-db table gateway. 2.0 asks only for
what a contenir-db-model 2 `Repository` already provides:

| 1.x | 2.0 |
| --- | --- |
| `select(): Select` | `createSelect(): Select` |
| `getAdapter(): Adapter` | removed: set the adapter on the query filter |
| `getTable(): string` | removed: the table name is read from `createSelect()` |
| `prepareSelect(Select $select): void` | removed: put the default order and joins in `createSelect()`, or use the `onAfterFilter()` hook |
| `getResultSet(): ResultSetInterface` | `fetch(Select $select): array`, which runs a page and returns its items |

```php
// 1.x
class ProductRepository implements QueryFilterTableInterface
{
    public function getAdapter(): Adapter { return $this->adapter; }
    public function select(): Select { return new Select('products'); }
    public function getTable(): string { return 'products'; }
    public function prepareSelect(Select $select): void { $select->order('name'); }
    public function getResultSet(): ResultSetInterface { return new HydratingResultSet(...); }
}

// 2.0, with contenir-db-model 2: no methods needed
final class ProductRepository extends Repository implements QueryFilterTableInterface
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, Product::class);
    }
}

// 2.0, with contenir-db-model 2 and no subclass
$queryFilter->setQueryFilterTable(new RepositoryTable($em->getRepository(Product::class)));

// 2.0, any other table gateway
final class ProductTable implements QueryFilterTableInterface
{
    public function __construct(private AdapterInterface $adapter) {}

    public function createSelect(): Select
    {
        return (new Select('products'))->order('name');
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

### The adapter moves to the query filter

Counting and `getPosition()` need a database adapter, which a repository does
not expose. Set it on the query filter; it is new on `QueryFilterInterface`
(`setAdapter()`/`getAdapter()`), and getters fail with `RuntimeException` until
it is set:

```php
// 1.x
$queryFilter->setQueryFilterTable($table);

// 2.0
$queryFilter->setQueryFilterTable($repository);
$queryFilter->setAdapter($adapter); // the PhpDb adapter your EntityManager uses
```

Your own `QueryFilterInterface` implementations must add the two methods.

### `getPagingResultSet()` returns `Paginator\SelectAdapter`

1.x returned laminas-paginator-adapter-laminasdb's `DbSelect`. 2.0 returns
`Contenir\Db\QueryFilter\Paginator\SelectAdapter`, also a Laminas Paginator
adapter, so `new Paginator($queryFilter->getPagingResultSet())` is unchanged.
Pages now come from `$table->fetch()`: entities from a contenir-db-model
repository, rather than the 1.x result set prototype's rows. Code that
type-hinted `DbSelect` must use `SelectAdapter` or
`Laminas\Paginator\Adapter\AdapterInterface`.

### Table name

`setQueryFilterTable()` reads the table name from the `FROM` of
`createSelect()` (the table, or its alias) instead of calling `getTable()`.
`setTableName()` still overrides it. `getPosition()` starts from
`createSelect()` instead of a bare `SELECT` on `getTable()`, so it keeps the
select's joins and order; it still needs MySQL or MariaDB.

## 2. `getValueOptions()` declares `array`

`AbstractFilterSelect::getValueOptions()` (and so every radio filter) now has a
native return type. A subclass without one no longer loads.

```php
// 1.x
public function getValueOptions()
{
    return ['books' => 'Books'];
}

// 2.0
public function getValueOptions(): array
{
    return ['books' => 'Books'];
}
```

## 3. `getPosition()` takes a string primary key

`QueryFilterInterface::getPosition()` and `AbstractQueryFilter::getPosition()`
typed `$primaryKey` as `string|iterable`, but an iterable never worked: the
key is interpolated into SQL and read as a property name. It is now `string`.

```php
// 1.x: accepted, then failed
$queryFilter->getPosition($entity, 'slug', ['id'], 'title');

// 2.0
$queryFilter->getPosition($entity, 'slug', 'id', 'title');
```

Your own implementations of `QueryFilterInterface` that still declare
`string|iterable` remain compatible. The documented return type is now
`array<array-key, array<string, mixed>>`.

## 4. `FilterSet::addFilter()` accepts filters only

The parameter was `string|object`; it is now `AbstractFilter|string`. A class
name that is not an `AbstractFilter` subclass, or does not exist, throws
`InvalidArgumentException` instead of failing with an `Error`.

```php
// 1.x: Error ("Call to undefined method stdClass::setFilterSet()")
$filterSet->addFilter(stdClass::class);

// 2.0: InvalidArgumentException('Filter class "stdClass" must extend ...')
```

## 5. Unset dependencies throw `RuntimeException`

The protected properties behind the getters are now nullable, so a getter
called too early throws a `RuntimeException` naming the setter to call, instead
of PHP's "must not be accessed before initialization" `Error`.

| Property | 1.x | 2.0 |
| --- | --- | --- |
| `AbstractQueryFilter::$form` | `AbstractForm` | `?AbstractForm = null` |
| `AbstractQueryFilter::$queryFilterTable` | `QueryFilterTableInterface` | `?QueryFilterTableInterface = null` |
| `AbstractQueryFilter::$tableName` | `string` | `?string = null` |
| `AbstractForm::$filterSet` | `FilterSet` | `?FilterSet = null` |

Subclasses that read these properties directly must allow for `null`, or use
the getters:

```php
// 1.x
protected function onBeforeFilter(Select $select): void
{
    $select->where(['site' => $this->tableName]);
}

// 2.0
protected function onBeforeFilter(Select $select): void
{
    $select->where(['site' => $this->getTableName()]);
}
```

Subclasses that redeclare these properties must use the new types.

## 6. `ConfigProvider::__invoke()` returns top-level keys

1.x returned `['dependencies' => ['controller_plugins' => …, 'service_manager' => …]]`.
Nothing reads `controller_plugins` under `dependencies`, so applications that
listed the ConfigProvider in a ConfigAggregator never got the `queryFilter`
plugin. 2.0 returns the same array as `Module::getConfig()`:

```php
// 2.0
[
    'controller_plugins' => ['aliases' => [...], 'factories' => [...]],
    'service_manager'    => ['aliases' => [], 'factories' => []],
]
```

If you merged `(new ConfigProvider())()['dependencies']` by hand, use
`(new ConfigProvider())()` or `getDependencyConfig()` instead.

## 7. Controller plugin

`QueryFilterPlugin::__invoke()` now matches its documentation:

- `$className` defaults to `null`, so `$this->queryFilter()` returns the
  plugin. In 1.x the argument was required.
- With no options the service's factory receives `null`, not `[]`, so
  `InvokableFactory` works. Traversable options are converted to an array.
- The plugin needs a container with `build()`
  (`Laminas\ServiceManager\ServiceLocatorInterface`); laminas-mvc provides
  one. Any other container now throws `RuntimeException` instead of an
  `Error`.
- A service that does not implement `QueryFilterInterface` throws
  `RuntimeException` instead of a `TypeError`.

## 8. `setQueryParams()` re-validates every call

1.x bound an `ArrayObject` to the form. A second call on the same query filter
returned the new values without running the input filters (so `''` was not
turned into `null`), and an invalid form passed the raw values through. 2.0
sets the data on the form and reads the input filter's values:

- Every call validates again.
- Values with an input specification are stored after their filters run,
  whether or not validation passed. Values without one (hidden filters) are
  stored as given, as before.

## 9. Wiring classes are final

`ConfigProvider`, `Module` and `Controller\Plugin\QueryFilterPluginFactory`
are framework wiring and are now `final`. Extend configuration in your
application config instead of subclassing:

```php
// 1.x
class MyConfigProvider extends \Contenir\Db\QueryFilter\ConfigProvider
{
    public function getDependencyConfig(): array
    {
        $config = parent::getDependencyConfig();
        $config['controller_plugins']['aliases']['filters'] = QueryFilterPlugin::class;

        return $config;
    }
}

// 2.0: config/autoload/queryfilter.global.php
return [
    'controller_plugins' => [
        'aliases' => ['filters' => \Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPlugin::class],
    ],
];
```

A custom plugin factory should build `QueryFilterPlugin` itself rather than
extend `QueryFilterPluginFactory`. `QueryFilterPlugin`, `QueryFilter`, `Form`,
`FilterSet` and the abstract classes stay extensible.

## Removed

- laminas/laminas-db and laminas/laminas-paginator-adapter-laminasdb (see section 1).
- `phpcs.xml`, `phpstan.neon` and the laminas-coding-standard, PHPStan and
  PHPUnit 9 dev dependencies, replaced by Mago through
  `php-db/phpdb-qa-tools` and PHPUnit 11.
