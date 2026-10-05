# Filters and filter sets

A filter is one query parameter: it describes its form element and input
validation, and it changes a `PhpDb\Sql\Select` according to its current
value. Filters are grouped in a `FilterSet`, which a form builds from and a
query filter applies.

## Writing a filter

Extend one of the abstract filter types, set the properties you need and
implement `filter()`:

```php
use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use PhpDb\Sql\Select;

final class SearchFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'search';
    protected ?string $filterLabel = 'Search';
    protected ?array $filterAttributes = ['placeholder' => 'Enter search term...'];

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if (is_string($value) && $value !== '') {
            $query->where->like('name', "%{$value}%");
        }
    }
}
```

Filters must be constructible without arguments when you add them by class
name. Filters with dependencies (for example option lists from a
repository) are added as instances.

## Properties

All filters share these properties, from `Filter\FilterTrait`:

| Property | Type | Default | Description |
| --- | --- | --- | --- |
| `filterParam` | `?string` | `null` | Query parameter name. Required, except for immutable filters |
| `filterDefault` | `string\|iterable\|null` | `null` | Value used when the parameter is missing or null |
| `filterRequired` | `bool` | `false` | Whether the input is required (select and radio filters) |
| `filterLabel` | `?string` | `null` | Form element label |
| `filterAttributes` | `?array` | `[]` | Form element attributes |

And these accessors:

| Method | Returns |
| --- | --- |
| `getFilterParam(): ?string` | The parameter name. Throws `RuntimeException` when it is not set (immutable filters return `null`) |
| `getFilterValue(): string\|iterable\|int\|null` | The input value from the filter set, or the default |
| `getFilterDefault()`, `getFilterLabel()`, `getFilterRequired()` | The property values |
| `getElement(): ?array` | Laminas Form element specification, or `null` for none |
| `getInputFilterSpecification(): ?array` | Laminas InputFilter specification, or `null` for none |

## Filter types

| Class | Form element | Input specification | Parameter |
| --- | --- | --- | --- |
| `Filter\AbstractFilter` | none | none | required |
| `Filter\AbstractFilterText` | `Text` | optional, `ToNull` | required |
| `Filter\AbstractFilterSelect` | `Select` with `getValueOptions()` | `filterRequired`, `ToNull` | required |
| `Filter\AbstractFilterRadio` | `Radio` with `getValueOptions()` | as select | required |
| `Filter\AbstractFilterHidden` | none | none | required |
| `Filter\AbstractFilterImmutable` | none | none | none: always its default |

Select and radio filters implement `getValueOptions(): array`. Laminas adds an
`InArray` validator to select and radio elements, so a value outside the
options makes the form invalid; see
[how invalid input is handled](query-filter.md#setting-query-parameters).

`ToNull` turns an empty string into `null`, so an empty text box or an empty
option does not filter.

Hidden filters take their value from the query parameter of the same name, or
their default. They have no element and no validation, so do not use them for
values a visitor must not control. Use an immutable filter, or the
`onBeforeFilter()` hook, for conditions such as tenant isolation.

Immutable filters have no parameter: `getFilterParam()` returns `null`, they are
never read from the request, and `getFilterValue()` always returns the default.

### Select filter with dynamic options

```php
final class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';
    protected ?string $filterLabel = 'Category';

    public function __construct(private CategoryRepository $categories)
    {
    }

    public function getValueOptions(): array
    {
        $options = ['' => 'All categories'];
        foreach ($this->categories->fetchAll() as $category) {
            $options[$category->id] = $category->name;
        }

        return $options;
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value !== null) {
            $query->where(['category_id' => $value]);
        }
    }
}
```

## Helpers for subclasses

`AbstractFilter` gives filters a few SQL helpers:

| Method | Purpose |
| --- | --- |
| `setAdapter(AdapterInterface $adapter): self` | Give the filter a php-db adapter, for `getSql()` |
| `getSql(): Sql` | A `PhpDb\Sql\Sql` on that adapter, for sub-selects |
| `getWhere(Select $select): Where` | The select's `WHERE` clause |
| `hasJoin(Select $select, string $joinName): bool` | Whether the select already joins that table (by plain table name) |

`hasJoin()` stops two filters joining the same table twice:

```php
public function filter(Select $query): void
{
    $value = $this->getFilterValue();
    if ($value === null) {
        return;
    }

    if (! $this->hasJoin($query, 'category')) {
        $query->join('category', 'product.category_id = category.id', []);
    }

    $query->where(['category.slug' => $value]);
}
```

## FilterSet

`FilterSet` holds the filters in order and the current input values:

```php
use Contenir\Db\QueryFilter\FilterSet;

$filterSet = new FilterSet([new SearchFilter(), CategoryFilter::class], ['search' => 'red']);
```

| Method | Description |
| --- | --- |
| `__construct(iterable $filters = [], array $input = [])` | Adds the filters, then sets the input |
| `addFilter(AbstractFilter\|string $filter): self` | Adds an instance, or instantiates a class name. A class name that is not an `AbstractFilter` subclass throws `InvalidArgumentException` |
| `addFilters(iterable $filters): self` | Adds several |
| `getFilters(): array` | All filters, as a list |
| `hasFilter(string $param): bool` | Whether a filter uses that parameter |
| `getFilter(string $param): ?AbstractFilter` | The filter for that parameter, or `null` |
| `removeFilter(string $param): self` | Removes it and reindexes |
| `clear(): self` | Removes every filter |
| `setInput(array $input): self` / `getInput(): array` | The input values, keyed by parameter |
| `applyFilters(Select $query): Select` | Calls every filter's `filter()` in order and returns the select |
| `filter(Select $query): Select` | Deprecated alias of `applyFilters()` |

`hasFilter()`, `getFilter()` and `removeFilter()` call `getFilterParam()` on
each filter, so they throw `RuntimeException` if the set holds a
non-immutable filter without a parameter name.

Adding a filter attaches it to the set (`setFilterSet()`), which is where
`getFilterValue()` reads its input. A filter belongs to one set at a time.
