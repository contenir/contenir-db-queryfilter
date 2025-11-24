# Contenir QueryFilter for Laminas

QueryFilter is a library that bridges the gap between user-facing search/filter forms and database queries in Laminas applications. It provides a clean abstraction for building dynamic, reusable database query filters with minimal boilerplate code.

**Supports both Laminas MVC and Mezzio (PSR-15) frameworks.**

## Features

- **Automatic Form Generation**: Filters automatically generate form elements and validation rules
- **Query Building**: Converts form data to SQL WHERE clauses
- **Pagination Support**: Provides DbSelect paginator adapter for Laminas Paginator
- **Navigation**: Position tracking for prev/next items in filtered results
- **Multiple Filter Types**: Text, Select, Radio, Hidden, and Immutable filters
- **Input Validation**: Integrated with Laminas InputFilter
- **Repository Integration**: Works with Contenir Model repositories
- **Framework Agnostic Core**: Works with both MVC controllers and PSR-15 handlers

## Requirements

- PHP 8.1 or higher
- Laminas MVC 3.0+ or Mezzio 3.0+
- contenir/contenir-db-model 1.0+

## Installation

```bash
composer require contenir/contenir-db-queryfilter
```

## Framework Configuration

### Mezzio Configuration

Add the ConfigProvider to your `config/config.php`:

```php
<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    // ... other providers
    \Contenir\Db\QueryFilter\ConfigProvider::class,

    new PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),
]);

return $aggregator->getMergedConfig();
```

### Laminas MVC Configuration

Add the module to your `config/modules.config.php`:

```php
<?php

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

---

## Quick Start

### Step 1: Create Custom Filters

Filters define both the form element and the SQL query modification:

```php
<?php

declare(strict_types=1);

namespace App\Filter;

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

```php
<?php

declare(strict_types=1);

namespace App\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Laminas\Db\Sql\Select;

class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';
    protected ?string $filterLabel = 'Category';
    protected string|iterable|null $filterDefault = '';

    public function getValueOptions(): array
    {
        return [
            '' => 'All Categories',
            'books' => 'Books',
            'electronics' => 'Electronics',
            'clothing' => 'Clothing',
        ];
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $query->where(['category' => $value]);
        }
    }
}
```

### Step 2: Create a Filter Form

```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Filter\CategoryFilter;
use App\Filter\SearchFilter;
use App\Filter\StatusFilter;
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

        // Optional: set form attributes
        $this->setAttribute('method', 'GET');
        $this->setAttribute('class', 'filter-form');
    }
}
```

---

## Mezzio Implementation

### Handler Setup

In Mezzio, you'll use request handlers instead of controllers. Since there's no controller plugin available, you instantiate QueryFilter directly.

#### Basic Handler Example

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\ProductFilterForm;
use App\Repository\ProductRepository;
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Paginator\Paginator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProductListHandler implements RequestHandlerInterface
{
    public function __construct(
        private TemplateRendererInterface $template,
        private ProductRepository $productRepository
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Create QueryFilter instance
        $queryFilter = new QueryFilter();
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setRepository($this->productRepository);

        // Convert PSR-7 request to Laminas request for QueryFilter
        $laminasRequest = $this->createLaminasRequest($request);
        $queryFilter->setRequest($laminasRequest);

        // Create paginator
        $paginator = new Paginator($queryFilter->getPagingResultSet());
        $paginator->setCurrentPageNumber(
            (int) ($request->getQueryParams()['page'] ?? 1)
        );
        $paginator->setItemCountPerPage(20);

        return new HtmlResponse($this->template->render('app::product-list', [
            'paginator' => $paginator,
            'form' => $queryFilter->getForm(),
            'submitted' => $queryFilter->isSubmitted(),
            'queryParams' => $request->getQueryParams(),
        ]));
    }

    /**
     * Convert PSR-7 ServerRequest to Laminas HTTP Request.
     */
    private function createLaminasRequest(ServerRequestInterface $psrRequest): \Laminas\Http\Request
    {
        $request = new \Laminas\Http\Request();
        $request->setMethod($psrRequest->getMethod());
        $request->setUri((string) $psrRequest->getUri());

        // Set query parameters
        $query = new \Laminas\Stdlib\Parameters($psrRequest->getQueryParams());
        $request->setQuery($query);

        return $request;
    }
}
```

#### Handler Factory

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use App\Repository\ProductRepository;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ProductListHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProductListHandler
    {
        return new ProductListHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(ProductRepository::class)
        );
    }
}
```

### Mezzio Configuration

#### Container Configuration (`config/autoload/dependencies.global.php`)

```php
<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            \App\Handler\ProductListHandler::class => \App\Handler\ProductListHandlerFactory::class,
            \App\Repository\ProductRepository::class => \App\Repository\ProductRepositoryFactory::class,
        ],
    ],
];
```

#### Routes Configuration (`config/routes.php`)

```php
<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;

return static function (Application $app, ContainerInterface $container): void {
    $app->get('/products', \App\Handler\ProductListHandler::class, 'product.list');
    $app->get('/products/{slug}', \App\Handler\ProductDetailHandler::class, 'product.detail');
};
```

### Mezzio View Template (Twig Example)

```twig
{# templates/app/product-list.html.twig #}

<div class="product-filter">
    <form method="GET" action="{{ path('product.list') }}">
        {% for element in form %}
            <div class="form-group">
                {{ formLabel(element) }}
                {{ formElement(element) }}
                {{ formElementErrors(element) }}
            </div>
        {% endfor %}
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ path('product.list') }}" class="btn btn-secondary">Reset</a>
    </form>
</div>

{% if submitted %}
    <p class="text-muted">
        Showing {{ paginator.totalItemCount }} results
    </p>
{% endif %}

<div class="product-list">
    {% for product in paginator %}
        <div class="product-item">
            <h3>{{ product.name }}</h3>
            <p>{{ product.description }}</p>
            <span class="category">{{ product.category }}</span>
        </div>
    {% else %}
        <p>No products found matching your criteria.</p>
    {% endfor %}
</div>

{# Pagination #}
{% if paginator.pageCount > 1 %}
    {{ paginationControl(paginator, 'sliding', 'partial/pagination') }}
{% endif %}
```

### Helper Trait for Mezzio Handlers

Create a reusable trait for converting PSR-7 requests:

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use Laminas\Http\Request as LaminasRequest;
use Laminas\Stdlib\Parameters;
use Psr\Http\Message\ServerRequestInterface;

trait QueryFilterRequestTrait
{
    /**
     * Convert PSR-7 ServerRequest to Laminas HTTP Request.
     */
    protected function createLaminasRequest(ServerRequestInterface $psrRequest): LaminasRequest
    {
        $request = new LaminasRequest();
        $request->setMethod($psrRequest->getMethod());
        $request->setUri((string) $psrRequest->getUri());
        $request->setQuery(new Parameters($psrRequest->getQueryParams()));

        return $request;
    }

    /**
     * Get current page number from request.
     */
    protected function getCurrentPage(ServerRequestInterface $request, string $param = 'page'): int
    {
        return max(1, (int) ($request->getQueryParams()[$param] ?? 1));
    }
}
```

Usage in handler:

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Server\RequestHandlerInterface;

class ProductListHandler implements RequestHandlerInterface
{
    use QueryFilterRequestTrait;

    // ... handler implementation using trait methods
}
```

---

## Laminas MVC Implementation

### Controller Setup

In Laminas MVC, you can use the `queryFilter()` controller plugin for convenient access.

#### Controller Example

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ProductFilterForm;
use App\Repository\ProductRepository;
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Paginator\Paginator;
use Laminas\View\Model\ViewModel;

class ProductController extends AbstractActionController
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    public function listAction(): ViewModel
    {
        // Use the controller plugin to create QueryFilter
        $queryFilter = $this->queryFilter(QueryFilter::class);
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setRepository($this->productRepository);
        $queryFilter->setRequest($this->getRequest());

        // Create paginator
        $paginator = new Paginator($queryFilter->getPagingResultSet());
        $paginator->setCurrentPageNumber(
            (int) $this->params()->fromQuery('page', 1)
        );
        $paginator->setItemCountPerPage(20);

        return new ViewModel([
            'paginator' => $paginator,
            'form' => $queryFilter->getForm(),
            'submitted' => $queryFilter->isSubmitted(),
        ]);
    }

    public function detailAction(): ViewModel
    {
        $slug = $this->params()->fromRoute('slug');
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            return $this->notFoundAction();
        }

        // Get prev/next navigation within filtered results
        $queryFilter = $this->queryFilter(QueryFilter::class);
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setRepository($this->productRepository);
        $queryFilter->setRequest($this->getRequest());

        $position = $queryFilter->getPosition($product, 'slug', 'id', 'name');

        return new ViewModel([
            'product' => $product,
            'position' => $position,
        ]);
    }
}
```

#### Controller Factory

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use Psr\Container\ContainerInterface;

class ProductControllerFactory
{
    public function __invoke(ContainerInterface $container): ProductController
    {
        return new ProductController(
            $container->get(ProductRepository::class)
        );
    }
}
```

### MVC Configuration

#### Module Configuration (`module/App/config/module.config.php`)

```php
<?php

declare(strict_types=1);

namespace App;

return [
    'controllers' => [
        'factories' => [
            Controller\ProductController::class => Controller\ProductControllerFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'product' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/products',
                    'defaults' => [
                        'controller' => Controller\ProductController::class,
                        'action' => 'list',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'detail' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:slug',
                            'defaults' => [
                                'action' => 'detail',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

### MVC View Template

```php
<?php // module/App/view/app/product/list.phtml ?>

<div class="product-filter">
    <?= $this->form()->openTag($form) ?>

    <?php foreach ($form as $element): ?>
        <div class="form-group">
            <?= $this->formLabel($element) ?>
            <?= $this->formElement($element) ?>
            <?= $this->formElementErrors($element) ?>
        </div>
    <?php endforeach ?>

    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="<?= $this->url('product') ?>" class="btn btn-secondary">Reset</a>

    <?= $this->form()->closeTag() ?>
</div>

<?php if ($submitted): ?>
    <p class="text-muted">
        Showing <?= $paginator->getTotalItemCount() ?> results
    </p>
<?php endif ?>

<div class="product-list">
    <?php foreach ($paginator as $product): ?>
        <div class="product-item">
            <h3>
                <a href="<?= $this->url('product/detail', ['slug' => $product->slug]) ?>">
                    <?= $this->escapeHtml($product->name) ?>
                </a>
            </h3>
            <p><?= $this->escapeHtml($product->description) ?></p>
        </div>
    <?php endforeach ?>

    <?php if (count($paginator) === 0): ?>
        <p>No products found matching your criteria.</p>
    <?php endif ?>
</div>

<?php if ($paginator->getPages()->pageCount > 1): ?>
    <?= $this->paginationControl(
        $paginator,
        'sliding',
        'partial/pagination'
    ) ?>
<?php endif ?>
```

---

## Filter Types Reference

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

---

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

### Dynamic Filter Options

Inject dependencies for dynamic options:

```php
<?php

declare(strict_types=1);

namespace App\Filter;

use App\Repository\CategoryRepository;
use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Laminas\Db\Sql\Select;

class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';
    protected ?string $filterLabel = 'Category';

    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    public function getValueOptions(): array
    {
        $options = ['' => 'All Categories'];

        foreach ($this->categoryRepository->fetchAll() as $category) {
            $options[$category->id] = $category->name;
        }

        return $options;
    }

    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value) {
            $query->where(['category_id' => $value]);
        }
    }
}
```

### Custom QueryFilter Class

Extend `QueryFilter` for application-specific logic:

```php
<?php

declare(strict_types=1);

namespace App\QueryFilter;

use Contenir\Db\QueryFilter\QueryFilter;

class ProductQueryFilter extends QueryFilter
{
    /**
     * Get filtered products as array.
     */
    public function getFilteredProducts(): array
    {
        $paginator = new \Laminas\Paginator\Paginator($this->getPagingResultSet());
        $paginator->setItemCountPerPage(-1); // All items

        return iterator_to_array($paginator);
    }
}
```

---

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
HTTP Request (PSR-7 or Laminas)
    ↓
Handler/Controller
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
Handler/Controller
    ├─ Create Paginator
    └─ Return Response with view data
    ↓
View/Template
    ├─ Render form
    └─ Display paginated results
```

---

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
