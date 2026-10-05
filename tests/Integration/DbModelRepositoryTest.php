<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Integration;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\QueryFilter\QueryFilter;
use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Contenir\Db\QueryFilter\RepositoryTable;
use ContenirTest\Db\QueryFilter\TestAsset\Entity\Product;
use ContenirTest\Db\QueryFilter\TestAsset\Factory\QueryFilterFactory;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\StatusFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Repository\ProductRepository;
use ContenirTest\Db\QueryFilter\Trait\SqliteAdapterTrait;
use Laminas\Paginator\Paginator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function count;
use function iterator_to_array;

/**
 * A contenir-db-model 2 repository as the query filter's table, on
 * in-memory SQLite, both as a subclass implementing the interface and as a
 * plain repository wrapped in RepositoryTable: pages are hydrated entities.
 */
#[Group('integration')]
final class DbModelRepositoryTest extends TestCase
{
    use SqliteAdapterTrait;

    private EntityManager $em;

    /**
     * @return array<string, array{array<string, mixed>, list<string>}>
     */
    public static function requestProvider(): array
    {
        return [
            'defaults only' => [[], ['Red Book', 'Red Record']],
            'search'        => [['search' => 'Book'], ['Red Book']],
            'category'      => [['category' => 'music'], ['Red Record']],
            'status'        => [['status' => 'draft'], ['Blue Book']],
            'no match'      => [['search' => 'Green'], []],
        ];
    }

    #[Test]
    public function aPlainRepositoryWorksThroughRepositoryTable(): void
    {
        $queryFilter = $this->queryFilter(new RepositoryTable($this->em->getRepository(Product::class)));
        $queryFilter->setQueryParams(['search' => 'Red']);

        $pages = $queryFilter->getPagingResultSet();

        static::assertSame(
            [2, ['Red Book', 'Red Record']],
            [
                $pages->count(),
                array_map(
                    static fn(object $product): string => $product instanceof Product ? $product->name : '',
                    $pages->getItems(0, 10),
                ),
            ],
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string>         $expectedNames
     */
    #[Test]
    #[DataProvider('requestProvider')]
    public function pagesAreFilteredEntities(array $params, array $expectedNames): void
    {
        $queryFilter = $this->queryFilter();
        $queryFilter->setQueryParams($params);

        $paginator = new Paginator($queryFilter->getPagingResultSet());
        $products  = iterator_to_array($paginator->getCurrentItems(), preserve_keys: false);

        static::assertContainsOnlyInstancesOf(Product::class, $products);
        static::assertSame(
            [$expectedNames, count($expectedNames)],
            [
                array_map(static fn(Product $product): string => $product->name, $products),
                $paginator->getTotalItemCount(),
            ],
        );
    }

    #[Test]
    public function pagesShareTheEntityManagersIdentityMap(): void
    {
        $queryFilter = $this->queryFilter();
        $queryFilter->setQueryParams(['category' => 'music']);
        $loaded = $this->em->getRepository(Product::class)->find(3);

        static::assertSame([$loaded], $queryFilter->getPagingResultSet()->getItems(0, 10));
    }

    #[Test]
    public function repositoryTableUsesTheRepositorySelect(): void
    {
        $repository = $this->em->getRepository(Product::class);

        static::assertSame(
            $repository->createSelect()->getSqlString(),
            (new RepositoryTable($repository))->createSelect()->getSqlString(),
        );
    }

    #[Test]
    public function tableNameComesFromTheRepositorySelect(): void
    {
        static::assertSame('products', $this->queryFilter()->getTableName());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpSqliteAdapter();
        $this->em = new EntityManager($this->adapter);
    }

    private function queryFilter(?QueryFilterTableInterface $table = null): QueryFilter
    {
        $queryFilter = new QueryFilter(QueryFilterFactory::makeForm(
            new SearchFilter(),
            new CategoryFilter(),
            new StatusFilter(),
            new ActiveOnlyFilter(),
        ));
        $queryFilter->setQueryFilterTable($table ?? new ProductRepository($this->em))->setAdapter($this->adapter);

        return $queryFilter;
    }
}
