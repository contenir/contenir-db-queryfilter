# Framework integration

The core classes are framework-agnostic: anything that can hand
`setQueryParams()` an array of query parameters can use them. This page shows
complete Mezzio and Laminas MVC set-ups, and the MVC controller plugin.

`ProductRepository` in these examples is a contenir-db-model 2 repository
that implements `QueryFilterTableInterface` (see
[Tables](query-filter.md#tables)); `AdapterInterface` is the php-db adapter
service the entity manager uses.

## Configuration

The package ships a `ConfigProvider` and a laminas-mvc `Module`. Both return
the same configuration, which registers one controller plugin under the
aliases `queryFilter` and `QueryFilter`:

```php
[
    'controller_plugins' => [
        'aliases'   => [
            'queryFilter' => Controller\Plugin\QueryFilterPlugin::class,
            'QueryFilter' => Controller\Plugin\QueryFilterPlugin::class,
        ],
        'factories' => [
            Controller\Plugin\QueryFilterPlugin::class => Controller\Plugin\QueryFilterPluginFactory::class,
        ],
    ],
    'service_manager' => ['aliases' => [], 'factories' => []],
]
```

Mezzio applications do not need any of it, since they build `QueryFilter`
directly, but listing the `ConfigProvider` is harmless.

### Mezzio

Add the ConfigProvider to `config/config.php` (laminas-component-installer
does this for you):

```php
$aggregator = new ConfigAggregator([
    \Contenir\Db\QueryFilter\ConfigProvider::class,
    // ... other providers
]);
```

### Laminas MVC

Add the module to `config/modules.config.php`:

```php
return [
    // ... other modules
    'Contenir\Db\QueryFilter',
];
```

Or list the ConfigProvider in a ConfigAggregator, as for Mezzio.

## The queryFilter() controller plugin

`QueryFilterPlugin` builds query filters from the application service
manager:

```php
$queryFilter = $this->queryFilter(ProductQueryFilter::class);
$queryFilter = $this->queryFilter(ProductQueryFilter::class, ['per_page' => 20]);
$plugin      = $this->queryFilter(); // the plugin itself
```

- It calls `build()`, so every call returns a **new** instance, and options go
  to the service's factory. Without options the factory receives `null`, as
  for a plain `get()`, so `InvokableFactory` works.
- The class must be registered with the service manager (for example with
  `InvokableFactory`), or be resolvable by an abstract factory such as
  `ReflectionBasedAbstractFactory`.
- The service must implement `QueryFilterInterface`; anything else throws a
  `RuntimeException`.
- The plugin needs a container with `build()`
  (`Laminas\ServiceManager\ServiceLocatorInterface`). laminas-mvc's plugin
  manager provides one. Constructed with any other PSR-11 container, it throws
  a `RuntimeException` when asked to build.

## Mezzio Implementation

### Required Packages

For Mezzio with Laminas View and Laminas Router:

```bash
composer require mezzio/mezzio
composer require mezzio/mezzio-laminasviewrenderer
composer require mezzio/mezzio-laminasrouter
composer require laminas/laminas-form
```

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
use PhpDb\Adapter\AdapterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProductListHandler implements RequestHandlerInterface
{
    public function __construct(
        private TemplateRendererInterface $template,
        private ProductRepository $productRepository,
        private AdapterInterface $adapter,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Create QueryFilter instance
        $queryFilter = new QueryFilter();
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setQueryFilterTable($this->productRepository);
        $queryFilter->setAdapter($this->adapter);
        $queryFilter->setQueryParams($request->getQueryParams());

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
}
```

#### Handler Factory

```php
<?php

declare(strict_types=1);

namespace App\Handler;

use App\Repository\ProductRepository;
use Mezzio\Template\TemplateRendererInterface;
use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class ProductListHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProductListHandler
    {
        return new ProductListHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(ProductRepository::class),
            $container->get(AdapterInterface::class),
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
            \App\Handler\ProductDetailHandler::class => \App\Handler\ProductDetailHandlerFactory::class,
            \App\Repository\ProductRepository::class => \Contenir\Db\Model\Container\RepositoryFactory::class,
        ],
    ],
];
```

#### View Configuration (`config/autoload/templates.global.php`)

```php
<?php

declare(strict_types=1);

return [
    'templates' => [
        'paths' => [
            'app'     => ['templates/app'],
            'error'   => ['templates/error'],
            'layout'  => ['templates/layout'],
            'partial' => ['templates/partial'],
        ],
        'extension' => 'phtml',
    ],
    'view_helpers' => [
        'invokables' => [],
        'factories' => [],
    ],
];
```

#### Routes Configuration (`config/autoload/routes.global.php`)

Using Laminas Router:

```php
<?php

declare(strict_types=1);

return [
    'routes' => [
        [
            'name' => 'product.list',
            'path' => '/products',
            'middleware' => \App\Handler\ProductListHandler::class,
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'product.detail',
            'path' => '/products/:slug',
            'middleware' => \App\Handler\ProductDetailHandler::class,
            'allowed_methods' => ['GET'],
            'options' => [
                'constraints' => [
                    'slug' => '[a-zA-Z0-9-]+',
                ],
            ],
        ],
    ],
];
```

### Mezzio View Template (Laminas View)

```php
<?php // templates/app/product-list.phtml ?>

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
    <a href="<?= $this->url('product.list') ?>" class="btn btn-secondary">Reset</a>

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
                <a href="<?= $this->url('product.detail', ['slug' => $product->slug]) ?>">
                    <?= $this->escapeHtml($product->name) ?>
                </a>
            </h3>
            <p><?= $this->escapeHtml($product->description) ?></p>
            <span class="category"><?= $this->escapeHtml($product->category) ?></span>
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

## Laminas MVC Implementation

### Controller Setup

In Laminas MVC, you can use the `queryFilter()` controller plugin for convenient access.
See [The queryFilter() controller plugin](#the-queryfilter-controller-plugin) for how it
builds instances.

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
use PhpDb\Adapter\AdapterInterface;

class ProductController extends AbstractActionController
{
    public function __construct(
        private ProductRepository $productRepository,
        private AdapterInterface $adapter,
    ) {}

    public function listAction(): ViewModel
    {
        // Use the controller plugin to create QueryFilter
        $queryFilter = $this->queryFilter(QueryFilter::class);
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setQueryFilterTable($this->productRepository);
        $queryFilter->setAdapter($this->adapter);
        $queryFilter->setQueryParams($this->params()->fromQuery());

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
        $product = $this->productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            return $this->notFoundAction();
        }

        // Get prev/next navigation within filtered results
        $queryFilter = $this->queryFilter(QueryFilter::class);
        $queryFilter->setForm(new ProductFilterForm());
        $queryFilter->setQueryFilterTable($this->productRepository);
        $queryFilter->setAdapter($this->adapter);
        $queryFilter->setQueryParams($this->params()->fromQuery());

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
use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class ProductControllerFactory
{
    public function __invoke(ContainerInterface $container): ProductController
    {
        return new ProductController(
            $container->get(ProductRepository::class),
            $container->get(AdapterInterface::class),
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
    'service_manager' => [
        'factories' => [
            \Contenir\Db\QueryFilter\QueryFilter::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
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
