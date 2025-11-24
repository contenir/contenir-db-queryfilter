# Contenir QueryFilter for Laminas MVC

QueryFilter is a library that bridges the gap between user-facing search/filter forms and database queries in Laminas MVC applications. It provides a clean abstraction for building dynamic, reusable database query filters with minimal boilerplate code.

## Features

- **Automatic Form Generation**: Filters automatically generate form elements and validation rules
- **Query Building**: Converts form data to SQL WHERE clauses
- **Pagination Support**: Provides DbSelect paginator adapter for Laminas Paginator
- **Navigation**: Position tracking for prev/next items in filtered results
- **Multiple Filter Types**: Text, Select, Radio, Hidden, and Immutable filters
- **Input Validation**: Integrated with Laminas InputFilter
- **Repository Integration**: Works with Contenir Model repositories

## Requirements

- PHP 8.1 or higher
- Laminas MVC 3.0+
- contenir/contenir-db-model 1.0+

## Installation

```bash
composer require contenir/contenir-db-queryfilter
```

### Module Configuration

Add the module to your `config/modules.config.php`:

```php
return [
    // ... other modules
    'Contenir\Db\QueryFilter',
];
```

Or use the ConfigProvider in `config/config.php`:

```php
$aggregator = new ConfigAggregator([
    \Contenir\Db\QueryFilter\ConfigProvider::class,
    // ... other providers
]);
```

## Quick Start

### 1. Create a Custom Filter

```php
use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Laminas\Db\Sql\Select;

class SearchFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'search';
    protected ?string $filterLabel = 'Search';
    protected ?array $filterAttributes = [
        'class' => 'form-control',
        'placeholder' => 'Enter search term...',
    ];

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $where = $this->getWhere($query);
            $where->like('name', '%' . $value . '%');
            $query->where($where);
        }
    }
}
```

### 2. Create a Filter Form

```php
use Contenir\Db\QueryFilter\AbstractForm;
use Contenir\Db\QueryFilter\FilterSet;

class ProductFilterForm extends AbstractForm
{
    public function __construct()
    {
        parent::__construct('product-filter');

        $filterSet = new FilterSet([
            new SearchFilter(),
            new CategoryFilter(),
            new StatusFilter(),
        ]);

        $this->setFilterSet($filterSet);
        $this->build();
    }
}
```

### 3. Use in Controller

```php
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;

class ProductController extends AbstractActionController
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function listAction()
    {
        $queryFilter = $this->queryFilter(\Contenir\Db\QueryFilter\QueryFilter::class);
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setRepository($this->productRepository);
        $queryFilter->setRequest($this->getRequest());

        $paginator = new Paginator($queryFilter->getPagingResultSet());
        $paginator->setCurrentPageNumber($this->params()->fromQuery('page', 1));
        $paginator->setItemCountPerPage(20);

        return [
            'paginator' => $paginator,
            'form' => $queryFilter->getForm(),
            'submitted' => $queryFilter->isSubmitted(),
        ];
    }
}
```

## Filter Types

### AbstractFilterText

Text input filter for search strings and text matching.

```php
class NameFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'name';
    protected ?string $filterLabel = 'Name';

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $query->where(['name' => $value]);
        }
    }
}
```

### AbstractFilterSelect

Dropdown select filter for categorical selections.

```php
class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';
    protected ?string $filterLabel = 'Category';
    protected string|iterable|null $filterDefault = 'all';

    public function getValueOptions(): array
    {
        return [
            'all' => 'All Categories',
            'books' => 'Books',
            'electronics' => 'Electronics',
        ];
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value && $value !== 'all') {
            $query->where(['category' => $value]);
        }
    }
}
```

### AbstractFilterRadio

Radio button filter for exclusive selections.

```php
class StatusFilter extends AbstractFilterRadio
{
    protected ?string $filterParam = 'status';
    protected ?string $filterLabel = 'Status';

    public function getValueOptions(): array
    {
        return [
            '' => 'All',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $query->where(['status' => $value]);
        }
    }
}
```

### AbstractFilterHidden

Hidden filters that apply without user interface elements.

```php
class TenantFilter extends AbstractFilterHidden
{
    protected ?string $filterParam = 'tenant_id';

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $query->where(['tenant_id' => $value]);
        }
    }
}
```

### AbstractFilterImmutable

Immutable filters that cannot be changed by users.

```php
class ActiveOnlyFilter extends AbstractFilterImmutable
{
    public function filter(Select $query): void
    {
        $query->where(['is_active' => 1]);
    }
}
```

## Filter Properties

All filters support these properties via `FilterTrait`:

| Property | Type | Description |
|----------|------|-------------|
| `filterParam` | `?string` | Query parameter name (required for most filters) |
| `filterDefault` | `string\|iterable\|null` | Default value when parameter is missing |
| `filterRequired` | `bool` | Whether the field must have a value |
| `filterLabel` | `?string` | Display label for form element |
| `filterAttributes` | `?array` | HTML attributes for form elements |

## Advanced Usage

### Adding JOINs in Filters

Use the `hasJoin()` helper to prevent duplicate JOINs:

```php
public function filter(Select $query): void
{
    $value = $this->getFilterValue();
    if ($value) {
        if (!$this->hasJoin($query, 'category')) {
            $query->join(
                'category',
                'product.category_id = category.id',
                []
            );
        }
        $query->where(['category.slug' => $value]);
    }
}
```

### Position/Navigation Tracking

Get previous and next items within filtered results:

```php
$position = $queryFilter->getPosition(
    $entity,
    'slug',           // identifier field
    'resource_id',    // primary key
    'title'           // title field
);

// Returns: ['prev' => [...], 'next' => [...]]
```

### Custom QueryFilter Class

Extend `QueryFilter` for application-specific logic:

```php
use Contenir\Db\QueryFilter\QueryFilter;

class ProductQueryFilter extends QueryFilter
{
    public function getActiveProducts(): array
    {
        // Custom method implementation
    }
}
```

## Architecture

### Class Hierarchy

```
AbstractQueryFilter (Abstract Base)
    └── QueryFilter (Concrete Implementation)

AbstractForm (Extends Laminas\Form\Form)
    └── Form (Concrete Implementation)

FilterSet (Container for Filters)

AbstractFilter (Abstract Base)
    ├── AbstractFilterText
    ├── AbstractFilterSelect
    │   └── AbstractFilterRadio
    └── AbstractFilterHidden
        └── AbstractFilterImmutable
```

### Data Flow

```
HTTP Request
    ↓
Controller (uses QueryFilter plugin)
    ↓
QueryFilter.setRequest($request)
    ├─ Extract parameters from query string
    ├─ Bind data to form
    └─ Validate input
    ↓
QueryFilter.getPagingResultSet()
    ├─ Get repository's SELECT
    ├─ Apply all filters via FilterSet.filter()
    └─ Return DbSelect paginator adapter
    ↓
Controller/View
    ├─ Render form
    └─ Display paginated results
```

## Development

### Running Tests

```bash
composer test
```

### Code Style

This package uses [Laminas Coding Standard](https://github.com/laminas/laminas-coding-standard).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

### Static Analysis

```bash
composer phpstan
```

### All Checks

```bash
composer check
```

## License

BSD-3-Clause License. See [LICENSE](LICENSE) file for details.
