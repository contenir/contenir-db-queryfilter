# Upgrading from 1.x to 2.0

2.0 keeps the 1.2 API. The breaks below come from adding native types and
from bug fixes; most applications only need the first two.

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| laminas/laminas-db | ^2.0 | ^2.0 (2.19+ resolves on PHP 8.3) |
| laminas/laminas-form | ^3.20 | ^3.20 |
| laminas/laminas-servicemanager | indirect | ^3.22, required directly |

```bash
composer require contenir/contenir-db-queryfilter:^2.0
```

Projects that must stay on PHP 8.1 or 8.2 can keep using `^1.2`, which is
maintained on the `1.x` branch.

## 1. `getValueOptions()` declares `array`

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

## 2. `getPosition()` takes a string primary key

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

## 3. `FilterSet::addFilter()` accepts filters only

The parameter was `string|object`; it is now `AbstractFilter|string`. A class
name that is not an `AbstractFilter` subclass, or does not exist, throws
`InvalidArgumentException` instead of failing with an `Error`.

```php
// 1.x: Error ("Call to undefined method stdClass::setFilterSet()")
$filterSet->addFilter(stdClass::class);

// 2.0: InvalidArgumentException('Filter class "stdClass" must extend ...')
```

## 4. Unset dependencies throw `RuntimeException`

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

## 5. `ConfigProvider::__invoke()` returns top-level keys

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

## 6. Controller plugin

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

## 7. `setQueryParams()` re-validates every call

1.x bound an `ArrayObject` to the form. A second call on the same query filter
returned the new values without running the input filters (so `''` was not
turned into `null`), and an invalid form passed the raw values through. 2.0
sets the data on the form and reads the input filter's values:

- Every call validates again.
- Values with an input specification are stored after their filters run,
  whether or not validation passed. Values without one (hidden filters) are
  stored as given, as before.

## 8. Wiring classes are final

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

- `phpcs.xml`, `phpstan.neon` and the laminas-coding-standard, PHPStan and
  PHPUnit 9 dev dependencies, replaced by Mago through
  `php-db/phpdb-qa-tools` and PHPUnit 11.
